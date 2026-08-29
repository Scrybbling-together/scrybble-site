<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function array_some(array $array, callable $callback): bool
    {
        foreach ($array as $key => $value) {
            if ($callback($value, $key, $array)) {
                return true;
            }
        }

        return false;
    }

    public function up(): void
    {
        $efs = Storage::disk('efs');

        $sync_id_to_user_mapping = [];
        $files = $efs->allFiles('userZips');
        foreach ($files as $file) {
            preg_match("/^userZips\/([a-z0-9]{16})\.zip/i", $file, $user_zip_matches);
            [$_, $sync_id] = $user_zip_matches;
            $user_directories = array_filter($efs->directories(), fn ($directory) => str_starts_with($directory, 'user-'));
            foreach ($user_directories as $user_directory) {
                preg_match('/user-([0-9]+)/', $user_directory, $user_directory_matches);
                [$_, $user_id] = $user_directory_matches;
                $sync_id_belongs_to_this_user = $this->array_some($efs->allDirectories($user_directory), fn ($directory) => str_contains($directory, $sync_id));
                if ($sync_id_belongs_to_this_user) {
                    $sync_id_to_user_mapping[$file] = "user-{$user_id}/processed/{$sync_id}.zip";
                }
            }
        }

        foreach ($sync_id_to_user_mapping as $file => $sync_id_to_user) {
            $efs->move($file, $sync_id_to_user);
        }
    }
};
