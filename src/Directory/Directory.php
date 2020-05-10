<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Directory;

use Innmind\Filesystem\{
    Directory as DirectoryInterface,
    Name,
    File,
    Exception\FileNotFound,
    Exception\LogicException,
    Event\FileWasAdded,
    Event\FileWasRemoved,
};
use Innmind\Url\Path;
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
    /** @var Set<File> */
    private Set $files;
    private MediaType $mediaType;
    /** @var Sequence<object> */
    private Sequence $modifications;

    /**
     * @param Set<File>|null $files
     */
    public function __construct(Name $name, Set $files = null)
    {
        /** @var Set<File> $default */
        $default = Set::of(File::class);
        $files ??= $default;

        assertSet(File::class, $files, 2);

        $files->reduce(
            Set::strings(),
            static function(Set $names, File $file): Set {
                $name = $file->name()->toString();

                if ($names->contains($name)) {
                    throw new LogicException("Same file '$name' found multiple times");
                }

                return ($names)($name);
            },
        );

        $this->name = $name;
        $this->files = $files;
        $this->mediaType = new MediaType(
            'text',
            'directory',
        );
        $this->modifications = Sequence::objects();
    }

    public static function named(string $name): self
    {
        return new self(new Name($name));
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

        /** @var Set<string> $names */
        $names = $this
            ->files
            ->toSetOf('string', fn($file): \Generator => yield $file->name()->toString())
            ->sort(static fn(string $a, string $b): int => $a <=> $b);
        $this->content = Readable\Stream::ofContent(
            join("\n", $names)->toString(),
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
        $files = $this->files->filter(static fn(File $known): bool => !$known->name()->equals($file->name()));

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
        $file = $this->files->reduce(
            null,
            static function(?File $found, File $file) use ($name): ?File {
                if ($found) {
                    return $found;
                }

                if ($file->name()->equals($name)) {
                    return $file;
                }

                return null;
            }
        );

        if (\is_null($file)) {
            throw new FileNotFound($name->toString());
        }

        return $file;
    }

    /**
     * {@inheritdoc}
     */
    public function contains(Name $name): bool
    {
        return $this->files->reduce(
            false,
            static fn(bool $found, File $file): bool => $found || $file->name()->equals($name),
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
        $directory->files = $this->files->filter(static fn(File $file) => !$file->name()->equals($name));
        $directory->record(new FileWasRemoved($name));

        return $directory;
    }

    /**
     * {@inheritdoc}
     */
    public function replaceAt(Path $path, File $file): DirectoryInterface
    {
        $normalizedPath = Str::of($path->toString())->trim('/');
        $pieces = $normalizedPath->split('/');

        if ($normalizedPath->empty()) {
            return $this->add($file);
        }

        $child = $this->get(new Name($pieces->first()->toString()));

        if (!$child instanceof DirectoryInterface) {
            throw new LogicException('Path doesn\'t reference a directory');
        }

        if ($pieces->count() === 1) {
            return $this->add(
                $child->add($file),
            );
        }

        /** @var Set<string> $names */
        $names = $pieces->drop(1)->toSequenceOf(
            'string',
            static fn($name): \Generator => yield $name->toString(),
        );

        return $this->add(
            $child->replaceAt(
                Path::of(join('/', $names)->toString()),
                $file,
            ),
        );
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
    public function filter(callable $predicate): Set
    {
        return $this->files->filter($predicate);
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

    private function record(object $event): void
    {
        $this->modifications = ($this->modifications)($event);
    }
}
