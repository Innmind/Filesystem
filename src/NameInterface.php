<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

/**
 * Represent the name of a file
 */
interface NameInterface
{
    /**
     * Return the name of the file
     *
     * @return string
     */
    public function __toString(): string;
}