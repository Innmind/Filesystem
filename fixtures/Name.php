<?php
declare(strict_types = 1);

namespace Fixtures\Innmind\Filesystem;

use Innmind\Filesystem\Name as Model;
use Innmind\BlackBox\Set;

/**
 * @implements Set\Prodiver<non-empty-string>
 */
final class Name implements Set\Provider
{
    private function __construct(
        private string $prefix,
    ) {
    }

    /**
     * @return Set<Model>
     */
    public static function any(): Set
    {
        return self::strings()
            ->toSet()
            ->map(Model::of(...));
    }

    public static function strings(): self
    {
        return new self('');
    }

    /**
     * @psalm-mutation-free
     *
     * @param non-empty-string $prefix
     */
    public function prefixedBy(string $prefix): self
    {
        return new self($prefix);
    }

    #[\Override]
    public function toSet(): Set
    {
        return Set::strings()->madeOf(
            Set::integers()->between(32, 46)->map(\chr(...)),
            Set::integers()->between(48, 126)->map(\chr(...)),
            Set::strings()->unicode()->emoticons(),
        )
            ->between(1, 255 - \mb_strlen($this->prefix, 'ASCII'))
            ->map(fn($name) => $this->prefix.$name)
            ->filter(static fn($name) => \mb_strlen($name, 'ASCII') <= 255)
            ->filter(
                static fn(string $name): bool => $name !== '.' &&
                    $name !== '..' &&
                    !\preg_match('~\s+~', $name),
            );
    }
}
