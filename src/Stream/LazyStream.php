<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Stream;

use Innmind\Filesystem\{
    Stream as StreamInterface,
    Exception\InvalidArgumentException
};

final class LazyStream implements StreamInterface
{
    private $path;
    private $stream;

    public function __construct(string $path)
    {
        if (empty($path)) {
            throw new InvalidArgumentException;
        }

        $this->path = $path;
    }

    /**
     * {@inheritdoc}
     */
    public function __toString(): string
    {
        return (string) $this->init();
    }

    /**
     * {@inheritdoc}
     */
    public function close(): StreamInterface
    {
        $this->init()->close();

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function size(): int
    {
        return $this->init()->size();
    }

    /**
     * {@inheritdoc}
     */
    public function knowsSize(): bool
    {
        return $this->init()->knowsSize();
    }

    /**
     * {@inheritdoc}
     */
    public function position(): int
    {
        return $this->init()->position();
    }

    /**
     * {@inheritdoc}
     */
    public function isEof(): bool
    {
        return $this->init()->isEof();
    }

    /**
     * {@inheritdoc}
     */
    public function seek(int $position, int $whence = self::SEEK_SET): StreamInterface
    {
        $this->init()->seek($position, $whence);

        return $this;
    }

    /**
     * {@inheritd oc}
     */
    public function rewind(): StreamInterface
    {
        if ($this->isInitialized()) {
            $this->init()->rewind();
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function read(int $length): string
    {
        return $this->init()->read($length);
    }

    public function isInitialized(): bool
    {
        return $this->stream instanceof Stream;
    }

    private function init()
    {
        if (!$this->isInitialized()) {
            $this->stream = Stream::fromPath($this->path);
        }

        return $this->stream;
    }
}
