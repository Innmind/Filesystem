<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\CloseOnceRead,
    Adapter\InMemory,
    Adapter,
    File,
    Directory,
};
use Innmind\Immutable\Set;
use function Innmind\Immutable\unwrap;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\PHPUnit\BlackBox;
use Fixtures\Innmind\Filesystem\{
    File as FFile,
    Directory as FDirectory,
};
use Properties\Innmind\Filesystem\Adapter as PAdapter;

class CloseOnceReadTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Adapter::class,
            new CloseOnceRead(
                $this->createMock(Adapter::class)
            )
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
                $adapter = new CloseOnceRead(new InMemory);

                if (!$property->applicableTo($adapter)) {
                    $this->markTestSkipped();
                }

                $property->ensureHeldBy($adapter);
            });
    }

    /**
     * @group properties
     */
    public function testHoldProperties()
    {
        $this
            ->forAll(PAdapter::properties())
            ->then(function($properties) {
                $properties->ensureHeldBy(new CloseOnceRead(new InMemory));
            });
    }

    public function testGetDecorateFiles()
    {
        $this
            ->forAll(FFile::any())
            ->then(function($expected) {
                $adapter = new CloseOnceRead(new InMemory);

                $adapter->add($expected);
                $file = $adapter->get($expected->name());

                $this->assertInstanceOf(File\CloseOnceRead::class, $file);
                $this->assertSame($expected->name(), $file->name());
            });
    }

    public function testGetDecorateDirectories()
    {
        $this
            ->forAll(FDirectory::any())
            ->then(function($expected) {
                $adapter = new CloseOnceRead(new InMemory);

                $adapter->add($expected);
                $directory = $adapter->get($expected->name());

                $this->assertInstanceOf(Directory\CloseOnceRead::class, $directory);
                $this->assertSame($expected->name(), $directory->name());
            });
    }

    public function testAllDecorateFiles()
    {
        $this
            ->forAll(
                FDirectory::any(),
                FFile::any(),
            )
            ->then(function($expectedDirectory, $expectedFile) {
                $adapter = new CloseOnceRead(new InMemory);

                $adapter->add($expectedDirectory);
                $adapter->add($expectedFile);
                [$directory, $file] = unwrap($adapter->all());

                $this->assertInstanceOf(Directory\CloseOnceRead::class, $directory);
                $this->assertSame($expectedDirectory->name(), $directory->name());
                $this->assertInstanceOf(File\CloseOnceRead::class, $file);
                $this->assertSame($expectedFile->name(), $file->name());
            });
    }

    public function properties(): iterable
    {
        foreach (PAdapter::list() as $property) {
            yield [$property];
        }
    }
}
