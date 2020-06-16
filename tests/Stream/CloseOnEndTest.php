<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Stream;

use Innmind\Filesystem\Stream\CloseOnEnd;
use Innmind\Stream\Readable as ReadableInterface;
use PHPUnit\Framework\TestCase;
use Innmind\BlackBox\{
    PHPUnit\BlackBox,
    Set,
};
use Fixtures\Innmind\Stream\Readable as Fixture;
use Properties\Innmind\Stream\Readable;

class CloseOnEndTest extends TestCase
{
    use BlackBox;

    /**
     * @group properties
     */
    public function testHoldProperties()
    {
        $this
            ->forAll(
                Readable::properties(),
                Fixture::any()
            )
            ->then(function($properties, $inner) {
                $properties->ensureHeldBy(new CloseOnEnd($inner));
            });
    }

    public function testNeverCloseTheStreamWhenEndNotReached()
    {
        $this
            ->forAll(Set\Sequence::of(Set\Elements::of(false)))
            ->then(function($calls) {
                $stream = new CloseOnEnd(
                    $inner = $this->createMock(ReadableInterface::class),
                );
                $inner
                    ->expects($this->exactly(\count($calls)))
                    ->method('end')
                    ->willReturn(false);

                foreach ($calls as $call) {
                    $this->assertFalse($stream->end());
                }
            });
    }

    public function testCloseStreamWhenEndReached()
    {
        $stream = new CloseOnEnd(
            $inner = $this->createMock(ReadableInterface::class),
        );
        $inner
            ->expects($this->once())
            ->method('end')
            ->willReturn(true);
        $inner
            ->expects($this->once())
            ->method('close');

        $this->assertTrue($stream->end());
    }
}
