<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\File;
use Innmind\Immutable\{
    Attempt,
    Sequence,
    SideEffect,
};

/**
 * @internal
 */
interface Implementation
{
    /**
     * @return Attempt<bool>
     */
    public function exists(TreePath $path): Attempt;

    /**
     * @return Attempt<File|Name\Directory>
     */
    public function read(
        TreePath $parent,
        Name\File|Name\Directory|Name\Unknown $name,
    ): Attempt;

    /**
     * @return Sequence<Name\File|Name\Directory>
     */
    public function list(TreePath $parent): Sequence;

    /**
     * @return Attempt<SideEffect>
     */
    public function remove(TreePath $path): Attempt;

    /**
     * @return Attempt<SideEffect>
     */
    public function createDirectory(TreePath $path): Attempt;

    /**
     * @return Attempt<SideEffect>
     */
    public function write(TreePath $parent, File $file): Attempt;
}
