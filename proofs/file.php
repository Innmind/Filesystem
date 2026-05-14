<?php
declare(strict_types = 1);

use Fixtures\Innmind\Filesystem\File;

return static function($prove) {
    yield $prove
        ->proof('File::mapContent()')
        ->given(
            File::any(),
            File::any(),
        )
        ->test(static function($assert, $file, $replacement) {
            $new = $file->mapContent(static function($content) use ($assert, $file, $replacement) {
                $assert->same($file->content(), $content);

                return $replacement->content();
            });

            $assert
                ->expected($file)
                ->not()
                ->same($new);
            $assert->same($file->name(), $new->name());
            $assert->same($file->mediaType(), $new->mediaType());
            $assert->same($replacement->content(), $new->content());
        });
};
