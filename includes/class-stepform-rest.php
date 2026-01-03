<?php
namespace StepForm;

use WP_REST_Request;
use WP_REST_Response;

if (!defined('ABSPATH')) {
    exit;
}

class Rest
{
    public static function register_routes(): void
    {
        register_rest_route('stepform/v1', '/forms/(?P<form>\d+)/submit', [
            'methods' => 'POST',
            'callback' => [self::class, 'handle_submission'],
            'permission_callback' => '__return_true',
        ]);

        register_rest_route('stepform/v1', '/forms/(?P<form>\d+)', [
            'methods' => 'GET',
            'callback' => [self::class, 'get_form'],
            'permission_callback' => '__return_true',
        ]);
    }

    public static function get_form(WP_REST_Request $request): WP_REST_Response
    {
        $form_id = (int) $request['form'];
        $form = get_post($form_id);
        if (!$form || $form->post_type !== 'stepform_form') {
            return new WP_REST_Response(['message' => __('Form not found', 'stepform')], 404);
        }

        $config = get_post_meta($form_id, '_stepform_config', true);
        return new WP_REST_Response([
            'title' => $form->post_title,
            'config' => $config,
        ], 200);
    }

    public static function handle_submission(WP_REST_Request $request): WP_REST_Response
    {
        $form_id = (int) $request['form'];
        $form = get_post($form_id);
        if (!$form || $form->post_type !== 'stepform_form') {
            return new WP_REST_Response(['message' => __('Form not found', 'stepform')], 404);
        }

        $payload = $request->get_json_params();
        $fields = $payload['fields'] ?? [];
        $clean = [];
        foreach ((array) $fields as $key => $value) {
            $clean[sanitize_text_field($key)] = is_array($value) ? array_map('sanitize_text_field', $value) : sanitize_text_field($value);
        }

        $entry_id = wp_insert_post([
            'post_type' => 'stepform_entry',
            'post_status' => 'publish',
            'post_title' => sprintf(__('Submission #%s', 'stepform'), wp_generate_password(4, false)),
        ]);

        if (!$entry_id) {
            return new WP_REST_Response(['message' => __('Unable to save submission', 'stepform')], 500);
        }

        update_post_meta($entry_id, '_stepform_form_id', $form_id);
        update_post_meta($entry_id, '_stepform_entry', $clean);

        do_action('stepform_after_submission', $entry_id, $form_id, $clean);

        return new WP_REST_Response([
            'message' => __('Submission received', 'stepform'),
            'entry' => $entry_id,
        ], 200);
    }
}
