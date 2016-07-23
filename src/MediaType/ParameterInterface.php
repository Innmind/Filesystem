<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\MediaType;

interface ParameterInterface
{
    public function name(): string;
    public function value(): string;
    public function __toString(): string;
}
