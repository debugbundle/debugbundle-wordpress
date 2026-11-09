.PHONY: test typecheck coverage verify smoke-wordpress release-artifact wordpress-org-assets

test:
	composer test

typecheck:
	composer typecheck

coverage:
	XDEBUG_MODE=coverage vendor/bin/phpunit --configuration phpunit.xml.dist --coverage-clover coverage.xml
	php scripts/check_coverage.php coverage.xml

verify: test typecheck coverage

smoke-wordpress:
	./scripts/smoke-wordpress.sh

.PHONY: smoke-wordpress-down
smoke-wordpress-down:
	docker compose -p debugbundle-wordpress-smoke -f docker-compose.smoke.yml down --volumes --remove-orphans

release-artifact:
	@if [ -z "$(VERSION)" ]; then echo "VERSION is required" >&2; exit 1; fi
	./scripts/assemble-release.sh $(VERSION)

.PHONY: release-artifact-docker
release-artifact-docker:
	test -n "$(VERSION)"
	docker run --rm -v "$(CURDIR):/app" -w /app debugbundle-php-coverage:local sh -lc './scripts/assemble-release.sh $(VERSION)'

wordpress-org-assets:
	./scripts/generate-wordpress-org-assets.sh

.PHONY: update-php-sdk-lock
update-php-sdk-lock:
	docker run --rm -v "$(CURDIR):/app" -w /app composer:2 update debugbundle/sdk-php --no-install --no-scripts --no-interaction

.PHONY: update-browser-sdk
update-browser-sdk:
	docker run --rm -v "$(CURDIR):/workspace" -w /workspace node:24-alpine sh -lc 'corepack enable && corepack pnpm install --no-frozen-lockfile && corepack pnpm build'

.PHONY: verify-docker
verify-docker:
	docker run --rm -v "$(CURDIR):/app" -w /app --entrypoint sh composer:2 -lc 'composer install --no-interaction --prefer-dist && composer test && composer typecheck'

.PHONY: test-focused test-php-sdk check-php-sdk
test-focused:
	docker run --rm -v "$(CURDIR):/app" -w /app composer:2 composer test -- $(TEST_ARGS)

test-php-sdk:
	docker run --rm -v "$(CURDIR):/app" -v "$(PHP_SDK_CHECKOUT):/app/vendor/debugbundle/sdk-php:ro" -w /app composer:2 composer test -- $(TEST_ARGS)

check-php-sdk:
	docker run --rm -v "$(CURDIR):/app" -v "$(PHP_SDK_CHECKOUT):/app/vendor/debugbundle/sdk-php:ro" -w /app composer:2 sh -c 'composer validate --strict && composer test && composer typecheck'

.PHONY: coverage-php-sdk
coverage-php-sdk:
	docker build -q -f "$(PHP_SDK_CHECKOUT)/smoke/Dockerfile.coverage" -t debugbundle-php-coverage:local "$(PHP_SDK_CHECKOUT)"
	docker run --rm -e XDEBUG_MODE=coverage -v "$(CURDIR):/app" -v "$(PHP_SDK_CHECKOUT):/app/vendor/debugbundle/sdk-php:ro" -w /app debugbundle-php-coverage:local sh -c 'vendor/bin/phpunit --configuration phpunit.xml.dist --coverage-clover coverage.xml && php scripts/check_coverage.php coverage.xml'

.PHONY: staged-release-check
CORE_DEPENDENCIES ?= candidate
staged-release-check:
	python3 scripts/check-staged-release.py --php-sdk "$(PHP_SDK_CHECKOUT)" --browser-artifact "$(BROWSER_ARTIFACT)" --core-dependencies "$(CORE_DEPENDENCIES)"

.PHONY: staged-runtime-check
staged-runtime-check:
	python3 scripts/check-staged-runtime.py --wordpress-version "$(WP_VERSION)" --php-version "$(PHP_VERSION)"

# Use the already qualified staged ZIP; only the disposable harness is refreshed.
.PHONY: evidence-performance
evidence-performance:
	DEBUGBUNDLE_EVIDENCE_PERFORMANCE=1 python3 scripts/check-staged-runtime.py --wordpress-version "$(WP_VERSION)" --php-version "$(PHP_VERSION)"

.PHONY: release-registry-check
.PHONY: release-update-dependencies
release-update-dependencies:
	docker run --rm -v "$(CURDIR):/app" -w /app debugbundle-php-coverage:local sh -lc 'composer update debugbundle/sdk-php --with-dependencies --minimal-changes --no-interaction --prefer-dist'
	docker run --rm -e CI=true -v "$(CURDIR):/app" -w /app node:24-alpine sh -lc 'corepack enable && corepack prepare pnpm@10.32.1 --activate && pnpm install --no-frozen-lockfile && pnpm build:browser'

# Qualify installed registry dependencies without a source overlay.
release-registry-check:
	docker build -q -f "$(PHP_SDK_CHECKOUT)/smoke/Dockerfile.coverage" -t debugbundle-php-coverage:local "$(PHP_SDK_CHECKOUT)"
	docker run --rm -e XDEBUG_MODE=coverage -v "$(CURDIR):/app" -w /app debugbundle-php-coverage:local sh -lc 'composer install --no-interaction --prefer-dist && composer validate --strict && composer test && composer typecheck && vendor/bin/phpunit --configuration phpunit.xml.dist --coverage-clover coverage.xml && php scripts/check_coverage.php coverage.xml && ./scripts/assemble-release.sh $(VERSION)'

# Execute the exact staged asset with current qualification assertions.
.PHONY: staged-browser-check
staged-browser-check:
	docker run --rm -v "$(CURDIR):/workspace:ro" -w /workspace node:24-alpine node scripts/smoke-browser-asset.mjs .tmp/staged-release/wordpress/.smoke/plugin/debugbundle .tmp/staged-release/wordpress/.smoke/browser-inline.js
