# v1.1.1 UI maintenance handover

## Scope

- Official Updates shows installed version, update availability and one primary download/verify action. Opening the screen requests a check when the last successful result is over 15 minutes old.
- Reuse the existing authenticated, throttled check and prepare routes. Public release discovery remains independent of installation registration and transfers no campaign identity.
- Prepared packages remain staged for manual review/application. The interface must not claim an update is installed until application actually occurs.
- Move existing registration/contact/preferences/removal actions to Settings → Installation registration; retain their security and consent behaviour.
- Share the installer stylesheet between setup and server-access confirmation, with clear private-code instructions, accessible errors and narrow-screen layouts.
- Prepare the existing manual publication workflow and release notes for v1.1.1. Preserve v1.1.0 assets and the established signing identity.

Automatic application/rollback of updates is a separate follow-up. No application data, migration behaviour, registration requirements or signing validation was changed.

## Local verification

- Update/installer/registration tests plus release build, integrity and readiness checks verified locally (73 distinct tests). One initial readiness failure was caused by an omitted environment example in the local source copy; after restoring the unchanged upstream example, all six readiness tests passed.
- Frontend production build passed.
- Changed PHP and Blade files compile and pass syntax checks.
- Desktop/mobile browser fixtures checked at 1440px/390px for available/current/prepared updates, registration, server access and validation errors. No page-level horizontal overflow.
- Browser interaction checks confirm one automatic discovery request, refresh on success, and an enabled retry action on failure.
- Visual fixtures stub external resources/background endpoints. No live production verification was performed.

## Delivery gates

Save on a `ui/` review branch; do not open a PR while CI is on hold. Existing workflows trigger on pull requests and main/feat/fix pushes. No CI, merge, deployment, tag creation or publication was performed.

When the owner is ready: open the PR, run CI, review/merge, then manually run the verified v1.1.1 publication workflow. Publication still requires the established owner signing key, legal-readiness setting, registration health check and distribution token. The portal is deployed from its own private repository after its approved checks and merge.
