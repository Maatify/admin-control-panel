# Website UI Theme Module Reference

## Purpose
`maatify/website-ui-theme` provides a schema-first module for managing website UI theme records and exposing dropdown-ready query methods.

## Source of Truth
- Structural and layering style: `Modules/ImageProfile`
- Data model: `Modules/WebsiteUiTheme/schema.sql`
- Primary table: `maa_website_ui_themes`

## Structure
- `src/Command` — write commands (`CreateWebsiteUiThemeCommand`, `UpdateWebsiteUiThemeCommand`)
- `src/Contract` — write and read contracts
- `src/DTO` — entity, collection, and pagination DTOs
- `src/Exception` — module exception family
- `src/Infrastructure/Repository` — PDO command/query implementations
- `src/Service` — command and query services
- `src/Bootstrap` — DI bindings (`WebsiteUiThemeBindings`)

## Public Entry Points
- `WebsiteUiThemeCommandService`
- `WebsiteUiThemeQueryService`
- `WebsiteUiThemeFacade`

## In Scope
- CRUD-style create/update/delete over `maa_website_ui_themes`
- Paginated and lookup query methods
- Dropdown list retrieval for all themes and by `entity_type`

## Out of Scope
- Theme template file-system validation
- Rendering logic
