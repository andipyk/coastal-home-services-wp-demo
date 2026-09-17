<?php
/**
 * Idempotent content seed for the "Get a Free Quote" landing page (post 11).
 *
 * Rebuilds its Elementor layout in code instead of by hand in the editor:
 * hero heading -> intro copy -> the WPForms form inside a styled card.
 * Safe to re-run any time the page needs to be reset to this baseline.
 *
 * Run with (the --user is required — Document::save() run as WP-CLI's
 * default anonymous user silently no-ops the front-end publish step, even
 * though it still writes _elementor_data correctly; the editor's own
 * canvas preview looks fine either way, only the live front-end is
 * affected, so always verify against a real curl fetch, not just the
 * editor preview):
 *   docker compose run --rm wpcli eval-file wp-content/mu-plugins/_scripts/seed-landing-page.php --user=admin
 *   docker compose run --rm wpcli elementor flush_css --user=admin
 *
 * This file lives in a subdirectory of mu-plugins so WordPress does NOT
 * autoload it on every request (mu-plugins only autoloads files directly
 * in its root) — it only runs when explicitly passed to `wp eval-file`.
 */

defined('WP_CLI') || exit("Run via WP-CLI: wp eval-file " . __FILE__ . "\n");

$post_id = 11;

$brand = [
    'ink'     => '#12232B',
    'muted'   => '#55666D',
    'accent'  => '#E85A24',
    'card_bg' => '#FFFFFF',
    'border'  => '#E7ECEA',
];

function chs_id(): string {
    return substr(md5(uniqid('', true)), 0, 7);
}

$data = [
    [
        'id'       => chs_id(),
        'elType'   => 'container',
        'isInner'  => false,
        'settings' => [
            'content_width'    => 'boxed',
            'width'            => ['unit' => 'px', 'size' => 820],
            'padding'          => ['unit' => 'px', 'top' => '64', 'right' => '20', 'bottom' => '88', 'left' => '20', 'isLinked' => false],
            'flex_direction'   => 'column',
            'flex_align_items' => 'center',
        ],
        'elements' => [
            [
                'id'         => chs_id(),
                'elType'     => 'widget',
                'widgetType' => 'heading',
                'elements'   => [],
                'settings'   => [
                    'title'                  => 'Request Your Free Quote',
                    'header_size'            => 'h2',
                    'align'                  => 'center',
                    'title_color'            => $brand['ink'],
                    'typography_typography'  => 'custom',
                    'typography_font_size'   => ['unit' => 'px', 'size' => 34],
                    'typography_font_weight' => '800',
                ],
            ],
            [
                'id'         => chs_id(),
                'elType'     => 'widget',
                'widgetType' => 'text-editor',
                'elements'   => [],
                'settings'   => [
                    'editor'                => '<p>Fast, reliable HVAC, plumbing &amp; electrical help for your home. Tell us what&#8217;s going on and we&#8217;ll follow up within one business hour.</p>',
                    'align'                 => 'center',
                    'text_color'            => $brand['muted'],
                    'typography_typography' => 'custom',
                    'typography_font_size'  => ['unit' => 'px', 'size' => 18],
                ],
            ],
            [
                'id'       => chs_id(),
                'elType'   => 'container',
                'isInner'  => true,
                'settings' => [
                    'content_width'              => 'full',
                    'width'                      => ['unit' => 'px', 'size' => 600],
                    '_element_width'             => 'initial',
                    'padding'                    => ['unit' => 'px', 'top' => '40', 'right' => '36', 'bottom' => '40', 'left' => '36', 'isLinked' => false],
                    'margin'                     => ['unit' => 'px', 'top' => '32', 'right' => '0', 'bottom' => '0', 'left' => '0', 'isLinked' => false],
                    'background_background'      => 'classic',
                    'background_color'           => $brand['card_bg'],
                    'border_radius'              => ['unit' => 'px', 'top' => '14', 'right' => '14', 'bottom' => '14', 'left' => '14', 'isLinked' => true],
                    'box_shadow_box_shadow_type' => 'yes',
                    'box_shadow_box_shadow'      => ['horizontal' => '0', 'vertical' => '10', 'blur' => '30', 'spread' => '-6', 'color' => 'rgba(18,35,43,0.16)'],
                    'border_border'              => 'solid',
                    'border_width'               => ['unit' => 'px', 'top' => '1', 'right' => '1', 'bottom' => '1', 'left' => '1', 'isLinked' => true],
                    'border_color'               => $brand['border'],
                ],
                'elements' => [
                    [
                        'id'         => chs_id(),
                        'elType'     => 'widget',
                        'widgetType' => 'shortcode',
                        'elements'   => [],
                        'settings'   => [
                            'shortcode' => '[wpforms id="8"]',
                        ],
                    ],
                ],
            ],
        ],
    ],
];

$document = \Elementor\Plugin::instance()->documents->get($post_id);
if (!$document) {
    WP_CLI::error("Could not load Elementor document for post {$post_id}.");
}
$document->save(['elements' => $data]);

WP_CLI::success("Rebuilt Elementor layout for post {$post_id}.");
