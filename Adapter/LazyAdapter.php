<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    LazyAdapterInterface,
    AdapterInterface,
    FileInterface,
    Exception\FileNotFoundException
};
use Innmind\Immutable\{
    Map,
    Set
};

class LazyAdapter implements LazyAdapterInterface
{
    private $adapter;
    private $toAdd;
    private $toRemove;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
        $this->toAdd = new Map('string', FileInterface::class);
        $this->toRemove = new Set('string');
    }

    /**
     * {@inheritdoc}
     */
    public function add(FileInterface $file): AdapterInterface
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
    public function get(string $file): FileInterface
    {
        if (!$this->has($file)) {
            throw new FileNotFoundException;
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
    public function remove(string $file): AdapterInterface
    {
        if (!$this->has($file)) {
            throw new FileNotFoundException;
        }

        $this->toRemove = $this->toRemove->add($file);
        $this->toAdd = $this->toAdd->remove($file);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function persist(): LazyAdapterInterface
    {
        $this->toAdd = $this
            ->toAdd
            ->foreach(function (string $name, FileInterface $file) {
                $this->adapter->add($file);
            })
            ->clear();
        $this
            ->toRemove
            ->foreach(function (string $name) {
                if ($this->adapter->has($name)) {
                    $this->adapter->remove($name);
                }
            });
        $this->toRemove = new Set('string');

        return $this;
    }
}
