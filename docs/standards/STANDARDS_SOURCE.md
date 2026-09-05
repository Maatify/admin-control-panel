# Engineering Standards Snapshot

This project carries a pinned local copy of the engineering standards. The
upstream repository is a read-only source for this project; it is not loaded
from a floating branch at runtime.

- **Upstream repository:** `Maatify/php-engineering-standards`
- **Upstream commit:** `2f5a443edc56db949410865639600945b3b7b28f`
- **Upstream path:** `standards/`
- **Local path:** `docs/standards/`
- **Snapshot date:** `2026-09-05`

The eight standards files under `docs/standards/` were copied from that
commit. The local path mapping is:

- `standards/ai/AI_COLLABORATION_WORKFLOW_AR.md` → `docs/standards/AI_COLLABORATION_WORKFLOW_AR.md`
- `standards/modules/*` → `docs/standards/modules/*`
- `standards/packages/*` → `docs/standards/packages/*`

Admin-specific compatibility corrections are applied only in the local copy
and are listed in
[`ADMIN_ADOPTION_POLICY.md`](ADMIN_ADOPTION_POLICY.md). They do not modify the
upstream repository or claim that this local copy is byte-identical. Trailing
whitespace and redundant end-of-file blank lines were also normalized so the
files pass the repository's required whitespace gate.

Any future upgrade must be handled by a separate reviewable change that
records the new upstream commit and its impact on this project.
