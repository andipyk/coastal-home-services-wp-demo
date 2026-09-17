<?php
/**
 * Coastal Home Services demo — ACF field group for the `lead` post type.
 *
 * Registered in code (ACF "local fields") so the field definitions live in
 * version control instead of only in the database.
 */

defined('ABSPATH') || exit;

add_action('acf/init', function () {
    if (!function_exists('acf_add_local_field_group')) {
        return; // ACF not active — nothing to register.
    }

    acf_add_local_field_group([
        'key'      => 'group_coastal_lead',
        'title'    => 'Lead Details',
        'fields'   => [
            [
                'key'   => 'field_coastal_phone',
                'label' => 'Phone',
                'name'  => 'phone',
                'type'  => 'text',
            ],
            [
                'key'     => 'field_coastal_service_type',
                'label'   => 'Service Type',
                'name'    => 'service_type',
                'type'    => 'select',
                'choices' => [
                    'hvac'       => 'HVAC',
                    'plumbing'   => 'Plumbing',
                    'electrical' => 'Electrical',
                ],
                'default_value' => 'hvac',
            ],
            [
                'key'           => 'field_coastal_status',
                'label'         => 'Status',
                'name'          => 'status',
                'type'          => 'select',
                'choices'       => [
                    'new'       => 'New',
                    'synced'    => 'Synced to CRM',
                    'contacted' => 'Contacted',
                ],
                'default_value' => 'new',
            ],
            [
                'key'          => 'field_coastal_synced_at',
                'label'        => 'Synced At',
                'name'         => 'synced_at',
                'type'         => 'date_time_picker',
                'display_format' => 'Y-m-d H:i:s',
                'return_format'  => 'Y-m-d H:i:s',
            ],
        ],
        'location' => [
            [
                [
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'lead',
                ],
            ],
        ],
    ]);
});
