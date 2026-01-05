<?php
/**
 * Core plugin bootstrap
 *
 * @package ProfessionalStepFormBuilder
 */

if (!defined('ABSPATH')) {
    exit;
}

class PSFB_Plugin
{
    private static $instance;

    const VERSION = '1.0.0';
    const CPT = 'psfb_form';
    const NONCE_ACTION = 'psfb_nonce_action';

    /**
     * Singleton getter
     */
    public static function get_instance()
    {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->define_constants();
        $this->includes();
        $this->init_hooks();
    }

    private function define_constants()
    {
        define('PSFB_VERSION', self::VERSION);
        define('PSFB_PATH', plugin_dir_path(__FILE__) . '..' . DIRECTORY_SEPARATOR);
        define('PSFB_URL', plugin_dir_url(__FILE__) . '..' . '/');
    }

    private function includes()
    {
        require_once PSFB_PATH . 'includes/class-psfb-admin.php';
        require_once PSFB_PATH . 'includes/class-psfb-frontend.php';
        require_once PSFB_PATH . 'includes/class-psfb-rest.php';
        require_once PSFB_PATH . 'includes/class-psfb-entries.php';
        require_once PSFB_PATH . 'includes/class-psfb-notifications.php';
    }

    private function init_hooks()
    {
        register_activation_hook(PSFB_PATH . 'professional-step-form-builder.php', [$this, 'activate']);
        add_action('init', [$this, 'register_post_type']);
        add_action('plugins_loaded', [$this, 'load_textdomain']);

        new PSFB_Admin();
        new PSFB_Frontend();
        new PSFB_REST();
        new PSFB_Entries();
        new PSFB_Notifications();
    }

    public function activate()
    {
        $this->register_post_type();
        $entries = new PSFB_Entries();
        $entries->create_table();
        flush_rewrite_rules();
    }

    public function load_textdomain()
    {
        load_plugin_textdomain('psfb', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }

    public function register_post_type()
    {
        $labels = [
            'name' => __('Forms', 'psfb'),
            'singular_name' => __('Form', 'psfb'),
            'add_new_item' => __('Add New Form', 'psfb'),
            'edit_item' => __('Edit Form', 'psfb'),
        ];
        register_post_type(self::CPT, [
            'labels' => $labels,
            'public' => false,
            'show_ui' => false,
            'show_in_menu' => false,
            'supports' => ['title'],
        ]);
    }
}
