# kompetenzZ BigBlueButton Module for HumHub

Integrate [BigBlueButton](https://bigbluebutton.org) video conferencing directly into your HumHub spaces and user profiles. Create and manage meetings, control access with fine-grained permissions, share public guest links, and review recordings — all from within HumHub.

## Features

**Sessions**
- Create sessions with title, description, topics, and a thumbnail image
- Upload a PDF presentation — automatically loaded into the meeting and previewed as thumbnail
- Set a virtual webcam background image for participants
- Per-session options: layout (Custom, Smart, Presentation Focus, Video Focus), mute on entry, waiting room, recording

**Access & Permissions**
- Three permission levels: *Administer*, *Start*, *Join* — configurable globally, per space, and per session
- Role-based access (space roles) or explicit attendee/moderator lists per session
- Option to let participants start a session or join as moderators

**Public / Guest Access**
- Generate a shareable public join link (token-based) for external guests
- Guests enter a display name and join as viewers — no HumHub account required
- Waiting room support for guest admission control
- Logged-in users visiting a public link are redirected to the internal session page

**Sidebar & Navigation**
- Sidebar widget displays active sessions with a live indicator badge in spaces and on the dashboard
- Configurable navigation label and sidebar sort order per space
- Global "Live Sessions" link in the top navigation (optional)
- Quick-access link in space header for admins

**Recordings**
- View all recordings and their formats (presentation, video, podcast, screenshare, notes) on the session page
- Admin-controlled publish/unpublish per format — recordings are hidden from members until explicitly published

**Global Admin View**
- Overview of all sessions across all spaces and user profiles at `/bbb/sessions`

**HumHub Integration**
- Full content integration: sessions appear in activity streams, are searchable, and support tagging
- Soft-delete support, enabled/disabled state per session

## Screenshots

See [docs/screenshots/](docs/screenshots/) or the marketplace Screenshots tab.

## Documentation

A short user and admin manual is available in [docs/MANUAL.md](docs/MANUAL.md).

## Installation

**Via HumHub Marketplace:** Search for "BigBlueButton" and install directly.

**Manual installation:**
1. Copy the `bbb` directory into your `protected/modules/` or `modules-custom/` folder.
2. Run `composer install` inside the module directory.
3. Enable the module in **Administration → Modules**.
4. Configure your BBB server URL and shared secret in **Administration → BigBlueButton**.

## Requirements

- HumHub ≥ 1.18.0
- A running BigBlueButton server (≥ 2.7 recommended)
- PHP `spatie/pdf-to-image` dependencies (Imagick or GD) for PDF thumbnail generation

## Tips for Hosting & Connectivity

The module is completely agnostic about the BBB backend — once a user joins, the module is no longer involved in the connection.

**WebRTC on port 443:** For users behind strict firewalls (e.g. public sector networks), configure BBB to serve WebRTC over HTTPS on port 443. A setup without STUN/TURN is almost guaranteed to fail in such environments. We use a dedicated [coturn](https://github.com/coturn/coturn) server for TURN/STUN traffic.

**Docker:** The [official BBB Docker stack](https://github.com/bigbluebutton/docker) works well. We run meetings with 120+ participants and full camera usage without performance issues.

**Bare metal:** Follow the [official BBB installation guide](https://docs.bigbluebutton.org/administration/install/) for hardware requirements.

## Credits

Built on top of the excellent [littleredbutton/bigbluebutton-api-php](https://github.com/littleredbutton/bigbluebutton-api-php) library.

## License

GPL-3.0-or-later — see [LICENSE](LICENSE).

## Authors

- Niels Heinemann, kompetenzZ (heinemann@kompetenzz.de)
- Claudia Wiewel, kompetenzZ (wiewel@kompetenzz.de)
- Contributors welcome!
