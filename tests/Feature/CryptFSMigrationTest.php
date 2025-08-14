<?php

namespace Tests\Feature;

use App\Helpers\FileManipulations;
use App\Helpers\UserStorage;
use App\Models\User;
use App\modules\CryptFS\Services\CryptFSMountingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use ReflectionClass;
use Tests\TestCase;

class CryptFSMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected string $testUserId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testUserId = (string) rand(5000, 9999);
    }

    protected function tearDown(): void
    {
        $this->cleanupTestDirectories();
        parent::tearDown();
    }

    public function test_migration_moves_files_from_legacy_to_encrypted_storage()
    {
        $user = User::factory()->create(['id' => $this->testUserId]);

        $this->createLegacyStorageWithTestFiles($user);
        $this->setupEncryptedStorageDirectories($user);

        $service = new CryptFSMountingService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('migrateLegacyStorage');

        $success = $method->invoke($service, $user);

        $this->assertTrue($success);

        $this->assertLegacyStorageIsEmpty($user);
        $this->assertFilesExistInDecryptedStorage($user);
    }

    public function test_migration_skips_when_no_legacy_storage_exists()
    {
        $user = User::factory()->create(['id' => $this->testUserId]);

        $service = new CryptFSMountingService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('migrateLegacyStorage');

        $method->invoke($service, $user);

        $efs = Storage::disk('efs');
        $this->assertFalse($efs->exists("user-{$user->id}"));
    }

    public function test_legacy_storage_remains_accessible_without_encrypted_folders()
    {
        $user = User::factory()->create(['id' => $this->testUserId]);

        $this->createLegacyStorageWithTestFiles($user);

        $userStorage = UserStorage::get($user);

        $this->assertTrue($userStorage->exists('test-file-1.txt'));
        $this->assertTrue($userStorage->exists('folder/test-file-2.txt'));
        $this->assertTrue($userStorage->exists('folder/subfolder/test-file-3.txt'));

        $this->assertEquals("Content of test file 1", $userStorage->get('test-file-1.txt'));
        $this->assertEquals("Content of test file 2", $userStorage->get('folder/test-file-2.txt'));
        $this->assertEquals("Content of test file 3", $userStorage->get('folder/subfolder/test-file-3.txt'));

        $efs = Storage::disk('efs');
        $this->assertTrue($efs->exists("user-{$user->id}"));
    }

    public function test_migration_handles_missing_decrypted_directory_gracefully()
    {
        $user = User::factory()->create(['id' => $this->testUserId]);

        $this->createLegacyStorageWithTestFiles($user);

        $service = new CryptFSMountingService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('migrateLegacyStorage');

        $success = $method->invoke($service, $user);

        $this->assertFalse($success);

        $efs = Storage::disk('efs');
        $this->assertTrue($efs->exists("user-{$user->id}"));
    }

    public function test_migration_retries_successfully_after_initial_failure()
    {
        $user = User::factory()->create(['id' => $this->testUserId]);

        $this->createLegacyStorageWithTestFiles($user);

        $service = new CryptFSMountingService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('migrateLegacyStorage');

        $firstAttempt = $method->invoke($service, $user);
        $this->assertFalse($firstAttempt);

        $this->setupEncryptedStorageDirectories($user);

        $secondAttempt = $method->invoke($service, $user);
        $this->assertTrue($secondAttempt);

        $this->assertLegacyStorageIsEmpty($user);
        $this->assertFilesExistInDecryptedStorage($user);
    }

    private function createLegacyStorageWithTestFiles(User $user): void
    {
        $efs = Storage::disk('efs');
        $userDir = "user-{$user->id}";

        $efs->put("{$userDir}/test-file-1.txt", "Content of test file 1");
        $efs->put("{$userDir}/folder/test-file-2.txt", "Content of test file 2");
        $efs->put("{$userDir}/folder/subfolder/test-file-3.txt", "Content of test file 3");

        $this->assertTrue($efs->exists("{$userDir}/test-file-1.txt"));
        $this->assertTrue($efs->exists("{$userDir}/folder/test-file-2.txt"));
        $this->assertTrue($efs->exists("{$userDir}/folder/subfolder/test-file-3.txt"));
    }

    private function setupEncryptedStorageDirectories(User $user): void
    {
        $encryptedPath = config('scrybble.cryptfs.encrypted_path') . "/user-{$user->id}";
        $decryptedPath = config('scrybble.cryptfs.decrypted_path') . "/user-{$user->id}";

        if (!is_dir($decryptedPath)) {
            mkdir($decryptedPath, 0755, true);
        }
        if (!is_dir($encryptedPath)) {
            mkdir($encryptedPath, 0755, true);
        }

        $this->assertTrue(is_dir($decryptedPath));
        $this->assertTrue(is_dir($encryptedPath));
    }

    private function assertLegacyStorageIsEmpty(User $user): void
    {
        $efs = Storage::disk('efs');
        $userDir = "user-{$user->id}";

        $this->assertFalse($efs->exists($userDir));
    }

    private function assertFilesExistInDecryptedStorage(User $user): void
    {
        $decryptedStorage = UserStorage::get($user);

        $this->assertTrue($decryptedStorage->exists('test-file-1.txt'));
        $this->assertTrue($decryptedStorage->exists('folder/test-file-2.txt'));
        $this->assertTrue($decryptedStorage->exists('folder/subfolder/test-file-3.txt'));

        $this->assertEquals("Content of test file 1", $decryptedStorage->get('test-file-1.txt'));
        $this->assertEquals("Content of test file 2", $decryptedStorage->get('folder/test-file-2.txt'));
        $this->assertEquals("Content of test file 3", $decryptedStorage->get('folder/subfolder/test-file-3.txt'));
    }

    private function cleanupTestDirectories(): void
    {
        $efs = Storage::disk('efs');
        $userDir = "user-{$this->testUserId}";

        if ($efs->exists($userDir)) {
            $efs->deleteDirectory($userDir);
        }

        $decryptedPath = config('scrybble.cryptfs.decrypted_path') . "/user-{$this->testUserId}";
        if (is_dir($decryptedPath)) {
            FileManipulations::removeDirectory($decryptedPath);
        }

        $encryptedPath = config('scrybble.cryptfs.encrypted_path') . "/user-{$this->testUserId}";
        if (is_dir($encryptedPath)) {
            FileManipulations::removeDirectory($encryptedPath);
        }
    }
}
