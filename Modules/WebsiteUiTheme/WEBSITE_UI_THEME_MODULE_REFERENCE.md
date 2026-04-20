# Website UI Theme Module Reference

## Purpose
`maatify/website-ui-theme` provides a schema-first dropdown module for retrieving allowed website UI themes.

## Source of Truth
- Structural and layering style: `Modules/ImageProfile`
- Data model: `Modules/WebsiteUiTheme/schema.sql`
- Primary table: `maa_website_ui_themes`

## Structure
- `src/Contract` — read-side contract for dropdown retrieval
- `src/DTO` — dropdown item and collection DTOs
- `src/Exception` — module exception family
- `src/Infrastructure/Repository` — PDO query reader implementation
- `src/Service` — query service and facade
- `src/Bootstrap` — DI bindings (`WebsiteUiThemeBindings`)

## Public Entry Points
- `WebsiteUiThemeQueryService`
- `WebsiteUiThemeFacade`

## In Scope
- General dropdown list of all themes
- Dropdown list filtered by `entity_type`

## Out of Scope
- CRUD management
- Theme file validation against filesystem
- Rendering logic
