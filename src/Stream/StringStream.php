<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Stream;

class StringStream extends Stream
{
    public function __construct(string $content)
    {
        $resource = fopen('php://temp', 'r+');
        fwrite($resource, $content);

        parent::__construct($resource);
    }
}
