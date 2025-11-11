<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Filesystem\{
    Adapter\Name as Name_,
    Adapter\TreePath,
    Adapter\Implementation,
    Adapter\Filesystem,
    Adapter\InMemory,
    Adapter\Logger,
};
use Innmind\IO\IO;
use Innmind\Url\Path;
use Innmind\Immutable\{
    Attempt,
    Maybe,
    Set,
    SideEffect,
};
use Psr\Log\LoggerInterface;

/**
 * Layer between value objects and concrete implementation
 */
final class Adapter
{
    /** @var \WeakMap<File|Directory, TreePath> */
    private \WeakMap $loaded;

    private function __construct(
        private Implementation $adapter,
        private CaseSensitivity $case,
    ) {
        /** @var \WeakMap<File|Directory, TreePath> */
        $this->loaded = new \WeakMap;
    }

    public static function mount(
        Path $path,
        CaseSensitivity $case = CaseSensitivity::sensitive,
        ?IO $io = null,
    ): Attempt {
        return Filesystem::mount($path, $io)->map(static fn($implementation) => new self(
            $implementation,
            $case,
        ));
    }

    public static function inMemory(): self
    {
        return new self(
            InMemory::emulateFilesystem(),
            CaseSensitivity::sensitive,
        );
    }

    public static function logger(
        self $adapter,
        LoggerInterface $logger,
    ): self {
        return new self(
            Logger::psr($adapter->adapter, $logger),
            $adapter->case,
        );
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function add(File|Directory $file): Attempt
    {
        return $this->write(TreePath::root(), $file);
    }

    /**
     * @return Maybe<File|Directory>
     */
    public function get(Name $file): Maybe
    {
        return $this->read(
            TreePath::root(),
            Name_\Unknown::of($file),
        );
    }

    public function contains(Name $file): bool
    {
        return $this->adapter->exists(TreePath::of($file))->match(
            static fn($exists) => $exists,
            static fn() => false,
        );
    }

    /**
     * @return Attempt<SideEffect>
     */
    public function remove(Name $file): Attempt
    {
        return $this->adapter->remove(TreePath::root(), $file);
    }

    public function root(): Directory
    {
        $root = TreePath::root();

        return Directory::named(
            'root',
            $this
                ->adapter
                ->list($root)
                ->map(fn($name) => $this->read($root, $name))
                ->flatMap(static fn($read) => $read->toSequence()),
        );
    }

    /**
     * @return Maybe<File|Directory>
     */
    private function read(
        TreePath $path,
        Name_\File|Name_\Directory|Name_\Unknown $name,
    ): Maybe {
        $fullPath = TreePath::of($name->unwrap())->under($path);

        return $this
            ->adapter
            ->read($path, $name)
            ->maybe()
            ->map(fn($file) => match (true) {
                $file instanceof File => $file,
                default => Directory::of(
                    $file->unwrap(),
                    $this
                        ->adapter
                        ->list(TreePath::directory($name->unwrap())->under($path))
                        ->map(fn($file) => $this->read(
                            $fullPath,
                            $file,
                        ))
                        ->flatMap(static fn($read) => $read->toSequence()),
                ),
            })
            ->map(function($file) use ($fullPath) {
                $this->loaded[$file] = $fullPath;

                return $file;
            });
    }

    /**
     * @return Attempt<SideEffect>
     */
    private function write(TreePath $path, File|Directory $file): Attempt
    {
        $fullPath = TreePath::of($file)->under($path);

        /** @psalm-suppress PossiblyNullReference */
        if (
            $this->loaded->offsetExists($file) &&
            $this->loaded[$file]->equals($fullPath)
        ) {
            // no need to persist untouched file where it was loaded from
            return Attempt::result(SideEffect::identity);
        }

        $this->loaded[$file] = $fullPath;

        if ($file instanceof Directory) {
            /** @var Set<Name> */
            $names = Set::of();

            return $this
                ->adapter
                ->createDirectory($path, $file->name())
                ->flatMap(
                    fn() => $file
                        ->all()
                        ->sink($names)
                        ->attempt(
                            fn($persisted, $file) => $this
                                ->write($fullPath, $file)
                                ->map(static fn() => ($persisted)($file->name())),
                        ),
                )
                ->flatMap(
                    fn($persisted) => $file
                        ->removed()
                        ->exclude(fn($file): bool => $this->case->contains(
                            $file,
                            $persisted,
                        ))
                        ->unsorted()
                        ->sink(SideEffect::identity)
                        ->attempt(fn($_, $file) => $this->adapter->remove($fullPath, $file)),
                );
        }

        return $this
            ->adapter
            ->remove($path, $file->name())
            ->flatMap(fn() => $this->adapter->write(
                $path,
                $file,
            ));
    }
}
