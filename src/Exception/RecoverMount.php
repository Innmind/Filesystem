<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Exception;

use Innmind\Filesystem\Adapter;
use Innmind\Immutable\Attempt;

/**
 * @internal
 */
final class RecoverMount extends \RuntimeException
{
    /**
     * @param \Closure(): Attempt<Adapter> $recover
     */
    public function __construct(
        private \Closure $recover,
    ) {
    }

    /**
     * @return Attempt<Adapter>
     */
    public function recover(): Attempt
    {
        return ($this->recover)();
    }
}
