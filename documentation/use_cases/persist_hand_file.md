# Persist a file created by hand

## Create an empty file

```php
use Innmind\Filesystem\{
    Adapter\filesystem,
    File\File,
};
use Innmind\Url\Path;

$filesystem = Filesystem::mount(Path::of('/var/data/'));
$filesystem->add(File::named('some name'));
```

This is equivalent of running the cli command `touch '/var/data/some name'`.

## Create a file with some content

```php
use Innmind\Filesystem\{
    Adapter\filesystem,
    File\File,
    File\Content\Lines,
    File\Content\Line,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Str,
};

$filesystem = Filesystem::mount(Path::of('/var/data/'));
$filesystem->add(File::named(
    'some name',
    Lines::of(Sequence::of(
        Line::of(Str::of('first line')),
        Line::of(Str::of('second line')),
        Line::of(Str::of('etc...')),
    ))
));
```

When the file is persisted the _end of line_ character will automatically added for you.

## Create a file inside a directory

```php
use Innmind\Filesystem\{
    Adapter\filesystem,
    File\File,
    Directory\Directory,
};
use Innmind\Url\Path;

$filesystem = Filesystem::mount(Path::of('/var/data/'));
$filesystem->add(
    Directory::named('whatever')->add(File::named('some name')),
);
```

This is equivalent of running the cli command `touch '/var/data/whatever/some name'`.
