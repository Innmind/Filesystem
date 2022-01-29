<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\InMemory,
    Adapter,
    Directory\Directory,
    File\File,
    File\Content\Lines,
    Name,
};
use Innmind\Immutable\Set;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\PHPUnit\BlackBox;
use Properties\Innmind\Filesystem\Adapter as PAdapter;

class InMemoryTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $a = InMemory::new();

        $this->assertInstanceOf(Adapter::class, $a);
        $this->assertFalse($a->contains(new Name('foo')));
        $this->assertNull(
            $a->add($d = Directory::of(new Name('foo'))),
        );
        $this->assertTrue($a->contains(new Name('foo')));
        $this->assertSame(
            $d,
            $a->get(new Name('foo'))->match(
                static fn($file) => $file,
                static fn() => null,
            ),
        );
        $this->assertNull($a->remove(new Name('foo')));
        $this->assertFalse($a->contains(new Name('foo')));
    }

    public function testReturnNothingWhenGettingUnknownFile()
    {
        $this->assertNull(InMemory::new()->get(new Name('foo'))->match(
            static fn($file) => $file,
            static fn() => null,
        ));
    }

    public function testRemovingUnknownFileDoesntThrow()
    {
        $this->assertNull(InMemory::new()->remove(new Name('foo')));
    }

    public function testAll()
    {
        $adapter = InMemory::new();
        $adapter->add($foo = new File(
            new Name('foo'),
            Lines::ofContent('foo'),
        ));
        $adapter->add($bar = new File(
            new Name('bar'),
            Lines::ofContent('bar'),
        ));

        $all = $adapter->all();
        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(
            [$foo, $bar],
            $all->toList(),
        );
    }

    /**
     * @dataProvider properties
     */
    public function testHoldProperty($property)
    {
        $this
            ->forAll($property)
            ->then(function($property) {
                if (!$property->applicableTo(InMemory::new())) {
                    $this->markTestSkipped();
                }

                $property->ensureHeldBy(InMemory::new());
            });
    }

    /**
     * @group properties
     */
    public function testHoldProperties()
    {
        $this
            ->forAll(PAdapter::properties())
            ->then(static function($properties) {
                $properties->ensureHeldBy(InMemory::new());
            });
    }

    public function properties(): iterable
    {
        foreach (PAdapter::list() as $property) {
            yield [$property];
        }
    }
}
