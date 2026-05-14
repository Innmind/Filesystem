<?php
declare(strict_types = 1);

use Innmind\Filesystem\{
    Adapter,
    Directory,
    File,
    File\Content,
    CaseSensitivity,
    Recover,
};
use Innmind\IO\{
    IO,
    Simulation\Disk,
};
use Innmind\Url\Path;
use Properties\Innmind\Filesystem\Adapter as PAdapter;
use Innmind\BlackBox\Set;
use Symfony\Component\Filesystem\Filesystem as FS;

return static function($prove) {
    $path = \rtrim(\sys_get_temp_dir(), '/').'/innmind/filesystem/';

    yield $prove->properties(
        'Filesystem properties',
        PAdapter::properties(),
        Set::of(static function() use ($path) {
            (new FS)->remove($path);

            return Adapter::mount(
                Path::of($path),
                match (\PHP_OS) {
                    'Darwin' => CaseSensitivity::insensitive,
                    default => CaseSensitivity::sensitive,
                },
            )
                ->recover(Recover::mount(...))
                ->unwrap();
        }),
    );

    foreach (PAdapter::alwaysApplicable() as $property) {
        yield $prove
            ->property(
                $property,
                Set::of(static function() use ($path) {
                    (new FS)->remove($path);

                    return Adapter::mount(
                        Path::of($path),
                        match (\PHP_OS) {
                            'Darwin' => CaseSensitivity::insensitive,
                            default => CaseSensitivity::sensitive,
                        },
                    )
                        ->recover(Recover::mount(...))
                        ->unwrap();
                }),
            )
            ->named('Filesystem');
    }

    yield $prove->test(
        'Regression adding file in directory due to case sensitivity',
        static function($assert) use ($path) {
            $property = new PAdapter\AddRemoveAddModificationsStillAddTheFile(
                Directory::named('0')
                    ->add($file = File::named('L', Content::none()))
                    ->remove($file->name()),
                File::named('l', Content::none()),
            );

            (new FS)->remove($path);
            $adapter = Adapter::mount(
                Path::of($path),
                match (\PHP_OS) {
                    'Darwin' => CaseSensitivity::insensitive,
                    default => CaseSensitivity::sensitive,
                },
            )
                ->recover(Recover::mount(...))
                ->unwrap();

            $property->ensureHeldBy($assert, $adapter);

            (new FS)->remove($path);
        },
    );

    foreach (CaseSensitivity::cases() as $case) {
        yield $prove->properties(
            'Filesystem properties on simulated disk',
            PAdapter::properties(),
            Set::of(static fn() => Adapter::mount(
                Path::of('/'),
                $case,
                IO::simulation(
                    IO::fromAmbientAuthority(),
                    Disk::new(),
                ),
            )->unwrap()),
        );

        foreach (PAdapter::alwaysApplicable() as $property) {
            yield $prove
                ->property(
                    $property,
                    Set::of(static fn() => Adapter::mount(
                        Path::of('/'),
                        $case,
                        IO::simulation(
                            IO::fromAmbientAuthority(),
                            Disk::new(),
                        ),
                    )->unwrap()),
                )
                ->named('Filesystem on simulated disk');
        }
    }
};
