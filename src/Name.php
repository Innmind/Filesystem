<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Filesystem\Exception\InvalidArgumentException;
use Innmind\Immutable\Str;

class Name implements NameInterface
{
    private $name;

    public function __construct(string $name)
    {
        if ((new Str($name))->matches('|/|')) {
            throw new InvalidArgumentException(
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
