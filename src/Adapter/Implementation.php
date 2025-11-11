<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    File,
    File\Content,
    Name,
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
     * @return Attempt<File|Name>
     */
    public function read(TreePath $path): Attempt;

    /**
     * @return Sequence<TreePath> The paths must be relative
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
    public function write(TreePath $path, Content $content): Attempt;
}
