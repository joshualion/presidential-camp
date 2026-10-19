# Update discovery: packaged public-key path correction

The official 1.1.0 and 1.1.2 archives ship the trusted release public key at `config/release-public.pem`, but their environment examples explicitly selected `storage/app/release-keys/campaign_release_public.pem`, which is not packaged. This environment override defeats the correct config/release.php default. Existing installations retaining that override can fail signature verification even when the published metadata is valid.

In the existing application's private `.env`, set:

```dotenv
CAMPAIGN_RELEASE_PUBLIC_KEY_PATH=config/release-public.pem
RELEASE_SIGNING_PUBLIC_KEY=
```

Confirm that `config/release-public.pem` exists, then run `php artisan config:clear` from the application directory. Retry the update check. Keep APP_KEY unchanged; do not generate a new release key or disable signature/TLS verification. This correction does not install an update, run migrations or require payment.

Both archives contain the same RSA public key (SHA-256 of SubjectPublicKeyInfo DER: e27c3c9f48b0c9a4cf06a56ca7648f2ee8f0c3020898b85058d87eb07a3f697d). The compact 1.1.2 metadata verifies with that key. The original 1.1.0 checker is byte-identical to the compatibility test copy after the test class rename. Prior compatibility tests supplied the trusted key explicitly and did not exercise the bad environment override.

## Automatic configuration in the corrective source branch

- Both environment examples now select the bundled key. The installer validates that key and writes its path when the setting is absent, empty or the known legacy default points to a missing file. It also handles an existing environment file or a copied old template. Existing APP_KEY values and deliberate custom/inline trust settings are preserved.
- The new public-key reader handles the exact missing legacy default by reading the bundled key. An upgrade can therefore retain the customer's .env unchanged, including the old default. Custom missing keys, invalid existing keys and invalid signatures still fail; verification is never disabled. No external key download or storage account is involved.
- Release-readiness checks now block packaging if either environment example reintroduces the wrong path or an inline public-key override.

This source correction cannot change code already installed on a remote server. An unpatched 1.1.0 installation still needs the one-time configuration repair above, or a reviewed manual recovery patch/upgrade carrying the corrected code. After that, ordinary installation and upgrade flows do not require customers to locate public-key files or edit updater settings.

No published asset is replaced. A new official package requires a separately verified immutable release. No CI, deployment or publication is triggered by this branch.

Validation: replayed the actual 1.1.0 archive's environment template and public key through the corrected installer, verified actual published 1.1.2 metadata without an inline key override, and confirmed altered metadata is rejected under both the bundled and legacy path configurations. Local PHP 8.3 validation: full suite completed with 456 passes and one runtime-only failure because the reconstructed PHP environment lacked GD. After installing GD and its library, that exact image-upload test passed (2 assertions). In total, all 457 tests were verified, with 1,976 assertions across the suite and targeted retry. All new trust/installer tests passed in the full suite; release build, readiness, key commands, integrity and updater checks also passed. Changed PHP files were formatted with Pint. GitHub CI remains unrun for this private corrective branch.
