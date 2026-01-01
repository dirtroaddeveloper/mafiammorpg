# Backend

This directory will host the server-authoritative game backend. The implementation should:

- Enforce all timers and cooldowns server-side.
- Use Redis for locks, cooldowns, and shared transient state.
- Persist canonical data in PostgreSQL.
- Keep clients thin and treat all requests as untrusted.

## Suggested Structure
- `src/` — Application entrypoint, modules, and shared utilities.
- `src/systems/` — Domain systems (crimes, travel, jail, etc.).
- `src/jobs/` — Scheduled jobs (cooldown expiry, heat decay, economy snapshots).
- `src/api/` — HTTP or RPC handlers with validation and auth.

## Next Steps
- Choose a backend framework and language.
- Define data models and migrations.
- Implement authentication and session handling first.
