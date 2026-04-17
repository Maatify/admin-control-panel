# Image Profiles — UI & API Integration Guide

**Module:** `Modules/ImageProfile`
**Status:** CANONICAL / BINDING CONTRACT

## 0) Why this document exists
This document defines the strict API contract for interacting with Image Profiles via the admin backend REST API. It documents the currently implemented endpoints, payload schemas, response structures, permission mapping, and integration rules that the future UI (Twig/JS) must follow.

Image Profiles are backed by `maa_image_profiles` and represent reusable validation/processing profiles (code, dimensions, mime/extensions, aspect ratio constraints, status, and optional processing hints).

---

## 1) Endpoints

### Image Profiles Dropdown
**Method:** POST  
**URL:** `/api/image-profiles/dropdown`  
**Route name:** `image_profiles.dropdown.api`  
**Capability:** `image_profiles.dropdown`

#### Purpose
Returns active profiles for selector/dropdown UI use-cases.

#### Request Payload
No payload required.

#### Response — Success
```json
{
  "data": [
    {
      "id": 1,
      "code": "avatar",
      "display_name": "Avatar",
      "min_width": 200,
      "min_height": 200,
      "max_width": 2000,
      "max_height": 2000,
      "max_size_bytes": 2097152,
      "allowed_extensions": "jpg,jpeg,png,webp",
      "allowed_mime_types": "image/jpeg,image/png,image/webp",
      "is_active": true,
      "notes": null,
      "min_aspect_ratio": null,
      "max_aspect_ratio": null,
      "requires_transparency": false,
      "preferred_format": "webp",
      "preferred_quality": 85,
      "variants": null,
      "created_at": "2026-01-01 00:00:00",
      "updated_at": null
    }
  ]
}
```

#### Response — Error
```json
{
  "success": false,
  "error": {
    "code": "error",
    "category": "SYSTEM",
    "message": "Invalid request payload",
    "meta": [],
    "retryable": false
  }
}
```

---

### List Image Profiles
**Method:** POST  
**URL:** `/api/image-profiles/query`  
**Route name:** `image_profiles.list.api`  
**Capability:** `image_profiles.list`

#### Purpose
Returns paginated, searchable, filterable profile list for admin tables.

#### Request Payload
Standard `SharedListQuerySchema` format.

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `page` | `int` | No | Minimum 1 |
| `per_page` | `int` | No | Minimum 1 |
| `search.global` | `string` | No | Global search against profile code/display name |
| `search.columns.id` | `string/int` | No | Exact filter |
| `search.columns.code` | `string` | No | Exact filter |
| `search.columns.is_active` | `string/int` | No | Exact filter (0/1) |

#### Response — Success
```json
{
  "data": [
    {
      "id": 1,
      "code": "avatar",
      "display_name": "Avatar",
      "min_width": 200,
      "min_height": 200,
      "max_width": 2000,
      "max_height": 2000,
      "max_size_bytes": 2097152,
      "allowed_extensions": "jpg,jpeg,png,webp",
      "allowed_mime_types": "image/jpeg,image/png,image/webp",
      "is_active": true,
      "notes": null,
      "min_aspect_ratio": null,
      "max_aspect_ratio": null,
      "requires_transparency": false,
      "preferred_format": "webp",
      "preferred_quality": 85,
      "variants": null,
      "created_at": "2026-01-01 00:00:00",
      "updated_at": null
    }
  ],
  "pagination": {
    "page": 1,
    "per_page": 20,
    "total": 50,
    "filtered": 1
  }
}
```

#### Table/List Notes
- Global search is enabled.
- Column filters are enabled for: `id`, `code`, `is_active`.
- Date filter is not enabled for this endpoint.

---

### Get Image Profile Details
**Method:** POST  
**URL:** `/api/image-profiles/details`  
**Route name:** `image_profiles.details.api`  
**Capability:** `image_profiles.details`

#### Purpose
Fetches a single profile by internal `id`.

#### Request Payload
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `id` | `int` | Yes | Minimum 1 |

#### Response — Success
```json
{
  "id": 1,
  "code": "avatar",
  "display_name": "Avatar",
  "min_width": 200,
  "min_height": 200,
  "max_width": 2000,
  "max_height": 2000,
  "max_size_bytes": 2097152,
  "allowed_extensions": "jpg,jpeg,png,webp",
  "allowed_mime_types": "image/jpeg,image/png,image/webp",
  "is_active": true,
  "notes": null,
  "min_aspect_ratio": null,
  "max_aspect_ratio": null,
  "requires_transparency": false,
  "preferred_format": "webp",
  "preferred_quality": 85,
  "variants": null,
  "created_at": "2026-01-01 00:00:00",
  "updated_at": null
}
```

---

### Create Image Profile
**Method:** POST  
**URL:** `/api/image-profiles/create`  
**Route name:** `image_profiles.create.api`  
**Capability:** `image_profiles.create`

#### Purpose
Creates a new profile row in `maa_image_profiles`.

#### Request Payload
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `code` | `string` | Yes | 1-64 chars |
| `display_name` | `string \| null` | No | 1-128 chars when provided |
| `min_width` | `int \| null` | No | Minimum 0 |
| `min_height` | `int \| null` | No | Minimum 0 |
| `max_width` | `int \| null` | No | Minimum 0 |
| `max_height` | `int \| null` | No | Minimum 0 |
| `max_size_bytes` | `int \| null` | No | Minimum 0 |
| `allowed_extensions` | `string \| null` | No | 1-255 chars when provided |
| `allowed_mime_types` | `string \| null` | No | Any non-empty string when provided |
| `is_active` | `bool` | No | Default `true` |
| `notes` | `string \| null` | No | Optional |
| `min_aspect_ratio` | `string \| null` | No | Max 16 chars |
| `max_aspect_ratio` | `string \| null` | No | Max 16 chars |
| `requires_transparency` | `bool` | No | Default `false` |
| `preferred_format` | `string \| null` | No | 1-10 chars when provided |
| `preferred_quality` | `int \| null` | No | 1-100 |
| `variants` | `string \| null` | No | Optional JSON string/blob |

#### Response — Success
```json
{
  "success": true
}
```

---

### Update Image Profile
**Method:** POST  
**URL:** `/api/image-profiles/update`  
**Route name:** `image_profiles.update.api`  
**Capability:** `image_profiles.update`

#### Purpose
Updates an existing profile by `id`.

#### Request Payload
> `update` uses a full payload contract: all fields are required in the request body, but nullable fields may be sent as `null`.

| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `id` | `int` | Yes | Minimum 1 |
| `code` | `string` | Yes | 1-64 chars |
| `display_name` | `string \| null` | Yes | null or 1-128 chars |
| `min_width` | `int \| null` | Yes | null or minimum 0 |
| `min_height` | `int \| null` | Yes | null or minimum 0 |
| `max_width` | `int \| null` | Yes | null or minimum 0 |
| `max_height` | `int \| null` | Yes | null or minimum 0 |
| `max_size_bytes` | `int \| null` | Yes | null or minimum 0 |
| `allowed_extensions` | `string \| null` | Yes | null or 1-255 chars |
| `allowed_mime_types` | `string \| null` | Yes | null or string |
| `is_active` | `bool` | Yes | Required |
| `notes` | `string \| null` | Yes | null or string |
| `min_aspect_ratio` | `string \| null` | Yes | null or max 16 chars |
| `max_aspect_ratio` | `string \| null` | Yes | null or max 16 chars |
| `requires_transparency` | `bool` | Yes | Required |
| `preferred_format` | `string \| null` | Yes | null or 1-10 chars |
| `preferred_quality` | `int \| null` | Yes | null or 1-100 |
| `variants` | `string \| null` | Yes | null or string |

#### Response — Success
```json
{
  "success": true
}
```

---

### Update Image Profile Status
**Method:** POST  
**URL:** `/api/image-profiles/set-active`  
**Route name:** `image_profiles.set_active.api`  
**Capability:** `image_profiles.set_active`

#### Purpose
Toggles active/inactive state without updating all profile fields.

#### Request Payload
| Field | Type | Required | Validation |
|-------|------|----------|------------|
| `id` | `int` | Yes | Minimum 1 |
| `is_active` | `bool` | Yes | Required |

#### Response — Success
```json
{
  "success": true
}
```

---

## 2) Permission Mapping (Route → Capability)

The permission mapper resolves route names to capabilities as follows:

| Route Name | Capability |
|------------|------------|
| `image_profiles.list.api` | `image_profiles.list` |
| `image_profiles.details.api` | `image_profiles.details` |
| `image_profiles.create.api` | `image_profiles.create` |
| `image_profiles.update.api` | `image_profiles.update` |
| `image_profiles.set_active.api` | `image_profiles.set_active` |
| `image_profiles.dropdown.api` | `image_profiles.dropdown` |

---

## 3) Frontend Integration Notes (for upcoming Twig/JS step)

- Use `/api/image-profiles/query` as the primary table data source.
- Send list payloads in `SharedListQuerySchema` shape (`page`, `per_page`, `search.global`, `search.columns`).
- Expect `data[]` + `pagination` response envelope from list endpoint.
- Use `/api/image-profiles/details` to prefill edit forms when row details must be refreshed from backend.
- Use command endpoints (`create`, `update`, `set-active`) as action endpoints and rely on `{ "success": true }` as success contract.
- Use `/api/image-profiles/dropdown` for active-profile selectors in cross-feature forms.
- Do not assume upload/media processing endpoints exist in this API. This module currently documents profile-management API only.

---

## 4) What the UI must NOT do

- Must NOT send partial payloads to `/api/image-profiles/update`; send the full update contract.
- Must NOT assume unsupported list filters beyond `id`, `code`, `is_active`.
- Must NOT parse HTML error responses; expect canonical JSON error envelopes.
- Must NOT invent new ImageProfile endpoints in UI before backend contracts are explicitly added.

---

## 5) Implementation Checklist
- [x] Endpoints documented and aligned with registered routes.
- [x] Validation contract documented for all command/query endpoints.
- [x] Permission mapping documented for all ImageProfile API routes.
- [x] Frontend integration notes added for upcoming UI implementation.
