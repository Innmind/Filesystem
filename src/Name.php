<?php
declare(strict_types = 1);

namespace Innmind\Filesystem;

use Innmind\Filesystem\Exception\DomainException;
use Innmind\Immutable\Str;

final class Name
{
    private string $value;

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

        $this->assertContainsOnlyValidCharacters($value);
        $this->assertFirstCharacterValid($value);

        if ($value === '.' || $value === '..') {
            // as they are special links on unix filesystems
            throw new DomainException("'.' and '..' can't be used");
        }

        $this->value = $value;
    }

    public function equals(self $name): bool
    {
        return $this->value === $name->value;
    }

    public function toString(): string
    {
        return $this->value;
    }

    private function assertContainsOnlyValidCharacters(string $value): void
    {
        $value = Str::of($value);
        $invalid = [0, 34, 39, ...range(128, 255)];

        foreach ($invalid as $ord) {
            if ($value->contains(\chr($ord))) {
                throw new DomainException($value->toString());
            }
        }
    }

    private function assertFirstCharacterValid(string $value): void
    {
        $index = \ord(Str::of($value, 'ASCII')->take(1)->toString());

        if (\in_array($index, [32, ...range(9, 13), ...range(123, 125)], true)) {
            throw new DomainException($value);
        }
    }
}
