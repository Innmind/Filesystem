<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Name;

use Innmind\Filesystem\{
    Name as NameInterface,
    Exception\DomainException,
};
use Innmind\Immutable\Str;

final class Name implements NameInterface
{
    private string $name;

    public function __construct(string $name)
    {
        if (Str::of($name)->matches('|/|')) {
            throw new DomainException('A file name can\'t contain a slash');
        }

        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function toString(): string
    {
        return $this->name;
    }
}
