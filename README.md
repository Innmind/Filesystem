# Filesystem

[![Build Status](https://github.com/Innmind/Filesystem/workflows/CI/badge.svg?branch=master)](https://github.com/Innmind/Filesystem/actions?query=workflow%3ACI)
[![codecov](https://codecov.io/gh/Innmind/Filesystem/branch/develop/graph/badge.svg)](https://codecov.io/gh/Innmind/Filesystem)
[![Type Coverage](https://shepherd.dev/github/Innmind/Filesystem/coverage.svg)](https://shepherd.dev/github/Innmind/Filesystem)

Filesystem abstraction layer, the goal is to provide a model where you design how you put your files into directories without worrying where it will be persisted.

[Documentation](https://innmind.github.com/Filesystem/)

## Installation

```sh
composer install innmind/filesystem
```

## Usage

The whole model is structured around files, directories, contents and adapters. [`File`](src/File.php), [`Directory`](src/Directory.php) and [`Content`](src/File/Content.php) are immutable objects.

Example:
```php
use Innmind\Filesystem\{
    File\File,
    File\Content,
    Directory\Directory,
    Adapter\Filesystem,
};
use Innmind\Url\Path;

$directory = Directory::named('uploads')->add(
    File::named(
        $_FILES['my_upload']['name'],
        Content\AtPath::of(Path::of($_FILES['my_upload']['tmp_name'])),
    ),
);
$adapter = Filesystem::mount(Path::of('/var/www/web/'));
$adapter->add($directory);
```

This example show you how you can create a new directory `uploads` in the folder `/var/www/web/` of your filesystem and create the uploaded file into it.

**Note**: For performance reasons the filesystem adapter only persist to disk the files that have changed (achievable via the immutable nature of file objects).

All adapters implements [`Adapter`](src/Adapter.php), so you can easily replace them; especially for unit tests, that's why the library comes with an [`InMemory`](src/Adapter/InMemory.php) adapter that only keeps the files into memory so you won't mess up your file system.
