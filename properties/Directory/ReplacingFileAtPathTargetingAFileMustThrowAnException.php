<?php
declare(strict_types = 1);

namespace Properties\Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    File,
    Name,
    Exception\LogicException,
};
use Innmind\Url\Path;
use Innmind\BlackBox\Property;
use PHPUnit\Framework\Assert;

final class ReplacingFileAtPathTargetingAFileMustThrowAnException implements Property
{
    private File $target;
    private File $file;

    public function __construct(File $target, File $file)
    {
        $this->target = $target;
        $this->file = $file;
    }

    public function name(): string
    {
        return 'Replacing file at path targeting a file must throw an exception';
    }

    public function applicableTo(object $directory): bool
    {
        return !$directory->contains($this->target->name());
    }

    public function ensureHeldBy(object $directory): object
    {
        try {
            $directory
                ->add($this->target)
                ->replaceAt(
                    Path::of($this->target->name()->toString()),
                    $this->file,
                );

            Assert::fail('it should throw');
        } catch (LogicException $e) {
            Assert::assertTrue(true);
        }

        return $directory;
    }
}
