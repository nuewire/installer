# Changelog

## Unreleased

- Check package versions through GitHub Releases with a stable-tag fallback, without invoking `composer outdated`.
- Added optional `NUEWIRE_GITHUB_TOKEN`, API URL, owner, timeout, and repository override configuration.
- Composer remains required only when an update is installed.

## 2.6.0 - 2026-07-29

- Added the `nuewire/hero` and `nuewire/banner` features.
- Hero and Banner installation resolve Platform, Filesystem, and Platform Logs dependencies.
- Added automatic `nuewire:hero:install` and `nuewire:banner:install` finalization.

## 2.5.0 - 2026-07-29

- Added the `nuewire/media` feature.
- Media installation now resolves Platform, Filesystem, and Platform Logs dependencies.
- Added automatic `nuewire:media:install` finalization.

## 2.4.0 - 2026-07-29

- Added the `nuewire/unduhan` feature.
- Downloads installation now resolves Platform, Filesystem, and Platform Logs dependencies.
- Added automatic `nuewire:unduhan:install` finalization.

## 2.3.0

- Added the `nuewire/newsletter` feature with Platform and Mail dependencies.
- Added setup finalization through `nuewire:newsletter:install`.
- Added Newsletter package status and update coverage.

## 2.2.0

- Added the `nuewire/page` feature with Platform dependency and setup command.
- Added static-page package status and update coverage.

## 2.1.1

- Moved Updates to Settings → Package Management.
- Updated dashboard package widgets to link to the new Settings URL.
- Kept the previous Plugin URL redirecting through Platform's canonical resolver.

## 2.1.0

- Added package coverage and installed-version dashboard widgets.
- Updated bundled package versions for the customizable-dashboard release.

## 2.0.1

- Replaced Livewire `::` class aliases with portable flat aliases for Livewire 3 and 4.
- Updated the package update page alias to `nuewire-updates`.
- Updated bundled path-repository release metadata for the compatibility fix.

## 2.0.0

- Updated the bundled Platform constraint to `^2.0`.
- Moved Updates to Plugin → Package Management.
- Updated bundled path-repository release metadata for the contextual-navigation suite.

## 1.6.0

- Added the `nuewire/cache` feature with Platform dependency.
- Added the `nuewire:cache:install` setup command to installer finalization.

## 1.5.0

- Added the `nuewire/backup` feature with Platform and Filesystem dependencies.
- Added the `nuewire:backup:install` setup command to installer finalization.

## 1.4.0

- Added the `nuewire/logs` feature with Platform dependency and setup command.

## 1.3.0

- Added `nuewire/support` as a managed core package and shared path helpers.

## 1.2.0

- Add Users and ACL feature definitions.
- Resolve feature dependencies and setup commands.
- Register package update permissions.

## 1.1.0

- Added the optional Livewire package update page.
- Added local-environment web updates with fixed Composer arguments and authorization controls.

## 1.0.2

- Publish translations to `lang/vendor/nuewire/installer`.

## 1.0.1

- Register Artisan commands during the service provider registration phase.
- Add regression coverage for all Nuewire command names.

## 1.0.0

- Interactive feature selection.
- Package status and updates.
- Optional signed remote catalog.
