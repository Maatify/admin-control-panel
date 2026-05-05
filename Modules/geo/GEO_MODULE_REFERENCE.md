# GEO MODULE REFERENCE

**Maatify Geo Module — Complete API Reference**

---

## Design Rules

| Rule | Value |
|---|---|
| Namespace | `Maatify\Geo\` |
| Tables | `geo_countries`, `geo_country_translations`, `geo_cities`, `geo_city_translations` |
| Languages table | **NEVER referenced** — `language_id` is a plain `INT` |
| Status field | `is_active` TINYINT(1) — `1 = active`, `0 = inactive` |
| Soft delete | Not implemented — hard delete only |
| display_order | Auto-assigned on create; scoped per country for cities |
| PDO | All persistence via PDO — no ORM |
| PHPStan | Level max |

---

## Schema

### `geo_countries`

| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK AI | |
| code | CHAR(2) UNIQUE | ISO 3166-1 alpha-2: EG, US, GB |
| name | VARCHAR(100) | Base (default locale) name |
| icon | VARCHAR(512) NULL | Flag path or URL |
| is_active | TINYINT(1) | `1` = active |
| display_order | INT UNSIGNED | Global ordering |
| created_at | DATETIME | |
| updated_at | DATETIME NULL | Auto ON UPDATE |

### `geo_country_translations`

| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK AI | |
| country_id | INT UNSIGNED FK | → `geo_countries.id` ON DELETE CASCADE |
| language_id | INT UNSIGNED | Plain int — no FK to languages |
| name | VARCHAR(100) | Translated name |
| created_at / updated_at | DATETIME | |

UNIQUE KEY `(country_id, language_id)`

### `geo_cities`

| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK AI | |
| country_id | INT UNSIGNED FK | → `geo_countries.id` ON DELETE RESTRICT |
| code | VARCHAR(20) NULL | Optional IATA/ICAO code |
| name | VARCHAR(100) | Base name |
| is_active | TINYINT(1) | `1` = active |
| display_order | INT UNSIGNED | Scoped per country |
| created_at / updated_at | DATETIME | |

### `geo_city_translations`

| Column | Type | Notes |
|---|---|---|
| id | INT UNSIGNED PK AI | |
| city_id | INT UNSIGNED FK | → `geo_cities.id` ON DELETE CASCADE |
| language_id | INT UNSIGNED | Plain int — no FK to languages |
| name | VARCHAR(100) | Translated name |
| created_at / updated_at | DATETIME | |

UNIQUE KEY `(city_id, language_id)`

---

## GeoQueryService — Public API

### Country queries

```php
// Paginated admin list
$result = $service->paginateCountries(
    page: 1, perPage: 20,
    globalSearch: 'egy',
    columnFilters: ['is_active' => 1],
    languageId: 2,      // optional
);
// $result['data']       → list<CountryDTO>
// $result['pagination'] → ['page', 'per_page', 'total', 'filtered']

// All active countries (public)
$countries = $service->activeCountries();               // base name only
$countries = $service->activeCountries(languageId: 2);  // COALESCE translated name

// Single record
$country = $service->getCountryById(id: 5);
$country = $service->getCountryById(id: 5, languageId: 2);
$country = $service->getCountryByCode(code: 'EG');
$country = $service->getCountryByCode(code: 'EG', languageId: 2);
```

### City queries

```php
// Paginated admin list
$result = $service->paginateCities(
    page: 1, perPage: 20,
    globalSearch: null,
    columnFilters: ['country_id' => 1, 'is_active' => 1],
    languageId: 2,
);

// Active cities by country (public)
$cities = $service->activeCitiesByCountryId(countryId: 1);
$cities = $service->activeCitiesByCountryId(countryId: 1, languageId: 2);
$cities = $service->activeCitiesByCountryCode(countryCode: 'EG');
$cities = $service->activeCitiesByCountryCode(countryCode: 'EG', languageId: 2);

// Single record
$city = $service->getCityById(id: 12);
$city = $service->getCityById(id: 12, languageId: 2);
```

### Translation queries (admin)

```php
// Single row
$t = $service->findCountryTranslation(countryId: 1, languageId: 2);
$t = $service->findCityTranslation(cityId: 5, languageId: 2);

// Paginated listing (geo tables only — no languages JOIN)
$result = $service->listCountryTranslationsPaginated(countryId: 1, page: 1, perPage: 50);
$result = $service->listCityTranslationsPaginated(cityId: 5, page: 1, perPage: 50);
```

---

## GeoCommandService — Public API

### Country commands

```php
// Create
$dto = $service->createCountry(new CreateCountryCommand(code: 'EG', name: 'Egypt'));

// Update
$dto = $service->updateCountry(new UpdateCountryCommand(id: 1, code: 'EG', name: 'Egypt', icon: null, isActive: true));

// Status toggle
$dto = $service->updateCountryStatus(new UpdateCountryStatusCommand(id: 1, isActive: false));

// Reorder
$service->reorderCountry(id: 1, newOrder: 3);

// Translation
$t = $service->upsertCountryTranslation(new UpsertCountryTranslationCommand(countryId: 1, languageId: 2, translatedName: 'مصر'));
$service->deleteCountryTranslation(new DeleteCountryTranslationCommand(countryId: 1, languageId: 2));
```

### City commands

```php
// Create
$dto = $service->createCity(new CreateCityCommand(countryId: 1, name: 'Cairo', code: 'CAI'));

// Update
$dto = $service->updateCity(new UpdateCityCommand(id: 5, name: 'Cairo', code: 'CAI', isActive: true));

// Status toggle
$dto = $service->updateCityStatus(new UpdateCityStatusCommand(id: 5, isActive: false));

// Reorder (scoped per country)
$service->reorderCity(id: 5, newOrder: 2);

// Translation
$t = $service->upsertCityTranslation(new UpsertCityTranslationCommand(cityId: 5, languageId: 2, translatedName: 'القاهرة'));
$service->deleteCityTranslation(new DeleteCityTranslationCommand(cityId: 5, languageId: 2));
```

---

## DTO Reference

### CountryDTO

| Field | Type | Notes |
|---|---|---|
| id | int | |
| code | string | ISO alpha-2 |
| name | string | Base name |
| icon | ?string | |
| isActive | bool | |
| displayOrder | int | |
| createdAt | string | |
| updatedAt | ?string | |
| translatedName | ?string | null when no languageId; COALESCE result when languageId given |
| languageId | ?int | null when no languageId given |

`displayName()` → returns `translatedName ?? name`

### CityDTO

| Field | Type | Notes |
|---|---|---|
| id | int | |
| countryId | int | |
| code | ?string | |
| name | string | Base name |
| isActive | bool | |
| displayOrder | int | |
| createdAt | string | |
| updatedAt | ?string | |
| translatedName | ?string | COALESCE result when languageId given |
| languageId | ?int | |

### CountryTranslationDTO

| Field | Type | Notes |
|---|---|---|
| id | int | |
| countryId | int | |
| languageId | int | Plain int — no language metadata |
| name | string | Translated name |
| createdAt | string | |
| updatedAt | ?string | |

### CityTranslationDTO

| Field | Type | Notes |
|---|---|---|
| id | int | |
| cityId | int | |
| languageId | int | Plain int — no language metadata |
| name | string | Translated name |
| createdAt | string | |
| updatedAt | ?string | |

---

## Admin Layer Responsibility

The geo module intentionally returns **only translation data it owns**.
To display "all languages including untranslated" in the admin panel, the execution (admin) module performs a `LEFT JOIN` with the `languages` table independently:

```sql
SELECT
    l.id   AS language_id,
    l.code AS language_code,
    l.name AS language_name,
    ct.id,
    ct.name AS translated_name,
    ct.created_at,
    ct.updated_at
FROM languages l
LEFT JOIN geo_country_translations ct
       ON ct.language_id = l.id AND ct.country_id = :country_id
WHERE l.is_active = 1
ORDER BY l.id ASC
```

This keeps the geo module 100% standalone.

---

## Exception Reference

| Class | HTTP | When |
|---|---|---|
| `CountryNotFoundException` | 404 | Country not found by id or code |
| `CityNotFoundException` | 404 | City not found by id |
| `CountryCodeAlreadyExistsException` | 409 | ISO code already in use |
| `GeoInvalidArgumentException` | 400 | Empty field, invalid id, invalid code format |
| `GeoPersistenceException` | 500 | PDO prepare/execute failure |

All exceptions implement `GeoExceptionInterface` — catch the whole domain:

```php
try {
    $service->createCountry($command);
} catch (GeoExceptionInterface $e) {
    // handle all geo errors
}
```

