<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

/**
 * All modifications are kept in memory and are really persisted to
 * implementation when `persist` is explicitly called
 */
interface LazyAdapter extends Adapter
{
    /**
     * Really persist all modifications to underlying implementation
     */
    public function persist(): void;
}
