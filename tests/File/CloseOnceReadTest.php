<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\File;

use Innmind\Filesystem\{
    File\CloseOnceRead,
    File as FileInterface,
    Stream\CloseOnEnd,
    Source,
    Adapter,
};
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Fixtures\Innmind\Filesystem\File;
use Fixtures\Innmind\Url\Path;

class CloseOnceReadTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $this->assertInstanceOf(
            FileInterface::class,
            new CloseOnceRead($this->createMock(FileInterface::class)),
        );
        $this->assertInstanceOf(
            Source::class,
            new CloseOnceRead($this->createMock(FileInterface::class)),
        );
    }

    public function testApi()
    {
        $this
            ->forAll(File::any())
            ->then(function($inner) {
                $file = new CloseOnceRead($inner);

                $this->assertSame($inner->name(), $file->name());
                $this->assertInstanceOf(CloseOnEnd::class, $file->content());
                $this->assertSame(
                    $inner->content()->toString(),
                    $file->content()->toString(),
                );
                $this->assertSame($inner->mediaType(), $file->mediaType());
            });
    }

    public function testFileFromUnknownOriginWillAlwaysReturnFalseToSourcedAt()
    {
        $this
            ->forAll(
                File::any(),
                Path::any(),
            )
            ->then(function($inner, $path) {
                $file = new CloseOnceRead($inner);

                $this->assertFalse($file->sourcedAt(
                    $this->createMock(Adapter::class),
                    $path,
                ));
            });
    }

    public function testSourcedAt()
    {
        $this
            ->forAll(
                Set\Elements::of(true, false),
                Path::any(),
            )
            ->then(function($expected, $path) {
                $file = new CloseOnceRead(
                    $inner = $this->createMock(Source::class),
                );
                $inner
                    ->expects($this->once())
                    ->method('sourcedAt')
                    ->willReturn($expected);

                $this->assertSame(
                    $expected,
                    $file->sourcedAt(
                        $this->createMock(Adapter::class),
                        $path,
                    ),
                );
            });
    }
}
