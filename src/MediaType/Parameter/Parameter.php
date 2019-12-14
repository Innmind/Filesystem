<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\MediaType\Parameter;

use Innmind\Filesystem\MediaType\Parameter as ParameterInterface;

final class Parameter implements ParameterInterface
{
    private string $name;
    private string $value;

    public function __construct(string $name, string $value)
    {
        $this->name = $name;
        $this->value = $value;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function __toString(): string
    {
        return \sprintf(
            '%s=%s',
            $this->name,
            $this->value
        );
    }
}
