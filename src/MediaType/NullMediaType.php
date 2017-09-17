<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\MediaType;

use Innmind\Filesystem\MediaType as MediaTypeInterface;
use Innmind\Immutable\{
    MapInterface,
    Map
};

final class NullMediaType implements MediaTypeInterface
{
    private $parameters;

    public function __construct()
    {
        $this->parameters = new Map('string', Parameter::class);
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
    public function parameters(): MapInterface
    {
        return $this->parameters;
    }

    public function __toString(): string
    {
        return 'application/octet-stream';
    }
}
