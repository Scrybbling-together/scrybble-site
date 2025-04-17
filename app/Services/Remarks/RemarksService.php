<?php

namespace App\Services\Remarks;


use Eloquent\Pathogen\AbsolutePathInterface;

/**
 *
 */
interface RemarksService
{
    /**
     * @param AbsolutePathInterface $sourceDirectory
     * @param AbsolutePathInterface $targetDirectory
     * @return string
     */
    public function extractNotesAndHighlights(AbsolutePathInterface $sourceDirectory, AbsolutePathInterface $targetDirectory): string;
}
