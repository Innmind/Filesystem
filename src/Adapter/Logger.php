<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter\Name as Name_,
    File,
    Name,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Attempt,
};
use Psr\Log\LoggerInterface;

/**
 * @internal
 */
final class Logger implements Implementation
{
    private function __construct(
        private Implementation $implementation,
        private LoggerInterface $logger,
    ) {
    }

    public static function psr(Implementation $implementation, LoggerInterface $logger): self
    {
        return new self($implementation, $logger);
    }

    #[\Override]
    public function exists(TreePath $path): Attempt
    {
        return $this
            ->implementation
            ->exists($path)
            ->map(function($exists) use ($path) {
                $this->logger->debug('Cheking if filesystem contains {file}', [
                    'file' => self::path($path),
                    'contains' => $exists,
                ]);

                return $exists;
            });
    }

    #[\Override]
    public function read(
        TreePath $parent,
        Name_\File|Name_\Directory|Name_\Unknown $name,
    ): Attempt {
        return $this
            ->implementation
            ->read($parent, $name)
            ->map(function($file) use ($parent, $name) {
                $this->logger->debug('Accessing file {file}', [
                    'file' => self::path(TreePath::of($name->unwrap())->under($parent)),
                ]);

                return $file;
            });
    }

    #[\Override]
    public function list(TreePath $parent): Sequence
    {
        $this->logger->debug('Listing files in {directory}', [
            'directory' => self::path($parent),
        ]);

        return $this->implementation->list($parent);
    }

    #[\Override]
    public function remove(TreePath $parent, Name $name): Attempt
    {
        return $this
            ->implementation
            ->remove($parent, $name)
            ->map(function($_) use ($parent, $name) {
                $this->logger->debug('File removed {file}', [
                    'file' => self::path(TreePath::of($name)->under($parent)),
                ]);

                return $_;
            });
    }

    #[\Override]
    public function createDirectory(TreePath $parent, Name $name): Attempt
    {
        return $this
            ->implementation
            ->createDirectory($parent, $name)
            ->map(function($_) use ($parent, $name) {
                $this->logger->debug('Directory created {directory}', [
                    'directory' => self::path(TreePath::directory($name)->under($parent)),
                ]);

                return $_;
            });
    }

    #[\Override]
    public function write(TreePath $parent, File $file): Attempt
    {
        return $this
            ->implementation
            ->write($parent, $file)
            ->map(function($_) use ($parent, $file) {
                $this->logger->debug('File written {file}', [
                    'file' => self::path(TreePath::of($file)->under($parent)),
                    'mediaType' => $file->mediaType()->toString(),
                ]);

                return $_;
            });
    }

    private static function path(TreePath $path): string
    {
        return $path->asPath(Path::of('/'))->toString();
    }
}
