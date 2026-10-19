# Automatic update coverage and metadata compatibility

The optional hosted automatic-update service now covers all official newer releases for one registered installation and campaign scope during a purchased period. The default is 12 calendar months. The Portal owner controls future purchase durations (1–36 months) in Services & Pricing alongside existing NGN scope prices. No recurring debit or automatic renewal is created.

The agreed duration is captured on the purchase. Coverage starts at verified full payment, not the first update or a retry. Existing verified purchases receive 12 months from their original payment date; unpaid or partially paid orders do not receive coverage. Existing paid target-release retries remain available after expiry. Coverage does not transfer between installations or campaign scopes. Refunds, rejected/cancelled orders and invalid payment ledger data deny authorization. After expiry, another purchase is needed for a new release; existing use of the application is unaffected.

The Portal stores separate per-release update attempts under the paid purchase, without duplicate orders. An active reserved attempt must finish or be recovered before another release can be reserved. A temporarily unavailable completion receipt can be retried. An expired coverage period never interrupts an already-authorized completion receipt.

The 1.1.1 public full manifest exceeded the 1 MiB response limit in the original checker. 1.1.2 publishes a compact RSA-signed discovery manifest containing the ZIP hash/size and the exact SHA-256 of the embedded full signed manifest. The new engine verifies both signatures, the embedded manifest hash, the version and migration policy, and every staged file. Legacy full discovery manifests remain supported with an 8 MiB cap. New discovery metadata must stay below the old 1 MiB limit; release building fails otherwise.

The corrected signed bootstrap supports 1.0.8–1.1.1. Install it once on older engines before automatic updating. A 1.1.1 engine expects full outer checksums and also needs this bootstrap to use the compact format. Original update discovery and staged manual download in 1.1.0 can read the compact metadata without modifying application files. Hosts must still meet updater compatibility requirements. No fresh migrations, wipes or seeders are used.

Do not replace published immutable 1.1.1 assets. Publish 1.1.2 with the established release signing identity after local verification and Portal coverage deployment.
