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

class FileTest extends TestCase
{
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
}
