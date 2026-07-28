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

release-artifact:
	@if [ -z "$(VERSION)" ]; then echo "VERSION is required" >&2; exit 1; fi
	./scripts/assemble-release.sh $(VERSION)

wordpress-org-assets:
	./scripts/generate-wordpress-org-assets.sh
