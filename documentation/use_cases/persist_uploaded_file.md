# Persist an uploaded file

```php
use Innmind\Filesystem\{
    File,
    File\Content,
    Directory,
    Adapter,
    Name,
};
use Innmind\Url\Path;
use Innmind\Immutable\Predicate\Instance;

$tmp = Adapter::mount(Path::of(\dirname($_FILES['my_upload']['tmp_name'])))->unwrap();
$filesystem = Adapter::mount(Path::of('/var/data/'))->unwrap();

$_ = $tmp
    ->get(Name::of(\basename($_FILES['my_upload']['tmp_name'])))
    ->keep(Instance::of(File::class))
    ->map(static fn($file) => $file->rename(
        Name::of($_FILES['my_upload']['name']),
    ))
    ->map(Directory::named('uploads')->add(...))
    ->match(
        $filesystem->add(...),
        static fn() => null, // the file doesn't exist somehow
    )
    ?->unwrap();
```
