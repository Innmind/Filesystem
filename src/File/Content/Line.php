<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\File\Content;

use Innmind\Immutable\Str;

/**
 * @psalm-immutable
 */
final class Line
{
    private function __construct(private Str $content)
    {
    }

    /**
     * @psalm-pure
     */
    public static function of(Str $content): self
    {
        if ($content->contains("\n")) {
            throw new \DomainException('New line delimiter should not appear in the line content');
        }

        return new self($content);
    }

    /**
     * @internal
     * @psalm-pure
     */
    public static function fromStream(Str $content): self
    {
        return new self($content->rightTrim("\n"));
    }

    /**
     * @param callable(Str): Str $map
     */
    public function map(callable $map): self
    {
        /** @psalm-suppress ImpureFunctionCall */
        return self::of($map($this->content));
    }

    public function str(): Str
    {
        return $this->content;
    }

    public function toString(): string
    {
        return $this->content->toString();
    }
}
