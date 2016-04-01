# Filesystem

Filesystem abstraction layer, the goal is to provide a model where you design how you put your files into directories without worrying where it will be persisted.

The whole model is structures around files, directories, streams and adapters. [`File`](File.php), [`Directory`](Directory.php) and [`Stream`](Stream/Stream.php) are immutable objects.

Example:
```php
use Innmind\Filesystem\{
    File,
    Directory,
    Stream\Stream,
    Adapter\FilesystemAdapter
};

$directory = (new Directory('uploads'))
    ->add(
        new File(
            $_FILES['my_upload']['name'],
            new Stream(fopen($_FILES['my_upload']['tmp_name'], 'r'))
        )
    );
$adapter = new Filesystem('/var/www/web');
$adapter->add($directory);
```

This example show you how you can create a new directory `uploads` in the folder `/var/www/web` of your filesystem and create the uploaded file into it.

**Note**: For performance reasons the filesystem adapter only persist to disk the files that have changed (achievable via the immutable nature of file objects).

All adapters implements [`AdapterInterface`](AdapterInterface.php), so you can easily replace them; especially for unit tests, that's why the library comes with a [`MemoryAdapter`] that only keeps the files into memory so you won't mess up your file system.

By default when you call `add` or `remove` on an adapter the changes are directly applied, but you can change this behaviour by wrapping your adapter in another one implementing [`LazyAdapterInterface`](LazyAdapterInterface.php) (such as [`LazyAdapter`](Adapter/LazyAdapter.php)).

Example:
```php
use Innmind\Filesystem\Adapter\LazyAdapter;

$directory = /*....*/ ;
$adapter = new LazyAdapter(new FilesystemAdapter('/var/www/web'));
$adapter->add($directory); // nothing is written to disk
$adapter->persist(); // every new files are persisted, and removals occur at this time as well
```
