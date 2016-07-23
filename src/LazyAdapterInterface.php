<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

/**
 * All modifications are kept in memory and are really persisted to
 * implementation when `persist` is explicitly called
 */
interface LazyAdapterInterface extends AdapterInterface
{
    /**
     * Really persist all modifications to underlying implementation
     *
     * @return self
     */
    public function persist(): self;
}
