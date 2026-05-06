# Installation

**Via HumHub Marketplace:** Search for "BigBlueButton" and install directly.

**Manual installation:**
1. Copy the `bbb` directory into your `protected/modules/` or `modules-custom/` folder.
2. Run `composer install` inside the module directory.
3. Enable the module in **Administration → Modules**.
4. Configure your BBB server URL and shared secret in **Administration → BigBlueButton**.

## Requirements

- HumHub ≥ 1.18.0
- A running BigBlueButton server (≥ 3 recommended)
- PHP `spatie/pdf-to-image` dependencies (Imagick or GD) for PDF thumbnail generation
