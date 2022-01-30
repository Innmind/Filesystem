# Backup a directory

```php
use Innmind\Filesystem\Adapter\Filesystem;
use Innmind\Url\Path;

$source = Filesystem::mount(Path::of('/var/data/'));
$backup = Filesystem::mount(Path::of('/volumes/backup/'));
$source
    ->all()
    ->foreach(static fn($file) => $backup->add($file));
```

This example will copy all files and directories from `/var/data/` inside the folder `/volumes/backup/`. This operation is a merge and not an overwrite, meaning that files in the backup that don't exist in the source folder won't be deleted.
