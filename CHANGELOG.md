# Changelog

## [Unreleased]

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
