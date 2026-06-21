# Phase 3B Community Stabilization Checklist

## Phase 3B-1 Gate And Defaults

- Community Forum is disabled by default for self-hosted releases.
- `community` is the license/module key for the Community Forum module.
- Enabling Community Forum exposes the authenticated community frontend only when the module is available.
- Disabling Community Forum blocks direct community routes.
- AJAX community endpoints return JSON `403` when the module is disabled.
- Self-hosted installs work without Reverb, Redis, supervisor, or websocket ports.
- Polling mode is the default self-hosted realtime mode.
- Reverb/WebSocket mode is optional advanced mode only.
- Settings UI shows module availability and polling/realtime status.
- Community navigation is hidden when the module is unavailable or disabled.
- Installer flow is unaffected by community gating.

## Later Phases

## Phase 3B-2 Internal Communication

- Backend internal communication page opens cleanly when Community Forum is disabled.
- New Message recipient dropdown populates under production root, virtual host root, and temporary `/public` smoke path.
- Selected recipient displays correctly after selection.
- Governorship recipient list stays inside the licensed state.
- Senatorial recipient list stays inside the licensed district.
- LGA recipient list stays inside the licensed LGA.
- Out-of-scope recipient submission is rejected by the backend.
- Sender cannot start a conversation with self.
- Messaging layout uses a clean two-pane view on desktop and stacks cleanly on mobile.
- Message sending works with `BROADCAST_CONNECTION=log` or other non-Reverb defaults.
- Old LiveChat menu entry is hidden from primary navigation.
- Old LiveChat route redirects to Internal Communication.
- Old LiveChat AJAX endpoints do not expose legacy messages or users.

## Later Phases

## Phase 3B-3 Vue/AJAX Routes And Polling

- `window.CampaignManager` exposes base-path-safe community, notification, message, search, and media URLs.
- Community enabled feed loads under temporary `/public` smoke path.
- Posts load without root-relative `/community/posts` requests.
- Comments load, create, update, and delete through generated URLs.
- Likes work through generated URLs.
- Notification bell count and feed load through generated URLs.
- Message widget still works through generated URLs.
- Post images, videos, and user avatars display under the configured base path.
- Disabling Community Forum returns safe redirects or JSON `403`.
- No Reverb running: post, comment, like, notification, and messaging actions still work through polling.
- Browser console has no root-relative 404s for community, posts, comments, notifications, search, messages, or uploads.
- Polling pauses while the tab is hidden where implemented.
- Polling intervals are conservative for shared hosting.

## Phase 3B-4 Moderation And Media Safety

- Community disabled blocks post and comment routes.
- Community unlicensed blocks post creation even if the setting is enabled.
- Community enabled allows a permitted user to create a text post.
- Image upload works within the configured image limit.
- Oversized image upload fails cleanly with validation feedback.
- Video upload is disabled by default for self-hosted installs.
- Video upload can be enabled explicitly and still enforces size/type limits.
- User can delete their own post.
- Unauthorized user cannot delete another user's post.
- ICT Director can moderate in-scope posts/comments.
- Moderator cannot moderate out-of-scope posts/comments.
- Comment owner can delete their own comment.
- Post owner can remove comments from their own post.
- Broadcast/Reverb unavailable does not break post, comment, like, or notification creation.
- Deleted post media is removed from `public/uploads/community` when safe.
- Community settings show image/video controls, upload limits, and realtime/polling status.
- Remaining CDN dependencies are documented for packaging readiness.

## Later Phases

## Phase 3B-5 Community Sidebar, Post Watch, And Audience Targeting

- Finance, donation, store, sponsored placement, gala ticket, wallet, marketplace, and funding request widgets are hidden from the Community sidebar until their modules are implemented and enabled.
- Community sidebar renders without broken finance/payment/store links.
- Community sidebar keeps only safe widgets: Election Watch, Campaign Scope, and Community Guidelines.
- Election Watch shows `No active election watch at the moment.` when no election data exists.
- Election Watch shows an upcoming election countdown when a pending election date exists.
- Election Watch shows scoped top-party results only when active election vote data exists.
- Election Watch does not guess an “our campaign is leading” result when no campaign party/candidate is configured.
- Audience selector opens from the create-post modal.
- Default post audience is the poster's own campaign/location scope.
- `Entire Campaign` means the installed/license campaign scope, not uncontrolled public internet visibility.
- Specific audience choices are limited to in-scope user/package locations.
- Backend validation rejects out-of-scope state, LGA, ward, or polling-unit targets.
- Support group audience is hidden/deferred until group audience behavior is finalized.
- Posts display only to intended audiences and within licensed package scope.
- Old posts without new audience metadata still display through safe legacy visibility rules.

## Later Phases

## Phase 3B-6 Availability, Local Assets, And Messaging Polish

- Self-hosted community toggle is available but off by default when the license does not mention `community`.
- Explicit `community=false` in local license modules locks the Community Forum toggle.
- Explicit `community=true` allows the toggle.
- Enabling Community Forum opens the Community Forum frontend.
- Disabled Community Forum still blocks direct frontend community routes.
- Community frontend loads without CDN/internet dependency for jQuery, Bootstrap, and FontAwesome.
- Backend topbar says `Community Forum`, not `Front-end Community`.
- Internal Communication page has one heading.
- Internal Communication layout is centered/two-pane and not skewed.
- Lower-level user can reply to a higher-level initiated conversation.
- Lower-level user cannot initiate an upward conversation when hierarchy rules disallow it.
- Unread message icon/count appears in the backend topbar.
- Clicking the message icon opens Internal Communication.
- Backend Internal Communication remains available when Community Forum is disabled.
- Messaging works with `BROADCAST_CONNECTION=log` and without Reverb.

## Later Phases

- Packaging readiness should verify all remaining backend/general CDN dependencies and package local replacements where needed.
