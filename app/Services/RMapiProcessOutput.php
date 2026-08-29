<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Captured output of a single rmapi subprocess invocation.
 *
 * All three text fields are byte-exact captures in chronological emit order:
 *  - {@see $combined} is what the user would see in a terminal (stdout and
 *    stderr interleaved as the subprocess wrote them).
 *  - {@see $stdout} contains only stdout bytes, in emit order.
 *  - {@see $stderr} contains only stderr bytes, in emit order.
 */
final readonly class RMapiProcessOutput
{
    public function __construct(
        public string $combined,
        public string $stdout,
        public string $stderr,
        public int $exitCode,
    ) {}
}
