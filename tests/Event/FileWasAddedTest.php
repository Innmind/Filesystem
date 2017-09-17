<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Event;

use Innmind\Filesystem\{
    Event\FileWasAdded,
    File
};
use PHPUnit\Framework\TestCase;

class FileWasAddedTest extends TestCase
{
    public function testInterface()
    {
        $file = $this->createMock(File::class);

        $event = new FileWasAdded($file);

        $this->assertSame($file, $event->file());
    }
}
