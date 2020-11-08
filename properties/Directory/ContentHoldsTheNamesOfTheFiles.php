<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class ContentHoldsTheNamesOfTheFiles implements Property
{
    public function name(): string
    {
        return 'Content stream holds the names of the files';
    }

    public function applicableTo(object $directory): bool
    {
        return true;
    }

    public function ensureHeldBy(object $directory): object
    {
        $content = $directory->content()->toString();
        $directory->foreach(static function($file) use ($content) {
            Assert::assertStringContainsString(
                $file->name()->toString(),
                $content,
            );
        });

        return $directory;
    }
}
