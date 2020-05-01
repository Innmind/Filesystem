<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests\File;

use Innmind\Filesystem\{
    File\File,
    File as FileInterface,
    Name,
};
use Innmind\Stream\Readable\Stream;
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
        $f = new File($name = new Name('foo'), $c = Stream::ofContent('bar'));

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
        $file = File::named('foo', Stream::ofContent(''));

        $this->assertInstanceOf(File::class, $file);
        $this->assertSame('foo', $file->name()->toString());
    }

    public function testWithContent()
    {
        $f = new File(new Name('foo'), $c = Stream::ofContent('bar'));
        $f2 = $f->withContent($c2 = Stream::ofContent('baz'));

        $this->assertNotSame($f, $f2);
        $this->assertSame($f->name(), $f2->name());
        $this->assertSame($c, $f->content());
        $this->assertSame($c2, $f2->content());
    }

    public function testMediaType()
    {
        $f = new File(
            new Name('foo'),
            Stream::ofContent('bar'),
            $mt = MediaType::of('application/json')
        );

        $this->assertSame($mt, $f->mediaType());
    }

    public function testContentIsNeverAltered()
    {
        $this
            ->forAll(
                FName::any(),
                Set\Strings::any(),
                FMediaType::any(),
            )
            ->then(function($name, $content, $mediaType) {
                $file = new File(
                    $name,
                    $stream = Stream::ofContent($content),
                    $mediaType,
                );

                $this->assertSame($name, $file->name());
                $this->assertSame($stream, $file->content());
                $this->assertSame($mediaType, $file->mediaType());
            });
    }

    public function testByDefaultTheMediaTypeIsOctetStream()
    {
        $this
            ->forAll(
                FName::any(),
                Set\Strings::any(),
            )
            ->then(function($name, $content) {
                $file = new File(
                    $name,
                    Stream::ofContent($content),
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
                Set\Strings::any(),
                FMediaType::any(),
            )
            ->then(function($name, $content, $mediaType) {
                $file = File::named(
                    $name->toString(),
                    $stream = Stream::ofContent($content),
                    $mediaType,
                );

                $this->assertTrue($file->name()->equals($name));
                $this->assertSame($stream, $file->content());
                $this->assertSame($mediaType, $file->mediaType());
            });
    }

    public function testWithContentIsPure()
    {
        $this
            ->forAll(
                FName::any(),
                Set\Strings::any(),
                Set\Strings::any(),
                FMediaType::any(),
            )
            ->then(function($name, $content, $content2, $mediaType) {
                $file1 = new File(
                    $name,
                    $stream = Stream::ofContent($content),
                    $mediaType,
                );
                $file2 = $file1->withContent(Stream::ofContent($content2));

                $this->assertSame($name, $file1->name());
                $this->assertSame($stream, $file1->content());
                $this->assertSame($mediaType, $file1->mediaType());
                $this->assertSame($name, $file2->name());
                $this->assertSame($content2, $file2->content()->toString());
                $this->assertSame($mediaType, $file2->mediaType());
            });
    }
}
