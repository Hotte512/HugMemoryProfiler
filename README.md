This is a Vibe Coded Plugin I made with Claude, this Version is not tested yet!
I did use the File Based Loggin v1 with SW6.6.10.


# SwpMemoryProfiler

Profiles peak memory usage per HTTP request in Shopware 6. Stores data in MySQL, provides admin dashboard with filters, CLI commands, and daily auto-cleanup.

Built to size PHP `memory_limit` based on real measurements instead of guesses.

## Features

- Per-request peak memory + delta + duration logging
- Context separation: Storefront / API / Admin
- Admin dashboard under *Settings → System → Memory Profiler* with filters (context, time range, route, status code, min peak)
- CLI: `bin/console swp:memory:report` and `swp:memory:clear`
- Daily scheduled task for auto-cleanup (default: 30 days retention)
- Failsafe: DB errors never crash the request

## Requirements

- Shopware 6.6.x
- PHP 8.3+
- MariaDB 10.4+ / MySQL 8.0+

## Installation

```bash
cd custom/plugins/
unzip SwpMemoryProfiler.zip

cd ../..
php bin/console plugin:refresh
php bin/console plugin:install --activate SwpMemoryProfiler
php bin/console database:migrate --all SwpMemoryProfiler
./bin/build-administration.sh
php bin/console cache:clear
```

## Configuration

Edit `src/Resources/config/services.xml`:

```xml
<parameter key="swp_memory_profiler.enabled">true</parameter>
<parameter key="swp_memory_profiler.retention_days">30</parameter>
```

Set `enabled=false` to stop logging while keeping data and admin UI accessible. Roadmap item: move to admin config UI.

## Usage

**Admin UI:** Login → Settings → System → Memory Profiler

**CLI:**

```bash
# Top 20 entries of last 24 hours
php bin/console swp:memory:report --hours=24

# Storefront only, last week, top 50
php bin/console swp:memory:report --context=STORE --hours=168 --limit=50

# Cleanup
php bin/console swp:memory:clear --older-than=7
php bin/console swp:memory:clear --force        # nukes everything
```

## Performance

~1–3 ms overhead per request (single DBAL `INSERT`). At ~50 req/sec the load is negligible. For high-traffic shops (>500 req/sec sustained) consider disabling between profiling sessions.

Recommended workflow: enable for 48h to capture realistic peaks under load, analyze in admin, disable, adjust `memory_limit` in php-fpm pool / php.ini cli.

## Development

See [CLAUDE.md](CLAUDE.md) for architecture details and conventions.

```bash
# Tests
ddev exec vendor/bin/phpunit
ddev exec vendor/bin/phpstan analyse src
```

## License

AGPL-3.0
