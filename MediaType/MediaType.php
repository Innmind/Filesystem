<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\MediaType;

use Innmind\Filesystem\{
    MediaTypeInterface,
    Exception\InvalidArgumentException,
    Exception\InvalidTopLevelTypeException
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Set
};

final class MediaType implements MediaTypeInterface
{
    private static $topLevels;
    private $topLevel;
    private $subType;
    private $suffix;
    private $parameters;

    public function __construct(
        string $topLevel,
        string $subType,
        string $suffix,
        MapInterface $parameters
    ) {
        if (
            (string) $parameters->keyType() !== 'string' ||
            (string) $parameters->valueType() !== ParameterInterface::class
        ) {
            throw new InvalidArgumentException;
        }

        if (!self::topLevels()->contains($topLevel)) {
            throw new InvalidTopLevelTypeException;
        }

        $this->topLevel = $topLevel;
        $this->subType = $subType;
        $this->suffix = $suffix;
        $this->parameters = $parameters;
    }

    public function topLevel(): string
    {
        return $this->topLevel;
    }

    public function subType(): string
    {
        return $this->subType;
    }

    public function suffix(): string
    {
        return $this->suffix;
    }

    /**
     * {@inheritdoc}
     */
    public function parameters(): MapInterface
    {
        return $this->parameters;
    }

    public function __toString(): string
    {
        $parameters = $this->parameters->join(', ');

        return sprintf(
            '%s/%s%s%s',
            $this->topLevel,
            $this->subType,
            !empty($this->suffix) ? '+'.$this->suffix : '',
            !empty($parameters) ? '; '.$parameters : ''
        );
    }

    /**
     * List of allowed top levels
     *
     * @return SetInterface<string>
     */
    public static function topLevels(): SetInterface
    {
        if (self::$topLevels === null) {
            self::$topLevels = (new Set('string'))
                ->add('application')
                ->add('audio')
                ->add('example')
                ->add('image')
                ->add('message')
                ->add('model')
                ->add('multipart')
                ->add('text')
                ->add('video');
        }

        return self::$topLevels;
    }
}
