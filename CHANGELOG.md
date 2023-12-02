# Changelog

## 7.4.0 - 2023-12-02

### Added

- Support for `symfony/filesystem:~7.0`

## 7.3.0 - 2023-11-05

### Added

- Watch the streams before reading from them (allows for an easy integration in `Fiber`s)

## 7.2.0- 2023-11-01

### Added

- `Properties\Innmind\Filesystem\Adapter\AddDirectory::directory()`
- `Properties\Innmind\Filesystem\Adapter\RemoveAddRemoveModificationsDoesntAddTheFile::directory()`

## 7.1.0 - 2023-10-22

### Added

- `Innmind\Filesystem\File\Content::io()`

## 7.0.0 - 2023-10-21

### Added

- `Innmind\Filesystem\File\Content::chunks()`

### Changed

- `Innmind\Filesystem\Name` constructor is now private, use `::of()` named constructor instead
- `Innmind\Filesystem\File\File` constructor is now private, use `::of()` named constructor instead
- `Innmind\Filesystem\File\Content` is now a final class and its different implementations are declared internal, use the `Content` named constructors instead
- `Innmind\Filesystem\Directory` no longer extends `File`, all previous function typed against `File` are now typed `File|Directory`
- `Innmind\Filesystem\File` is now a final class instead of an interface
- `Innmind\Filesystem\Directory` is now a final class instead of an interface
- `Innmind\Filesystem\Directory::files()` has been renamed to `::all()`

### Fixed

- An inconsistency in `File\Content` that must contain at least one line but it wasn't applied after a `Content::filter()`

### Removed

- `Innmind\Filesystem\Adapter\HashedName`
- `Innmind\Filesystem\Adapter::all()`
- `Innmind\Filesystem\Chunk`
- `Innmind\Filesystem\File\Content\Chunkable`
- Possibility to use a `Innmind\Immutable\Set` of files inside a `Directory`
- `Innmind\Filesystem\Stream\LazyStream`

## 6.6.0 - 2023-09-16

### Added

- Support for `innmind/immutable` `5`

### Deprecated

- `Innmind\Filesystem\Adapter\HashedName`

## 6.5.1 - 2023-07-14

### Fixed

- Deleting a file in a directory for the adapter `InMemory::emulateFilesystem()` wasn't applied

## 6.5.0 - 2023-07-09

### Changed

- Require `innmind/black-box` `5`

### Removed

- Support for PHP `8.1`

## 6.4.0 - 2023-05-18

### Added

- `Innmind\Filesystem\Adapter\InMemory::emulateFilesystem()` it will merge directories (instead of the overwriting done by `::new()`)

### Fixed

- Accessing the media type of a file no longer raise an error when it's unavailable, instead it defaults to `application/octet-stream`
- When adding a `Directory` loaded via `Adapter\Filesystem` the sub directories are no longer loaded if not modified
- Throw an exception when failing to load a file

## 6.3.2 - 2023-04-30

### Changed

- `Innmind\Filesystem\File\Content\Chunkable` and `Innmind\Filesystem\Chunk` are declared immutable (released as a bugfix since it should have been here since the start)

## 6.3.1 - 2023-04-15

### Changed

- `Innmind\Filesystem\Directory` is declared immutable (released as a bugfix since it should have been here since the start)

## 6.3.0 - 2023-03-31

### Added

- `Innmind\Filesystem\File\Content\Chunks`

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
