<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\MediaType;

use Innmind\Filesystem\{
    MediaType as MediaTypeInterface,
    Exception\InvalidTopLevelType,
    Exception\InvalidMediaTypeString
};
use Innmind\Immutable\{
    MapInterface,
    SetInterface,
    Set,
    Str,
    Map
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
        string $suffix = '',
        MapInterface $parameters = null
    ) {
        $parameters = $parameters ?? new Map('string', Parameter::class);

        if (
            (string) $parameters->keyType() !== 'string' ||
            (string) $parameters->valueType() !== Parameter::class
        ) {
            throw new \TypeError(sprintf(
                'Argument 4 must be of type MapInterface<string, %s>',
                Parameter::class
            ));
        }

        if (!self::topLevels()->contains($topLevel)) {
            throw new InvalidTopLevelType;
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
            $parameters->length() > 0 ? '; '.$parameters : ''
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
                ->add('font')
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

    /**
     * Build an object out of a string
     *
     * @param string $string
     *
     * @return self
     */
    public static function fromString(string $string): self
    {
        $string = new Str($string);
        $pattern = sprintf(
            '~%s/[\w\-.]+(\+\w+)?([;,] [\w\-.]+=[\w\-.]+)?~',
            self::topLevels()->join('|')
        );

        if (!$string->matches($pattern)) {
            throw new InvalidMediaTypeString;
        }

        $splits = $string->pregSplit('~[;,] ?~');
        $matches = $splits
            ->get(0)
            ->getMatches(sprintf(
                '~^(?<topLevel>%s)/(?<subType>[\w\-.]+)(\+(?<suffix>\w+))?$~',
                self::topLevels()->join('|')
            ));

        $topLevel = $matches->get('topLevel');
        $subType = $matches->get('subType');
        $suffix = $matches->contains('suffix') ? $matches->get('suffix') : '';
        $params = new Map('string', Parameter::class);

        $splits
            ->drop(1)
            ->foreach(function(Str $param) use (&$params) {
                $matches = $param->getMatches(
                    '~^(?<key>[\w\-.]+)=(?<value>[\w\-.]+)$~'
                );

                $params = $params->put(
                    (string) $matches->get('key'),
                    new Parameter\Parameter(
                        (string) $matches->get('key'),
                        (string) $matches->get('value')
                    )
                );
            });

        return new self(
            (string) $topLevel,
            (string) $subType,
            (string) $suffix,
            $params
        );
    }
}
