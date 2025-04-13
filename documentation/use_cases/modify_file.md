# Modify a file

!!! warning ""
    You can't modify a file _in place_, meaning you can't read and write to the same file at once. You need to write to a different file first.

## Replace a line

```php
use Innmind\Filesystem\{
    Adapter\Filesystem,
    File,
    Name,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Str,
    Predicate\Instance,
};

// replace the "unreleased" title with the new version
$insertRelease = static function(Str $line): Str {
    if ($line->startsWith('## [Unreleased]')) {
        return Str::of('## 1.0.0 - 2022-01-30');
    }

    return $line;
};
// replace the old changelog with the new one containing
// the new release version
$release = static function(File $changelog) use ($insertRelease): File {
    return $changelog->withContent(
        $changelog->content()->map(
            static fn($line) => $line->map($insertRelease),
        ),
    );
}
$filesystem = Filesystem::mount(Path::of('some/repository/'));
$tmp = Filesystem::mount(Path::of('/tmp/'));
$filesystem
    ->get(Name::of('CHANGELOG.md'))
    ->keep(Instance::of(File::class))
    ->map($release)
    ->flatMap(static function($changelog) use ($tmp) {
        // this operation is due to the fact that you cannot read and
        // write to the same file at once
        $tmp->add($changelog)->unwrap();

        return $tmp->get($changelog->name());
    })
    ->match(
        static fn($changelog) => $filesystem->add($changelog)->unwrap(),
        static fn() => null, // the changelog doesn't exist
    );
```

This example modifies the `CHANGELOG.md` file to replace the `## [Unreleased]` title with a version number.

## Insert a new line

```php
use Innmind\Filesystem\{
    Adapter\Filesystem,
    File,
    File\Content,
    File\Content\Line,
    Name,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Sequence,
    Str,
    Predicate\Instance,
};

// Insert "Jane Doe" after the user "John Doe"
$updateUser = static function(Line $user): Content {
    if ($user->toString() === 'John Doe') {
        return Content::ofLines(Sequence::of(
            $user,
            Line::of(Str::of('Jane Doe')),
        ));
    }

    return Content::ofLines(Sequence::of($user));
};
$update = static function(File $users) use ($updateUser): File {
    return $users->withContent(
        $users->content()->flatMap(static fn($line) => $updateUser($line)),
    );
};
$filesystem = Filesystem::mount(Path::of('/var/data/'));
$tmp = Filesystem::mount(Path::of('/tmp/'));
$filesystem
    ->get(Name::of('users.csv'))
    ->keep(Instance::of(File::class))
    ->map($update)
    ->flatMap(static function($users) use ($tmp) {
        // this operation is due to the fact that you cannot read and
        // write to the same file at once
        $tmp->add($users)->unwrap();

        return $tmp->get($users->name());
    })
    ->match(
        static fn($users) => $filesystem->add($users)->unwrap(),
        static fn() => null, // the csv doesn't exist
    );
```

This example will insert the user `Jane Doe` after `John Doe` wherever he is in the `users.csv` file. If the file doesn't exist then nothing happens.

## Merge two files

```php
use Innmind\Filesystem\{
    Adapter\Filesystem,
    File,
    File\Content,
    Name,
};
use Innmind\Url\Path;
use Innmind\Immutable\{
    Maybe,
    Predicate\Instance,
};

$merge = static function(File $file1, File $file2): File {
    return File::named(
        'all_users.csv',
        Content::ofLines(
            $file1->content()->lines()->append(
                $file2->content()->lines(),
            ),
        ),
    );
};
$filesystem = Filesystem::mount(Path::of('/var/data/'));
$users1 = $filesystem
    ->get(Name::of('users1.csv'))
    ->keep(Instance::of(File::class));
$users2 = $filesystem
    ->get(Name::of('users2.csv'))
    ->keep(Instance::of(File::class));
Maybe::all($users1, $users2)
    ->map(static fn($file1, $file2) => $merge($file1, $file2))
    ->match(
        static fn($merged) => $filesystem->add($merged)->unwrap(),
        static fn() => null,
    );
```

This example will create a file `all_users.csv` containing both files `users1.csv` and `users2.csv`. If one of the files or both of them doesn't exist then the new file won't be created.
