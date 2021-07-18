<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests\File;

use Innmind\Filesystem\{
    File\File,
    File\Content,
    File as FileInterface,
    Name,
};
use Innmind\MediaType\MediaType;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Fixtures\Innmind\Filesystem\Name as FName;
use Fixtures\Innmind\MediaType\MediaType as FMediaType;

class FileTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $f = new File($name = new Name('foo'), $c = $this->createMock(Content::class));

        $this->assertInstanceOf(FileInterface::class, $f);
        $this->assertSame($name, $f->name());
        $this->assertSame($c, $f->content());
        $this->assertSame(
            'application/octet-stream',
            $f->mediaType()->toString()
        );
    }

    public function testNamed()
    {
        $file = File::named('foo', $this->createMock(Content::class));

        $this->assertInstanceOf(File::class, $file);
        $this->assertSame('foo', $file->name()->toString());
    }

    public function testWithContent()
    {
        $f = new File(new Name('foo'), $c = $this->createMock(Content::class));
        $f2 = $f->withContent($c2 = $this->createMock(Content::class));

        $this->assertNotSame($f, $f2);
        $this->assertSame($f->name(), $f2->name());
        $this->assertSame($c, $f->content());
        $this->assertSame($c2, $f2->content());
    }

    public function testMediaType()
    {
        $f = new File(
            new Name('foo'),
            $this->createMock(Content::class),
            $mt = MediaType::of('application/json')->match(
                static fn($mediaType) => $mediaType,
                static fn() => null,
            ),
        );

        $this->assertNotNull($mt);
        $this->assertSame($mt, $f->mediaType());
    }

    public function testContentIsNeverAltered()
    {
        $this
            ->forAll(
                FName::any(),
                FMediaType::any(),
            )
            ->then(function($name, $mediaType) {
                $file = new File(
                    $name,
                    $content = $this->createMock(Content::class),
                    $mediaType,
                );

                $this->assertSame($name, $file->name());
                $this->assertSame($content, $file->content());
                $this->assertSame($mediaType, $file->mediaType());
            });
    }

    public function testByDefaultTheMediaTypeIsOctetStream()
    {
        $this
            ->forAll(FName::any())
            ->then(function($name) {
                $file = new File(
                    $name,
                    $this->createMock(Content::class),
                );

                $this->assertSame(
                    'application/octet-stream',
                    $file->mediaType()->toString(),
                );
            });
    }

    public function testNamedConstructorNeverAltersTheContent()
    {
        $this
            ->forAll(
                FName::any(),
                FMediaType::any(),
            )
            ->then(function($name, $mediaType) {
                $file = File::named(
                    $name->toString(),
                    $content = $this->createMock(Content::class),
                    $mediaType,
                );

                $this->assertTrue($file->name()->equals($name));
                $this->assertSame($content, $file->content());
                $this->assertSame($mediaType, $file->mediaType());
            });
    }

    public function testWithContentIsPure()
    {
        $this
            ->forAll(
                FName::any(),
                FMediaType::any(),
            )
            ->then(function($name, $mediaType) {
                $file1 = new File(
                    $name,
                    $content = $this->createMock(Content::class),
                    $mediaType,
                );
                $file2 = $file1->withContent($content2 = $this->createMock(Content::class));

                $this->assertSame($name, $file1->name());
                $this->assertSame($content, $file1->content());
                $this->assertSame($mediaType, $file1->mediaType());
                $this->assertSame($name, $file2->name());
                $this->assertSame($content2, $file2->content());
                $this->assertSame($mediaType, $file2->mediaType());
            });
    }
}
