# UI Extension Guide

The Admin Kernel allows host applications to customize the UI via Dependency Injection and Configuration.

## 1. Navigation Injection

The sidebar menu is driven by `App\Domain\Contracts\Ui\NavigationProviderInterface`.
By default, the Kernel uses `App\Infrastructure\Ui\DefaultNavigationProvider` which provides the standard menu.

### How to Override

To provide your own menu items, implement the interface and bind it in the Container Builder Hook.

**1. Create your Provider:**

```php
namespace App\Host\Ui;

use App\Domain\DTO\Ui\NavigationItemDTO;
use App\Domain\Contracts\Ui\NavigationProviderInterface;

class HostNavigationProvider implements NavigationProviderInterface
{
    public function getNavigationItems(): array
    {
        return [
            new NavigationItemDTO('My Dashboard', '/my-dashboard', '<svg>...</svg>'),
            // ... add more items
        ];
    }
}
```

**2. Bind in host bootstrap (in your `public/index.php` or bootstrap):**

```php
$container = \App\Bootstrap\Container::create(function (ContainerBuilder $builder) {
    $builder->addDefinitions([
        \App\Domain\Contracts\Ui\NavigationProviderInterface::class => \DI\autowire(\App\Host\Ui\HostNavigationProvider::class),
    ]);
});
```

## 2. Asset Configuration

The Admin Panel uses an asset base URL for all CSS, JS, and image references.
This defaults to `/`.

### How to Override

Set the `ASSET_BASE_URL` environment variable in your `.env` file.

```dotenv
ASSET_BASE_URL="https://cdn.example.com/admin-assets/"
```

**Note:** Ensure the URL ends with a trailing slash if it is a directory.
The Kernel appends `assets/...` to this base.

Example:
- Default: `/` -> `/assets/css/style.css`
- CDN: `https://cdn.com/` -> `https://cdn.com/assets/css/style.css`
