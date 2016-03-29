<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests;

use Innmind\Filesystem\{
    Name,
    NameInterface
};

class NameTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $n = new Name('foo');

        $this->assertInstanceOf(NameInterface::class, $n);
        $this->assertSame('foo', (string) $n);
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\InvalidArgumentException
     * @expectedExceptionMessage A file name can't contain a slash
     */
    public function testThrowWhenABuildingNameWithASlash()
    {
        new Name('foo/bar');
    }
}
