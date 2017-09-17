<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

interface Stream
{
    const SEEK_SET = SEEK_SET;
    const SEEK_CUR = SEEK_CUR;
    const SEEK_END = SEEK_END;

    /**
     * Return the complete content of the resource
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Closes the stream
     *
     * @return self
     */
    public function close(): self;

    /**
     * Return the size of the file
     *
     * @throws StreamSizeUnknownException
     *
     * @return int
     */
    public function size(): int;

    /**
     * Check if the size of the stream is known
     *
     * @return bool
     */
    public function knowsSize(): bool;

    /**
     * Returns the current position of the file read/write pointer
     *
     * @return int
     */
    public function position(): int;

    /**
     * Returns whether it's reached the end of stream or not
     *
     * @return bool
     */
    public function isEof(): bool;

    /**
     * Seek to a position
     *
     * @param int $position
     * @param int $whence
     *
     * @throws PositionNotSeekableException
     *
     * @return self
     */
    public function seek(int $position, int $whence = self::SEEK_SET): self;

    /**
     * Set the position to the start of the stream
     *
     * @return self
     */
    public function rewind(): self;

    /**
     * Returns a specific length of the stream
     *
     * @param int $length
     *
     * @throws StreamReadException
     *
     * @return string
     */
    public function read(int $length): string;
}
