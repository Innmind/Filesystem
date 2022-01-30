<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Immutable\{
    Set,
    Maybe,
};

/**
 * Layer between value objects and concrete implementation
 */
interface Adapter
{
    public function add(File $file): void;

    /**
     * @return Maybe<File>
     */
    public function get(Name $file): Maybe;
    public function contains(Name $file): bool;
    public function remove(Name $file): void;

    /**
     * @return Set<File>
     */
    public function all(): Set;
}
