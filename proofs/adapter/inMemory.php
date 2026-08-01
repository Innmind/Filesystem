<?php
declare(strict_types = 1);

use Innmind\Filesystem\{
    Adapter,
    Name,
    File,
    File\Content,
};
use Properties\Innmind\Filesystem\Adapter as PAdapter;
use Innmind\BlackBox\Set;

return static function($prove) {
    yield $prove->properties(
        'InMemory properties emulating filesystem',
        PAdapter::properties(),
        Set::of(Adapter::inMemory(...)),
    );

    foreach (PAdapter::alwaysApplicable() as $property) {
        yield $prove
            ->property(
                $property,
                Set::of(Adapter::inMemory(...)),
            )
            ->named('InMemory emulating filesystem');
    }

    yield $prove->test(
        'Adding a file in a directory should not remove other files starting with the same name',
        static function($assert) {
            $adapter = Adapter::inMemory();
            $property = new PAdapter\AddDirectoryFromAnotherAdapterWithFileAdded(
                Name::of('0'),
                File::named(
                    '+1',
                    Content::none(),
                ),
                File::named(
                    '+',
                    Content::none(),
                ),
            );

            $property->ensureHeldBy($assert, $adapter);
        },
    );
};
