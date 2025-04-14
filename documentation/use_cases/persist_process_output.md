# Persist a process output

This example uses the [`innmind/operating-system`](https://packagist.org/packages/innmind/operating-system) package.

```php
use Innmind\Filesystem\{
    File,
    File\Content,
    Adapter\Filesystem,
};
use Innmind\OperatingSystem\Factory;
use Innmind\Server\Control\Server\Command;
use Innmind\Url\Path;

$os = Factory::build();
$filesystem = Filesystem::mount(Path::of('/var/data/'));
$fileContent = Content::ofChunks(
    $os
        ->control()
        ->processes()
        ->execute(
            Command::of('gzip')
                ->withShortOption('d')
                ->withArgument('some-archive.txt.tar.gz'),
        )
        ->output()
        ->chunks()
        ->map(static function($pair) {
            [$chunk, $type] = $pair;

            return $chunk;
        }),
);
$filesystem
    ->add(
        File::named(
            'some-archive.txt',
            $fileContent,
        ),
    )
    ->unwrap();
```
