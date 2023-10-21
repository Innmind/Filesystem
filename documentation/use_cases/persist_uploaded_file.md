# Persist an uploaded file

```php
use Innmind\Filesystem\{
    File,
    File\Content,
    Directory,
    Adapter\Filesystem,
};
use Innmind\IO\IO;
use Innmind\Stream\Streams;
use Innmind\Url\Path;

$streams = Streams::fromAmbienAuthority();
$io = IO::of($streams->watch()->waitForever(...))

$filesystem = Filesystem::mount(Path::of('/var/data/'));
$filesystem->add(
    Directory::named('uploads')->add(
        File::named(
            $_FILES['my_upload']['name'],
            Content::atPath(
                $streams->readable(),
                $io->readable(),
                Path::of($_FILES['my_upload']['tmp_name']),
            ),
        ),
    ),
);
```
