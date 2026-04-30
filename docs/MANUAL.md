# BigBlueButton Module — User & Admin Manual

## Overview

The BigBlueButton (BBB) module integrates video conferencing directly into HumHub spaces and user profiles. It lets you create and manage meetings, control who can start or join, share public guest links, display session widgets in sidebars, and manage recordings — all without leaving HumHub.

---

## 1. Installation & Initial Setup (Global Admin)

1. Install the module and activate it in **Administration → Modules**.
2. Go to **Administration → BigBlueButton** and enter:
   - **BBB Server URL** — the base URL of your BigBlueButton instance (e.g. `https://bbb.example.com`)
   - **Shared Secret** — the API secret from your BBB server (`bbb-conf --secret`)
3. Save. The module is now operational for all spaces.

Optionally configure the **global navigation label** ("Live Sessions" by default) and whether a top-navigation link should appear.

---

## 2. Enabling the Module in a Space

A space admin enables the module under **Space Settings → Modules → BigBlueButton**.

Per-space options (under **Space Settings → BigBlueButton**):

| Setting | Description |
|---|---|
| Show navigation link | Adds a "Live Sessions" entry to the space left sidebar |
| Navigation label | Custom label for the menu item |
| Sidebar widget sort order | Vertical position of the session widget (1 = top, default) |

**Quick start:** Use the "Create Default Session" button to automatically create a ready-to-use default session for the space with sidebar display enabled.

---

## 3. Sessions

### Creating a Session

Navigate to a space's session list and click **New Session**. The form has the following sections:

#### Basic Information

| Field | Notes |
|---|---|
| **Name** | URL slug (lowercase letters, digits, hyphens — must be unique) |
| **Title** | Display name shown to users |
| **Description** | Rich text — appears on the session page and in search results |
| **Topics** | Tag-style labels for filtering and search |
| **Visibility** | Public (visible to all space members) or Private |

#### Meeting Options

| Option | Description |
|---|---|
| **Layout** | Custom, Smart, Presentation Focus, or Video Focus |
| **Mute on entry** | Participants join muted |
| **Allow recording** | Enables recording for this session |
| **Waiting room** | Guests must be admitted by a moderator before joining |
| **Enable public join** | Generates a shareable link for external guests (see [Section 5](#5-public--guest-access)) |
| **Show in sidebar** | Displays the session in the space/dashboard sidebar widget |
| **Space default session** | Marks this as the primary session for the space |

#### Multimedia

| Upload | Constraints |
|---|---|
| **Session image** | PNG/JPG, at least 200 × 200 px — used as the session thumbnail |
| **Presentation (PDF)** | Max 40 MB — uploaded directly into the BBB meeting; first page becomes thumbnail |
| **Webcam background** | PNG/JPG, at least 800 × 400 px — suggested as virtual background for participants |

#### Permissions

By default, access is controlled by the space role system. You can override this for individual sessions:

- **Join by permission** — members with the *Join Session* permission (default: all space members) can join
- **Explicit attendee list** — only the listed users can join
- **Moderate by permission** — members with the *Admin* or *Start Session* permission are moderators
- **Explicit moderator list** — only the listed users are moderators
- **Participants may start** — attendees can start the meeting without a moderator present
- **Participants join as moderators** — all participants automatically get moderator rights

---

## 4. Permissions

Three permissions can be configured at global, space, and individual session level:

| Permission | Default Holders | What it allows |
|---|---|---|
| **Administer sessions** | Space admins/owners | Create, edit, delete sessions; manage all recordings |
| **Start session** | Space moderators and admins | Start (launch) a meeting |
| **Join session** | All space members | Join a running meeting |

Permissions are set in the standard HumHub permissions interface (**Space Settings → Permissions**).

---

## 5. Public / Guest Access

When **Enable public join** is turned on for a session, a shareable link is generated:

```
https://your-humhub.example.com/bbb/public/join/<token>
```

Guests visiting this link:

1. See the session title and a name input field.
2. Enter their display name and click **Join**.
3. Are placed in a waiting room if one is configured, or join directly as a viewer.
4. Always join as **viewer** — they can never start a session or become a moderator.

If a logged-in HumHub user opens a public link, they are automatically redirected to the internal session page.

The public token is regenerated each time the session is saved with public join enabled. Deactivating public join invalidates the old token immediately.

---

## 6. Starting and Joining

- **Start** — Creates the BBB meeting and opens the video conference (in a new window by default).
- **Join** — If the meeting is already running, joining opens the conference directly. If not running yet, the waiting page polls automatically — a **Join now** button appears as soon as the meeting starts.

Members who lack the *Start Session* permission see the waiting page until someone with that permission starts the meeting.

---

## 7. Recordings

If recording is enabled for a session, BBB recordings become available after the meeting ends.

### Viewing Recordings

Recordings are shown on the session detail page. Each recording may contain multiple formats:

| Format | Description |
|---|---|
| `presentation` | Standard shared-screen recording |
| `video` | Webcam video recording |
| `podcast` | Audio-only |
| `screenshare` | Screen capture |
| `notes` | Shared notes |

### Publishing Recordings

By default, recordings are **not visible to regular members**. A session admin must explicitly publish each format:

1. Open the session detail page.
2. Find the recording in the list.
3. Click **Publish** next to the format you want to share.

Published formats become visible to all members who can join the session. Formats can be unpublished at any time.

---

## 8. Sidebar Widget

When **Show in sidebar** is enabled for a session, it appears in the right-column sidebar of the space (and optionally on the global dashboard). The widget shows:

- Session title and thumbnail
- A live indicator badge when the session is running
- A quick-access join/start button

The widget's vertical position within the sidebar is controlled by the **Sort order** value in the space BBB settings (lower numbers appear higher).

---

## 9. Global Sessions List (Admins)

Global admins can access an overview of all sessions across the entire HumHub instance at:

```
/bbb/sessions
```

Sessions are grouped by their container (global, spaces, user profiles). This page is also linked in the administration area if the global navigation item is disabled.

---

## 10. Roles Summary

| Who | Can do |
|---|---|
| Global admin | All of the below, plus global settings and global sessions list |
| Space admin | Create/edit/delete sessions, publish recordings, manage permissions |
| Space moderator | Start sessions |
| Space member | Join running sessions (if *Join Session* permission granted) |
| Guest (public link) | Join as viewer only (session must be running) |
