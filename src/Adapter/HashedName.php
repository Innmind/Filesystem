<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Directory,
    Name,
    Exception\LogicException,
};
use Innmind\Immutable\{
    Set,
    Str,
    Maybe,
};

/**
 * Take the name of a file hashes it and persist files in subdirectories
 * following the pattern /[ab]/[cd]/{remaining-of-the-hash}
 *
 * You can't add directories via this adapter
 */
final class HashedName implements Adapter
{
    private Adapter $filesystem;

    private function __construct(Adapter $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public static function of(Adapter $filesystem): self
    {
        return new self($filesystem);
    }

    public function add(File $file): void
    {
        if ($file instanceof Directory) {
            throw new LogicException('A directory can\'t be hashed');
        }

        $hashes = $this->hash($file->name());
        [$first, $second] = $this->fetch($hashes[0], $hashes[1], $hashes[2]);

        $first = $first->otherwise(static fn() => Maybe::just(Directory\Directory::of($hashes[0])));
        $second = $second
            ->otherwise(static fn() => Maybe::just(Directory\Directory::of($hashes[1])))
            ->map(static fn($second) => $second->add(File\File::of(
                $hashes[2],
                $file->content(),
            )));
        $persist = $second
            ->flatMap(static fn($second) => $first->map(
                static fn($first) => $first->add($second),
            ))
            ->match(
                fn($first) => fn() => $this->filesystem->add($first),
                static fn() => static fn() => null,
            );
        $persist();
    }

    public function get(Name $file): Maybe
    {
        $originalName = $file;
        $hashes = $this->hash($file);
        [, , $concreteFile] = $this->fetch($hashes[0], $hashes[1], $hashes[2]);

        /** @var Maybe<File> */
        return $concreteFile->map(static fn($file) => File\File::of(
            $originalName,
            $file->content(),
            $file->mediaType(),
        ));
    }

    public function contains(Name $file): bool
    {
        return $this->get($file)->match(
            static fn() => true,
            static fn() => false,
        );
    }

    public function remove(Name $file): void
    {
        $hashes = $this->hash($file);
        [$first, $second] = $this->fetch($hashes[0], $hashes[1], $hashes[2]);

        $second = $second->map(static fn($second) => $second->remove($hashes[2]));
        $first = $second->flatMap(static fn($second) => $first->map(
            static fn($first) => $first->add($second),
        ));
        $persist = $first->match(
            fn($first) => fn() => $this->filesystem->add($first),
            static fn() => static fn() => null,
        );
        $persist();
    }

    public function all(): Set
    {
        return Set::of(...$this->root()->files()->toList());
    }

    public function root(): Directory
    {
        //this is not ideal but the names can't be determined from the hashes
        return $this->filesystem->root();
    }

    /**
     * @return array{0: Name, 1: Name, 2: Name}
     */
    private function hash(Name $name): array
    {
        $extension = \pathinfo($name->toString(), \PATHINFO_EXTENSION);
        $hash = Str::of(\sha1(\pathinfo($name->toString(), \PATHINFO_BASENAME)));

        /** @psalm-suppress ArgumentTypeCoercion */
        $first = Name::of($hash->substring(0, 2)->toString());
        /** @psalm-suppress ArgumentTypeCoercion */
        $second = Name::of($hash->substring(2, 2)->toString());
        /** @psalm-suppress ArgumentTypeCoercion */
        $remaining = Name::of($hash->substring(4)->toString().($extension ? '.'.$extension : ''));

        return [$first, $second, $remaining];
    }

    /**
     * @return array{0: Maybe<Directory>, 1: Maybe<Directory>, 2: Maybe<File>}
     */
    private function fetch(Name $first, Name $second, Name $file): array
    {
        /** @var Maybe<Directory> */
        $firstDirectory = $this
            ->filesystem
            ->get($first)
            ->filter(static fn($first) => $first instanceof Directory);
        /** @var Maybe<Directory> */
        $secondDirectory = $firstDirectory
            ->flatMap(static fn($first) => $first->get($second))
            ->filter(static fn($second) => $second instanceof Directory);
        $concreteFile = $secondDirectory->flatMap(
            static fn($second) => $second->get($file),
        );

        return [$firstDirectory, $secondDirectory, $concreteFile];
    }
}
