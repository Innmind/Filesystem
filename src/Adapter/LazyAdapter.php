<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    LazyAdapter as LazyAdapterInterface,
    Adapter,
    File,
    Exception\FileNotFound
};
use Innmind\Immutable\{
    Map,
    Set,
};

class LazyAdapter implements LazyAdapterInterface
{
    private Adapter $adapter;
    private Map $toAdd;
    private Set $toRemove;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->toAdd = Map::of('string', File::class);
        $this->toRemove = Set::strings();
    }

    /**
     * {@inheritdoc}
     */
    public function add(File $file): void
    {
        $this->toAdd = $this->toAdd->put(
            $file->name()->toString(),
            $file
        );
        $this->toRemove = $this->toRemove->remove($file->name()->toString());
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $file): File
    {
        if (!$this->has($file)) {
            throw new FileNotFound($file);
        }

        if ($this->toAdd->contains($file)) {
            return $this->toAdd->get($file);
        }

        return $this->adapter->get($file);
    }

    /**
     * {@inheritdoc}
     */
    public function has(string $file): bool
    {
        if ($this->toRemove->contains($file)) {
            return false;
        }

        if ($this->toAdd->contains($file)) {
            return true;
        }

        return $this->adapter->has($file);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $file): void
    {
        if (!$this->has($file)) {
            throw new FileNotFound($file);
        }

        $this->toRemove = $this->toRemove->add($file);
        $this->toAdd = $this->toAdd->remove($file);
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Map
    {
        return $this
            ->adapter
            ->all()
            ->filter(function(string $name): bool {
                return !$this->toRemove->contains($name);
            })
            ->merge($this->toAdd);
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
                if ($this->adapter->has($name)) {
                    $this->adapter->remove($name);
                }
            });
        $this->toRemove = Set::strings();
    }
}
