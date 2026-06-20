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

- Phase 3B-5 should verify packaged built assets and remove frontend CDN dependency.
