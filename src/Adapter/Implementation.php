<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Immutable\Attempt;

/**
 * @internal
 */
interface Implementation
{
    /**
     * @return Attempt<bool>
     */
    public function exists(TreePath $path): Attempt;
}
