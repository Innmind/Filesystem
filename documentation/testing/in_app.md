# In your application

In your application you'll use most of the time the `Adapter::mount()` adapter but in your tests writing to the filesystem can be slow. Instead you can use the `Adapter::inMemory()` one.

```php
use Innmind\Filesystem\Adapter;

$filesystem = Adapter::inMemory();
// use $filesystem as usual
```

This adapter is tested against the same [properties](https://innmind.org/BlackBox/getting-started/property/) as `Adapter::mount()` to make sure there is no divergence of behaviour between the two.
