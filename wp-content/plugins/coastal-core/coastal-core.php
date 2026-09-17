<?php
/**
 * Plugin Name: Coastal Core
 * Description: Lead capture for Coastal Home Services: the lead post type, its ACF fields, the outbound webhook and the inbound status callback.
 * Version:     1.0.0
 * Requires at least: 7.0
 * Requires PHP: 8.3
 * Text Domain: coastal-core
 * License:     GPL-2.0-or-later
 */

defined('ABSPATH') || exit;

require_once __DIR__ . '/includes/post-type.php';
require_once __DIR__ . '/includes/acf-fields.php';
require_once __DIR__ . '/includes/lead-webhook.php';
require_once __DIR__ . '/includes/rest.php';
require_once __DIR__ . '/includes/frontend-polish.php';
