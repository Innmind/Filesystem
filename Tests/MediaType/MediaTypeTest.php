<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests\MediaType;

use Innmind\Filesystem\{
    MediaTypeInterface,
    MediaType\MediaType,
    MediaType\Parameter,
    MediaType\ParameterInterface
};
use Innmind\Immutable\Map;

class MediaTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $m = new MediaType(
            'application',
            'json',
            'whatever',
            $p = (new Map('string', ParameterInterface::class))
                ->put('charset', new Parameter('charset', 'UTF-8'))
        );

        $this->assertInstanceOf(MediaTypeInterface::class, $m);
        $this->assertSame($p, $m->parameters());
        $this->assertSame('application', $m->topLevel());
        $this->assertSame('json', $m->subType());
        $this->assertSame('whatever', $m->suffix());
        $this->assertSame('application/json+whatever; charset=UTF-8', (string) $m);
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\InvalidArgumentException
     */
    public function testThrowWhenInvalidListOfParameters()
    {
        new MediaType('application', 'foo', '', new Map('int', 'int'));
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\InvalidTopLevelTypeException
     */
    public function testThrowWhenTheTopLevelIsInvalid()
    {
        new MediaType('foo', 'bar', '', new Map('string', ParameterInterface::class));
    }
}
