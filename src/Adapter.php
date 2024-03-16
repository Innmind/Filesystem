<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Immutable\Maybe;

/**
 * Layer between value objects and concrete implementation
 */
interface Adapter
{
    public function add(File|Directory $file): void;

    /**
     * @return Maybe<File|Directory>
     */
    public function get(Name $file): Maybe;
    public function contains(Name $file): bool;
    public function remove(Name $file): void;
    public function root(): Directory;
}
