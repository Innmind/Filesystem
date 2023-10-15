<?php
declare(strict_types=1);

use Innmind\Filesystem\{
    Adapter\Filesystem,
    Directory\Directory,
    File,
    File\Content,
    CaseSensitivity,
};
use Innmind\Url\Path;
use Properties\Innmind\Filesystem\Adapter;
use Innmind\BlackBox\Set;
use Symfony\Component\Filesystem\Filesystem as FS;

return static function() {
    yield properties(
        'Filesystem properties',
        Adapter::properties(),
        Set\Call::of(function() {
            $path = \sys_get_temp_dir().'/innmind/filesystem/';
            (new FS)->remove($path);

            return Filesystem::mount(Path::of($path))->withCaseSensitivity(match (\PHP_OS) {
                'Darwin' => CaseSensitivity::insensitive,
                default => CaseSensitivity::sensitive,
            });
        }),
    );

    foreach (Adapter::alwaysApplicable() as $property) {
        yield property(
            $property,
            Set\Call::of(function() {
                $path = \sys_get_temp_dir().'/innmind/filesystem/';
                (new FS)->remove($path);

                return Filesystem::mount(Path::of($path))->withCaseSensitivity(match (\PHP_OS) {
                    'Darwin' => CaseSensitivity::insensitive,
                    default => CaseSensitivity::sensitive,
                });
            }),
        )->named('Filesystem');
    }

    yield test(
        'Regression adding file in directory due to case sensitivity',
        function($assert) {
            $property = new Adapter\AddRemoveAddModificationsStillAddTheFile(
                Directory::named('0')
                    ->add($file = File::named('L', Content::none()))
                    ->remove($file->name()),
                File::named('l', Content::none()),
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
