<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Directory,
    Name,
};
use Innmind\Immutable\{
    Attempt,
    Maybe,
};

/**
 * This should replace the Adapter interface in order to only expose a final
 * class in the end.
 */
final class Bridge implements Adapter
{
    private function __construct(
        private Adapter $adapter,
    ) {
    }

    public static function of(Adapter $adapter): self
    {
        return new self($adapter);
    }

    #[\Override]
    public function add(File|Directory $file): Attempt
    {
        return $this->adapter->add($file);
    }

    #[\Override]
    public function get(Name $file): Maybe
    {
        return $this->adapter->get($file);
    }

    #[\Override]
    public function contains(Name $file): bool
    {
        return $this->adapter->contains($file);
    }

    #[\Override]
    public function remove(Name $file): Attempt
    {
        return $this->adapter->remove($file);
    }

    #[\Override]
    public function root(): Directory
    {
        return $this->adapter->root()->rename(Name::of('root'));
    }
}
