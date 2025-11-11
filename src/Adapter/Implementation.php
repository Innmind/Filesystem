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
     * @return Attempt<File|Name> A Name represent a directory
     */
    public function read(TreePath $parent, Name $name): Attempt;

    /**
     * @return Sequence<Name> Todo encapsulate if the name represent a file/directory/unknown
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
