<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Exception;

use Innmind\Filesystem\Adapter\Implementation;
use Innmind\Immutable\Attempt;

/**
 * @internal
 */
final class MountPathDoesntExist extends \RuntimeException
{
    /**
     * @param \Closure(): Attempt<Implementation> $recover
     */
    public function __construct(
        private \Closure $recover,
    ) {
    }

    /**
     * @return Attempt<Implementation>
     */
    public function recover(): Attempt
    {
        return ($this->recover)();
    }
}
