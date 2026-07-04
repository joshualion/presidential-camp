# Member acceptance-test checklist

Run this checklist against each supported package: Presidential, Governorship, Senatorial, Federal Constituency, and Chairmanship.

## Installation and onboarding

- [ ] Enable Public Registration and open the registration page while logged out.
- [ ] Register a member and confirm the success notice appears.
- [ ] Confirm the redirect uses `/member/complete-profile` and the session remains authenticated.
- [ ] Confirm only locations inside the licensed package boundary are offered.
- [ ] Verify the Region → State → LGA → Ward → Polling Unit cascade, including a fixed hidden parent boundary.
- [ ] Submit a polling unit outside the licensed boundary and confirm the server rejects it.
- [ ] Complete the profile and support-group step, then confirm `/member/dashboard` loads.

## Missing-location fail-closed checks

- [ ] Create or import a member with no polling unit.
- [ ] Confirm Internal Communication, Community Forum, Agent facilities, Voting Bloc, Direct Referrals, Notice Board, Election Results, and Agent Workspace are absent from navigation.
- [ ] Request each hidden route directly and confirm HTTP 403 or the onboarding redirect, never data from another scope.
- [ ] Confirm location-scoped recipient, member, notice, election, and agent queries return no records for the unassigned member.
- [ ] Assign a valid polling unit and confirm only the licensed polling-unit scope becomes available.

## Capability and runtime gates

- [ ] Disable Public Registration: registration, My Voting Bloc, and Direct Referrals disappear and their direct routes deny access.
- [ ] Disable Community: Community Forum disappears and direct/API requests deny access.
- [ ] Disable Agents: agent menu/workflows disappear and direct routes deny access.
- [ ] Disable Elections: Election Results and Agent Workspace disappear and direct routes deny access.
- [ ] Use a member without an approved agent assignment: Election Results remains available, but Agent Workspace is hidden and denied.
- [ ] Confirm the planned Polling Unit Details route is hidden and returns HTTP 403.
- [ ] Suspend the member and confirm login fails; invalidate an existing active session and confirm it is logged out.

## Feature acceptance

- [ ] Dashboard shows only notices visible to the member's full location chain.
- [ ] Notice Board excludes drafts, future notices, expired notices, and out-of-scope audiences.
- [ ] Messaging lists only permitted recipients and prevents self-messaging or prohibited upward initiation.
- [ ] Voting Bloc counts only member descendants, handles malformed cycles, and exposes no email or phone data.
- [ ] Direct Referrals is derived from `users.referred_by`, even without a referral audit row.
- [ ] Election reports are polling-unit scoped.
- [ ] Only an approved active assignment can submit results/incidents, and verified/disputed/closed work cannot be amended.

## Route and presentation checks

- [ ] Member URLs use `/member/...`; administrator URLs use their access-level prefix.
- [ ] Old `/dashboard`, `/messages`, `/notifications`, and onboarding GET bookmarks redirect to canonical access-level routes.
- [ ] Every hidden menu facility also carries matching `member.capability:*` middleware.
- [ ] No removed administrator CRUD, polling-unit management, or bulk-SMS screen is reachable under `/member`.
- [ ] Logout is the final menu item and remains a POST action.

## Automated regression commands

```shell
php artisan test tests/Feature/MemberProductContractTest.php
php artisan test tests/Feature/MemberSecurityContainmentTest.php
php artisan test tests/Feature/MemberAccountFacilitiesTest.php
php artisan test tests/Feature/MemberVotingBlocTest.php
php artisan test tests/Feature/MemberNoticeBoardTest.php
php artisan test tests/Feature/MemberElectionParticipationTest.php
php artisan test tests/Feature/CampaignPackageLocationFormServiceTest.php
php artisan test tests/Feature/ScopedMessagingRecipientServiceTest.php
```

The automated suite is necessary but does not replace browser testing of Ajax cascades, menu visibility, uploads, session redirects, and responsive layouts.
