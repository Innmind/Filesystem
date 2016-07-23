<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Event;

use Innmind\Filesystem\{
    Event\FileWasAdded,
    FileInterface
};

class FileWasAddedTest extends \PHPUnit_Framework_TestCase
{
    public function testInterface()
    {
        $file = $this->createMock(FileInterface::class);

        $event = new FileWasAdded($file);

        $this->assertSame($file, $event->file());
    }
}
