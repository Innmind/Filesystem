<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Immutable\MapInterface;

interface MediaType
{
    public function topLevel(): string;
    public function subType(): string;
    public function suffix(): string;

    /**
     * @return MapInterface<string, Parameter>
     */
    public function parameters(): MapInterface;
    public function __toString(): string;
}
