<?php
declare(strict_types = 1);

use Innmind\Filesystem\Adapter;
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
};
