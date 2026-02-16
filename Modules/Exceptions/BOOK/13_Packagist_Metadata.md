# Packagist Metadata

To publish `maatify/exceptions` to Packagist, ensure your `composer.json` includes the following:

```json
{
    "name": "maatify/exceptions",
    "description": "Enterprise-grade, hardened exception handling library for PHP applications.",
    "type": "library",
    "license": "MIT",
    "require": {
        "php": "^8.2"
    },
    "autoload": {
        "psr-4": {
            "Maatify\\Exceptions\\": "src/"
        }
    },
    "authors": [
        {
            "name": "Maatify Dev Team",
            "email": "dev@maatify.dev"
        }
    ],
    "keywords": [
        "exceptions",
        "error-handling",
        "taxonomy",
        "security",
        "php"
    ],
    "minimum-stability": "stable"
}
```

**Note:** The paths in `autoload` must match the directory structure of the standalone repository (typically `src/` for the source code).
