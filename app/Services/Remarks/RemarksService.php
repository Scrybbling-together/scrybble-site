<?php

namespace App\Services\Remarks;

use Eloquent\Pathogen\AbsolutePathInterface;

interface RemarksService
{
    public function extractNotesAndHighlights(AbsolutePathInterface $sourceDirectory, AbsolutePathInterface $targetDirectory): string;
}
