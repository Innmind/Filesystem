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
    File,
    File\Content,
    Directory,
    Adapter,
};
use Innmind\Url\Path;

$directory = Directory::named('uploads')->add(
    File::named(
        $_FILES['my_upload']['name'],
        Content::ofString(\file_get_contents($_FILES['my_upload']['tmp_name'])),
    ),
);
$adapter = Adapter::mount(Path::of('/var/www/web/'))->unwrap();
$_ = $adapter
    ->add($directory)
    ->unwrap();
```

This example show you how you can create a new directory `uploads` in the folder `/var/www/web/` of your filesystem and create the uploaded file into it.

> [!NOTE]
> For performance reasons the filesystem adapter only persist to disk the files that have changed (achievable via the immutable nature of file objects).
