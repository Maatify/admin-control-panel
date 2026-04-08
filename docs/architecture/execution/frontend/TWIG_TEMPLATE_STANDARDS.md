# TWIG Template Standards (Canonical v2)

## 1. Base Structure

- Templates extend `layouts/base.twig`.
- Keep inline runtime contract scripts inside `{% block content %}`.
- Keep external script tags inside `{% block scripts %}`.

---

## 2. Runtime Contract Injection

### Required
- `window.{feature}Capabilities = { ... }`
- Place as early as practical in `{% block content %}` so page modules can read it safely.

### Context injection
- If page depends on route/entity context, inject explicit runtime context object:
  - `window.{feature}Context = { ... }`
- Do not rely on implicit URL parsing when context can be injected explicitly.

---

## 3. Table Container Contract (Two-Tier)

### Tier A — default/simple pages
- Use:
```html
<div id="table-container" class="w-full"></div>
```

### Tier B — non-default pages
- Use feature-specific container element id.
- Inject explicit container runtime contract:
```twig
<script>
  window.{feature}TableContainerId = '{feature-table-container-id}';
</script>
```
- JS module must consume this contract via bridge/helper table targeting.

---

## 4. Canonical v2 Script Mount Order

```twig
{% block scripts %}
  {# 1) Required infra scripts #}
  <script src="{{ asset('assets/maatify/admin-kernel/js/api_handler.js') }}"></script>
  <script src="{{ asset('assets/maatify/admin-kernel/js/data_table.js') }}"></script>

  {# 2) Bridge #}
  <script src="{{ asset('assets/maatify/admin-kernel/js/admin-page-bridge.js') }}"></script>

  {# 3) Family helper v2 #}
  <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{family}/{family}-helpers-v2.js') }}"></script>

  {# 4) Page v2 module(s) #}
  <script src="{{ asset('assets/maatify/admin-kernel/js/pages/{family}/{feature}-v2.js') }}"></script>
{% endblock %}
```

Optional UI libs (`select2.js`, `admin-ui-components.js`) remain page-specific based on feature needs.

---

## 5. Controlled Global Policy

Allowed page globals only:
- `window.{feature}Capabilities`
- `window.{feature}Context` (when needed)
- `window.{feature}TableContainerId` (when non-default container)
- reload hooks (prefer `...V2` naming direction for new work)

No additional ad-hoc globals unless required by an explicit compatibility contract.

---

## 6. Superseded Legacy Note

Legacy non-v2 mount examples are superseded for new frontend execution work.
Use canonical v2 mount order and runtime contract policy above.
