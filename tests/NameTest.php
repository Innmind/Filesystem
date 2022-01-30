<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests;

use Innmind\Filesystem\{
    Name,
    Exception\DomainException,
};
use Innmind\Url\Path;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Fixtures\Innmind\Filesystem\Name as Fixture;

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

    public function testEmptyNameIsNotAllowed()
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('A file name can\'t be empty');

        new Name('');
    }

    public function testAcceptsAnyValueNotContainingASlash()
    {
        $this
            ->forAll(
                Fixture::strings(),
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
                Fixture::strings(),
                Fixture::strings(),
            )
            ->then(function($a, $b) {
                $this->expectException(DomainException::class);

                new Name("$a/$b");
            });
    }

    public function testNameEqualsItself()
    {
        $this
            ->forAll(
                Fixture::strings(),
            )
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
                Fixture::strings(),
                Fixture::strings(),
            )
            ->then(function($a, $b) {
                $name1 = new Name($a);
                $name2 = new Name($b);

                $this->assertFalse($name1->equals($name2));
                $this->assertFalse($name2->equals($name1));
            });
    }

    public function testDotFoldersAreNotAccepted()
    {
        $this
            ->forAll(Set\Elements::of('.', '..'))
            ->then(function($name) {
                $this->expectException(DomainException::class);
                $this->expectExceptionMessage("'.' and '..' can't be used");

                new Name($name);
            });
    }

    public function testChr0IsNotAccepted()
    {
        $this->expectException(DomainException::class);

        new Name('a'.\chr(0).'a');
    }

    public function testNamesLongerThan255AreNotAccepted()
    {
        $this
            ->forAll(
                Set\Decorate::immutable(
                    static fn(array $chrs): string => \implode('', $chrs),
                    Set\Sequence::of(
                        Set\Decorate::immutable(
                            static fn(int $chr): string => \chr($chr),
                            Set\Elements::of(
                                ...\range(1, 46),
                                // chr(47) alias '/' not accepted
                                ...\range(48, 127),
                            ),
                        ),
                        Set\Integers::between(255, 1024), // upper limit at 1024 to avoid out of memory
                    ),
                )->filter(static fn(string $name): bool => $name !== '.' && $name !== '..'),
            )
            ->then(function($name) {
                $this->expectException(DomainException::class);

                new Name($name);
            });
    }

    public function testNameWithOnlyWhiteSpacesIsNotAccepted()
    {
        $this
            ->forAll(Set\Elements::of(
                32,
                ...\range(9, 13),
            ))
            ->then(function($ord) {
                try {
                    new Name(\chr($ord));

                    $this->fail('it should throw');
                } catch (DomainException $e) {
                    $this->assertTrue(true);
                }
            });
    }

    public function testAnySequenceOfNamesConstitutesAValidPath()
    {
        $this
            ->forAll(Set\Sequence::of(
                Fixture::any(),
                Set\Integers::between(1, 10), // enough to prove the behaviour
            ))
            ->then(function($names) {
                $strings = \array_map(
                    static fn($name) => $name->toString(),
                    $names,
                );
                $path = '/'.\implode('/', $strings);

                $this->assertInstanceOf(
                    Path::class,
                    Path::of($path),
                );
            });
    }

    public function testUnicodeCharactersAreAccepted()
    {
        $this
            ->forAll(Set\Unicode::latinExtendedA())
            ->then(function(string $name) {
                $this->assertInstanceOf(Name::class, new Name($name));
            });
    }
}
