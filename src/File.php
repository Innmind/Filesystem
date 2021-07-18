<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Filesystem\File\Content;
use Innmind\MediaType\MediaType;

interface File
{
    public function name(): Name;
    public function content(): Content;
    public function mediaType(): MediaType;
}
