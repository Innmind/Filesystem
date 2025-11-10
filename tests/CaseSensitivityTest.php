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

    public function testContainsSensitive(): BlackBox\Proof
    {
        return $this
            ->forAll(
                FName::strings(),
                Set::sequence(FName::strings()),
            )
            ->filter(static fn($a, $b) => !\in_array($a, $b, true))
            ->prove(function($a, $b) {
                $this->assertTrue(CaseSensitivity::sensitive->contains(
                    Name::of($a),
                    ISet::of(Name::of($a)),
                ));
                $this->assertFalse(CaseSensitivity::sensitive->contains(
                    Name::of($a),
                    ISet::of(...$b)->map(Name::of(...)),
                ));
            });
    }

    public function testContainsInsensitive(): BlackBox\Proof
    {
        return $this
            ->forAll(FName::strings())
            ->prove(function($a) {
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
