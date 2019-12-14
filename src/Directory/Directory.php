<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory as DirectoryInterface,
    Name,
    File,
    Exception\FileNotFound,
    Event\FileWasAdded,
    Event\FileWasRemoved,
};
use Innmind\Stream\Readable;
use Innmind\MediaType\MediaType;
use Innmind\Immutable\{
    Str,
    Sequence,
    Set,
};
use function Innmind\Immutable\{
    assertSet,
    join,
};

final class Directory implements DirectoryInterface
{
    private Name $name;
    private ?Readable $content = null;
    private Set $files;
    private MediaType $mediaType;
    private Sequence $modifications;

    public function __construct(string $name, Set $files = null)
    {
        $files ??= Set::of(File::class);

        assertSet(File::class, $files, 2);

        $this->name = new Name($name);
        $this->files = $files;
        $this->mediaType = new MediaType(
            'text',
            'directory',
        );
        $this->modifications = Sequence::objects();
    }

    /**
     * {@inheritdoc}
     */
    public function name(): Name
    {
        return $this->name;
    }

    /**
     * {@inheritdoc}
     */
    public function content(): Readable
    {
        if ($this->content instanceof Readable) {
            return $this->content;
        }

        $this->content = Readable\Stream::ofContent(
            join(
                "\n",
                $this
                    ->files
                    ->toSetOf('string', fn($file): \Generator => yield $file->name()->toString()),
            )->toString(),
        );

        return $this->content;
    }

    public function mediaType(): MediaType
    {
        return $this->mediaType;
    }

    /**
     * {@inheritdoc}
     */
    public function add(File $file): DirectoryInterface
    {
        $files = $this->files->filter(static fn(File $known): bool => $known->name()->toString() !== $file->name()->toString());

        $directory = clone $this;
        $directory->content = null;
        $directory->files = ($files)($file);
        $directory->record(new FileWasAdded($file));

        return $directory;
    }

    /**
     * {@inheritdoc}
     */
    public function get(Name $name): File
    {
        $name = $name->toString();

        $file = $this->files->reduce(
            null,
            static function(?File $found, File $file) use ($name): ?File {
                if ($found) {
                    return $found;
                }

                if ($file->name()->toString() === $name) {
                    return $file;
                }

                return null;
            }
        );

        if (\is_null($file)) {
            throw new FileNotFound($name);
        }

        return $file;
    }

    /**
     * {@inheritdoc}
     */
    public function contains(Name $name): bool
    {
        $name = $name->toString();

        return $this->files->reduce(
            false,
            static fn(bool $found, File $file): bool => $found || $file->name()->toString() === $name,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function remove(Name $name): DirectoryInterface
    {
        if (!$this->contains($name)) {
            return $this;
        }

        $directory = clone $this;
        $directory->content = null;
        $directory->files = $this->files->filter(static fn(File $file) => $file->name()->toString() !== $name->toString());
        $directory->record(new FileWasRemoved($name));

        return $directory;
    }

    /**
     * {@inheritdoc}
     */
    public function replaceAt(string $path, File $file): DirectoryInterface
    {
        $pieces = Str::of($path)->split('/');
        $directory = $this;

        while ($pieces->count() > 0) {
            $target = $pieces
                ->reduce(
                    $directory,
                    function(DirectoryInterface $parent, Str $seek): DirectoryInterface {
                        return $parent->get(new Name($seek->toString()));
                    }
                )
                ->add($target ?? $file);
            $pieces = $pieces->dropEnd(1);
        }

        return $directory->add($target);
    }

    /**
     * {@inheritdoc}
     */
    public function foreach(callable $function): void
    {
        $this->files->foreach($function);
    }

    /**
     * {@inheritdoc}
     */
    public function reduce($carry, callable $reducer)
    {
        return $this->files->reduce($carry, $reducer);
    }

    /**
     * {@inheritdoc}
     */
    public function modifications(): Sequence
    {
        return $this->modifications;
    }

    private function record($event): void
    {
        $this->modifications = $this->modifications->add($event);
    }
}
