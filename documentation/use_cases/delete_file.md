# Delete a file

## At the root of the adapter

```php
use Innmind\Filesystem\{
    Adapter,
    Name,
};
use Innmind\Url\Path;

$filesystem = Adapter::mount(Path::of('/var/data/'))->unwrap();
$_ = $filesystem
    ->remove(Name::of('some file'))
    ->unwrap();
```

If the file doesn't exist it will do nothing and if the name corresponds to a directory it will remove the whole directory.

## Inside a directory

```php
use Innmind\Filesystem\{
    Adapter,
    Name,
    Directory,
};
use Innmind\Url\Path;
use Innmind\Immutable\Predicate\Instance;

$filesystem = Adapter::mount(Path::of('/var/data/'))->unwrap();
$filesystem
    ->get(Name::of('some directory'))
    ->keep(Instance::of(Directory::class)) //(1)
    ->map(static fn($directory) => $directory->remove(Name::of('some file')))
    ->match(
        static fn($directory) => $filesystem->add($directory)->unwrap(), //(2)
        static fn() => null,
    );
```

1. make sure we are dealing with a directory
2. the file will be removed here only

This example will remove the file `some file` inside the directory `some directory`. If the directory doesn't exist or `some directory` is not a directory then nothing will happen.
