# kompetenzZ BigBlueButton Module for HumHub

This module integrates a BigBlueButton (BBB) video conferencing solution into your HumHub installation. It allows users to create, join, and manage BBB meetings directly within HumHub spaces and user profiles.

Grateful to benefit from the georgeous [official BBB API](https://github.com/bigbluebutton/bigbluebutton-api-php).

## Features
- Seamless integration of BigBlueButton meetings in HumHub wih stream, search and tagging
- Create and join video conference sessions from spaces or user profiles
- Permission management for managing, starting and joining sessions
- Create public join links
- Customizable via configuration for globals and container contexts
- Set BBB session options (layout, recording, presentation, waiting room)
- View and publish recordings

## Screenshots
See folder docs/screenshots/ or marketplace "Screenshots" tab.

## Installation
Use official humhub marketplace or

1. Copy the `bbb` module directory into your `humhub/modules-custom/` folder.
2. Install dependencies via composer.
3. Enable the module in the HumHub administration area.

## Configuration
- Configure your BBB server URL and shared secret in the module settings after activation.

## Usage
- Navigate globals menu entry or to a space or user profile and use the BBB menu to create or join meetings (after enabling module).
- Permissions for starting and joining sessions can be managed global, per space or per bbb session.

## Tips for Hosting and Connectivity 

### Module Independence
The module itself is completely agnostic regarding the BBB backend. Once a user joins a meeting, the module is no longer involved in the connection process.

### Bare Metal?
If you have access to a dedicated bare-metal server for your BBB deployment, we recommend following the standard installation path provided by BigBlueButton with hard ware like
https://docs.bigbluebutton.org/administration/install/#minimum-server-requirements 

### Container?
If not we recommend the official BBB Docker stack. We host meetings with up to 120 participants, with unrestricted camera usage, and experience no performance issues.

Based on our experience with a highly diverse user base—including setups behind strict firewalls (e.g., public sector networks) we strongly recommend configuring WebRTC connectivity via HTTPS on port 443. A setup without STUN/TURN servers is almost guaranteed to fail. While some proxies may support such a non-http-setup (Apache does not; nginx might), we use a dedicated coturn server to handle TURN/STUN traffic.

## License
This module is licensed under the GNU General Public License v3.0. See the `LICENSE` file for details.

## Authors
- kompetenzZ Team
- Contributors welcome!
