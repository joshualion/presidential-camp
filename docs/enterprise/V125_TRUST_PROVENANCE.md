# Entitlement trust provenance

`config/enterprise-public.pem` is the public half of the existing production signing identity, derived over the established pinned-SSH deployment workflow after live health/revision checks. No rotation or customer private key was introduced.

- Portal commit: `9418687f33e82edb829fc1925acb64c218982934`.
- Workflow: https://github.com/joshualion/campaigmanager.ng/actions/runs/41852750106
- Production job: `126654815790`.
- RSA: 3072 bits.
- SHA-256 SubjectPublicKeyInfo DER: `e56a049a293cb6c7591769c538433a9e557457ca849e692828247ac6b35c1872`.

The independently trusted Core release signature covers the public PEM. Fresh and upgraded customers receive it automatically. Blank legacy environment values fall back to the bundle; nonempty operator keys are preserved. API responses cannot substitute trust anchors.

Future rotation must ship reviewed public anchors in a signed Core update before changing the Portal identity. The bounded `previous_public_keys` list permits overlap; never populate it from HTTP responses. Remove obsolete anchors through a later signed update. Cached grants remain bounded by signed validity and individual expiry.

`campaign:enterprise:bootstrap` runs after schema/identity initialization, registers permissions idempotently, preserves customer grants and valid caches, and warns recoverably when connectivity is unavailable. Add-ons retries synchronization through the normal interface.
