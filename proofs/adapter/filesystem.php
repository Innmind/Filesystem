<?php
declare(strict_types = 1);

use Innmind\Filesystem\{
    Adapter\Filesystem,
    Directory,
    File,
    File\Content,
    CaseSensitivity,
};
use Innmind\Url\Path;
use Properties\Innmind\Filesystem\Adapter;
use Innmind\BlackBox\Set;
use Symfony\Component\Filesystem\Filesystem as FS;

return static function() {
    $path = \rtrim(\sys_get_temp_dir(), '/').'/innmind/filesystem/';

    yield properties(
        'Filesystem properties',
        Adapter::properties(),
        Set::call(static function() use ($path) {
            (new FS)->remove($path);

            return Filesystem::mount(
                Path::of($path),
                match (\PHP_OS) {
                    'Darwin' => CaseSensitivity::insensitive,
                    default => CaseSensitivity::sensitive,
                },
            )->unwrap();
        }),
    );

    foreach (Adapter::alwaysApplicable() as $property) {
        yield property(
            $property,
            Set::call(static function() use ($path) {
                (new FS)->remove($path);

                return Filesystem::mount(
                    Path::of($path),
                    match (\PHP_OS) {
                        'Darwin' => CaseSensitivity::insensitive,
                        default => CaseSensitivity::sensitive,
                    },
                )->unwrap();
            }),
        )->named('Filesystem');
    }

    yield test(
        'Regression adding file in directory due to case sensitivity',
        static function($assert) use ($path) {
            $property = new Adapter\AddRemoveAddModificationsStillAddTheFile(
                Directory::named('0')
                    ->add($file = File::named('L', Content::none()))
                    ->remove($file->name()),
                File::named('l', Content::none()),
            );

            (new FS)->remove($path);
            $adapter = Filesystem::mount(
                Path::of($path),
                match (\PHP_OS) {
                    'Darwin' => CaseSensitivity::insensitive,
                    default => CaseSensitivity::sensitive,
                },
            )->unwrap();

            $property->ensureHeldBy($assert, $adapter);

            (new FS)->remove($path);
        },
    );
};
