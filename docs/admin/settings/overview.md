# Managing System Settings — Overview

## 1. Purpose of the Settings Module

The Settings module serves as the central control hub for configuring the platform's behavior and managing its authoritative, long-form content. Administrators use this system to control dynamic application configurations (feature toggles, external integration keys) and legal or policy documents (Terms of Service, Privacy Policies) without requiring developer intervention or application redeployments. It exists to ensure that both the operational parameters of the system and the legally binding agreements presented to users can be governed, versioned, and audited securely.

## 2. Types of Settings in the System

Based on the system's architecture, there are two distinct types of settings managed under this module:

*   **Content Documents:** Represents formal, versioned texts such as legal agreements, privacy policies, or compliance documents. These are not simple text snippets; they are structured entities that track revisions, support multi-language translations per version, and optionally require explicit acceptance from end-users.
*   **Application Settings (AppSettings):** Represents the technical key-value configurations that control application logic. These settings are grouped logically (e.g., `smtp`, `features`, `limits`) and enforce strict data types (e.g., `STRING`, `INTEGER`, `BOOLEAN`) to ensure the platform reads configurations safely.

## 3. Core Architecture of Settings

The system separates configuration into two distinct, database-driven sub-modules:

*   **App Settings Architecture:** This is a strictly typed key-value store. Settings are identified by a composite of `group` and `key` (e.g., `group: "security"`, `key: "max_login_attempts"`). The database stores the `value`, its explicit `valueType` (via `AppSettingValueTypeEnum`), and an `isActive` flag.
*   **Content Documents Architecture:** This is a deeply structured, version-controlled system. It is composed of three hierarchical entities:
    *   **Document Type:** Defines the broad category (e.g., `terms_of_service`). It dictates whether the document is a system requirement and if it defaults to requiring user acceptance.
    *   **Document Version:** A specific iteration of a Document Type (e.g., `Version 2.0`). It holds metadata about its state (e.g., `isActive`, `publishedAt`, `archivedAt`, `requiresAcceptance`).
    *   **Document Translation:** The actual localized, long-form content (title, HTML body) tied to a specific Document Version and a specific Language ID.

## 4. Content Documents (High-Level)

*   **What they represent:** Content Documents represent authoritative content that end-users must read or agree to.
*   **Why they are separated from translations:** While the `I18n` translations system handles short, UI-level text snippets (like "Save Button" or "Welcome Message"), Content Documents handle entire pages of formatted HTML. More importantly, Content Documents are immutable once published. They track distinct versions (so the system knows *exactly* which version of the Terms of Service a user agreed to in 2024 vs. 2026), whereas standard `I18n` translations just overwrite the current text without preserving historical context.
*   **Their role in the system:** They act as the legal and compliance backbone of the platform, enforcing user acceptance gates before allowing access to certain platform features.

## 5. Application Settings (High-Level)

*   **What kind of configuration they control:** They control operational behavior, feature toggling, rate limits, and external API configurations.
*   **Examples:** While specific keys are dynamic, the architecture supports groups and typed values. An example would be a setting grouped under `system` with the key `maintenance_mode`, having a boolean value of `true`.
*   **How they affect system behavior:** Core application services query these settings at runtime. Changing an App Setting instantly modifies how the backend logic behaves without requiring a server restart.

## 6. Admin Interaction Model

*   **How admins access settings:** Administrators navigate to the "Settings" section of the control panel, where they select either the "App Settings" or "Content Documents" management areas.
*   **How they modify system behavior (App Settings):** Admins create or edit a key-value pair, explicitly define its group and data type, and toggle it active or inactive.
*   **How they modify system behavior (Content Documents):** Admins follow a strict, versioned flow. They cannot simply "edit" a published legal document. Instead, they:
    1.  Create a new Document Version under an existing Document Type.
    2.  Provide localized content (Translations) for that specific version.
    3.  Publish the version (which optionally archives older versions).
    4.  The system then immediately begins serving the newly published version to end-users.

## 7. System Behavior

*   **When changes take effect:**
    *   For **App Settings**, changes to a value or its active status take effect immediately across the platform.
    *   For **Content Documents**, changes only take effect when a Document Version transitions its state to `publishedAt` != null and `isActive` = true. Draft versions are completely hidden from the frontend.
*   **Validation or restrictions:**
    *   **App Settings:** The system validates that the provided `value` matches the declared `valueType` (e.g., you cannot save "ABC" if the type is declared as an Integer). It also enforces uniqueness on the `group` + `key` combination to prevent conflicts.
    *   **Content Documents:** The system enforces immutability. Once a Document Version is published and users begin accepting it, its core content is locked to maintain legal integrity. To change the text, an admin must generate a new Version.

## 8. Relationship with Other Modules

*   **Relationship with Languages:** Content Documents heavily rely on the `LanguageCore` module. When an admin writes the actual content for a Document Version, they must attach it to a valid Language ID provided by the platform.
*   **Relationship with Translations (I18n):** The Settings module has **no direct relationship** with the `I18n` module. They solve different problems. `I18n` handles dynamic UI rendering of small strings. Content Documents handle long-form, version-controlled HTML content.
*   **Relationship with Admin System:** Access to manage App Settings and Content Documents is strictly governed by the RBAC (Roles & Permissions) module.

## 9. Boundaries of the Settings Module

*   **What Settings control:** They strictly control dynamic configuration values (key-value pairs) and long-form, versioned platform documents (legal policies, terms, announcements).
*   **What they do NOT control:** They do not control the wording of UI buttons, menu items, or system error messages (handled by `I18n`). They do not control administrator accounts or roles (handled by `AdminKernel`/`RBAC`). They do not control the addition of new system languages (handled by `LanguageCore`).

## 10. Coverage Confirmation

*   **Full Settings system is covered:** Yes, both the `AppSettings` and `ContentDocuments` sub-modules have been explicitly documented based on their underlying PHP architectures.
*   **No assumptions:** Yes, all architectural details (such as `AppSettingValueTypeEnum`, document versioning logic, immutability, and language dependencies) were extracted directly from the system's Domain Entities and DTOs.
*   **Everything is based on real system behavior:** Yes, the distinction between simple text translation and versioned legal document acceptance is accurately reflected based on the presence of `DocumentAcceptance` entities and `publishedAt` properties in the source code.