<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory\CloseOnceRead,
    Source,
    Directory as DirectoryInterface,
    Adapter,
    File\CloseOnceRead as DecoratedFile,
};
use Innmind\Stream\Readable\Stream;
use Innmind\Url\Path as RealPath;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Fixtures\Innmind\Filesystem\{
    Directory,
    File,
};
use Properties\Innmind\Filesystem\Directory as PDirectory;
use Fixtures\Innmind\Url\Path;

class CloseOnceReadTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            Source::class,
            new CloseOnceRead(
                $this->createMock(DirectoryInterface::class),
            ),
        );
        $this->assertInstanceOf(
            DirectoryInterface::class,
            new CloseOnceRead(
                $this->createMock(DirectoryInterface::class),
            ),
        );
    }

    /**
     * @group properties
     */
    public function testHoldProperties()
    {
        $this
            ->forAll(
                PDirectory::properties(),
                Directory::any(),
            )
            ->then(function($properties, $inner) {
                $source = new CloseOnceRead($inner);

                $properties->ensureHeldBy($source);
            });
    }

    public function testAlwaysReturnFalseWhenCheckingSourceOfUnknownDirectoryOrigin()
    {
        $this
            ->forAll(
                Directory::any(),
                Path::any()
            )
            ->then(function($inner, $path) {
                $directory = new CloseOnceRead($inner);

                $this->assertFalse($directory->sourcedAt(
                    $this->createMock(Adapter::class),
                    $path,
                ));
            });
    }

    public function testSourcedAt()
    {
        $this
            ->forAll(
                Directory::any(),
                Path::any(),
                Path::any(),
            )
            ->then(function($inner, $pathA, $pathB) {
                $adapter = $this->createMock(Adapter::class);
                $directory = new CloseOnceRead(new DirectoryInterface\Source(
                    $inner,
                    $adapter,
                    $pathA,
                ));

                $this->assertTrue($directory->sourcedAt($adapter, $pathA));
                $this->assertFalse($directory->sourcedAt($adapter, $pathB));
                $this->assertFalse($directory->sourcedAt(
                    $this->createMock(Adapter::class),
                    $pathA,
                ));
            });
    }

    public function testGetDecorateFile()
    {
        $this
            ->forAll(
                Directory::any(),
                File::any(),
            )
            ->then(function($inner, $expected) {
                $directory = new CloseOnceRead($inner);
                $directory = $directory->add($expected);

                $file = $directory->get($expected->name());

                $this->assertInstanceOf(DecoratedFile::class, $file);
                $this->assertSame($expected->name(), $file->name());
                $this->assertSame(
                    $expected->content()->toString(),
                    $file->content()->toString(),
                );
                $this->assertSame($expected->mediaType(), $file->mediaType());
            });
    }

    public function testReplaceAtConserveDecoration()
    {
        $this
            ->forAll(
                Directory::any(),
                File::any(),
            )
            ->then(function($inner, $file) {
                $directory = new CloseOnceRead($inner);

                $this->assertInstanceOf(CloseOnceRead::class, $directory->replaceAt(
                    RealPath::of('/'),
                    $file,
                ));
            });
    }

    public function testForeachDecorateFiles()
    {
        $this
            ->forAll(Directory::any())
            ->then(function($inner) {
                $directory = new CloseOnceRead($inner);

                $directory->foreach(function($file) {
                    $this->assertThat(
                        $file,
                        $this->logicalOr(
                            $this->isInstanceOf(CloseOnceRead::class),
                            $this->isInstanceOf(DecoratedFile::class),
                        ),
                    );
                });
            });
    }

    public function testFilterDecorateFiles()
    {
        $this
            ->forAll(Directory::any())
            ->then(function($inner) {
                $directory = new CloseOnceRead($inner);

                $directory->filter(function($file) {
                    $this->assertThat(
                        $file,
                        $this->logicalOr(
                            $this->isInstanceOf(CloseOnceRead::class),
                            $this->isInstanceOf(DecoratedFile::class),
                        ),
                    );

                    return true;
                });
            });
    }

    public function testReduceDecorateFiles()
    {
        $this
            ->forAll(Directory::any())
            ->then(function($inner) {
                $directory = new CloseOnceRead($inner);

                $directory->reduce(null, function($carry, $file) {
                    $this->assertThat(
                        $file,
                        $this->logicalOr(
                            $this->isInstanceOf(CloseOnceRead::class),
                            $this->isInstanceOf(DecoratedFile::class),
                        ),
                    );

                    return null;
                });
            });
    }
}
