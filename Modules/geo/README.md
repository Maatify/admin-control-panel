# Maatify Geo Module

Standalone Country & City management with translations, display ordering, and active/inactive status.

---

## What it does

- Manages a master list of **countries** (`geo_countries`) with optional flag icons, state/postcode requirement flags
- Manages a master list of **cities** (`geo_cities`) linked to a country via `country_id`
- Supports **translated names** per language for both entities (`geo_country_translations`, `geo_city_translations`)
- Supports **display_order** (auto-assigned on create, re-orderable, scoped per country for cities)
- Supports **is_active** status flag on both countries and cities
- Tracks `is_state_required` / `is_postcode_required` per country (populated from shipping provider)
- Provides **COALESCE fallback** — if a translation is missing, the base name is used transparently
- Provides paginated admin lists for countries, cities, and their translations

## What it does NOT do

- Does NOT know about the `languages` table — `language_id` is stored as a plain `INT`
- Does NOT manage languages, user authentication, or any host kernel concerns
- Does NOT contain HTTP controllers or routing — those belong to the execution (admin) module

---

## Installation

```bash
composer require maatify/geo
```

Run the schema file against your database:

```bash
mysql -u root -p your_database < vendor/maatify/geo/schema/geo.schema.sql
```

Register the module bindings in your DI container:

```php
use Maatify\Geo\Bootstrap\GeoBindings;

GeoBindings::register($containerBuilder);
```

---

## Quick Examples

### Get active countries (no translation)

```php
$countries = $geoQueryService->activeCountries();
// $country->translatedName === null
```

### Get active countries with translation

```php
$countries = $geoQueryService->activeCountries(languageId: 2);
// $country->translatedName = COALESCE(translation, base_name)
```

### Get cities by country ID

```php
$cities = $geoQueryService->activeCitiesByCountryId(countryId: 5);
$cities = $geoQueryService->activeCitiesByCountryId(countryId: 5, languageId: 2);
```

### Get cities by country code

```php
$cities = $geoQueryService->activeCitiesByCountryCode(countryCode: 'EG');
$cities = $geoQueryService->activeCitiesByCountryCode(countryCode: 'EG', languageId: 2);
```

### Create a country

```php
$command = new CreateCountryCommand(code: 'EG', name: 'Egypt', isStateRequired: true, isPostcodeRequired: false);
$country = $geoCommandService->createCountry($command);
```

### Upsert a country translation

```php
$command = new UpsertCountryTranslationCommand(countryId: 1, languageId: 2, translatedName: 'مصر');
$translation = $geoCommandService->upsertCountryTranslation($command);
```

### Create a city

```php
$command = new CreateCityCommand(countryId: 1, name: 'Cairo', code: 'CAI');
$city = $geoCommandService->createCity($command);
```

---

## Admin Translation Listing

The geo module's `listTranslationsForCountryPaginated()` and `listTranslationsForCityPaginated()` return only **existing translation rows** from the geo tables.

To display all languages (including untranslated), the **admin execution module** performs a LEFT JOIN with the languages table independently — the geo module does not need to know about it.

---

## Architecture

```
src/
├── Bootstrap/      GeoBindings.php — DI wiring
├── Command/        Self-validating, final readonly value objects
├── Contract/       GeoQueryReaderInterface, GeoCommandRepositoryInterface
├── DTO/            CountryDTO, CityDTO, CountryTranslationDTO, CityTranslationDTO
├── Exception/      GeoExceptionInterface + typed module exceptions
├── Infrastructure/ PdoGeoQueryReader, PdoGeoCommandRepository
└── Service/        GeoQueryService, GeoCommandService
```

