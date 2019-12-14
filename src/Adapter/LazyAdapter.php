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
    MapInterface
};

class LazyAdapter implements LazyAdapterInterface
{
    private Adapter $adapter;
    private Map $toAdd;
    private Set $toRemove;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
        $this->toAdd = new Map('string', File::class);
        $this->toRemove = new Set('string');
    }

    /**
     * {@inheritdoc}
     */
    public function add(File $file): Adapter
    {
        $this->toAdd = $this->toAdd->put(
            (string) $file->name(),
            $file
        );
        $this->toRemove = $this->toRemove->remove((string) $file->name());

        return $this;
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
    public function remove(string $file): Adapter
    {
        if (!$this->has($file)) {
            throw new FileNotFound($file);
        }

        $this->toRemove = $this->toRemove->add($file);
        $this->toAdd = $this->toAdd->remove($file);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): MapInterface
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
    public function persist(): LazyAdapterInterface
    {
        $this->toAdd = $this
            ->toAdd
            ->foreach(function(string $name, File $file) {
                $this->adapter->add($file);
            })
            ->clear();
        $this
            ->toRemove
            ->foreach(function(string $name) {
                if ($this->adapter->has($name)) {
                    $this->adapter->remove($name);
                }
            });
        $this->toRemove = new Set('string');

        return $this;
    }
}
