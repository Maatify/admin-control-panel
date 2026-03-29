# Managing Translations — System Overview

## 1. Purpose of the Translations System

The Translations System is a localization layer designed to manage all localized text across the platform. It solves the problem of hardcoding strings into applications by allowing administrators to dynamically define, edit, and organize text in multiple languages. Administrators use this system to control exactly what users read in the interface, ensuring consistent terminology and allowing the platform to serve a global audience without requiring code deployments for text changes.

## 2. Core Architecture

The system organizes translations into a four-level hierarchy:

* **Scopes:** The top-level namespace (e.g., admin, client, api). It represents the major application boundary. It connects to Domains to organize specific features.
* **Domains:** A sub-grouping within a Scope (e.g., auth, products, errors). It represents a specific feature or module. A Domain must be explicitly allowed for a Scope before Keys can be created under it.
* **Keys:** The structured registry of valid text identifiers (e.g., login.title). A Key is a strict combination of Scope, Domain, and Key Part. It acts as the anchor for the actual text values.
* **Values:** The actual localized text strings. Each value is tied directly to a specific Key and Language.

## 3. How Translations Work in the System

When the system renders text in the UI, it follows these rules:

1. **Language Resolution:** The system determines the user's requested language.
2. **System Lookup:** The system retrieves the exact translation matching the requested language and key.
3. **Fallback Behavior:** If the exact translation is missing, the system utilizes a strict fallback chain. It checks if the requested language has a fallback language defined. If so, it automatically attempts to load the translation for that fallback language. If both the primary and fallback languages are missing the translation, the system returns the raw key string.

## 4. Admin Interaction Model

Administrators interact with the system through a drill-down flow designed to enforce the strict hierarchy:

1. **Start at Scopes:** The admin begins at the top level, viewing the available namespaces.
2. **Drill down to Domains:** The admin selects a Scope to view its explicitly assigned Domains.
3. **Drill down to Keys:** The admin selects a Domain to view the registry of Keys belonging to that specific Scope and Domain pair.
4. **Actual Editing:** The admin reaches the deepest level (the Translations List) where they manage the actual text values tied to those Keys across different languages.

## 5. Translation Editing Model

* **How values are edited:** Admins edit values directly through the translation interface.
* **How multiple languages are handled:** Translations for multiple languages are attached to the same Key.
* **How updates propagate to the system:** The system uses a strict consistency model. There are no background delays. When a translation is updated, it is immediately applied. Simultaneously, all translation coverage statistics are instantly recalculated to ensure admin dashboards reflect the new coverage immediately.

## 6. Language Dependency

The Translations module depends on the active languages defined in the system.

* **How languages affect translations:** The Translations module does not define what languages exist. It strictly relies on the active languages list. Every translation must be attached to a valid language.
* **What happens when a language is inactive/missing:** If a language is deleted or deactivated, translations tied to that language become unavailable. The system will block any attempts to save a translation for an invalid language.
* **How fallback language is used:** The fallback language is defined in the Languages management section. The Translations module simply reads this fallback configuration during runtime to resolve missing keys.

## 7. System Behavior & Consistency

* **Immediate vs delayed updates:** All updates are strongly consistent and immediate. The system enforces instantaneous updates.
* **Caching:** Translations are updated immediately without requiring cache clears.
* **Impact of changing shared keys:** Because Keys are strictly registered and globally queried by the application, changing the value of a shared key (e.g., changing a generic "Save" button text) will instantly and immediately update that text everywhere the key is referenced across the entire platform.

## 8. Navigation Overview

The high-level navigation structure mirrors the architectural hierarchy:

`Translations` → `Scopes` → `Domains` → `Keys`

## 9. Boundaries of This Module

* **What this module DOES:** It strictly manages the registry of valid text Keys, enforces the Scope and Domain hierarchy, and stores and retrieves the actual localized text strings. It maintains statistics about translation coverage.
* **What it DOES NOT do:** It does NOT manage language identity, language codes, or fallback configurations. Those responsibilities belong entirely to the Languages module.