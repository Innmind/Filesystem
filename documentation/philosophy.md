---
hide:
    - navigation
---

# Philosophy

This package is designed around the declaration of how the filesystem should look like via immutable objects and objects that will apply such structure.

The goal is that no matter the current state of the filesystem it will always create the directories and files you expect. No more `no such file or directory` errors.

This design also brings benefits when reading data from the filesystem. The objects always force you to handle both case where the file/directory you want to access exists or not. This prevents runtime error where a file/directory you assumed axisted but does not.

Reading a file content is also represented as an immutable object. By restricting the way you access the file content the package is able to only load the minimum amount of data in memory as you need. This way the package is able to handle any file size without running into memory issues.

By having a separation between the data representation (as immutable objects) and the objects that will apply these structures, you can do handle your filesystem in a [pure way](https://innmind.org/documentation/philosophy/oop-fp/#purity). This means that you can test safely your code without having to rely on a concrete filesystem.

## Terminology

### `Content`

This class reprensents the ways to interact with a file content. In order to handle any file size it represent the data as a [`Sequence`](https://innmind.org/Immutable/structures/sequence/) of lines or of chunks.

Representing a file as a `Sequence` of lines is useful when dealing with text files, while the chunks approach will be useful for binary files.

Your interactions with a file will mostly be described as what to do for each line/chunk. This way the package only needs to load one line/chunk at a time in memory.

And because it uses a `Sequence` the data can come from anywhere. For example you can represent a file content with the results from a SQL query. There's no direct tie to a concrete filesystem.

??? tip
    And by using a lazy `Sequence` you can generate files larger that may not fit in the process memory.

### `File`

A `File` is represented by a `Name`, a [`Content`](#content) and a [`MediaType`](https://github.com/Innmind/MediaType/).

Its `Name` is an immutable object. It uses an object and not a simple string in order to prevent the usage of invalid characters such as a directory separator or pseudo files `.` and `..`.

Its `MediaType` is here mostly as an helper. There's no guarantee that the detected type when reading the file from the filesystem is the correct one. (1)
{.annotate}

1. It uses the PHP `mime_content_type` function internally.

### `Directory`

A directory is represented as a `Sequence` of [`File`s](#file) or directories.

Because it uses a `Sequence` the data can come from anywhere. That's why this package provides both a filesystem and in memory adapters and that an [S3 adapter](https://github.com/Innmind/S3/) can be provided as an extension.

This means that in order to access a file/directory from a directory via its name it will iterate over the `Sequence` until it finds the value. Bear in mind that depending on your filesystem structures this may cause performance issues as it will take longer to access a file/directory than a direct call to the filesystem by providing the path.

??? tip
    And by using a lazy `Sequence` you can generate large directory structures that may not fit in the process memory.

### `Adapter`

An adapter is responsible to transform the immutable structures `Directory`/`File` to a concrete storage. And transform the raw data from the storage into `Sequence`s to represent back `Directory`/`File` structures.
