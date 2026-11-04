# Campaign Manager v1.2.4 validation

Validation-only branch from current main to run the full quality matrix after the SMS add-on module and checkout changes.

Acceptance: full tests and browser smoke, payment and signed entitlement handshakes, MySQL/MariaDB migration compatibility, PHP 8.2/8.3, interface contrast, direct-provider SMS routes and queued sending. Portal SMS wallet payments remain unchanged and **not launched**.

Do not merge, deploy or package before all checks and user-facing sandbox verification pass. Checkout and SMS enforcement remain off until approved rollout and pricing configuration.
