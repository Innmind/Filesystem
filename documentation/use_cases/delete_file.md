# Delete a file

## At the root of the adapter

```php
use Innmind\Filesystem\{
    Adapter\Filesystem,
    Name,
};
use Innmind\Url\Path;

$filesystem = Filesystem::mount(Path::of('/var/data/'));
$filesystem->remove(Name::of('some file'));
```

If the file doesn't exist it will do nothing and if the name corresponds to a directory it will remove the whole directory.

## Inside a directory

```php
use Innmind\Filesystem\{
    Adapter\Filesystem,
    Name,
    Directory,
};
use Innmind\Url\Path;
use Innmind\Immutable\Predicate\Instance;

$filesystem = Filesystem::mount(Path::of('/var/data/'));
$filesystem
    ->get(Name::of('some directory'))
    ->keep(Instance::of(Directory::class)) //(1)
    ->map(static fn($directory) => $directory->remove(Name::of('some file')))
    ->match(
        static fn($directory) => $filesystem->add($directory), //(2)
        static fn() => null,
    );
```

1. make sure we are dealing with a directory
2. the file will be removed here only

This example will remove the file `some file` inside the directory `some directory`. If the directory doesn't exist or `some directory` is not a directory then nothing will happen.
