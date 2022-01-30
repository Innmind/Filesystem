# Read a file from the filesystem

## At the root of the adapter

```php
use Innmind\Filesystem\{
    Adapter\filesystem,
    File,
    Name,
};
use Innmind\Url\Path;

$print = static function(File $file): void {
    $file
        ->content()
        ->foreach(function($line) {
            echo $line->toString()."\n";
        });
};

$filesystem = Filesystem::mount(Path::of('/var/data/'));
$filesystem
    ->get(new Name('some file'))
    ->match(
        static fn(File $file) => $print($file),
        static fn() => null, // the file doesn't exist
    );
```

This example will print each line to the screen, or nothing if the file doesn't exist.

## Inside a directory

```php
use Innmind\Filesystem\{
    Adapter\filesystem,
    File,
    Name,
    Directory,
};
use Innmind\Url\Path;

$print = static function(File $file): void {
    $file
        ->content()
        ->foreach(function($line) {
            echo $line->toString()."\n";
        });
};

$filesystem = Filesystem::mount(Path::of('/var/data/'));
$filesystem
    ->get(new Name('some directory'))
    ->filter(static fn($file) => $file instanceof Directory) // make sure "some directory" is not a file
    ->flatMap(static fn($directory) => $directory->get(new Name('some file')))
    ->match(
        static fn(File $file) => $print($file),
        static fn() => null, // the file doesn't exist
    );
```

This example will print each line to the screen, or nothing if the file doesn't exist or if `some directory` is a file and not a directory or `some directory` doesn't exist.
