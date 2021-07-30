# Image processing module

All images are optimized with [image-optimizer](https://packagist.org/packages/spatie/image-optimizer).

For best results binaries must [be installed](https://github.com/spatie/image-optimizer#optimization-tools).

## Installation

`composer require escolalms/images`

## Examples

**Resizing**

- `http://localhost:1000/api/images/img?path=1.jpg&h=100`
- `http://localhost:1000/api/images/img?path=1.jpg&w=100`
- `http://localhost:1000/api/images/img?path=1.jpg&w=100&h=100`

`input` file is taken from

```php
   $input_file = storage_path('app/public/'.$path);
```
