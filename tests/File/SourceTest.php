<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\File;

use Innmind\Filesystem\{
    File\Source,
    Source as SourceInterface,
    File,
    Adapter,
};
use Innmind\Stream\Readable\Stream;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Fixtures\Innmind\Filesystem\Name;
use Fixtures\Innmind\MediaType\MediaType;
use Fixtures\Innmind\Url\Path;

class SourceTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            SourceInterface::class,
            new Source(
                $this->createMock(File::class),
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
                    $this->createMock(File::class),
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
                    $this->createMock(File::class),
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
                    $this->createMock(File::class),
                    $adapter = $this->createMock(Adapter::class),
                    $path,
                );

                $this->assertTrue($source->sourcedAt(
                    $adapter,
                    $path,
                ));
            });
    }

    public function testName()
    {
        $this
            ->forAll(
                Name::any(),
                Path::any(),
            )
            ->then(function($name, $path) {
                $source = new Source(
                    $inner = $this->createMock(File::class),
                    $this->createMock(Adapter::class),
                    $path,
                );
                $inner
                    ->expects($this->once())
                    ->method('name')
                    ->willReturn($name);

                $this->assertSame($name, $source->name());
            });
    }

    public function testContent()
    {
        $this
            ->forAll(
                Set\Strings::any(),
                Path::any(),
            )
            ->then(function($content, $path) {
                $source = new Source(
                    $inner = $this->createMock(File::class),
                    $this->createMock(Adapter::class),
                    $path,
                );
                $inner
                    ->expects($this->once())
                    ->method('content')
                    ->willReturn($expected = Stream::ofContent($content));

                $this->assertSame($expected, $source->content());
            });
    }

    public function testMediaType()
    {
        $this
            ->forAll(
                MediaType::any(),
                Path::any(),
            )
            ->then(function($mediaType, $path) {
                $source = new Source(
                    $inner = $this->createMock(File::class),
                    $this->createMock(Adapter::class),
                    $path,
                );
                $inner
                    ->expects($this->once())
                    ->method('mediaType')
                    ->willReturn($mediaType);

                $this->assertSame($mediaType, $source->mediaType());
            });
    }
}
