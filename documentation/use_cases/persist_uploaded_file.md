# Persist an uploaded file

```php
use Innmind\Filesystem\{
    File,
    File\Content,
    Directory,
    Adapter\Filesystem,
    Name,
};
use Innmind\Url\Path;
use Innmind\Immutable\Predicate\Instance;

$tmp = Filesystem::mount(Path::of(\dirname($_FILES['my_upload']['tmp_name'])));
$filesystem = Filesystem::mount(Path::of('/var/data/'));

$tmp
    ->get(Name::of(\basename($_FILES['my_upload']['tmp_name'])))
    ->keep(Instance::of(File::class))
    ->map(static fn($file) => $file->rename(
        Name::of($_FILES['my_upload']['name']),
    ))
    ->map(Directory::named('uploads')->add(...))
    ->match(
        $filesystem->add(...),
        static fn() => null, // the file doesn't exist somehow
    );
```
