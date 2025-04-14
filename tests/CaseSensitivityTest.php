<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem;

use Innmind\Filesystem\{
    CaseSensitivity,
    Name,
};
use Innmind\Immutable\Set as ISet;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
    Set,
};
use Fixtures\Innmind\Filesystem\Name as FName;

class CaseSensitivityTest extends TestCase
{
    use BlackBox;

    public function testContains()
    {
        $this
            ->forAll(
                FName::strings(),
                FName::strings(),
            )
            ->filter(static fn($a, $b) => $a !== $b)
            ->then(function($a, $b) {
                $this->assertTrue(CaseSensitivity::sensitive->contains(
                    Name::of($a),
                    ISet::of(Name::of($a)),
                ));
                $this->assertFalse(CaseSensitivity::sensitive->contains(
                    Name::of($a),
                    ISet::of(Name::of($b)),
                ));
            });
        $this
            ->forAll(
                FName::strings(),
                Set::sequence(FName::strings()),
            )
            ->filter(static fn($a, $b) => !\in_array($a, $b, true))
            ->then(function($a, $b) {
                $this->assertFalse(CaseSensitivity::sensitive->contains(
                    Name::of($a),
                    ISet::of(...$b)->map(Name::of(...)),
                ));
            });
        $this
            ->forAll(FName::strings())
            ->then(function($a) {
                $this->assertTrue(CaseSensitivity::insensitive->contains(
                    Name::of($a),
                    ISet::of($a)->map(\strtolower(...))->map(Name::of(...)),
                ));
                $this->assertTrue(CaseSensitivity::insensitive->contains(
                    Name::of($a),
                    ISet::of($a)->map(\strtoupper(...))->map(Name::of(...)),
                ));
            });
    }
}
