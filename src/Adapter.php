<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Immutable\{
    Maybe,
    Attempt,
    SideEffect,
};

/**
 * Layer between value objects and concrete implementation
 */
interface Adapter
{
    /**
     * @return Attempt<SideEffect>
     */
    public function add(File|Directory $file): Attempt;

    /**
     * @return Maybe<File|Directory>
     */
    public function get(Name $file): Maybe;
    public function contains(Name $file): bool;

    /**
     * @return Attempt<SideEffect>
     */
    public function remove(Name $file): Attempt;
    public function root(): Directory;
}
