<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests\Name;

use Innmind\Filesystem\{
    Name\Name,
    Name as NameInterface
};
use PHPUnit\Framework\TestCase;

class NameTest extends TestCase
{
    public function testInterface()
    {
        $n = new Name('foo');

        $this->assertInstanceOf(NameInterface::class, $n);
        $this->assertSame('foo', (string) $n);
    }

    /**
     * @expectedException Innmind\Filesystem\Exception\DomainException
     * @expectedExceptionMessage A file name can't contain a slash
     */
    public function testThrowWhenABuildingNameWithASlash()
    {
        new Name('foo/bar');
    }
}
