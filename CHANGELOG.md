# Changelog

## 6.2.0 - 2023-01-29

### Changed

- `Innmind\Filesystem\Adapter\Filesystem::mount()` now accepts `Innmind\Stream\Capabilities` as a second argument
- `Innmind\Filesystem\Stream\LazyStream` is now declared internal, future versions may introduce BC breaks

## 6.1.0 - 2023-01-02

### Added

- `Innmind\Filesystem\CaseSensitivity` enum
- `Innmind\Filesystem\Adapter\Filesystem::withCaseSensitivity()`

## 6.0.0 - 2022-12-18

### Added

- `Innmind\Filesystem\Name::of()` named constructor
- `Innmind\Filesystem\Name::str(): Innmind\Immutable\Str`
- `Innmind\Filesystem\File::withContent(): Innmind\Filesystem\File`
- `Innmind\Filesystem\File\File::of()` named constructor
- `Innmind\Filesystem\Directory::files(): Innmind\Immutable\Sequence<Innmind\Filesystem\File>`
- `Innmind\Filesystem\Adapter::root(): Innmind\Filesystem\Directory`
- `Innmind\Filesystem\Directory::map(): Innmind\Filesystem\Directory`
- `Innmind\Filesystem\Directory::flatMap(): Innmind\Filesystem\Directory`
- `Innmind\Filesystem\File::rename(): Innmind\Filesystem\File`
- `Innmind\Filesystem\Directory\Directory::of()` `$files` parameter accepts an `Innmind\Immutable\Sequence<Innmind\Filesystem\File>`

### Changed

- `Innmind\Filesystem\Directory\Directory::remove()` no longer unwraps the whole directory
- `Innmind\Filesystem\Directory\Directory::filter()` no longer erase the previous removals

### Deprecated

- `Innmind\Filesystem\Name` constructor
- `Innmind\Filesystem\File\File` constructor
- `Innmind\Filesystem\Adapter::all()`

### Removed

- `Innmind\Filesystem\Adapter\Chunk`
- `Innmind\Filesystem\File\Content\AtPath::stream()`
- `Innmind\Filesystem\File\Content\OfStream::stream()`

## 5.2.0 - 2022-09-24

### Added

- `Innmind\Filesystem\Chunk`
- `Innmind\Filesystem\File\Content\Chunkable`
- `Innmind\Filesystem\File\Content\Line::str(): Innmind\Immutable\Str`

## 5.1.0 - 2022-02-22

### Changed

- `Innmind\Filesystem\Stream\LazyStream::end()` is declared as mutation free

## 5.0.0 - 2022-01-30

### Added

- `Innmind\Filesystem\File\Content`
- `Innmind\Filesystem\File\Content\Line`
- `Innmind\Filesystem\File\Content\Lines`
- `Innmind\Filesystem\File\Content\AtPath`
- `Innmind\Filesystem\File\Content\OfStream`
- `Innmind\Filesystem\File\Content\None`
- `Innmind\Filesystem\Exception\FailedToWriteFile`
- `Innmind\Filesystem\Exception\FailedToLoadFile`

### Changed

- `Innmind\Filesystem\File::content()` now returns `Innmind\Filesystem\File\Content`
- Sets of loaded files are now lazy instead of deferred to avoid keeping in memory a whole directory tree
- `Innmind\Filesystem\Directory\Directory` constructor is now private, use `Directory::of()` instead
- `Innmind\Filesystem\Directory::get()` now returns `Innmind\Immutable\Maybe<Innmind\Filesystem\File>` instead of throwing an exception
- `Innmind\Filesystem\Directory::foreach()` now returns `Innmind\Immutable\SideEffect`
- `Innmind\Filesystem\Directory::filter()` now returns `Innmind\Filesystem\Directory`
- `Innmind\Filesystem\Directory::modifications()` has been replaced by `Innmind\Filesystem\Directory::removed()`
- `Innmind\Filesystem\Directory::content()` no longer contains the names of the files it contains
- `Innmind\Filesystem\Adapter\Filesystem` constructor is now private, use `Filesystem::mount()` instead
- `Innmind\Filesystem\Adapter\HashedName` constructor is now private, use `HashedName::of()` instead
- `Innmind\Filesystem\Adapter\InMemory` constructor is now private, use `InMemory::new()` instead
- `Innmind\Filesystem\Adapter\Logger` constructor is now private, use `Logger::psr()` instead
- `Innmind\Filesystem\Adapter::get()` now returns `Innmind\Immutable\Maybe<Innmind\Filesystem\File>` instead of throwing an exception

### Removed

- `Innmind\Filesystem\Stream\NullStream`
- `Innmind\Filesystem\File\File::withContent()`
- `Innmind\Filesystem\Exception\FileNotFound`
- `Innmind\Filesystem\Source`
- `Innmind\Filesystem\Directory\Source`
- `Innmind\Filesystem\File\Source`
- `Innmind\Filesystem\Event\FileWasAdded`
- `Innmind\Filesystem\Event\FileWasRemoved`
- `Innmind\Filesystem\Directory::replaceAt()`
- `Innmind\Filesystem\LazyAdapter`
- `Innmind\Filesystem\Adapter\Lazy`
- `Innmind\Filesystem\Adapter\CacheOpenedFiles`