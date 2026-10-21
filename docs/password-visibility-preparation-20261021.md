# Password visibility preparation — 21 October 2026

This isolated Core source change adds accessible hidden-by-default reveal/hide controls to shared authentication, administration, account and installer layouts. Existing plaintext current-password inputs are now password inputs. Saved SMTP passwords are omitted from HTML and model serialization; blank replacement input preserves the current value.

No updater, installation registration, payment handoff, signed return or coverage contract was changed. The Portal-only checkout repair can unblock the existing v1.1.5 to published v1.1.8 acceptance test without this Core patch. No live installation or published release asset was modified.

Validation:

- Full existing Core regression: 615 tests passed, 2,683 assertions.
- Added SMTP/password regression: 1 test passed, 10 assertions. Authentication and role checks remain enabled; unrelated onboarding is isolated in this regression.
- DOM execution checks: hidden defaults, independent reveal/hide controls, unchanged entered values, concealment on submit, dynamic fields, unique control IDs and no browser storage.
- Frontend build passed; generated assets exactly match the four existing Git blob hashes on main, so no generated asset change is required.
- PHP lint passed. Composer locked audit and npm production audit found no vulnerabilities.

Release status:

These UI improvements need a later immutable release to reach self-hosted installations. They are not needed for payment/checkout integration. No version bump, tag, candidate signature or publication occurred.

After explicit version/release approval, use the existing private release-candidate workflow with an unused approved version (1.1.9 is the expected successor if still unused). Its tests, MySQL backup tests, signed private candidate, official-archive upgrade rehearsals and recovery checks must pass. The public publication workflow is currently pinned to v1.1.8 and must be intentionally prepared and reviewed for the approved new version before publication; never run the pinned v1.1.8 publisher against this changed source, and never overwrite published v1.1.8 assets.

CI and merge remain required before release preparation is considered verified. Keep this change separate from the urgent Portal deployment.
