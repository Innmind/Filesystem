<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Exception\FileNotFound
};
use Innmind\Immutable\MapInterface;

final class SilenceRemovalExceptionAdapter implements Adapter
{
    private $adapter;

    public function __construct(Adapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function add(File $file): Adapter
    {
        $this->adapter->add($file);

        return $this;
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
    public function has(string $file): bool
    {
        return $this->adapter->has($file);
    }

    /**
     * {@inheritdoc}
     */
    public function remove(string $file): Adapter
    {
        try {
            $this->adapter->remove($file);
        } catch (FileNotFound $e) {
            //pass
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function all(): MapInterface
    {
        return $this->adapter->all();
    }
}
