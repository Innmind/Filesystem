<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests;

use Innmind\Filesystem\Name;
use Innmind\Url\Path;
use Innmind\Immutable\Str;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};
use Fixtures\Innmind\Filesystem\Name as Fixture;

class NameTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $n = Name::of('foo');

        $this->assertSame('foo', $n->toString());
    }

    public function testThrowWhenABuildingNameWithASlash()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('A file name can\'t contain a slash, foo/bar given');

        $_ = Name::of('foo/bar');
    }

    public function testEquals()
    {
        $this->assertTrue((Name::of('foo'))->equals(Name::of('foo')));
        $this->assertFalse((Name::of('foo'))->equals(Name::of('bar')));
    }

    public function testEmptyNameIsNotAllowed()
    {
        $this->expectException(\DomainException::class);
        $this->expectExceptionMessage('A file name can\'t be empty');

        $_ = Name::of('');
    }

    public function testAcceptsAnyValueNotContainingASlash(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Fixture::strings(),
            )
            ->prove(function($value) {
                $name = Name::of($value);

                $this->assertSame($value, $name->toString());
            });
    }

    public function testNameContainingASlashIsNotAccepted(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Fixture::strings(),
                Fixture::strings(),
            )
            ->prove(function($a, $b) {
                $this->expectException(\DomainException::class);

                $_ = Name::of("$a/$b");
            });
    }

    public function testNameEqualsItself(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Fixture::strings(),
            )
            ->prove(function($value) {
                $name1 = Name::of($value);
                $name2 = Name::of($value);

                $this->assertTrue($name1->equals($name1));
                $this->assertTrue($name1->equals($name2));
            });
    }

    public function testNameDoesntEqualDifferentName(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Fixture::strings(),
                Fixture::strings(),
            )
            ->prove(function($a, $b) {
                $name1 = Name::of($a);
                $name2 = Name::of($b);

                $this->assertFalse($name1->equals($name2));
                $this->assertFalse($name2->equals($name1));
            });
    }

    public function testDotFoldersAreNotAccepted(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::of('.', '..'))
            ->prove(function($name) {
                try {
                    $_ = Name::of($name);

                    $this->fail('it should throw');
                } catch (\DomainException $e) {
                    $this->assertSame("'.' and '..' can't be used", $e->getMessage());
                }
            });
    }

    public function testChr0IsNotAccepted()
    {
        $this->expectException(\DomainException::class);

        $_ = Name::of('a'.\chr(0).'a');
    }

    public function testNamesLongerThan255AreNotAccepted(): BlackBox\Proof
    {
        return $this
            ->forAll(
                Set::sequence(
                    Set::of(
                        ...\range(1, 46),
                        // chr(47) alias '/' not accepted
                        ...\range(48, 127),
                    )->map(static fn(int $chr): string => \chr($chr)),
                )
                    ->between(256, 1024) // upper limit at 1024 to avoid out of memory
                    ->map(static fn(array $chrs): string => \implode('', $chrs))
                    ->filter(static fn(string $name): bool => $name !== '.' && $name !== '..'),
            )
            ->prove(function($name) {
                try {
                    $_ = Name::of($name);

                    $this->fail('it should throw');
                } catch (\Throwable $e) {
                    $this->assertInstanceOf(\DomainException::class, $e);
                }
            });
    }

    public function testNameWithOnlyWhiteSpacesIsNotAccepted(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::of(
                32,
                ...\range(9, 13),
            ))
            ->prove(function($ord) {
                try {
                    $_ = Name::of(\chr($ord));

                    $this->fail('it should throw');
                } catch (\DomainException $e) {
                    $this->assertTrue(true);
                }
            });
    }

    public function testAnySequenceOfNamesConstitutesAValidPath(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::sequence(
                Fixture::any(),
            )->between(1, 10)) // enough to prove the behaviour
            ->prove(function($names) {
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

    public function testUnicodeCharactersAreAccepted(): BlackBox\Proof
    {
        return $this
            ->forAll(Set::strings()->unicode()->latinExtendedA())
            ->prove(function(string $name) {
                $this->assertInstanceOf(Name::class, Name::of($name));
            });
    }

    public function testStr(): BlackBox\Proof
    {
        return $this
            ->forAll(Fixture::strings())
            ->prove(function($value) {
                $this->assertInstanceOf(Str::class, Name::of($value)->str());
                $this->assertSame(
                    $value,
                    Name::of($value)->str()->toString(),
                );
            });
    }
}
