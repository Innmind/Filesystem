<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Url\Path;

/**
 * This interface should only be used in implementations of the Adapter interface
 *
 * The goal is to determine if a file should be persisted by the implementation,
 * in order to avoid unecessary writes when a file didn't change
 */
interface Source extends File
{
    public function sourcedAt(Adapter $adapter, Path $path): bool;
}
