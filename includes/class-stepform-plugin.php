<?php
namespace StepForm;

if (!defined('ABSPATH')) {
    exit;
}

class Plugin
{
    private static $instance;

    public static function instance(): self
    {
        if (!self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    private function __construct()
    {
        $this->includes();
        add_action('init', [$this, 'register_post_types']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend']);
        add_shortcode('stepform', [$this, 'render_shortcode']);
        add_action('rest_api_init', ['\StepForm\\Rest', 'register_routes']);
    }

    private function includes(): void
    {
        require_once __DIR__ . '/class-stepform-admin.php';
        require_once __DIR__ . '/class-stepform-rest.php';
        require_once __DIR__ . '/class-stepform-render.php';
    }

    public function register_post_types(): void
    {
        register_post_type('stepform_form', [
            'label' => __('Forms', 'stepform'),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => false,
            'supports' => ['title'],
            'rewrite' => false,
        ]);

        register_post_type('stepform_entry', [
            'label' => __('Entries', 'stepform'),
            'public' => false,
            'show_ui' => true,
            'supports' => ['title'],
            'rewrite' => false,
            'show_in_menu' => false,
        ]);

        Admin::instance();
    }

    public function enqueue_admin(): void
    {
        $screen = get_current_screen();
        if (!$screen || (strpos($screen->id, 'stepform') === false && $screen->id !== 'edit-stepform_form')) {
            return;
        }

        wp_enqueue_style('stepform-admin', plugins_url('../assets/admin.css', __FILE__), [], '1.0.0');
        wp_enqueue_script(
            'stepform-admin',
            plugins_url('../assets/admin.js', __FILE__),
            ['wp-element'],
            '1.0.0',
            true
        );

        $config = [
            'nonce' => wp_create_nonce('wp_rest'),
            'restUrl' => rest_url('stepform/v1'),
            'texts' => [
                'addStep' => __('Add New Step', 'stepform'),
                'addField' => __('Add Field', 'stepform'),
                'placeholderFieldLabel' => __('Field Label', 'stepform'),
            ],
        ];
        wp_localize_script('stepform-admin', 'STEPFORM_ADMIN', $config);
    }

    public function enqueue_frontend(): void
    {
        global $post;
        if (!$post || !has_shortcode((string) $post->post_content, 'stepform')) {
            return;
        }

        wp_enqueue_style('stepform-frontend', plugins_url('../assets/frontend.css', __FILE__), [], '1.0.0');
        wp_enqueue_script(
            'stepform-frontend',
            plugins_url('../assets/frontend.js', __FILE__),
            ['wp-api-fetch'],
            '1.0.0',
            true
        );
        wp_localize_script('stepform-frontend', 'STEPFORM_FRONTEND', [
            'restUrl' => rest_url('stepform/v1'),
            'nonce' => wp_create_nonce('wp_rest'),
        ]);
    }

    public function render_shortcode($atts): string
    {
        $atts = shortcode_atts([
            'id' => 0,
        ], $atts);
        $form_id = absint($atts['id']);
        if (!$form_id) {
            return '';
        }

        $form = get_post($form_id);
        if (!$form || $form->post_type !== 'stepform_form') {
            return '';
        }

        $data = get_post_meta($form_id, '_stepform_config', true);
        $config = is_array($data) ? $data : [];

        ob_start();
        Render::frontend($form, $config);
        return ob_get_clean();
    }
}
