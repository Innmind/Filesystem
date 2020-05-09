<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory\Source,
    Source as SourceInterface,
    Directory as DirectoryInterface,
    Adapter,
};
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Fixtures\Innmind\Filesystem\Directory;
use Properties\Innmind\Filesystem\Directory as PDirectory;
use Fixtures\Innmind\Url\Path;

class SourceTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            SourceInterface::class,
            new Source(
                $this->createMock(DirectoryInterface::class),
                $this->createMock(Adapter::class),
                $this->seeder()(Path::any()),
            ),
        );
        $this->assertInstanceOf(
            DirectoryInterface::class,
            new Source(
                $this->createMock(DirectoryInterface::class),
                $this->createMock(Adapter::class),
                $this->seeder()(Path::any()),
            ),
        );
    }

    public function testAnyFileSourceFromAnotherShouldBePersisted()
    {
        $this
            ->forAll(Path::any())
            ->then(function($path) {
                $source = new Source(
                    $this->createMock(DirectoryInterface::class),
                    $this->createMock(Adapter::class),
                    $path,
                );

                $this->assertFalse($source->sourcedAt(
                    $this->createMock(Adapter::class),
                    $path,
                ));
            });
    }

    public function testFileOpenedInADifferentPathThanTheTargetPathShouldBePersisted()
    {
        $this
            ->forAll(
                Path::any(),
                Path::any(),
            )
            ->filter(function($openedAt, $writeAt) {
                return !$openedAt->equals($writeAt);
            })
            ->then(function($openedAt, $writeAt) {
                $source = new Source(
                    $this->createMock(DirectoryInterface::class),
                    $adapter = $this->createMock(Adapter::class),
                    $openedAt,
                );

                $this->assertFalse($source->sourcedAt(
                    $adapter,
                    $writeAt,
                ));
            });
    }

    public function testShouldNotPersistAFileWhereItWasOpenedInTheSameAdapter()
    {
        $this
            ->forAll(Path::any())
            ->then(function($path) {
                $source = new Source(
                    $this->createMock(DirectoryInterface::class),
                    $adapter = $this->createMock(Adapter::class),
                    $path,
                );

                $this->assertTrue($source->sourcedAt(
                    $adapter,
                    $path,
                ));
            });
    }

    /**
     * @group properties
     */
    public function testHoldProperties()
    {
        $this
            ->forAll(
                PDirectory::properties(),
                Directory::maxDepth(1),
                Path::any(),
            )
            ->then(function($properties, $inner, $path) {
                $source = new Source(
                    $inner,
                    $this->createMock(Adapter::class),
                    $path,
                );

                $properties->ensureHeldBy($source);
            });
    }
}
