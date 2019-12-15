# Filesystem

| `develop` |
|-----------|
| [![codecov](https://codecov.io/gh/Innmind/Filesystem/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/Filesystem) |
| [![Build Status](https://github.com/Innmind/Filesystem/workflows/CI/badge.svg)](https://github.com/Innmind/Filesystem/actions?query=workflow%3ACI) |

Filesystem abstraction layer, the goal is to provide a model where you design how you put your files into directories without worrying where it will be persisted.

## Installation

```sh
composer install innmind/filesystem
```

## Usage

The whole model is structures around files, directories, streams and adapters. [`File`](src/File.php), [`Directory`](src/Directory.php) and [`Stream`](https://github.com/Innmind/Stream/blob/develop/src/Readable.php) are immutable objects.

Example:
```php
use Innmind\Filesystem\{
    File\File,
    Directory\Directory,
    Name,
    Adapter\Filesystem,
};
use Innmind\Url\Path;
use Innmind\Stream\Readable\Stream;

$directory = Directory::named('uploads')->add(
    File::named(
        $_FILES['my_upload']['name'],
        Stream::open($_FILES['my_upload']['tmp_name'])
    )
);
$adapter = new Filesystem(Path::of('/var/www/web'));
$adapter->add($directory);
```

This example show you how you can create a new directory `uploads` in the folder `/var/www/web` of your filesystem and create the uploaded file into it.

**Note**: For performance reasons the filesystem adapter only persist to disk the files that have changed (achievable via the immutable nature of file objects).

All adapters implements [`Adapter`](src/Adapter.php), so you can easily replace them; especially for unit tests, that's why the library comes with an [`InMemory`](src/Adapter/InMemory.php) adapter that only keeps the files into memory so you won't mess up your file system.

By default when you call `add` or `remove` on an adapter the changes are directly applied, but you can change this behaviour by wrapping your adapter in another one implementing [`LazyAdapter`](src/LazyAdapter.php) (such as [`Lazy`](src/Adapter/Lazy.php)).

Example:
```php
use Innmind\Filesystem\Adapter\LazyAdapter;

$directory = /*....*/ ;
$adapter = new Lazy(new Filesystem(Path::of('/var/www/web')));
$adapter->add($directory); // nothing is written to disk
$adapter->persist(); // every new files are persisted, and removals occur at this time as well
```
