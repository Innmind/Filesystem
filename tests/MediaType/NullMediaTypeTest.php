<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\MediaType;

use Innmind\Filesystem\{
    MediaTypeInterface,
    MediaType\NullMediaType
};
use PHPUnit\Framework\TestCase;

class NullMediaTypeTest extends TestCase
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
