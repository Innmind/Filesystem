# Your own adapter

This library allows you to extend its behaviour by creating new implementations of the exposed interface `Adapter`. The interface is strict enough to guide you through the expected behaviour but the type system can't express all of them, leaving the door open to inconsistencies between implementations. That's why the library expose a set of properties (as declared by [`innmind/black-box`](https://packagist.org/packages/innmind/black-box)) to help you make sure your implementations fulfill the expected behaviours.

You can test properties on your adapter as follow:

```php
use Properties\Innmind\Filesystem\Adapter;
use Innmind\BlackBox\Set;

return static function() {
    yield properties(
        'YourAdapter',
        Adapter::properties(),
        Set::call(fn() => /* instanciate YourAdapter here */),
    );

    foreach (Adapter::alwaysApplicable() as $property) {
        yield property(
            $property,
            Set::call(fn() => /* instanciate YourAdapter here */),
        )->named('YourAdapter');
    }
};
```

Then you can [run your proofs](https://innmind.github.io/BlackBox/organize/) via BlackBox.
