# SMS access migration policy for v1.2.5

Fresh installations require a signed SMS Messaging entitlement. The former environment rollout bypass does not authorize SMS, even when an old `.env` sets it to false. The existing user permissions and direct-provider sending policy still apply after installation access is granted.

The additive migration records one immutable initial decision for this installation. A negative decision is recorded too: configuring a provider later or repeating the migration cannot create a grandfather grant. An upgrade receives lifetime legacy direct-provider SMS access only when it already has direct SMS enabled, an active Termii or BulkSMSNigeria account with stored credentials owned by an active user, a configured campaign package and a valid HTTPS application domain. The snapshot records the evidence count and policy name without copying any provider key.

The grant is bound to that domain and campaign package and, where already registered, the existing installation identity. Domain/package changes or registration revocation stop the legacy grant. Normal provider configuration changes do not remove an established legacy grant. No Portal wallet credit or SMS resale licence is granted. This policy preserves legitimate previously configured direct-provider use; it does not grandfather empty Community installations or the unlaunched Portal SMS wallet.

Do not automatically rollback the decision table or reconstruct it on requests. A reviewed database backup is the recovery mechanism for interrupted infrastructure failures. Existing provider credentials, batches, messages, permissions and balances are never changed by this migration.

## Trusted entitlement bootstrap release blocker

The existing Portal signing identity must be preserved. The matching **public** PEM must be obtained through an authenticated operator/release channel and pinned into the signed official application package (or a release-root-authenticated key bundle). Neither an unsigned network response nor a key carried inside its own entitlement establishes trust. The production private key is never needed in Core, committed source or a ZIP. Until the existing public trust anchor is available, zero-touch signed activation cannot be represented as complete.
