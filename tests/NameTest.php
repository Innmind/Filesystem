<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests;

use Innmind\Filesystem\{
    Name,
    Exception\DomainException,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};

class NameTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $n = new Name('foo');

        $this->assertSame('foo', $n->toString());
    }

    public function testThrowWhenABuildingNameWithASlash()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('A file name can\'t contain a slash, foo/bar given');

        new Name('foo/bar');
    }

    public function testEquals()
    {
        $this->assertTrue((new Name('foo'))->equals(new Name('foo')));
        $this->assertFalse((new Name('foo'))->equals(new Name('bar')));
    }

    public function testAcceptsAnyValueNotContainingASlash()
    {
        $this
            ->forAll(
                Set\Strings::any()->filter(fn($s) => \strpos($s, '/') === false),
            )
            ->then(function($value) {
                $name = new Name($value);

                $this->assertSame($value, $name->toString());
            });
    }

    public function testNameContainingASlashIsNotAccepted()
    {
        $this
            ->forAll(
                Set\Strings::any(),
                Set\Strings::any(),
            )
            ->then(function($a, $b) {
                $this->expectException(DomainException::class);

                new Name("$a/$b");
            });
    }

    public function testNameEqualsItself()
    {
        $this
            ->forAll(Set\Strings::any()->filter(fn($s) => \strpos($s, '/') === false))
            ->then(function($value) {
                $name1 = new Name($value);
                $name2 = new Name($value);

                $this->assertTrue($name1->equals($name1));
                $this->assertTrue($name1->equals($name2));
            });
    }

    public function testNameDoesntEqualDifferentName()
    {
        $this
            ->forAll(
                Set\Strings::any()->filter(fn($s) => \strpos($s, '/') === false),
                Set\Strings::any()->filter(fn($s) => \strpos($s, '/') === false),
            )
            ->then(function($a, $b) {
                $name1 = new Name($a);
                $name2 = new Name($b);

                $this->assertFalse($name1->equals($name2));
                $this->assertFalse($name2->equals($name1));
            });
    }
}
