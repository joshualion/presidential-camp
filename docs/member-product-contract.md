# Member product contract

`users.access_level = user` is the authoritative boundary for the member portal. The Spatie `Member` role supplies action permissions where a feature uses role capabilities, but it does not redefine member identity.

The executable registry is `config/member_access.php`. Menus and routes must use `MemberCapabilityService`; hiding a link is never a substitute for route authorization.

| Capability key | Member surface | Status | Required location | Module/runtime gate | Direct-route enforcement |
| --- | --- | --- | --- | --- | --- |
| `dashboard.view` | Dashboard | Available | None | None | `member.capability:dashboard.view` |
| `profile.manage` | Manage Profile | Available | None | None | `member.capability:profile.manage` |
| `account.security` | Change Password | Available | None | None | `member.capability:account.security` |
| `notifications.view` | Campaign notifications | Available | None | None | `member.capability:notifications.view` |
| `messaging.use` | Internal Communication | Available | Polling unit | None | `member.capability:messaging.use` |
| `community.use` | Community Forum | Available | Polling unit | Community module and runtime setting | `member.capability:community.use` plus module middleware |
| `agents.participate` | Agent application/status | Available | Polling unit | Agents module | `member.capability:agents.participate` plus module middleware |
| `announcements.dashboard` | Dashboard notice summary | Available | None | None | Enforced by dashboard capability and audience query |
| `voting_block.view` | My Voting Bloc | Available | Polling unit | Public Registration enabled | `member.capability:voting_block.view` |
| `referrals.view` | Direct Referrals | Available | Polling unit | Public Registration enabled | `member.capability:referrals.view` |
| `polling_unit.details` | Polling Unit Details | Planned | Polling unit | Planned status always denies | `member.capability:polling_unit.details` |
| `announcements.board` | Notice Board | Available | Polling unit | Published audience match | `member.capability:announcements.board` |
| `elections.reports` | Election Results | Available | Polling unit | Elections module | `member.capability:elections.reports` plus module middleware |
| `elections.submit` | Agent Workspace | Available | Polling unit | Elections module and approved active assignment | `member.capability:elections.submit` plus module middleware |

Unavailable and planned capabilities must fail with HTTP 403 on direct access and must not appear in member navigation.

## Package behavior

The member contract is identical for the five normalized package types: `presidential`, `governorship`, `senatorial`, `federal_constituency`, and `chairmanship`. Package selection changes the licensed geographic boundary, not the meaning of a member capability.

| Package | Licensed boundary used during onboarding | Member requirement after onboarding |
| --- | --- | --- |
| Presidential | National | Polling unit inside the licensed national data set |
| Governorship | State | Polling unit inside the licensed state |
| Senatorial | Senatorial district | Polling unit inside the licensed district |
| Federal constituency | Federal constituency | Polling unit inside the licensed constituency |
| Chairmanship | LGA | Polling unit inside the licensed LGA |

An unassigned member receives licensed-scope onboarding choices but has no structural member scope. Until a polling unit is saved, every capability declaring `requires_location = polling_unit_id` is hidden and returns HTTP 403 when requested directly.

## Route ownership

Authenticated member pages use the `/member` namespace. Shared protected facilities also use the authenticated access-level namespace, such as `/member/messages`, `/member/notifications`, and `/member/complete-profile`. Legacy unprefixed GET entry points may redirect to the canonical route, but forms and Ajax clients must generate canonical named routes.

## View cleanup boundary

The member view namespace contains only executable member-product screens. Administrator member CRUD, polling-unit administration, bulk SMS, and the legacy generic-election copies were removed in Phase 7. Reintroducing any of those screens requires a capability entry, menu gate, route middleware, controller authorization, and acceptance coverage; copying an administrator view into `backend/user` is not sufficient.

## Announcements and notice board

- Every administrator publishes to their assigned jurisdiction; request input cannot expand that scope.
- Superadmin and national administrators publish campaign-wide notices and can manage all announcements.
- Lower administrators can update or delete only notices they created.
- Members see only active notices that are published, unexpired, and match their region/state/electoral-boundary/LGA/ward/polling-unit chain.
- Priority controls ordering; scheduled and expiring notices are filtered by the shared `AnnouncementAudienceService` used by both dashboard and Notice Board.
- Notice details use UUID route binding under the authenticated access-level namespace, for example `/member/notices/{uuid}`, `/pu/notices/{uuid}`, and `/superadmin/notices/{uuid}`.

## Agent election participation

- All onboarded members may view election results limited to their polling-unit scope when the Elections module is enabled.
- Result and incident submissions require both Agents and Elections modules plus a currently approved polling-unit agent assignment.
- Submission URLs carry election and assignment UUIDs; the server derives the polling unit and all reporting boundaries from the approved assignment rather than request input.
- Agents may amend only their own submitted/pending result. Verified, disputed, voided, replaced, suspended, and revoked work is not editable.
- Incident reports are restricted to open elections and the agent's approved polling unit, with at most three image attachments per submission.

## Voting bloc and referral rules

- `users.referred_by` is the authoritative parent relationship used for direct and multi-level reports.
- The superadmin Public Registration switch is the runtime gate for registration, referral links, Direct Referrals, and My Voting Bloc.
- `referrals` is an audit mirror written atomically during public registration; reporting does not depend on that mirror being backfilled for imported users.
- Only member accounts (`access_level = user`) are counted. Administrative accounts are never included in a member's bloc.
- Traversal is cycle-safe and bounded by `member_access.referrals.max_depth` and `max_members` so malformed legacy data cannot cause infinite recursion or unbounded requests.
- Bloc pages expose member name, coarse location, voter eligibility, and account status; they do not expose referred members' email addresses or phone numbers.
