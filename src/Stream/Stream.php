<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Stream;

use Innmind\Filesystem\{
    StreamInterface,
    Exception\InvalidArgumentException,
    Exception\StreamSizeUnknownException,
    Exception\PositionNotSeekableException
};

class Stream implements StreamInterface
{
    private $resource;
    private $size;

    public function __construct($resource)
    {
        if (!is_resource($resource)) {
            throw new InvalidArgumentException;
        }

        $this->resource = $resource;
        $stats = fstat($resource);
        $this->size = $stats['size'] ?? null;
        $this->rewind();
    }

    public static function fromPath(string $path): self
    {
        return new self(fopen($path, 'r'));
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        $this->rewind();

        return (string) stream_get_contents($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function close(): StreamInterface
    {
        fclose($this->resource);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        if (!$this->knowsSize()) {
            throw new StreamSizeUnknownException;
        }

        return $this->size;
    }

    /**
     * {@inheritdoc}
     */
    public function knowsSize(): bool
    {
        return is_int($this->size);
    }

    /**
     * {@inheritdoc}
     */
    public function position(): int
    {
        return ftell($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function isEof(): bool
    {
        return feof($this->resource);
    }

    /**
     * {@inheritdoc}
     */
    public function seek(int $position, int $whence = self::SEEK_SET): StreamInterface
    {
        $status = fseek($this->resource, $position, $whence);

        if ($status === -1) {
            throw new PositionNotSeekableException;
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function rewind(): StreamInterface
    {
        return $this->seek(0);
    }

    /**
     * {@inheritdoc}
     */
    public function read(int $length): string
    {
        return fread($this->resource, $length);
    }
}
