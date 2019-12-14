<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Name;

use Innmind\Filesystem\Name;
use Innmind\Immutable\Str;

final class Hashed implements Name
{
    private Name $first;
    private Name $second;
    private Name $remaining;
    private string $original;

    public function __construct(Name $name)
    {
        $extension = \pathinfo($name->toString(), PATHINFO_EXTENSION);
        $hash = Str::of(\sha1(\pathinfo($name->toString(), PATHINFO_BASENAME)));

        $this->first = new Name\Name($hash->substring(0, 2)->toString());
        $this->second = new Name\Name($hash->substring(2, 2)->toString());
        $this->remaining = new Name\Name($hash->substring(4)->toString().($extension ? '.'.$extension : ''));
        $this->original = $name->toString();
    }

    public function first(): Name
    {
        return $this->first;
    }

    public function second(): Name
    {
        return $this->second;
    }

    public function remaining(): Name
    {
        return $this->remaining;
    }

    public function toString(): string
    {
        return $this->original;
    }
}
