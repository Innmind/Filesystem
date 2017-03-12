<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    AdapterInterface,
    FileInterface,
    Exception\FileNotFoundException
};
use Innmind\Immutable\MapInterface;

final class SilenceRemovalExceptionAdapter implements AdapterInterface
{
    private $adapter;

    public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * {@inheritdoc}
     */
    public function add(FileInterface $file): AdapterInterface
    {
        $this->adapter->add($file);

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function get(string $file): FileInterface
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
    public function remove(string $file): AdapterInterface
    {
        try {
            $this->adapter->remove($file);
        } catch (FileNotFoundException $e) {
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
