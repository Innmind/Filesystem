<?php
declare(strict_types = 1);

use Innmind\Filesystem\Adapter\InMemory;
use Properties\Innmind\Filesystem\Adapter;
use Innmind\BlackBox\Set;

return static function() {
    yield properties(
        'InMemory properties',
        Adapter::properties(),
        Set::call(InMemory::new(...)),
    );
    yield properties(
        'InMemory properties emulating filesystem',
        Adapter::properties(),
        Set::call(InMemory::emulateFilesystem(...)),
    );

    foreach (Adapter::alwaysApplicable() as $property) {
        yield property(
            $property,
            Set::call(InMemory::new(...)),
        )->named('InMemory');
        yield property(
            $property,
            Set::call(InMemory::emulateFilesystem(...)),
        )->named('InMemory emulating filesystem');
    }
};
