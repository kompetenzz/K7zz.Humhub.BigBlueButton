## v0.19.8 – 2026-
### Improved
- Pickup logged out members in guest join page
- Fix rendering md/html in sidebar widget
- Fix displaying global sessions overview in global admin menu 
- Fix displaying space sessions overview link in space gear menu for global admins

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

