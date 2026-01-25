<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Filesystem\Exception\RecoverMount;
use Innmind\Immutable\Attempt;

final class Recover
{
    private function __construct(
    ) {
    }

    /**
     * @return Attempt<Adapter>
     */
    #[\NoDiscard]
    public static function mount(\Throwable $e): Attempt
    {
        return match (true) {
            $e instanceof RecoverMount => $e->recover(),
            default => Attempt::error($e),
        };
    }
}
