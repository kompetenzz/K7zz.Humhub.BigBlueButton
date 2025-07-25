# kompetenzZ BigBlueButton Module for HumHub

This module integrates a BigBlueButton (BBB) video conferencing solution into your HumHub installation. It allows users to create, join, and manage BBB meetings directly within HumHub spaces and user profiles.

## Features
- Seamless integration of BigBlueButton meetings in HumHub
- Create and join video conference sessions from spaces or user profiles
- Permission management for starting and joining sessions
- Customizable via configuration for globals and container contexts

## Installation
1. Copy the `bbb` module directory into your `humhub/modules-custom/` folder.
2. Install dependencies if required (see below).
3. Enable the module in the HumHub administration area.

## Configuration
- Configure your BBB server URL and shared secret in the module settings after activation.

## Usage
- Navigate globals menu entry or to a space or user profile and use the BBB menu to create or join meetings (after enabling module).
- Permissions for starting and joining sessions can be managed per space or user.

## Development
- Main module class: `Module.php`
- Configuration: `config.php`, `module.json`
- Helpers and services: see `helpers/` and `services/`
- Views: see `views/`

## License
This module is licensed under the GNU General Public License v3.0. See the `LICENSE` file for details.

## Authors
- kompetenzZ Team
- Contributors welcome!
