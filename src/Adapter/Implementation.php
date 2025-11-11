<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    File,
    Name,
    Adapter\Name as Name_,
};
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
     * @return Attempt<File|Name_\Directory>
     */
    public function read(
        TreePath $parent,
        Name_\File|Name_\Directory|Name_\Unknown $name,
    ): Attempt;

    /**
     * @return Sequence<Name_\File|Name_\Directory>
     */
    public function list(TreePath $parent): Sequence;

    /**
     * @return Attempt<SideEffect>
     */
    public function remove(TreePath $parent, Name $name): Attempt;

    /**
     * @return Attempt<SideEffect>
     */
    public function createDirectory(TreePath $parent, Name $name): Attempt;

    /**
     * @return Attempt<SideEffect>
     */
    public function write(TreePath $parent, File $file): Attempt;
}
