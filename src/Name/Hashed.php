<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Name;

use Innmind\Filesystem\Name;
use Innmind\Immutable\Str;

final class Hashed implements Name
{
    private string $first;
    private string $second;
    private string $remaining;
    private string $original;

    public function __construct(Name $name)
    {
        $extension = \pathinfo((string) $name, PATHINFO_EXTENSION);
        $hash = Str::of(\sha1(\pathinfo((string) $name, PATHINFO_BASENAME)));

        $this->first = (string) $hash->substring(0, 2);
        $this->second = (string) $hash->substring(2, 2);
        $this->remaining = $hash->substring(4).($extension ? '.'.$extension : '');
        $this->original = (string) $name;
    }

    public function first(): string
    {
        return $this->first;
    }

    public function second(): string
    {
        return $this->second;
    }

    public function remaining(): string
    {
        return $this->remaining;
    }

    public function __toString(): string
    {
        return $this->original;
    }
}
