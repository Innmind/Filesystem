<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

/**
 * Represent the name of a file
 */
interface Name
{
    public function __toString(): string;
}
