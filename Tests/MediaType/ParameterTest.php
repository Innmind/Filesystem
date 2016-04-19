<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests\MediaType;

use Innmind\Filesystem\MediaType\{
    Parameter,
    ParameterInterface
};

class ParameterTest extends \PHPUnit_Framework_TestCase
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
