# Changelog

All notable changes to this plugin will be documented here. Format follows [Keep a Changelog](https://keepachangelog.com/).

## [1.0.0] - 2026-04

### Added
- Initial release
- Symfony EventSubscriber on `kernel.request` and `kernel.terminate`
- Logs peak memory + duration to `/tmp/sw6-memory.log`
- Context separation: STORE / API / ADMIN
