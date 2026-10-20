# v1.1.4 validation and release handover

Base: `8a345fafd182dc18bcd6892849185119896852b5` (v1.1.3 source).

## Required before publication

1. Run **Prepare official INEC NDC logo** on the release branch. It downloads the official INEC image, normalizes it to PNG, and commits both required local assets. Review the image and commit.
2. Run **Application checks** on PHP 8.2 and 8.3. The release adds PDF, navigation, NDC preservation and signed 1.1.3-to-1.1.4 policy tests.
3. Run **Prepare private release candidate** for version `1.1.4`. Inspect the signed manifest, both NDC assets and the offline-form files in the ZIP.
4. Verify the signed upgrade on an isolated copy of an installed 1.1.3 database and uploads with valid update coverage. Inspect backups, the retained environment and uploads, completed migrations, resulting version, login and menus. Policy tests alone do not prove the complete production update.
5. Publish using the existing **Publish verified Community v1.1.4** workflow only after checks pass and the owner makes the repository public for workflows. Existing 1.1.3 release assets must remain unchanged.

## Initial preparation status

Implementation prepared through GitHub. This session had no local execution environment.
No PHP, browser, Composer, Node, full application upgrade or workflow checks have run.
Official logo URL verified on INEC; downloading/normalizing and committing the asset remains the first workflow task.
No release has been published.

## Reviewed migration

`2026_10_20_000001_add_nigeria_democratic_congress`:
SHA-256 `06036a50125f8670adb74fed01913a89516f62769bc8e222d7cab26a04bac594`.
The migration adds NDC only when absent, fills an empty logo, preserves custom records/logos,
and copies only the new default logo from the bundled managed asset. It does not delete data.
