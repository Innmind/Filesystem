<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

interface File
{
    public function name(): Name;
    public function content(): Stream;
    public function mediaType(): MediaType;
}
