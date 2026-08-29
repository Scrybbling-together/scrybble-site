<?php

declare(strict_types=1);

namespace App\Services\Remarks;

use Eloquent\Pathogen\AbsolutePathInterface;
use RuntimeException;
use Symfony\Component\Process\Process;

class RemarksRunDockerContainer implements RemarksService
{
    private string $remarks_version = '0.3.16';

    public function extractNotesAndHighlights(AbsolutePathInterface $sourceDirectory, AbsolutePathInterface $targetDirectory): string
    {
        $process = new Process([
            'docker', 'run',
            '-v', $sourceDirectory->string().'/:/in',
            '-v', $targetDirectory->string().':/out',
            'laauurraaa/remarks-bin:'.$this->remarks_version,
            '/in', '/out',
        ]);

        $process->run();

        if (! $process->isSuccessful()) {
            throw new RuntimeException(
                'remarks-bin docker failed: '.$process->getErrorOutput()
            );
        }

        return $process->getOutput()."\n".$process->getErrorOutput();
    }
}
