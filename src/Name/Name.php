<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Name;

use Innmind\Filesystem\{
    Name as NameInterface,
    Exception\DomainException
};
use Innmind\Immutable\Str;

class Name implements NameInterface
{
    private string $name;

    public function __construct(string $name)
    {
        if ((new Str($name))->matches('|/|')) {
            throw new DomainException(
                'A file name can\'t contain a slash'
            );
        }

        $this->name = $name;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return $this->name;
    }
}
