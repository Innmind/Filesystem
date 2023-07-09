<?php
declare(strict_types=1);

use Innmind\Filesystem\Stream\LazyStream;
use Innmind\Url\Path;
use Properties\Innmind\Stream\Readable;
use Fixtures\Innmind\Stream\Readable as FReadable;

return static function() {
    yield proof(
        'LazyStream properties',
        given(
            Readable::properties(),
            FReadable::any()
        ),
        function($assert, $properties, $content) {
            $path = \tempnam(\sys_get_temp_dir(), 'lazy_stream');
            $stream = new LazyStream(Path::of($path));
            \file_put_contents($path, $content->toString());

            $properties->ensureHeldBy($assert, $stream);
        },
    );
};
