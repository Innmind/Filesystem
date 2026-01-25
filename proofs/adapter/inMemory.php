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

return static function() {
    yield properties(
        'InMemory properties emulating filesystem',
        PAdapter::properties(),
        Set::call(Adapter::inMemory(...)),
    );

    foreach (PAdapter::alwaysApplicable() as $property) {
        yield property(
            $property,
            Set::call(Adapter::inMemory(...)),
        )->named('InMemory emulating filesystem');
    }

    yield test(
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
