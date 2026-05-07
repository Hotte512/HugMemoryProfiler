.PHONY: help install activate deactivate uninstall test stan build watch report clear-old clear-all

help:
	@echo "SwpMemoryProfiler - Dev Targets"
	@echo ""
	@echo "  make install       Install + activate plugin in DDEV"
	@echo "  make uninstall     Deactivate + uninstall + remove data"
	@echo "  make build         Rebuild administration"
	@echo "  make watch         Start hot-reload (separate terminal)"
	@echo "  make test          Run all PHPUnit tests"
	@echo "  make test-unit     Run only unit tests (no DB)"
	@echo "  make stan          PHPStan static analysis"
	@echo "  make report        Show top 20 entries of last 24h"
	@echo "  make clear-old     Delete entries older than 7 days"
	@echo "  make clear-all     Delete ALL entries (with confirmation)"

install:
	ddev exec php bin/console plugin:refresh
	ddev exec php bin/console plugin:install --activate SwpMemoryProfiler
	ddev exec php bin/console database:migrate --all SwpMemoryProfiler
	ddev exec php bin/console cache:clear

activate:
	ddev exec php bin/console plugin:activate SwpMemoryProfiler
	ddev exec php bin/console cache:clear

deactivate:
	ddev exec php bin/console plugin:deactivate SwpMemoryProfiler
	ddev exec php bin/console cache:clear

uninstall:
	ddev exec php bin/console plugin:uninstall SwpMemoryProfiler

build:
	ddev exec ./bin/build-administration.sh

watch:
	ddev exec ./bin/watch-administration.sh

test:
	ddev exec vendor/bin/phpunit --testdox

test-unit:
	ddev exec vendor/bin/phpunit --testsuite=Unit --testdox

stan:
	ddev exec vendor/bin/phpstan analyse src --memory-limit=512M

report:
	ddev exec php bin/console swp:memory:report --hours=24 --limit=20

clear-old:
	ddev exec php bin/console swp:memory:clear --older-than=7

clear-all:
	ddev exec php bin/console swp:memory:clear
