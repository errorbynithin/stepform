<?php
/**
 * AJAX and REST handlers.
 *
 * @package ProfessionalStepFormBuilder
 */

if (!defined('ABSPATH')) {
    exit;
}

class PSFB_REST
{
    public function __construct()
    {
        add_action('wp_ajax_psfb_save_form', [$this, 'save_form']);
        add_action('wp_ajax_psfb_load_form', [$this, 'load_form']);
        add_action('wp_ajax_psfb_export_entries', [$this, 'export_entries']);
        add_action('wp_ajax_psfb_delete_form', [$this, 'delete_form']);
        add_action('wp_ajax_nopriv_psfb_submit_entry', [$this, 'submit_entry']);
        add_action('wp_ajax_psfb_submit_entry', [$this, 'submit_entry']);
    }

    private function check_nonce()
    {
        check_ajax_referer(PSFB_Plugin::NONCE_ACTION, 'nonce');
    }

    public function save_form()
    {
        $this->check_nonce();

        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $title = sanitize_text_field(wp_unslash($_POST['title'] ?? ''));
        $builder = wp_unslash($_POST['builder'] ?? '[]');
        $settings = wp_unslash($_POST['settings'] ?? '[]');

        $post_data = [
            'post_title' => $title ? $title : __('Untitled Form', 'psfb'),
            'post_type' => PSFB_Plugin::CPT,
            'post_status' => 'publish',
        ];

        if ($form_id) {
            $post_data['ID'] = $form_id;
            wp_update_post($post_data);
        } else {
            $form_id = wp_insert_post($post_data);
        }

        if (is_wp_error($form_id)) {
            wp_send_json_error(['message' => __('Unable to save form', 'psfb')]);
        }

        update_post_meta($form_id, '_psfb_builder', $builder);
        update_post_meta($form_id, '_psfb_settings', $settings);

        wp_send_json_success([
            'id' => $form_id,
            'title' => get_the_title($form_id),
        ]);
    }

    public function load_form()
    {
        $this->check_nonce();
        $form_id = isset($_GET['form_id']) ? absint($_GET['form_id']) : 0;
        if (!$form_id) {
            wp_send_json_error(['message' => __('Form not found', 'psfb')]);
        }
        $form = get_post($form_id);
        if (!$form || $form->post_type !== PSFB_Plugin::CPT) {
            wp_send_json_error(['message' => __('Form not found', 'psfb')]);
        }
        wp_send_json_success([
            'id' => $form->ID,
            'title' => $form->post_title,
            'builder' => get_post_meta($form->ID, '_psfb_builder', true),
            'settings' => get_post_meta($form->ID, '_psfb_settings', true),
        ]);
    }

    public function delete_form()
    {
        $this->check_nonce();
        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        if (!$form_id) {
            wp_send_json_error(['message' => __('Missing form id', 'psfb')]);
        }
        wp_delete_post($form_id, true);
        wp_send_json_success();
    }

    public function submit_entry()
    {
        check_ajax_referer(PSFB_Plugin::NONCE_ACTION, 'nonce');
        $form_id = isset($_POST['form_id']) ? absint($_POST['form_id']) : 0;
        $payload = wp_unslash($_POST['payload'] ?? '[]');
        $fields = json_decode($payload, true);
        $email = sanitize_text_field(wp_unslash($_POST['email'] ?? ''));

        if (!$form_id || !is_array($fields)) {
            wp_send_json_error(['message' => __('Invalid submission', 'psfb')]);
        }

        $builder = get_post_meta($form_id, '_psfb_builder', true);
        $settings = get_post_meta($form_id, '_psfb_settings', true);

        $entries = new PSFB_Entries();
        $inserted_id = $this->store_entry($entries, $form_id, $fields, $email);

        if (!empty($settings)) {
            $this->send_notifications($settings, $fields, $form_id, $email);
        }

        do_action('psfb_after_submit', $inserted_id, $form_id, $fields);

        wp_send_json_success(['id' => $inserted_id]);
    }

    private function store_entry($entries, $form_id, $fields, $email)
    {
        global $wpdb;
        $table = $wpdb->prefix . PSFB_Entries::TABLE;
        $wpdb->insert(
            $table,
            [
                'form_id' => $form_id,
                'form_key' => 'form_' . $form_id,
                'submitted_at' => current_time('mysql'),
                'user_email' => $email,
                'user_name' => sanitize_text_field(wp_unslash($_POST['name'] ?? '')),
                'user_ip' => isset($_SERVER['REMOTE_ADDR']) ? sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR'])) : '',
                'data' => wp_json_encode($fields),
            ],
            ['%d', '%s', '%s', '%s', '%s', '%s', '%s']
        );

        return $wpdb->insert_id;
    }

    private function send_notifications($settings, $fields, $form_id, $email)
    {
        $notifications = new PSFB_Notifications();
        $notifications->dispatch($settings, $fields, $form_id, $email);
    }

    public function export_entries()
    {
        $this->check_nonce();
        $form_id = isset($_GET['form_id']) ? absint($_GET['form_id']) : 0;
        if (!$form_id) {
            wp_send_json_error(['message' => __('Form is required', 'psfb')]);
        }
        global $wpdb;
        $table = $wpdb->prefix . PSFB_Entries::TABLE;
        $entries = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE form_id = %d ORDER BY submitted_at DESC", $form_id));

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="psfb-entries-' . $form_id . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Submitted', 'Email', 'Data']);
        foreach ($entries as $entry) {
            fputcsv($output, [$entry->id, $entry->submitted_at, $entry->user_email, $entry->data]);
        }
        fclose($output);
        exit;
    }
}
