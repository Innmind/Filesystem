<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Exception\FileNotFound
};
use Innmind\Immutable\Map;

final class SilenceRemovalExceptionAdapter implements Adapter
{
    private Adapter $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function add(File $file): void
    {
        $this->adapter->add($file);
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $file): File
    {
        return $this->adapter->get($file);
    }

    /**
     * {@inheritdoc}
     */
    public function contains(string $file): bool
    {
        return $this->adapter->contains($file);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $file): void
    {
        try {
            $this->adapter->remove($file);
        } catch (FileNotFound $e) {
            //pass
        }
    }

    /**
     * {@inheritdoc}
     */
    public function all(): Map
    {
        return $this->adapter->all();
    }
}
