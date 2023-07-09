<?php
declare(strict_types=1);

use Innmind\Filesystem\{
    Adapter\Filesystem,
    Directory\Directory,
    File\File,
    File\Content\None,
    CaseSensitivity,
};
use Innmind\Url\Path;
use Properties\Innmind\Filesystem\Adapter;
use Innmind\BlackBox\Set;
use Symfony\Component\Filesystem\Filesystem as FS;

return static function() {
    yield proof(
        'Filesystem properties',
        given(Adapter::properties()),
        function($assert, $properties) {
            $path = \sys_get_temp_dir().'/innmind/filesystem/';
            (new FS)->remove($path);

            $properties->ensureHeldBy($assert, Filesystem::mount(Path::of($path)));

            (new FS)->remove($path);
        },
    );

    foreach (Adapter::list() as $property) {
        yield proof(
            'Filesystem property',
            given($property),
            function($assert, $property) {
                $path = \sys_get_temp_dir().'/innmind/filesystem/';
                (new FS)->remove($path);
                $filesystem = Filesystem::mount(Path::of($path));

                if ($property->applicableTo($filesystem)) {
                    $property->ensureHeldBy($assert, $filesystem);
                }

                (new FS)->remove($path);
            },
        );
    }

    yield test(
        'Regression adding file in directory due to case sensitivity',
        function($assert) {
            $property = new Adapter\AddRemoveAddModificationsStillAddTheFile(
                Directory::named('0')
                    ->add($file = File::named('L', None::of()))
                    ->remove($file->name()),
                File::named('l', None::of()),
            );

            $path = \sys_get_temp_dir().'/innmind/filesystem/';
            (new FS)->remove($path);
            $adapter = Filesystem::mount(Path::of($path))->withCaseSensitivity(match (\PHP_OS) {
                'Darwin' => CaseSensitivity::insensitive,
                default => CaseSensitivity::sensitive,
            });

            $property->ensureHeldBy($assert, $adapter);

            (new FS)->remove($path);
        }
    );
};
