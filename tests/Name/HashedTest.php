<?php
declare(strict_types = 1);

namespace Tests\Innmind\Filesystem\Name;

use Innmind\Filesystem\{
    Name\Hashed,
    Name
};
use PHPUnit\Framework\TestCase;

class HashedTest extends TestCase
{
    /**
     * @dataProvider cases
     */
    public function testInterface($value, $first, $second, $remaining)
    {
        $name = new Hashed(new Name\Name($value));

        $this->assertInstanceOf(Name::class, $name);
        $this->assertSame($first, $name->first());
        $this->assertSame($second, $name->second());
        $this->assertSame($remaining, $name->remaining());
        $this->assertSame($value, (string) $name);
    }

    public function cases(): array
    {
        return [
            ['foo', '0b', 'ee', 'c7b5ea3f0fdbc95d0dd47f3c5bc275da8a33'],
            ['foo.txt', '92', '06', 'ac42b532ef8e983470c251f4e1a365fd636c.txt'],
            ['foo.html.twig', '40', 'e6', 'b3acfcf952028de453f1ebf19d4a16715186.twig'],
        ];
    }
}
