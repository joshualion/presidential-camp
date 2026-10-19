# Update discovery: packaged public-key path correction

The official 1.1.0 and 1.1.2 archives ship the trusted release public key at `config/release-public.pem`, but their environment examples explicitly selected `storage/app/release-keys/campaign_release_public.pem`, which is not packaged. This environment override defeats the correct config/release.php default. Existing installations retaining that override can fail signature verification even when the published metadata is valid.

In the existing application's private `.env`, set:

```dotenv
CAMPAIGN_RELEASE_PUBLIC_KEY_PATH=config/release-public.pem
RELEASE_SIGNING_PUBLIC_KEY=
```

Confirm that `config/release-public.pem` exists, then run `php artisan config:clear` from the application directory. Retry the update check. Keep APP_KEY unchanged; do not generate a new release key or disable signature/TLS verification. This correction does not install an update, run migrations or require payment.

Both archives contain the same RSA public key (SHA-256 of SubjectPublicKeyInfo DER: e27c3c9f48b0c9a4cf06a56ca7648f2ee8f0c3020898b85058d87eb07a3f697d). The compact 1.1.2 metadata verifies with that key. The original 1.1.0 checker is byte-identical to the compatibility test copy after the test class rename. Prior compatibility tests supplied the trusted key explicitly and did not exercise the bad environment override.

The source environment examples are corrected on this branch, with a regression test for their bundled-key contract. No published release asset is replaced. Existing installations require the configuration correction; changing the examples alone does not rewrite their .env. A new official package requires a separately verified immutable release.

Archive inspection, public-key fingerprint comparison and independent RSA verification were performed locally. PHP/Pest execution for this follow-up is pending: the earlier portable PHP runtime is no longer available in the execution environment. No CI, deployment or publication is triggered by this branch.
