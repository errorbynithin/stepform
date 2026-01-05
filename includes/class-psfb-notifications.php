<?php
/**
 * Notification handling.
 *
 * @package ProfessionalStepFormBuilder
 */

if (!defined('ABSPATH')) {
    exit;
}

class PSFB_Notifications
{
    public function __construct()
    {
        // Reserved for future hooks.
    }

    public function dispatch($settings, $fields, $form_id, $email)
    {
        $settings = is_array($settings) ? $settings : [];
        $all_fields = $this->compile_fields($fields);

        if (!empty($settings['admin_notification']['enabled'])) {
            $cfg = $settings['admin_notification'];
            $this->send_mail(
                $cfg['to'] ?? get_option('admin_email'),
                $cfg['subject'] ?? __('New submission', 'psfb'),
                $this->parse_template($cfg['body'] ?? '', $all_fields),
                $cfg,
                $all_fields
            );
        }

        if (!empty($settings['user_notification']['enabled']) && $email) {
            $cfg = $settings['user_notification'];
            $this->send_mail(
                $email,
                $cfg['subject'] ?? __('Thanks for submitting', 'psfb'),
                $this->parse_template($cfg['body'] ?? '', $all_fields),
                $cfg,
                $all_fields
            );
        }
    }

    private function send_mail($to, $subject, $body, $cfg, $all_fields)
    {
        $headers = [];
        if (!empty($cfg['from_name']) || !empty($cfg['from_email'])) {
            $from_name = $cfg['from_name'] ?? get_bloginfo('name');
            $from_email = $cfg['from_email'] ?? get_option('admin_email');
            $headers[] = 'From: ' . $from_name . ' <' . $from_email . '>';
        }

        wp_mail($to, $subject, $body ?: $all_fields, $headers);
    }

    private function compile_fields($fields)
    {
        if (!is_array($fields)) {
            return '';
        }
        $lines = [];
        foreach ($fields as $field) {
            $label = $field['label'] ?? $field['name'] ?? __('Field', 'psfb');
            $value = isset($field['value']) ? $field['value'] : '';
            if (is_array($value)) {
                $value = implode(', ', $value);
            }
            $lines[] = $label . ': ' . $value;
        }
        return implode("\n", $lines);
    }

    private function parse_template($template, $all_fields)
    {
        if (!$template) {
            return $all_fields;
        }

        return str_replace('[all_fields]', nl2br(esc_html($all_fields)), $template);
    }
}
