<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    File,
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
     * @return Attempt<File|Sequence<TreePath>>
     */
    public function read(TreePath $path): Attempt;

    /**
     * @return Sequence<TreePath>
     */
    public function list(TreePath $parent): Sequence;
}
