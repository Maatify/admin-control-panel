# I18n Module

**Project:** maatify/admin-control-panel  
**Module:** I18n  
**Namespace:** `Maatify\I18n`  
**Scope:** Shared Internationalization & Language Management  
**Database:** Shared (Single DB, Multi-Consumer)

---

## 1. Purpose

The **I18n module** provides a **single, canonical internationalization system** designed to serve:

- Admin Panel (management & authoring)
- Website (read-only rendering)
- Mobile Applications (read-only rendering)

All consumers operate on **one shared database** with **strict separation of responsibilities**.

This module is **UI-agnostic**, **framework-agnostic**, and **safe for extraction** as a standalone library.

---

## 2. Architectural Principles (LOCKED)

The following rules are **non-negotiable**:

1. **One database – multiple consumers**
2. **Admin is the only write authority**
3. **Web / App are read-only consumers**
4. **Repositories never throw business exceptions**
5. **Services are the only integration point**
6. **Table naming decisions are final**

---

## 3. Domain Separation

The module intentionally separates **System Language** from **Translation Content**.

### 3.1 System Language (Core Entity)

Tables:

```text
languages
language_settings
````

These tables represent **languages as first-class system entities**, not just translations.

They are used for:

* UI language selection
* Text direction (LTR / RTL)
* Sorting and presentation
* Notifications & emails
* Application bootstrap logic
* Fallback relationships

This data is **system-level**, not content-level.

---

### 3.2 I18n Translation Domain

Tables:

```text
i18n_keys
i18n_translations
```

These tables represent **translatable content only**.

They are used for:

* Translation keys
* Language-specific values
* Safe fallback resolution
* Content rendering

The `i18n_` prefix explicitly marks this as a **dedicated domain**, separate from core system entities.

---

## 4. Table Naming Decision (FINAL)

The following naming is **intentional and locked**:

```text
languages            → Core system entity
language_settings    → System/UI extension

i18n_keys            → I18n domain
i18n_translations    → I18n domain
```

This is **not an inconsistency** but a **deliberate architectural boundary**.

❌ Renaming, merging, or re-prefixing these tables is forbidden after this point.

---

## 5. Responsibilities by Consumer

### 5.1 Admin Panel (Write Authority)

The Admin Panel is the **only component allowed to mutate data**.

Allowed operations:

* Create / update / activate languages
* Manage language settings
* Create / rename translation keys
* Insert / update translations

Admin interacts with:

```text
LanguageManagementService
TranslationWriteService
```

---

### 5.2 Website & Mobile Apps (Read-Only)

Web and mobile applications:

* Never write
* Never validate existence
* Never throw exceptions

They interact only with:

```text
TranslationReadService
```

Example usage:

```php
$value = $i18n->getValue('ar', 'login.button.submit');
```

Returned value:

* `string` → resolved translation
* `null` → unresolved (caller decides fallback)

---

## 6. Repository Rules

Repositories are **database-bound adapters only**.

Rules:

* Any PDO failure → return `null` or empty collection
* No business validation
* No exceptions
* No fallback logic
* No assumptions about correctness

Repositories may **defensively validate row structure**, but must stop immediately on invalid data.

---

## 7. Services as the Only Boundary

All business decisions live in **Services**, never in repositories.

```text
Repositories → Raw data access
Services     → Use-cases & orchestration
Consumers    → Call services only
```

This ensures:

* Clean boundaries
* Safe reuse
* Kernel compatibility
* Predictable behavior

---

## 8. Extensibility (Future Phases)

No additional tables are required for the current scope.

Future extensions (NOT part of baseline) may include:

* Multi-tenant language isolation
* Versioned translations
* Per-user overrides
* A/B text experiments
* External translation provider sync

Any such changes require **explicit new phases** and **separate approval**.

---

## 9. Status

✅ Schema: Locked  
✅ Naming: Final  
✅ Responsibilities: Clear  
✅ Multi-consumer ready  
✅ Safe for reuse & extraction

This module is considered **architecturally complete** for its intended scope.
