<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests\Name;

use Innmind\Filesystem\{
    Name\Name,
    Name as NameInterface,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;

class NameTest extends TestCase
{
    public function testInterface()
    {
        $n = new Name('foo');

        $this->assertInstanceOf(NameInterface::class, $n);
        $this->assertSame('foo', $n->toString());
    }

    public function testThrowWhenABuildingNameWithASlash()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('A file name can\'t contain a slash');

        new Name('foo/bar');
    }
}
