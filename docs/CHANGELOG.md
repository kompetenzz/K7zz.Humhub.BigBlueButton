## v1.0.0 – 2026-07-06
### New
- **BBB chat integration: SyncroChat**: Meeting chat is now bridged between HumHub and BBB in both directions, via a new BBB meeting webhook (meeting-started/ended, user-joined/left, chat messages, recording state, recording published).
  - Messages written in the HumHub session chat box are relayed live into the BBB in-meeting chat, and vice versa.
  - Messages written before the meeting has started are queued and automatically injected into BBB once the meeting starts.
  - The chat history shows dividers for day changes, meeting boundaries ("Meeting Start" / "Meeting End" with time) and recording state ("Recording started/stopped").
  - Avatars show the HumHub profile picture when the BBB participant can be matched to a HumHub account, otherwise a colored initial bubble (also for anonymous/guest participants).
  - Toggle via a new global setting ("Enable session chat") and a per-session option; disabled by default.
- **Emoji reactions in the session chat**: React to any chat message with 👍 ❤️ 😂 😮 🎉 via a hover picker; a click on an existing badge toggles your own reaction. The message author receives a notification. Reactions live in HumHub only — BBB has no reaction API, so they are not visible inside a running meeting.
- **Message formatting**: URLs in chat messages are automatically linked (opening in a new tab), and WhatsApp-style inline markup is rendered: `*bold*`, `_italic_`, `~strikethrough~` and `` `code` ``. Input is HTML-encoded before formatting, so no markup can be injected.
- **Chat & recording notifications with own categories**: Moderators are notified when someone writes in the session chat (message text included); users are notified when someone reacts to their message and when a recording of a session they can join becomes available. Each type is a separate category in the personal notification settings ("BBB chat message received", "BBB chat reaction received", "BBB recording available", defaults: web; recordings also by e-mail).
- **Webhook health warning**: If webhook registration with the BBB server fails on session start, the module now warns on all channels instead of failing silently: a log entry, a notification to the session's moderators, a red banner on the session page (visible to users who can start the session), and a bold moderator-only message inside the BBB meeting chat. If webhook events do arrive later, the banner clears itself.

### Fixed
- **Session-state polling caused BBB server overload**: Every `is-running` poll (used for the "Active" badge and the join-waiting page) triggered a live, synchronous API call to the BBB server. During a session with many participants, this added up to a steady stream of blocking outbound requests that competed with the meeting's own media load, degrading the whole HumHub instance. `isRunning()` now answers from the local `bbb_session_meeting` table (kept current via the same BBB webhook introduced for chat, which is now always registered for every session regardless of the chat-integration setting), and only falls back to a live BBB call - throttled to at most once per 15s per session - if no local record is found (e.g. webhook not delivered).
- **Notifications were silently dropped**: Session-start (and other) notifications were stored but never delivered for space-hosted sessions because the category default did not enable the web target; additionally, self-directed system warnings were suppressed by HumHub's originator check. Web notifications are now enabled by default for all BBB categories, and all categories are listed in the module config so they appear in the personal notification settings.
- **Chat polling never started mid-visit**: The 5-second chat refresh only started when the meeting was already running at page load. The chat box now listens to the session-state poller and starts/stops its refresh dynamically when the meeting starts or ends.
- **Empty right column**: When chat is disabled for a session and no recordings exist, the session page no longer shows an empty gray right column; the info card is centered instead. With recordings but no chat, the recordings box is shown without height limit.

### Improved
- **Session state polling**: The client-side poll timer now pauses while its browser tab is hidden, and backs off to a 10-minute interval once a meeting has been confirmed running (only "did it end" is left to detect, which is far less time-sensitive than "did it start"). While still waiting for a meeting to start, polling continues at the normal interval even in a hidden tab, so someone waiting in a background tab won't miss a meeting that starts and ends while they're away. After clicking Start/Join, a few quick follow-up polls pick up the new meeting state within seconds.
- **Chat auto-refresh**: The refresh keeps your scroll position while you read older messages (it only follows new messages when you are already at the bottom) and no longer closes an open reaction picker.
- **Recordings box**: Fixed height with its own scrollbar below the chat; the chat scrolls to the newest message only after the recordings box has finished loading, so the position no longer jumps.
- **Module uninstall**: The uninstall migration now drops all module tables (meetings, chat, reactions, joins, recording formats), not just the two original ones.

## v0.19.15 – 2026-06-29
### Fixed
- **Start with hidden presentation**: The join-API user-data key used to hide the default presentation on login was incorrect (`bbb_presentation_hidden_on_login`, not a valid BBB parameter) and was silently ignored by BBB. Corrected to `bbb_hide_presentation_on_join`.

### Improved
- **Session state polling**: Reduced polling frequency for live session state indicators (sidebar/list "Active" badges) from 5s to 60s to lower server load.

## v0.19.14 – 2026-06-03
### New
- **Custom Pages Element Extension**: Two new element types for the HumHub Custom Pages module.
  - **BBB Session** (single): Renders one selected session as a sidebar-style panel (image, title, live badge, description, start/join buttons). Session is chosen via a grouped dropdown (Global → Spaces → Users) in the template editor.
  - **BBB Sessions** (list): Renders a compact, filterable session list as a grid table (thumbnail | title + live badge | topics + description | buttons). Supports filters "Active sessions only" and "Sidebar sessions only", and an additional sort option "By title (A–Z)".

## v0.19.13 – 2026-05-28
### Improved
- **Fallback navigation for hidden nav items**: When the BBB navigation item is hidden (addNavItem = false), the admin/owner- link now is always labeled "Video-Sessions" regardless of the configured nav item title.

## v0.19.12 – 2026-05-20
### New
- **Notifications on session start**: When a session is started, all explicitly assigned attendees and moderators receive a notification with a call icon. In sessions hosted on a user profile, the profile owner is additionally notified if they are not already among the invitees.
- **Configurable start notifications**: Start notifications are now a named category in HumHub's notification settings ("BBB session started"), allowing each user to opt in or out per target (web, e-mail, mobile). The default is ON for sessions on user profiles and OFF for sessions in spaces.
- **Per-session notification toggle**: New "Enable start notifications" checkbox in the session edit form. Administrators can disable start notifications entirely for individual sessions, regardless of each user's personal preference.

### Improved
- **User profile sessions**: Sessions created in a user profile now correctly appear in the profile right-column sidebar ("Show in right column" now works for profiles).
- **User profile sessions**: The default session title in the sidebar shows "Meet me".
- **User profile sessions**: The session edit form no longer shows "Join by permissions", "Moderate by permissions" and Topics for profile-hosted sessions, as these options are not meaningful in that context.
- **Session edit form**: Labels and hints for "Default session" checkbox now correctly reflect the context (Space / Profile / Global).

## v0.19.11 – 2026-05-12
### New
- **Start with right bar collapsed**: New session option to collapse the participants panel (right bar) when participants join. Chat is automatically unavailable when the right bar is collapsed.
- **Start with chat minimized**: New session option to start with the public chat panel collapsed (only available when the right bar is open).
- **Start with hidden presentation**: New session option to hide the presentation when participants join the session.

### Fixed
- Screenshots in documentation

## v0.19.10 – 2026-05-06
### Fixed
- Consistent path-based public join URLs (`/bbb/public/join/TOKEN`) across all views and form
- Routing accepts both path-based and query-param format (`?token=TOKEN`) for backwards compatibility

## v0.19.9 – 2026-04-30
### Improved
- Restructured session edit form with grouped cards (Basic Information, Visibility, In-Session, Join & Moderation)
- New FilePreviewField widget for consistent image/file upload handling
- Added MANUAL.md and improved README.md

## v0.19.8 – 2026-04-30
### Improved
- New screenshots
- Optimize edit page
- Pickup logged out members in guest join page
- Fix rendering md/html in sidebar widget
- Fix displaying global sessions overview in global admin menu 
- Fix displaying space sessions overview link in space gear menu also for global admins

## v0.19.7 – 2026-04-21
### Improved
- Optimize breakout room handling in tabbed context

## v0.19.6 – 2026-04-20
### Improved
- Fix eternal and aggressive session-state checks via javascript
- Better (key) icon for moderation permissions

## v0.19.5 – 2026-04-17
### New
- **Global sessions list**: Admins may view all sessions of the humhub instance in the global sessions list accessible via /bbb/sessions

### Improved
- Optimize waitingroom/join pages for members and guests. 
- Live reflect session status (running/paused) in session views
- Admins see bbb-sessions-page links in the account menu or space gear dropdown if menu items are disabled by config
- Detect logged in user on public gust join page and redirect to member page

## v0.19.2 – 2026-03-27

### New

- **Session detail page**: Each session now has its own page showing the image, description, topics and status — accessible by clicking the session tile or title.
- **Smarter waiting room**: Members waiting for a session to start no longer experience repeated page reloads. As soon as the session starts, a "Join now" button appears automatically.
- **Join in a new window**: The "Join now" button opens the video conference in a new window, keeping the current page in the background.
- **Recordings on the session page**: Available recordings are now also shown on the session detail page.
- **Guest links wait too**: Guests joining via a public link also get the smart waiting room with an automatic "Join now" button when the session starts.
- **Sidebar widgets for global dashboard**: Use the new sidebar widget settings to display boxes of space sessions in the global dashboard.
- **Live indicator badge** for running sessions in the sidebar widget


### Improved

- Fixed an issue where recordings could load indefinitely on slow connections.
- Shorter, cleaner URLs for session pages.
- Global topics back working from current humub master on

## v0.19.1 - 2026-03-25

### New

- **Sidebar widgets**: Use the new sidebar widget settings to display boxes of space sessions.

### Improved

- Better thumbnail image handling

