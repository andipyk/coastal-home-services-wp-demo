<?php
/**
 * Coastal Home Services demo — frontend polish.
 *
 * The active block theme's global content padding
 * (`--wp--style--root--padding-*`) is `clamp(30px, 5vw, 50px)`, which reads
 * as barely any margin at all on a wide desktop viewport (header, footer,
 * and page content all sit close to the browser edge). This raises the
 * ceiling of that clamp site-wide — header/footer/content all inherit it
 * automatically since they already use the theme's `has-global-padding`
 * class, so this is a one-variable fix rather than patching each template
 * part.
 */

defined('ABSPATH') || exit;

add_action('wp_enqueue_scripts', function () {
    wp_add_inline_style('global-styles', '
        :root {
            --wp--style--root--padding-left: clamp(24px, 6vw, 96px);
            --wp--style--root--padding-right: clamp(24px, 6vw, 96px);
        }
    ');
});
