# Changelog

All notable changes to this project will be documented in this file.

The format is based on Keep a Changelog and this project follows Semantic Versioning.

## [Unreleased]

### Changed

- Switched top-level plugin packaging metadata to GPLv2-or-later for WordPress distribution.
- Aligned the REST browser relay route with the shared PHP relay behavior and vendored relay compliance fixtures while keeping WordPress-specific persistent limiting and spool handling.

### Added

- Reusable release assembly script, local release packaging target, and WordPress.org deployment workflow scaffolding.

## [0.1.0] - 2026-05-19

### Added

- Initial standalone WordPress plugin scaffold with backend capture, browser asset build, relay route, spool handling, settings page, and Docker-friendly CI/release workflows.
- Admin backend/frontend test-event actions for setup verification.
- Mock-ingestion WordPress smoke coverage for backend and frontend delivery.
- Admin diagnostics for bundled SDK versions, PHP compatibility, flush status, relay errors, and spool size.
- Mock-ingestion outage coverage for relay spool creation and retry flush.
