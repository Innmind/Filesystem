<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter\InMemory;

use Innmind\Filesystem\{
    File,
    Directory,
};

/**
 * @internal
 */
final class Overwrite
{
    public function __invoke(Directory $parent, File|Directory $file): Directory
    {
        return $parent->add($file);
    }
}
