<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Tests\MediaType;

use Innmind\Filesystem\{
    MediaTypeInterface,
    MediaType\NullMediaType
};

class NullMediaTypeTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $m = new NullMediaType;

        $this->assertInstanceOf(MediaTypeInterface::class, $m);
        $this->assertSame('application', $m->topLevel());
        $this->assertSame('octet-stream', $m->subType());
        $this->assertSame('', $m->suffix());
        $this->assertSame(0, $m->parameters()->size());
        $this->assertSame('application/octet-stream', (string) $m);
    }
}
