# Your own adapter

This library allows you to extend its behaviour by creating new implementations of the exposed interfaces (`File`, `Directory` and `Adapter`). The interfaces are strict enough to guide you through the expected behaviour but the type system can't express all of them, leaving the door open to inconsistencies between implementations. That's why the library expose a set of properties (as declared by [`innmind/black-box`](https://packagist.org/packages/innmind/black-box)) to help you make sure your implementations fulfill the expected behaviours.

You can test properties on your adapter as follow (with PHPUnit):

```php
use Properties\Innmind\Filesystem\Adapter;
use Innmind\BlackBox\PHPUnit\BlackBox;
use PHPUnit\Framework\TestCase;

class MyAdapterTest extends TestCase
{
    use BlackBox;

    /**
     * This test will make sure each property is held by your adapter
     *
     * @dataProvider properties
     */
    public function testHoldProperty($property)
    {
        $this
            ->forAll($property)
            ->then(function($property) {
                $adapter = /* instanciate your implementation here */;

                if (!$property->applicableTo($adapter)) {
                    $this->markTestSkipped();
                }

                $property->ensureHeldBy($adapter);
            });
    }

    /**
     * This test will try to prove your adapter hold any sequence of property
     *
     * This is useful to find bugs due to state mismanage
     */
    public function testHoldProperties()
    {
        $this
            ->forAll(Adapter::properties())
            ->then(function($properties) {
                $properties->ensureHeldBy(/* instanciate your implementation here */);
            });
    }

    public function properties(): iterable
    {
        foreach (Adapter::list() as $property) {
            yield [$property];
        }
    }
}
```

You can use the same logic to test `Directory` implementations with `Properties\Innmind\Filesystem\Directory`.

**Note**: there is no properties for the `File` interface as it doesn't expose any behaviour.
