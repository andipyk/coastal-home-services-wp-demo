<?php
/**
 * Coastal Home Services demo — `lead` custom post type.
 *
 * Internal-only content type used to store form submissions as structured
 * WordPress content (not publicly queryable/routable).
 */

defined('ABSPATH') || exit;

add_action('init', function () {
    register_post_type('lead', [
        'label'              => __('Leads', 'coastal-core'),
        'labels'             => [
            'name'          => __('Leads', 'coastal-core'),
            'singular_name' => __('Lead', 'coastal-core'),
            'add_new_item'  => __('Add New Lead', 'coastal-core'),
            'edit_item'     => __('Edit Lead', 'coastal-core'),
            'search_items'  => __('Search Leads', 'coastal-core'),
            'not_found'     => __('No leads found', 'coastal-core'),
        ],
        'public'             => false,
        'publicly_queryable' => false,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => false, // internal only; exposed via our own scoped route instead
        'capability_type'    => 'post',
        'menu_icon'          => 'dashicons-phone',
        'supports'           => ['title'],
        'has_archive'        => false,
        'rewrite'            => false,
        'menu_position'      => 25,
    ]);
});
