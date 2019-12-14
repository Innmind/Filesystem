<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Filesystem\Exception\DomainException;
use Innmind\Immutable\Str;

final class Name
{
    private string $name;

    public function __construct(string $name)
    {
        if (Str::of($name)->matches('|/|')) {
            throw new DomainException('A file name can\'t contain a slash');
        }

        $this->name = $name;
    }

    public function toString(): string
    {
        return $this->name;
    }
}
