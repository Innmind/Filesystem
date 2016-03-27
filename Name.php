<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

class Name implements NameInterface
{
    private $name;

    public function __construct(string $name)
    {
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
