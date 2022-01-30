# Persist an uploaded file

```php
use Innmind\Filesystem\{
    File\File,
    File\Content,
    Directory\Directory,
    Adapter\Filesystem,
};
use Innmind\Url\Path;

$filesystem = Filesystem::mount(Path::of('/var/data/'));
$filesystem->add(
    Directory::named('uploads')->add(
        File::named(
            $_FILES['my_upload']['name'],
            Content\AtPath::of(Path::of($_FILES['my_upload']['tmp_name'])),
        ),
    )
);
```
