<?php
/**
 * Coastal Home Services demo — outbound lead webhook.
 *
 * On every WPForms submission (the site's lead form): create a `lead` post
 * with ACF fields, then POST a JSON payload to an external automation
 * webhook (Make.com / Zapier "Custom Webhook" trigger).
 *
 * The core logic lives in coastal_create_and_notify_lead() so it can be
 * exercised directly from WP-CLI (`wp eval`) without needing a real form
 * submission — see README.md "Testing without a browser".
 */

defined('ABSPATH') || exit;

/**
 * Create a `lead` post from raw (untrusted) input, fire the outbound
 * webhook, and return the new post ID.
 *
 * @param array $data Expected keys: name, phone, service_type.
 * @return int|WP_Error
 */
function coastal_create_and_notify_lead(array $data)
{
    $name  = sanitize_text_field($data['name'] ?? '');
    $phone = sanitize_text_field($data['phone'] ?? '');

    $allowed_services = ['hvac', 'plumbing', 'electrical'];
    $service_type     = sanitize_key($data['service_type'] ?? 'hvac');
    if (!in_array($service_type, $allowed_services, true)) {
        $service_type = 'hvac';
    }

    if ($name === '') {
        return new WP_Error('coastal_missing_name', 'A name is required to create a lead.');
    }

    $lead_id = wp_insert_post([
        'post_type'   => 'lead',
        'post_title'  => $name,
        'post_status' => 'publish',
    ], true);

    if (is_wp_error($lead_id)) {
        return $lead_id;
    }

    if (function_exists('update_field')) {
        update_field('phone', $phone, $lead_id);
        update_field('service_type', $service_type, $lead_id);
        update_field('status', 'new', $lead_id);
    }

    coastal_send_lead_webhook($lead_id, $name, $phone, $service_type);

    return $lead_id;
}

/**
 * POST the lead payload to the configured automation webhook.
 * Failures are logged, never fatal — a down automation tool must not break
 * the site's contact form for the visitor.
 */
function coastal_send_lead_webhook(int $lead_id, string $name, string $phone, string $service_type): void
{
    $webhook_url = getenv('COASTAL_WEBHOOK_URL');

    if (!$webhook_url) {
        error_log("[coastal-core] COASTAL_WEBHOOK_URL not set — skipping webhook for lead #{$lead_id}");
        return;
    }

    $payload = [
        'lead_id'      => $lead_id,
        'name'         => $name,
        'phone'        => $phone,
        'service_type' => $service_type,
        'created_at'   => current_time('mysql'),
        // rest_url() is the browser-facing address, which a cloud automation
        // cannot reach; COASTAL_CALLBACK_URL carries the tunnel URL instead.
        'callback_url' => getenv('COASTAL_CALLBACK_URL') ?: rest_url('coastal/v1/lead-status'),
    ];

    $response = wp_remote_post($webhook_url, [
        'headers' => ['Content-Type' => 'application/json'],
        'body'    => wp_json_encode($payload),
        'timeout' => 10,
    ]);

    if (is_wp_error($response)) {
        error_log('[coastal-core] webhook request failed: ' . $response->get_error_message());
        return;
    }

    $code = wp_remote_retrieve_response_code($response);
    if ($code >= 300) {
        error_log("[coastal-core] webhook returned HTTP {$code} for lead #{$lead_id}");
    }
}

/**
 * Find a WPForms field value by matching a substring of the field label,
 * since WPForms keys $fields by numeric field ID (which depends on how the
 * form was built), not by a stable name.
 */
function coastal_wpforms_field_value(array $fields, string $label_contains): string
{
    foreach ($fields as $field) {
        if (stripos($field['name'] ?? '', $label_contains) !== false) {
            return (string) ($field['value'] ?? '');
        }
    }
    return '';
}

add_action('wpforms_process_complete', function (array $fields, array $entry, array $form_data) {
    coastal_create_and_notify_lead([
        'name'         => coastal_wpforms_field_value($fields, 'name'),
        'phone'        => coastal_wpforms_field_value($fields, 'phone'),
        'service_type' => sanitize_key(coastal_wpforms_field_value($fields, 'service')),
    ]);
}, 10, 3);
