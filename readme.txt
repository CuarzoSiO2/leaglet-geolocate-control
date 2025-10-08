=== Leaflet Geolocate Control ===
Contributors: SiO2
Donate link: https://www.paypal.com/donate?business=cuarzo_78%40hotmail.com
Tags: leaflet, geolocation, map, gps, marker, calibration, offset, copy, popup
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 3.3.3
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

== Description ==

Leaflet Geolocate Control adds a compact, tactile geolocation button to Leaflet maps focused on precise field validation and reversible behavior.

Key features
* Tactile toggle button for geolocation with short-press to activate/deactivate.
* Edit mode activated by long-press (1.2s) with vibration and orange visual state.
* Uses the pin emoji 📍 as the control icon to guarantee availability across environments.
* Persistent calibration offset saved to localStorage and applied automatically.
* Drag-to-calibrate in edit mode with Save and Reset controls.
* Copyable coordinates in popup with immediate visual feedback.
* Typographic outline for clear visual messaging in popups.
* Floating non-intrusive messages centered on screen for status feedback.
* Uses zoom 16 in normal mode and zoom 18 in edit mode.
* Graceful handling of geolocation failures and reversible UI state.
* No external SVG dependency required.

Designed for field use and institutional workflows: independent, reversible, and visually explicit.

== Installation ==

1. Upload the `leaflet-geolocate-control` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Ensure your theme or map plugin loads Leaflet and exposes the map instance via `WPLeafletMapPlugin.getCurrentMap()` (or adapt insertion to your map provider).
4. (Optional) If you prefer a custom icon instead of the default emoji, replace the control content in the plugin code with your SVG/PNG and adjust styles accordingly.

== Frequently Asked Questions ==

= Where is the donation link shown? =
Add your donation link to the plugin readme (Donate link) and optionally implement an admin notice or Settings panel inside the plugin to show a donation button.

= How does edit mode work? =
Long-press the control for 1.2 seconds to enter edit mode. The button vibrates briefly, turns orange, and the returned marker becomes draggable. Use Save to persist calibration or Reset to clear offsets.

= Where is the calibration stored? =
Calibration offsets are stored locally in the browser's localStorage under the key `geoOffset`. This preserves per-device adjustments without server storage.

= Why use an emoji instead of an image? =
The pin emoji ensures a reliable visual across environments without requiring extra asset files, avoids external hosting, and reduces installation complexity. If you want pixel-consistent branding, include a local SVG/PNG and update the plugin.

= Can I change the zoom levels or icon? =
Yes. Edit the plugin JavaScript: zoom levels are set to 16 (normal) and 18 (edit); change the values and replace the emoji with a custom icon as needed.

= What browsers are supported? =
Modern browsers with the Geolocation API and pointer events. Mobile devices support vibration where available.

== Screenshots ==
1. Control in default state on a Leaflet map (pin emoji).
2. Edit mode (orange) with draggable marker and popup Save/Reset buttons.
3. Popup showing copyable coordinates and confirmation.

== Changelog ==

= 3.3.3 =
* Emoji pin icon (📍) as default control; edit mode, persistent offset, copyable coordinates, vibration, and floating messages.

== Upgrade Notice ==

= 3.3.3 =
First public release with emoji icon default. No automatic upgrade actions required.

== Notes for reviewers ==
* Plugin requires a Leaflet map instance to be available. By default it uses `WPLeafletMapPlugin.getCurrentMap()` to detect the map. If your integration exposes a different API, adjust the insertion function accordingly.
* Calibration is intentionally stored in localStorage to avoid server-side changes and to keep calibrations reversible and device-specific.
* If you prefer a non-emoji icon, include a local asset and adjust the plugin (no external hosting required).
