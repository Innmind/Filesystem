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
        $extension = \pathinfo($name->toString(), PATHINFO_EXTENSION);
        $hash = Str::of(\sha1(\pathinfo($name->toString(), PATHINFO_BASENAME)));

        $this->first = $hash->substring(0, 2)->toString();
        $this->second = $hash->substring(2, 2)->toString();
        $this->remaining = $hash->substring(4)->toString().($extension ? '.'.$extension : '');
        $this->original = $name->toString();
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

    public function toString(): string
    {
        return $this->original;
    }
}
