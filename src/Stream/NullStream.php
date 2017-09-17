<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Stream;

use Innmind\Filesystem\{
    Stream as StreamInterface,
    Exception\PositionNotSeekableException
};

final class NullStream implements StreamInterface
{
    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function close(): StreamInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function knowsSize(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function position(): int
    {
        return 0;
    }

    /**
     * {@inheritdoc}
     */
    public function isEof(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function seek(int $position, int $whence = self::SEEK_SET): StreamInterface
    {
        throw new PositionNotSeekableException;
    }

    /**
     * {@inheritd oc}
     */
    public function rewind(): StreamInterface
    {
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function read(int $length): string
    {
        return '';
    }
}
