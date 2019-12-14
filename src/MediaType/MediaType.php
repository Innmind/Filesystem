<?php
declare(strict_types = 1);

namespace Innmind\Filesystem\MediaType;

use Innmind\Filesystem\{
    MediaType as MediaTypeInterface,
    Exception\InvalidTopLevelType,
    Exception\InvalidMediaTypeString
};
use Innmind\Immutable\{
    Map,
    Set,
    Str,
};
use function Innmind\Immutable\join;

final class MediaType implements MediaTypeInterface
{
    private static ?Set $topLevels = null;
    private string $topLevel;
    private string $subType;
    private string $suffix;
    private Map $parameters;

    public function __construct(
        string $topLevel,
        string $subType,
        string $suffix = '',
        Map $parameters = null
    ) {
        $parameters = $parameters ?? Map::of('string', Parameter::class);

        if (
            (string) $parameters->keyType() !== 'string' ||
            (string) $parameters->valueType() !== Parameter::class
        ) {
            throw new \TypeError(\sprintf(
                'Argument 4 must be of type Map<string, %s>',
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
    public function parameters(): Map
    {
        return $this->parameters;
    }

    public function __toString(): string
    {
        $parameters = join(
            ', ',
            $this
                ->parameters
                ->values()
                ->toSequenceOf('string', fn($param) => yield (string) $param),
        );

        return sprintf(
            '%s/%s%s%s',
            $this->topLevel,
            $this->subType,
            !empty($this->suffix) ? '+'.$this->suffix : '',
            $parameters->length() > 0 ? '; '.$parameters->toString() : ''
        );
    }

    /**
     * List of allowed top levels
     *
     * @return Set<string>
     */
    public static function topLevels(): Set
    {
        if (self::$topLevels === null) {
            self::$topLevels = Set::of('string')
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
    public static function of(string $string): self
    {
        $string = Str::of($string);
        $pattern = \sprintf(
            '~%s/[\w\-.]+(\+\w+)?([;,] [\w\-.]+=[\w\-.]+)?~',
            join('|', self::topLevels())->toString()
        );

        if (!$string->matches($pattern)) {
            throw new InvalidMediaTypeString;
        }

        $splits = $string->pregSplit('~[;,] ?~');
        $matches = $splits
            ->get(0)
            ->capture(sprintf(
                '~^(?<topLevel>%s)/(?<subType>[\w\-.]+)(\+(?<suffix>\w+))?$~',
                join('|', self::topLevels())->toString()
            ));

        $topLevel = $matches->get('topLevel');
        $subType = $matches->get('subType');
        $suffix = $matches->contains('suffix') ? $matches->get('suffix') : Str::of('');
        $params = Map::of('string', Parameter::class);

        $splits
            ->drop(1)
            ->foreach(function(Str $param) use (&$params) {
                $matches = $param->capture(
                    '~^(?<key>[\w\-.]+)=(?<value>[\w\-.]+)$~'
                );

                $params = $params->put(
                    $matches->get('key')->toString(),
                    new Parameter\Parameter(
                        $matches->get('key')->toString(),
                        $matches->get('value')->toString()
                    )
                );
            });

        return new self(
            $topLevel->toString(),
            $subType->toString(),
            $suffix->toString(),
            $params
        );
    }

    /**
     * @deprecated
     * @see self::of()
     */
    public static function fromString(string $string): self
    {
        return self::of($string);
    }
}
