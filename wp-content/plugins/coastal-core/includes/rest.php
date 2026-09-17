<?php
/**
 * Coastal Home Services demo — inbound REST callback.
 *
 * Make.com / Zapier calls this once a lead has been pushed into the
 * CRM/Sheet/Slack destination, so WordPress can flip the lead's status to
 * "synced" for the client to see inside wp-admin.
 *
 * Auth: shared-secret header (`x-webhook-secret`), not cookie/nonce auth,
 * since the caller is a third-party automation platform, not a logged-in
 * browser session.
 */

defined('ABSPATH') || exit;

add_action('rest_api_init', function () {
    register_rest_route('coastal/v1', '/lead-status', [
        'methods'             => WP_REST_Server::CREATABLE,
        'permission_callback' => function (WP_REST_Request $request) {
            $expected = getenv('COASTAL_WEBHOOK_SECRET');
            $provided = $request->get_header('x-webhook-secret') ?? '';

            if (!$expected) {
                return new WP_Error(
                    'coastal_not_configured',
                    'COASTAL_WEBHOOK_SECRET is not configured on the server.',
                    ['status' => 500]
                );
            }

            if (!hash_equals($expected, $provided)) {
                return new WP_Error(
                    'coastal_forbidden',
                    'Invalid or missing x-webhook-secret header.',
                    ['status' => 403]
                );
            }

            return true;
        },
        'args' => [
            'lead_id' => [
                'type'              => 'integer',
                'required'          => true,
                'sanitize_callback' => 'absint',
            ],
            'status' => [
                'type'              => 'string',
                'required'          => false,
                'default'           => 'synced',
                'enum'              => ['new', 'synced', 'contacted'],
                'sanitize_callback' => 'sanitize_key',
            ],
        ],
        'callback' => function (WP_REST_Request $request) {
            $lead_id = $request->get_param('lead_id');
            $status  = $request->get_param('status');

            if (get_post_type($lead_id) !== 'lead') {
                return new WP_Error('coastal_not_found', 'Unknown lead.', ['status' => 404]);
            }

            if (function_exists('update_field')) {
                update_field('status', $status, $lead_id);
                update_field('synced_at', current_time('mysql'), $lead_id);
            }

            return rest_ensure_response([
                'ok'      => true,
                'lead_id' => $lead_id,
                'status'  => $status,
            ]);
        },
    ]);
});
