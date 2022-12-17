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
use Innmind\BlackBox\PHPUnit\BlackBox;
use Fixtures\Innmind\Filesystem\Name as FName;
use Fixtures\Innmind\MediaType\MediaType as FMediaType;

class FileTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $f = File::of($name = Name::of('foo'), $c = $this->createMock(Content::class));

        $this->assertInstanceOf(FileInterface::class, $f);
        $this->assertSame($name, $f->name());
        $this->assertSame($c, $f->content());
        $this->assertSame(
            'application/octet-stream',
            $f->mediaType()->toString(),
        );
    }

    public function testNamed()
    {
        $file = File::named('foo', $this->createMock(Content::class));

        $this->assertInstanceOf(File::class, $file);
        $this->assertSame('foo', $file->name()->toString());
    }

    public function testMediaType()
    {
        $f = File::of(
            Name::of('foo'),
            $this->createMock(Content::class),
            $mt = MediaType::of('application/json'),
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
                $file = File::of(
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
                $file = File::of(
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

    public function testWithContent()
    {
        $this
            ->forAll(
                FName::any(),
                FMediaType::any(),
            )
            ->then(function($name, $mediaType) {
                $file = File::of(
                    $name,
                    $content = $this->createMock(Content::class),
                    $mediaType,
                );
                $file2 = $file->withContent($content2 = $this->createMock(Content::class));

                $this->assertNotSame($file, $file2);
                $this->assertNotSame($file->content(), $file2->content());
                $this->assertSame($content, $file->content());
                $this->assertSame($content2, $file2->content());
            });
    }

    public function testWithContentKeepsTheMediaTypeByDefault()
    {
        $this
            ->forAll(
                FName::any(),
                FMediaType::any(),
            )
            ->then(function($name, $mediaType) {
                $file = File::of(
                    $name,
                    $this->createMock(Content::class),
                    $mediaType,
                );
                $file2 = $file->withContent($this->createMock(Content::class));

                $this->assertNotSame($file, $file2);
                $this->assertSame($file->mediaType(), $file2->mediaType());
            });
    }

    public function testRename()
    {
        $this
            ->forAll(
                FName::any(),
                FName::any(),
            )
            ->then(function($name1, $name2) {
                $file1 = File::of(
                    $name1,
                    $this->createMock(Content::class),
                );
                $file2 = $file1->rename($name2);

                $this->assertNotSame($file1, $file2);
                $this->assertSame($file1->content(), $file2->content());
                $this->assertSame($file1->mediaType(), $file2->mediaType());
                $this->assertSame($name1, $file1->name());
                $this->assertSame($name2, $file2->name());
            });
    }
}
