# Working with case insensitive filesystems

By default this package assumes you're working with a case sensitive filesystem, meaning you can have 2 files `a` and `A` inside a same directory. However this is not possible on a case insensitive filesystem (such as APFS on macOS), it will create only one of both.

If you're dealing with a case insensitive filesystem then you need to specify it on the adapter like this:

```php
use Innmind\Filesystem\{
    Adapter\Filesystem,
    CaseSensitivity,
};
use Innmind\Url\Path;

$adapter = Filesystem::mount(Path::of('somewhere/'))
    ->withCaseSensitivity(CaseSensitivity::insensitive);
$adapter instanceof Filesystem; // true, use $adapter as usual
```

**Advice**: If you persist user provided files on a filesystem you should use normalized names (like UUIDs) and keep the original names in a database to avoid collisions.
