<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Filesystem\Exception\DomainException;
use Innmind\Immutable\Str;

final class Name
{
    private string $value;

    public function __construct(string $value)
    {
        if (Str::of($value)->matches('|/|')) {
            throw new DomainException("A file name can't contain a slash, $value given");
        }

        if (Str::of($value)->empty()) {
            throw new DomainException('A file name can\'t be empty');
        }

        if ($value === '.' || $value === '..') {
            // as they are special links on unix filesystems
            throw new DomainException("'.' and '..' can't be used");
        }

        $this->value = $value;
    }

    public function equals(self $name): bool
    {
        return $this->value === $name->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
