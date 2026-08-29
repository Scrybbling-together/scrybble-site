<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Services\Remarks\RemarksHTTPServer;
use Eloquent\Pathogen\AbsolutePath;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

final class RemarksHTTPServerTest extends TestCase
{
    public function test_posts_to_configured_host_and_port(): void
    {
        Storage::fake('efs');
        Config::set('remarks.host', 'renderer.internal');
        Config::set('remarks.port', '8080');

        Http::fake([
            'renderer.internal:8080/process' => Http::response('ok', 200),
        ]);

        $efsRoot = AbsolutePath::fromString(Storage::disk('efs')->path('.'));
        $source = $efsRoot->joinAtoms('user-1', 'jobs', 'sync-123');
        $target = $efsRoot->joinAtoms('user-1', 'processed', 'sync-123');

        (new RemarksHTTPServer)->extractNotesAndHighlights($source, $target);

        Http::assertSent(fn ($request) => Str::contains($request->url(), 'renderer.internal:8080/process'));
    }
}
