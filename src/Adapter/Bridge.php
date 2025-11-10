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
        private Filesystem&Implementation $adapter,
    ) {
    }

    public static function of(Filesystem&Implementation $adapter): self
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
        return $this->read(TreePath::of($file));
    }

    #[\Override]
    public function contains(Name $file): bool
    {
        return $this->adapter->exists(TreePath::of($file))->match(
            static fn($exists) => $exists,
            static fn() => false,
        );
    }

    #[\Override]
    public function remove(Name $file): Attempt
    {
        return $this->adapter->remove(TreePath::of($file));
    }

    #[\Override]
    public function root(): Directory
    {
        return Directory::named(
            'root',
            $this
                ->adapter
                ->list(TreePath::root())
                ->map($this->read(...))
                ->flatMap(static fn($read) => $read->toSequence()),
        );
    }

    /**
     * @return Maybe<File|Directory>
     */
    private function read(TreePath $path): Maybe
    {
        return $this
            ->adapter
            ->read($path)
            ->maybe()
            ->map(fn($file) => match (true) {
                $file instanceof File => $file,
                default => Directory::of(
                    $file,
                    $this
                        ->adapter
                        ->list($path)
                        ->map(static fn($found) => $found->under($path))
                        ->map($this->read(...))
                        ->flatMap(static fn($read) => $read->toSequence()),
                ),
            });
    }
}
