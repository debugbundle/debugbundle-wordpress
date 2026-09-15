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

.PHONY: test-focused test-php-sdk
test-focused:
	docker run --rm -v "$(CURDIR):/app" -w /app composer:2 composer test -- $(TEST_ARGS)

test-php-sdk:
	docker run --rm -v "$(CURDIR):/app" -v "$(PHP_SDK_CHECKOUT):/app/vendor/debugbundle/sdk-php:ro" -w /app composer:2 composer test -- $(TEST_ARGS)
