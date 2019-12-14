<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Stream\Readable;
use Innmind\MediaType\MediaType;

interface File
{
    public function name(): Name;
    public function content(): Readable;
    public function mediaType(): MediaType;
}
