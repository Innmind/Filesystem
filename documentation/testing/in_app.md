# In your application

In your application you'll use most of the time the `Filesystem` adapter but in your tests writing to the filesystem can be slow. Instead you can use the `InMemory` adapter.

```php
use Innmind\Filesystem\Adapter\InMemory;

$filesystem = InMemory::emulateFilesystem();
// use $filesystem as usual
```

This adapter is tested against the same [properties](own_adapter.md) as `Filesystem` to make sure there is no divergence of behaviour between the two.
