<?php

namespace App\Services\Remarks;

use App\Services\Remarks;
use Eloquent\Pathogen\AbsolutePath;
use Eloquent\Pathogen\AbsolutePathInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class RemarksHTTPServer implements RemarksService
{

    /**
     * @inheritDoc
     */
    public function extractNotesAndHighlights(AbsolutePathInterface $sourceDirectory, AbsolutePathInterface $targetDirectory): string
    {
        $efsRoot = AbsolutePath::fromString(Storage::disk('efs')->path("."));
        $res = Http::timeout(5 * 60)->post(sprintf("%s:%s/process", config('remarks.host'), config('remarks.port')), [
            "in_path" => "/efs/" . $sourceDirectory->relativeTo($efsRoot)->string(),
            "out_path" => "/efs/" . $sourceDirectory->relativeTo($efsRoot)->string()
        ]);
        if ($res->status() !== 200) {
            throw new RuntimeException("Failed to convert notes and highlights: " . $res->body());
        }
        return $res->body();
    }
}
