# AdminKernel

## Environment Contract

`AdminKernel` reads only its own generic, host-agnostic environment contract — keys such as
`APP_ENV`, `DB_HOST`, `MAIL_HOST`, `STORAGE_DRIVER`, and the rest of the contract consumed by
`Kernel\DTO\AdminRuntimeConfigDTO`, `Ui\Config\MediaUrlConfigDTO`, and the `Storage` module's
`StorageConfig`.

**No class under `Modules/AdminKernel` may read a host-specific, namespaced environment variable
directly** (e.g. a prefix like `ADMIN_*` that belongs to one particular host application). If a
host needs its own namespacing to avoid colliding with another application sharing its process,
that translation is the host's responsibility, done once at the host's own composition root —
never inside this module.

This is what keeps `AdminKernel` reusable across different hosts (it is synced with `Athar`)
without carrying any single host's naming decisions.

For a concrete example, see how the `admin-control-panel` host does this translation in
`app/Bootstrap/AdminEnvironmentAdapter` and `app/Bootstrap/AdminStorageEnvAdapter`, documented in
[ADR-019](../../docs/adr/ADR-019-host-owned-env-adapters.md).
