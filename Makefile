.PHONY: smoke-wordpress release-artifact wordpress-org-assets

smoke-wordpress:
	./scripts/smoke-wordpress.sh

release-artifact:
	@if [ -z "$(VERSION)" ]; then echo "VERSION is required" >&2; exit 1; fi
	./scripts/assemble-release.sh $(VERSION)

wordpress-org-assets:
	./scripts/generate-wordpress-org-assets.sh