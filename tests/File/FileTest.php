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
        $f = new File($name = new Name('foo'), $c = $this->createMock(Content::class));

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
        $f = new File(
            new Name('foo'),
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
}
