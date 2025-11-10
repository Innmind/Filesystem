<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    File,
    Name,
};
use Innmind\Immutable\{
    Attempt,
    Sequence,
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
}
