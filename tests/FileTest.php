<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests;

use Innmind\Filesystem\{
    File,
    FileInterface,
    Stream\StringStream,
    NameInterface,
    MediaType\MediaType
};
use PHPUnit\Framework\TestCase;

class FileTest extends TestCase
{
    public function testInterface()
    {
        $f = new File('foo', $c = new StringStream('bar'));

        $this->assertInstanceOf(FileInterface::class, $f);
        $this->assertInstanceOf(NameInterface::class, $f->name());
        $this->assertSame('foo', (string) $f->name());
        $this->assertSame($c, $f->content());
        $this->assertSame(
            'application/octet-stream',
            (string) $f->mediaType()
        );
    }

    public function testWithContent()
    {
        $f = new File('foo', $c = new StringStream('bar'));
        $f2 = $f->withContent($c2 = new StringStream('baz'));

        $this->assertNotSame($f, $f2);
        $this->assertSame($f->name(), $f2->name());
        $this->assertSame($c, $f->content());
        $this->assertSame($c2, $f2->content());
    }

    public function testMediaType()
    {
        $f = new File(
            'foo',
            new StringStream('bar'),
            $mt = MediaType::fromString('application/json')
        );

        $this->assertSame($mt, $f->mediaType());
    }
}
