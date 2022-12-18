<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Exception;

use Innmind\Filesystem\Name;

final class DuplicatedFile extends LogicException
{
    public function __construct(Name $name)
    {
        parent::__construct("Same file '{$name->toString()}' found multiple times");
    }
}
