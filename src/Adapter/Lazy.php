<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    LazyAdapter as LazyAdapterInterface,
    Adapter,
    File,
    Name,
    Exception\FileNotFound,
};
use Innmind\Immutable\{
    Map,
    Set,
};

final class Lazy implements LazyAdapterInterface
{
    private Adapter $adapter;
    /** @var Map<string, File> */
    private Map $toAdd;
    /** @var Set<string> */
    private Set $toRemove;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        /** @var Map<string, File> */
        $this->toAdd = Map::of('string', File::class);
        $this->toRemove = Set::strings();
    }

    /**
     * {@inheritdoc}
     */
    public function add(File $file): void
    {
        $this->toAdd = ($this->toAdd)(
            $file->name()->toString(),
            $file,
        );
        $this->toRemove = $this->toRemove->remove($file->name()->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function get(Name $file): File
    {
        if (!$this->contains($file)) {
            throw new FileNotFound($file->toString());
        }

        if ($this->toAdd->contains($file->toString())) {
            return $this->toAdd->get($file->toString());
        }

        return $this->adapter->get($file);
    }

    /**
     * {@inheritdoc}
     */
    public function contains(Name $file): bool
    {
        if ($this->toRemove->contains($file->toString())) {
            return false;
        }

        if ($this->toAdd->contains($file->toString())) {
            return true;
        }

        return $this->adapter->contains($file);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Name $file): void
    {
        $this->toRemove = ($this->toRemove)($file->toString());
        $this->toAdd = $this->toAdd->remove($file->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Set
    {
        return $this
            ->adapter
            ->all()
            ->filter(function(File $file): bool {
                return !$this->toRemove->contains($file->name()->toString());
            })
            ->merge(
                $this->toAdd->values()->toSetOf(File::class)
            );
    }

    /**
     * {@inheritdoc}
     */
    public function persist(): void
    {
        $this
            ->toAdd
            ->foreach(function(string $name, File $file) {
                $this->adapter->add($file);
            });
        $this->toAdd = $this->toAdd->clear();
        $this
            ->toRemove
            ->foreach(function(string $name) {
                $name = new Name($name);

                if ($this->adapter->contains($name)) {
                    $this->adapter->remove($name);
                }
            });
        $this->toRemove = Set::strings();
    }
}
