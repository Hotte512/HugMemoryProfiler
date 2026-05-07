# Changelog

All notable changes to this plugin will be documented here. Format follows [Keep a Changelog](https://keepachangelog.com/).

## [2.0.0] - 2026-05

### Added
- MySQL persistence (table `swp_memory_profile`) replacing file-based logging
- Admin UI module under *Settings → System → Memory Profiler*
- Filter API: context, status code, route substring, time window, min peak
- CLI commands `swp:memory:report` and `swp:memory:clear`
- Daily scheduled task for auto-cleanup (configurable retention)
- Color coding in admin: ≥200 MB critical (red), ≥100 MB warning (orange)

### Changed
- Subscriber now writes to DB instead of `/tmp/sw6-memory.log`
- Plugin uninstall drops the table only when `keepUserData=false`

### Notes
- v1.0 file-based logs are not migrated. Archive or delete `/tmp/sw6-memory.log` manually.

## [1.0.0] - 2026-04

### Added
- Initial release
- Symfony EventSubscriber on `kernel.request` and `kernel.terminate`
- Logs peak memory + duration to `/tmp/sw6-memory.log`
- Context separation: STORE / API / ADMIN
