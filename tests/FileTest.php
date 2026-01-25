<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests;

use Innmind\Filesystem\{
    File,
    File\Content,
    File as FileInterface,
    Name,
};
use Innmind\MediaType\MediaType;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    PHPUnit\Framework\TestCase,
};
use Fixtures\Innmind\Filesystem\Name as FName;
use Fixtures\Innmind\MediaType\MediaType as FMediaType;

class FileTest extends TestCase
{
    use BlackBox;

    public function testInterface()
    {
        $f = File::of($name = Name::of('foo'), $c = Content::none());

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
        $file = File::named('foo', Content::none());

        $this->assertInstanceOf(File::class, $file);
        $this->assertSame('foo', $file->name()->toString());
    }

    public function testMediaType()
    {
        $f = File::of(
            Name::of('foo'),
            Content::none(),
            $mt = MediaType::of('application/json'),
        );

        $this->assertNotNull($mt);
        $this->assertSame($mt, $f->mediaType());
    }

    public function testContentIsNeverAltered(): BlackBox\Proof
    {
        return $this
            ->forAll(
                FName::any(),
                FMediaType::any(),
            )
            ->prove(function($name, $mediaType) {
                $file = File::of(
                    $name,
                    $content = Content::none(),
                    $mediaType,
                );

                $this->assertSame($name, $file->name());
                $this->assertSame($content, $file->content());
                $this->assertSame($mediaType, $file->mediaType());
            });
    }

    public function testByDefaultTheMediaTypeIsOctetStream(): BlackBox\Proof
    {
        return $this
            ->forAll(FName::any())
            ->prove(function($name) {
                $file = File::of(
                    $name,
                    Content::none(),
                );

                $this->assertSame(
                    'application/octet-stream',
                    $file->mediaType()->toString(),
                );
            });
    }

    public function testNamedConstructorNeverAltersTheContent(): BlackBox\Proof
    {
        return $this
            ->forAll(
                FName::any(),
                FMediaType::any(),
            )
            ->prove(function($name, $mediaType) {
                $file = File::named(
                    $name->toString(),
                    $content = Content::none(),
                    $mediaType,
                );

                $this->assertTrue($file->name()->equals($name));
                $this->assertSame($content, $file->content());
                $this->assertSame($mediaType, $file->mediaType());
            });
    }

    public function testWithContent(): BlackBox\Proof
    {
        return $this
            ->forAll(
                FName::any(),
                FMediaType::any(),
            )
            ->prove(function($name, $mediaType) {
                $file = File::of(
                    $name,
                    $content = Content::none(),
                    $mediaType,
                );
                $file2 = $file->withContent($content2 = Content::none());

                $this->assertNotSame($file, $file2);
                $this->assertNotSame($file->content(), $file2->content());
                $this->assertSame($content, $file->content());
                $this->assertSame($content2, $file2->content());
            });
    }

    public function testWithContentKeepsTheMediaTypeByDefault(): BlackBox\Proof
    {
        return $this
            ->forAll(
                FName::any(),
                FMediaType::any(),
            )
            ->prove(function($name, $mediaType) {
                $file = File::of(
                    $name,
                    Content::none(),
                    $mediaType,
                );
                $file2 = $file->withContent(Content::none());

                $this->assertNotSame($file, $file2);
                $this->assertSame($file->mediaType(), $file2->mediaType());
            });
    }

    public function testRename(): BlackBox\Proof
    {
        return $this
            ->forAll(
                FName::any(),
                FName::any(),
            )
            ->prove(function($name1, $name2) {
                $file1 = File::of(
                    $name1,
                    Content::none(),
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
