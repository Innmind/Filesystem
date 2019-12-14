<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Event;

use Innmind\Filesystem\{
    Event\FileWasRemoved,
    Name\Name,
};
use PHPUnit\Framework\TestCase;

class FileWasRemovedTest extends TestCase
{
    public function testInterface()
    {
        $event = new FileWasRemoved(new Name('foo'));

        $this->assertSame('foo', $event->file()->toString());
    }
}
