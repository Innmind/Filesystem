<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Directory,
    Name,
    CaseSensitivity,
};
use Innmind\Immutable\{
    Attempt,
    Maybe,
    Set,
    SideEffect,
};

/**
 * This should replace the Adapter interface in order to only expose a final
 * class in the end.
 */
final class Bridge implements Adapter
{
    /** @var \WeakMap<File|Directory, TreePath> */
    private \WeakMap $loaded;

    private function __construct(
        private Filesystem&Implementation $adapter,
        private CaseSensitivity $case,
    ) {
        /** @var \WeakMap<File|Directory, TreePath> */
        $this->loaded = new \WeakMap;
    }

    public static function of(
        Filesystem&Implementation $adapter,
        CaseSensitivity $case,
    ): self {
        return new self($adapter, $case);
    }

    #[\Override]
    public function add(File|Directory $file): Attempt
    {
        return $this->write(TreePath::root(), $file);
    }

    #[\Override]
    public function get(Name $file): Maybe
    {
        return $this->read(TreePath::of($file));
    }

    #[\Override]
    public function contains(Name $file): bool
    {
        return $this->adapter->exists(TreePath::of($file))->match(
            static fn($exists) => $exists,
            static fn() => false,
        );
    }

    #[\Override]
    public function remove(Name $file): Attempt
    {
        return $this->adapter->remove(TreePath::of($file));
    }

    #[\Override]
    public function root(): Directory
    {
        return Directory::named(
            'root',
            $this
                ->adapter
                ->list(TreePath::root())
                ->map($this->read(...))
                ->flatMap(static fn($read) => $read->toSequence()),
        );
    }

    /**
     * @return Maybe<File|Directory>
     */
    private function read(TreePath $path): Maybe
    {
        return $this
            ->adapter
            ->read($path)
            ->maybe()
            ->map(fn($file) => match (true) {
                $file instanceof File => $file,
                default => Directory::of(
                    $file,
                    $this
                        ->adapter
                        ->list($path)
                        ->map(static fn($found) => $found->under($path))
                        ->map($this->read(...))
                        ->flatMap(static fn($read) => $read->toSequence()),
                ),
            })
            ->map(function($file) use ($path) {
                $this->loaded[$file] = TreePath::of($file)->under($path);

                return $file;
            });
    }

    /**
     * @return Attempt<SideEffect>
     */
    private function write(TreePath $path, File|Directory $file): Attempt
    {
        $path = TreePath::of($file)->under($path);

        /** @psalm-suppress PossiblyNullReference */
        if (
            $this->loaded->offsetExists($file) &&
            $this->loaded[$file]->equals($path)
        ) {
            // no need to persist untouched file where it was loaded from
            return Attempt::result(SideEffect::identity);
        }

        $this->loaded[$file] = $path;

        if ($file instanceof Directory) {
            /** @var Set<Name> */
            $names = Set::of();

            return $this
                ->adapter
                ->createDirectory($path)
                ->flatMap(
                    fn() => $file
                        ->all()
                        ->sink($names)
                        ->attempt(
                            fn($persisted, $file) => $this
                                ->write($path, $file)
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
                        ->map(TreePath::of(...))
                        ->map(static fn($file) => $file->under($path))
                        ->sink(SideEffect::identity)
                        ->attempt(fn($_, $path) => $this->adapter->remove($path)),
                );
        }

        return $this
            ->adapter
            ->remove($path)
            ->flatMap(fn() => $this->adapter->write(
                $path,
                $file->content(),
            ));
    }
}
