<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Event;

use Innmind\Filesystem\Event\FileWasRemoved;

class FileWasRemovedTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $event = new FileWasRemoved('foo');

        $this->assertSame('foo', $event->file());
    }
}
