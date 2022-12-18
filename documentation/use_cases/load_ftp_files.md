# Load FTP files

Say you have a client that push csv files in an unstructured manner inside a FTP directory. You can load all files like so:

```php
use Innmind\Filesystem\{
    Adapter\Filesystem,
    File,
    Directory,
};
use Innmind\Url\Path;
use Innmind\Immutable\Sequence;

/**
 * @return Sequence<File>
 */
function flatten(File $file): Sequence
{
    if ($file instanceof Directory) {
        // bring all the files from sub directories to the same level
        return $file->files()->flatMap(flatten(...));
    }

    return Sequence::of($file);
}

Filesystem::mount(Path::of('/path/to/ftp/directory/'))
    ->root()
    ->files()
    ->flatMap(flatten(...))
    ->foreach(static fn(File $csv) => doYourStuff($csv));
```

The advantage of this approach is that you can easily test the whole program behaviour by replacing the `Filesystem` adapter by a `InMemory` one.
