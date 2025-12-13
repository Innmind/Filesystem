<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\Name as Name_,
    File,
    Name,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Str,
    Map,
    Attempt,
    Sequence,
    SideEffect,
};

/**
 * @internal
 */
final class InMemory implements Implementation
{
    /**
     * @param Map<string, File> $files
     * @param Map<string, Sequence<string>> $directories
     */
    private function __construct(
        private Map $files,
        private Map $directories,
    ) {
    }

    public static function emulateFilesystem(): self
    {
        return new self(
            Map::of(),
            Map::of(),
        );
    }

    #[\Override]
    public function exists(TreePath $path): Attempt
    {
        $path = $this->path($path);

        return Attempt::result($this->files->contains($path) || $this->directories->contains("$path/"));
    }

    #[\Override]
    public function read(
        TreePath $parent,
        Name_\File|Name_\Directory|Name_\Unknown $name,
    ): Attempt {
        if ($name instanceof Name_\Directory) {
            return $this
                ->directories
                ->get($this->path(TreePath::directory($name->unwrap())->under($parent)))
                ->map(static fn() => $name)
                ->attempt(static fn() => new \RuntimeException('Directory not found'));
        }

        if ($name instanceof Name_\File) {
            return $this
                ->files
                ->get($this->path(TreePath::of($name->unwrap())->under($parent)))
                ->attempt(static fn() => new \RuntimeException('File not found'));
        }

        return $this
            ->read($parent, Name_\Directory::of($name->unwrap()))
            ->recover(fn() => $this->read(
                $parent,
                Name_\File::of($name->unwrap()),
            ));
    }

    #[\Override]
    public function list(TreePath $parent): Sequence
    {
        $path = $this->path($parent);

        /** @psalm-suppress ArgumentTypeCoercion Due to Name::of() */
        return $this
            ->directories
            ->get($path)
            ->toSequence()
            ->flatMap(static fn($files) => $files)
            ->map(fn($file) => match ($this->directories->contains("$path$file/")) {
                true => Name_\Directory::of(Name::of($file)),
                false => Name_\File::of(Name::of($file)),
            });
    }

    #[\Override]
    public function remove(TreePath $parent, Name $name): Attempt
    {
        $asDirectory = $this->path(TreePath::directory($name)->under($parent));
        $this->files = $this
            ->files
            ->remove($this->path(TreePath::of($name)->under($parent)))
            ->exclude(static fn($path) => Str::of($path)->startsWith($asDirectory));
        $parent = $this->path($parent);
        $directories = $this
            ->directories
            ->exclude(static fn($path) => Str::of($path)->startsWith($asDirectory));
        $files = $directories
            ->get($parent)
            ->toSequence()
            ->flatMap(static fn($files) => $files)
            ->exclude(static fn($file) => $file === $name->toString());
        $this->directories = ($directories)(
            $parent,
            $files,
        );

        return Attempt::result(SideEffect::identity);
    }

    #[\Override]
    public function createDirectory(TreePath $parent, Name $name): Attempt
    {
        $path = $this->path(TreePath::directory($name)->under($parent));
        $asFile = Str::of($path)
            ->dropEnd(1) // trailing /
            ->toString();

        $this->files = $this->files->remove($asFile);

        if (!$this->directories->contains($path)) {
            $this->directories = ($this->directories)(
                $path,
                Sequence::strings(),
            );
            $parent = $this->path($parent);
            $files = $this
                ->directories
                ->get($parent)
                ->toSequence()
                ->flatMap(static fn($files) => $files)
                ->add($name->toString());
            $this->directories = ($this->directories)(
                $parent,
                $files,
            );
        }

        return Attempt::result(SideEffect::identity);
    }

    #[\Override]
    public function write(TreePath $parent, File $file): Attempt
    {
        $fullPath = $this->path(TreePath::of($file)->under($parent));
        $parent = $this->path($parent);

        $this->files = ($this->files)($fullPath, $file);
        $files = $this
            ->directories
            ->get($parent)
            ->toSequence()
            ->flatMap(static fn($files) => $files)
            ->add($file->name()->toString());
        $this->directories = ($this->directories)($parent, $files);

        return Attempt::result(SideEffect::identity);
    }

    private function path(TreePath $path): string
    {
        return $path->asPath(Path::of('/'))->toString();
    }
}
