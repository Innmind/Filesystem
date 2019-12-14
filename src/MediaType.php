<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Immutable\Map;

interface MediaType
{
    public function topLevel(): string;
    public function subType(): string;
    public function suffix(): string;

    /**
     * @return Map<string, Parameter>
     */
    public function parameters(): Map;
    public function __toString(): string;
}
