<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\InMemory,
    Adapter,
    Directory\Directory,
    File as FileInterface,
    File\File,
    Name,
    Exception\FileNotFound,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
};
use Properties\Innmind\Filesystem\Adapter as PAdapter;

class InMemoryTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $a = new InMemory;

        $this->assertInstanceOf(Adapter::class, $a);
        $this->assertFalse($a->contains(new Name('foo')));
        $this->assertNull(
            $a->add($d = new Directory(new Name('foo')))
        );
        $this->assertTrue($a->contains(new Name('foo')));
        $this->assertSame($d, $a->get(new Name('foo')));
        $this->assertNull($a->remove(new Name('foo')));
        $this->assertFalse($a->contains(new Name('foo')));
    }

    public function testThrowWhenGettingUnknownFile()
    {
        $this->expectException(FileNotFound::class);
        $this->expectExceptionMessage('foo');

        (new InMemory)->get(new Name('foo'));
    }

    public function testRemovingUnknownFileDoesntThrow()
    {
        $this->assertNull((new InMemory)->remove(new Name('foo')));
    }

    public function testAll()
    {
        $adapter = new InMemory;
        $adapter->add($foo = new File(
            new Name('foo'),
            Stream::ofContent('foo')
        ));
        $adapter->add($bar = new File(
            new Name('bar'),
            Stream::ofContent('bar')
        ));

        $all = $adapter->all();
        $this->assertInstanceOf(Set::class, $all);
        $this->assertSame(FileInterface::class, $all->type());
        $this->assertSame(
            [$foo, $bar],
            unwrap($all),
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
                if (!$property->applicableTo(new InMemory)) {
                    $this->markTestSkipped();
                }

                $property->ensureHeldBy(new InMemory);
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
                $properties->ensureHeldBy(new InMemory);
            });
    }

    public function properties(): iterable
    {
        foreach (PAdapter::list() as $property) {
            yield [$property];
        }
    }
}
