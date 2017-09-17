<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

interface File
{
    /**
     * Return the name of the file
     *
     * @return NameInterface
     */
    public function name(): Name;

    /**
     * Return the content stream of the file
     *
     * @return StreamInterface
     */
    public function content(): Stream;

    public function mediaType(): MediaType;
}
