<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Filesystem\Exception\DomainException;
use Innmind\Immutable\Str;

/**
 * @psalm-immutable
 */
final class Name
{
    private string $value;

    /**
     * @deprecated Use Name::of() instead
     */
    public function __construct(string $value)
    {
        if (Str::of($value)->empty()) {
            throw new DomainException('A file name can\'t be empty');
        }

        if (Str::of($value, 'ASCII')->length() > 255) {
            throw new DomainException($value);
        }

        if (Str::of($value)->contains('/')) {
            throw new DomainException("A file name can't contain a slash, $value given");
        }

        if (Str::of($value)->contains(\chr(0))) {
            throw new DomainException("A file name can't contain the null control character, $value given");
        }

        // name with only _spaces_ are not accepted as it is not as valid path
        if (Str::of($value)->matches('~^\s+$~')) {
            throw new DomainException($value);
        }

        if ($value === '.' || $value === '..') {
            // as they are special links on unix filesystems
            throw new DomainException("'.' and '..' can't be used");
        }

        $this->value = $value;
    }

    /**
     * @psalm-pure
     *
     * @param non-empty-string $value
     *
     * @throws DomainException
     */
    public static function of(string $value): self
    {
        return new self($value);
    }

    public function equals(self $name): bool
    {
        return $this->value === $name->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}
