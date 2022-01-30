# Delete a file

## At the root of the adapter

```php
use Innmind\Filesystem\{
    Adapter\Filesystem,
    Name,
};
use Innmind\Url\Path;

$filesystem = Filesystem::mount(Path::of('/var/data/'));
$filesystem->remove(new Name('some file'));
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

$filesystem = Filesystem::mount(Path::of('/var/data/'));
$filesystem
    ->get(new Name('some directory'))
    ->filter(static fn($file) => $file instanceof Directory) // make sure we are dealing with a directory
    ->map(static fn($directory) => $directory->remove(new Name('some file')))
    ->match(
        static fn($directory) => $filesystem->add($directory), // the file will be removed here only
        static fn() => null,
    );
```

This example will remove the file `some file` inside the directory `some directory`. If the directory doesn't exist or `some directory` is not a directory then nothing will happen.
