<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\MediaType\Parameter;

use Innmind\Filesystem\MediaType\{
    Parameter\Parameter,
    Parameter as ParameterInterface
};
use PHPUnit\Framework\TestCase;

class ParameterTest extends TestCase
{
    public function testInterface()
    {
        $p = new Parameter('foo', 'bar');

        $this->assertInstanceOf(ParameterInterface::class, $p);
        $this->assertSame('foo', $p->name());
        $this->assertSame('bar', $p->value());
        $this->assertSame('foo=bar', (string) $p);
    }
}
