<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\MediaType;

use Innmind\Filesystem\MediaType as MediaTypeInterface;
use Innmind\Immutable\Map;

final class NullMediaType implements MediaTypeInterface
{
    private Map $parameters;

    public function __construct()
    {
        $this->parameters = Map::of('string', Parameter::class);
    }

    public function topLevel(): string
    {
        return 'application';
    }

    public function subType(): string
    {
        return 'octet-stream';
    }

    public function suffix(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function parameters(): Map
    {
        return $this->parameters;
    }

    public function __toString(): string
    {
        return 'application/octet-stream';
    }
}
