<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter\Name;

use Innmind\Filesystem\Name;

/**
 * @internal
 * @psalm-immutable
 */
final class Directory
{
    private function __construct(
        private Name $name,
    ) {
    }

    /**
     * @psalm-pure
     */
    public static function of(Name $name): self
    {
        return new self($name);
    }

    public function unwrap(): Name
    {
        return $this->name;
    }
}
