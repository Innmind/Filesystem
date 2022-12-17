<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\Adapter;

use Innmind\Filesystem\{
    Adapter,
    File,
    Name,
    Directory,
};
use Innmind\Immutable\{
    Set,
    Maybe,
};
use Psr\Log\LoggerInterface;

final class Logger implements Adapter
{
    private Adapter $filesystem;
    private LoggerInterface $logger;

    private function __construct(Adapter $filesystem, LoggerInterface $logger)
    {
        $this->filesystem = $filesystem;
        $this->logger = $logger;
    }

    public static function psr(Adapter $filesystem, LoggerInterface $logger): self
    {
        return new self($filesystem, $logger);
    }

    public function add(File $file): void
    {
        $this->logger->debug('Adding file {file}', ['file' => $file->name()->toString()]);
        $this->filesystem->add($file);
    }

    public function get(Name $file): Maybe
    {
        return $this
            ->filesystem
            ->get($file)
            ->map(function($file) {
                $this->logger->debug(
                    'Accessing file {name}',
                    ['name' => $file->name()->toString()],
                );

                return $file;
            });
    }

    public function contains(Name $file): bool
    {
        $contains = $this->filesystem->contains($file);
        $this->logger->debug('Cheking if filesystem contains {file}', [
            'file' => $file->toString(),
            'contains' => $contains,
        ]);

        return $contains;
    }

    public function remove(Name $file): void
    {
        $this->logger->debug('Removing file {file}', ['file' => $file->toString()]);
        $this->filesystem->remove($file);
    }

    public function all(): Set
    {
        return $this->root()->files();
    }

    public function root(): Directory
    {
        return $this->filesystem->root();
    }
}
