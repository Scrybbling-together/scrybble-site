<?php

namespace App\Services\PRMStorage;

/**
 * PRM stands for [P]rocessed [R]e[M]arkable file
 */
interface PRMStorageInterface
{
    public function store(string $path, string $zipFileContents);

    public function getDownloadURL(string $path);
}
