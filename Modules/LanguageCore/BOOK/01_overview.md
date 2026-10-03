# 01. Overview

## What is LanguageCore?

`Maatify/LanguageCore` is the **Identity Provider** for languages in the Maatify ecosystem.

It answers the fundamental questions:
1.  Which languages does this system support?
2.  What are their codes (e.g., `en-US`)?
3.  How should they be displayed (LTR vs RTL, Sort Order)?
4.  What happens if a language is missing data (Fallback)?

## Why Separation?

In previous iterations, language identity was bundled with the translation engine (`maatify/i18n`). This created a circular dependency where you couldn't have a "User Profile" with a preferred language without also pulling in the entire translation governance machinery.

By extracting **LanguageCore**, we allow:
*   **Lightweight Identity:** User profiles, region selectors, and content tagging can depend on `LanguageCore` without needing `I18n`.
*   **Clear Boundaries:** `I18n` becomes a consumer of `LanguageCore`, using the `languages` table as a foreign key reference for translations.

## The Kernel Concept

This module acts as a **Kernel** component.
*   It has **zero** dependencies on other Maatify modules.
*   It enforces strict schema rules.
*   It provides the bedrock for all upper-layer localization logic.
