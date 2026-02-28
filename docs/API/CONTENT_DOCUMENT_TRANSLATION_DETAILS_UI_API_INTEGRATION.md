# 📄 Content Document Translation Management — UI & API Integration Guide

**Project:** `maatify/admin-control-panel`
**Module:** `AdminKernel / ContentDocuments / Translations`
**Audience:** UI & Frontend Developers
**Status:** **CANONICAL / BINDING CONTRACT**

---

# 0) Why this document exists

This document defines the **authoritative runtime contract** for:

> Editing (Upsert) a Content Document Translation.

It explains:

* What the UI is allowed to send
* How routing works (language-driven, not translation-id-driven)
* What validation rules exist
* What the API guarantees
* What errors mean
* What the UI must never do

If something is not described here → treat it as **unsupported behavior**.

---

# ⚠️ CRITICAL: URL Design Change (Language-Centric)

Translations are **NOT accessed by translation_id anymore**.

They are accessed by:

```
/content-document-types/{type_id}/documents/{document_id}/translations/{language_id}
```

This is a **language-driven model**.

There is:

* ❌ No translation ID in the UI
* ❌ No “create new translation” route
* ✅ Only language-based upsert

If translation does not exist → backend creates it.
If it exists → backend updates it.

UI does not need to distinguish.

---

# 1) UI vs API Distinction

## UI Page

```
GET /content-document-types/{type_id}/documents/{document_id}/translations/{language_id}
```

* Returns `text/html`
* Renders Twig
* Injects:

    * capabilities
    * language context
    * translation data (may be empty)
* Must never be called via AJAX

---

## API Endpoint

```
POST /api/content-document-types/{type_id}/documents/{document_id}/translations/{language_id}
```

* Returns `application/json`
* Performs validation
* Applies immutability rules
* Executes create/update
* Returns success envelope

---

# 2) Page Architecture

```
Twig Controller
  ├─ validates route parameters
  ├─ loads document version
  ├─ loads language list (active only)
  ├─ resolves selected language
  ├─ loads translation (or injects empty DTO)
  └─ renders page

JavaScript Module
  ├─ Initializes Jodit editor
  ├─ Applies RTL/LTR safely
  ├─ Handles dark-mode reinit
  ├─ Wires Save button
  └─ Sends API payload

API Controller
  ├─ validates body schema
  ├─ validates document exists
  ├─ validates language exists
  ├─ delegates to facade
  └─ returns canonical success envelope
```

---

# 3) Capabilities (Authorization Contract)

Injected server-side:

```php
$capabilities = [
    'can_view_types' => hasPermission('content_documents.types.query'),
    'can_view_versions' => hasPermission('content_documents.versions.query'),
    'can_view_translations' => hasPermission('content_documents.translations.query'),
    'can_upsert' => hasPermission('content_documents.translations.upsert'),
];
```

---

## Capability → UI Mapping

| Capability  | UI Responsibility |
|-------------|-------------------|
| can_upsert  | Show Save button  |
| !can_upsert | Disable editing   |

UI must NOT infer permissions.

Use only injected flags.

---

# 4) Upsert Translation (API)

## Endpoint

```
POST /api/content-document-types/{type_id}/documents/{document_id}/translations/{language_id}
```

---

## 4.1 Route Parameters

| Parameter   | Type | Required |
|-------------|------|----------|
| type_id     | int  | YES      |
| document_id | int  | YES      |
| language_id | int  | YES      |

If any ≤ 0 → `422` Invalid route parameters.

---

## 4.2 Request Payload

| Field            | Type   | Required | Notes               |
|------------------|--------|----------|---------------------|
| title            | string | YES      | 1–255 chars         |
| meta_title       | string | YES      | May be empty string |
| meta_description | string | YES      | May be empty string |
| content          | string | YES      | Must not be empty   |

⚠️ All fields must exist in payload.
Do not omit optional ones.

---

## 4.3 Example Request

```json
{
  "title": "Privacy Policy",
  "meta_title": "Privacy Policy - Example",
  "meta_description": "How we handle your data",
  "content": "<p>This is the policy...</p>"
}
```

---

## 4.4 Success Response

```json
{
  "success": true
}
```

No payload returned.

UI must not expect translation ID.

---

# 5) Validation Rules (Server-Enforced)

Based on `ContentDocumentTranslationsUpsertSchema`.

### Title

* Required
* Not empty
* Max 255 chars

### Meta Title

* Required key
* May be empty string
* Max 255 chars

### Meta Description

* Required key
* May be empty string
* Max 5000 chars

### Content

* Required
* Not empty

---

## 5.1 Example 422 Error

```json
{
  "success": false,
  "error": {
    "code": 422,
    "type": "VALIDATION_FAILED"
  }
}
```

---

# 6) Immutability Rules (CRITICAL)

Translations cannot be modified if document version is:

* Active
* Published
* Archived

If attempted → backend throws domain exception.

UI should display generic failure message.

---

# 7) Language Switcher Behavior

The page receives:

```js
window.contentDocumentTranslationsContext = {
  languages: [...],
  languageDirection: 'ltr' | 'rtl',
  languageCode: 'en'
};
```

---

## Rules

| Rule                         | Mandatory |
|------------------------------|-----------|
| Use only injected languages  | YES       |
| Never guess language list    | YES       |
| Redirect on selection        | YES       |
| Do not cache across versions | YES       |

---

# 8) Editor Behavior (Jodit)

* Direction is applied safely (does not flip admin layout)
* Dark mode reinitializes editor
* Save button:

    * Disabled during request
    * Restored after response
    * Shows field errors if provided

UI must not manipulate document.body direction.

---

# 9) Runtime Failure Scenarios

| Error        | Cause                  |
| ------------ | ---------------------- |
| 422          | Missing required field |
| 422          | Invalid route param    |
| 404          | Document not found     |
| 404          | Language not found     |
| 403          | Missing permission     |
| Domain error | Version immutable      |

---

# 10) No Pagination (By Design)

This endpoint edits a **single translation**.

There is:

* ❌ No pagination
* ❌ No bulk operations
* ❌ No translation listing here

Single-resource mutation only.

---

# 11) Implementation Checklist (Translation Details)

### ROUTING

* [ ] Use language_id in URL
* [ ] Never use translation_id
* [ ] Do not append query params

---

### FORM

* [ ] Always send all 4 fields
* [ ] Trim strings
* [ ] Prevent empty title
* [ ] Prevent empty content

---

### SAVE

* [ ] Disable button during request
* [ ] Handle 422 properly
* [ ] Show success message
* [ ] Never assume created vs updated

---

### LANGUAGE SWITCHER

* [ ] Use injected list only
* [ ] Redirect using data-value URL
* [ ] Highlight current language

---

# FINAL AUTHORITATIVE RULE

Translations are language-driven, not entity-driven.

The UI must treat:

```
(document_id, language_id)
```

as the canonical identity of a translation.

There is no concept of “new translation” in routing.

Upsert is automatic.

---
