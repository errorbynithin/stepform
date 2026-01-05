<?php
/**
 * Admin UI and builder.
 *
 * @package ProfessionalStepFormBuilder
 */

if (!defined('ABSPATH')) {
    exit;
}

class PSFB_Admin
{
    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue']);
    }

    public function register_menu()
    {
        add_menu_page(
            __('Professional Step Forms', 'psfb'),
            __('Step Forms', 'psfb'),
            'manage_options',
            'psfb-forms',
            [$this, 'render'],
            'dashicons-feedback',
            58
        );

        add_submenu_page(
            'psfb-forms',
            __('All Forms', 'psfb'),
            __('All Forms', 'psfb'),
            'manage_options',
            'psfb-forms',
            [$this, 'render']
        );

        add_submenu_page(
            'psfb-forms',
            __('Add Form', 'psfb'),
            __('Add Form', 'psfb'),
            'manage_options',
            'psfb-add-form',
            [$this, 'render_add']
        );
    }

    public function enqueue($hook)
    {
        if (strpos($hook, 'psfb') === false) {
            return;
        }

        wp_enqueue_style('psfb-admin', PSFB_URL . 'assets/css/admin.css', [], PSFB_VERSION);
        wp_enqueue_script('psfb-admin', PSFB_URL . 'assets/js/admin.js', ['wp-element'], PSFB_VERSION, true);

        wp_localize_script('psfb-admin', 'PSFB_DATA', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(PSFB_Plugin::NONCE_ACTION),
            'fieldLibrary' => $this->field_library(),
            'forms' => $this->get_forms_list(),
        ]);
    }

    public function render()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $forms = $this->get_forms_list();
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php esc_html_e('Step Forms', 'psfb'); ?></h1>
            <a href="<?php echo esc_url(admin_url('admin.php?page=psfb-add-form')); ?>" class="page-title-action"><?php esc_html_e('Add New', 'psfb'); ?></a>
            <hr class="wp-header-end" />
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Title', 'psfb'); ?></th>
                        <th><?php esc_html_e('Shortcode', 'psfb'); ?></th>
                        <th><?php esc_html_e('Actions', 'psfb'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$forms) : ?>
                        <tr><td colspan="3"><?php esc_html_e('No forms yet', 'psfb'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($forms as $form) : ?>
                            <tr>
                                <td><?php echo esc_html($form['title']); ?></td>
                                <td>[psfb_form id="<?php echo esc_attr($form['id']); ?>"]</td>
                                <td>
                                    <a class="button" href="<?php echo esc_url(admin_url('admin.php?page=psfb-add-form&form_id=' . $form['id'])); ?>"><?php esc_html_e('Edit', 'psfb'); ?></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php
    }

    public function render_add()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $form_id = isset($_GET['form_id']) ? absint($_GET['form_id']) : 0;
        $form = null;
        if ($form_id) {
            $form_post = get_post($form_id);
            if ($form_post) {
                $form = [
                    'id' => $form_post->ID,
                    'title' => $form_post->post_title,
                    'builder' => get_post_meta($form_post->ID, '_psfb_builder', true),
                    'settings' => get_post_meta($form_post->ID, '_psfb_settings', true),
                ];
            }
        }
        ?>
        <div class="wrap">
            <h1><?php echo $form_id ? esc_html__('Edit Form', 'psfb') : esc_html__('Add Form', 'psfb'); ?></h1>
            <div id="psfb-builder-root" data-form='<?php echo wp_json_encode($form); ?>'></div>
        </div>
        <?php
    }

    private function get_forms_list()
    {
        $forms = get_posts([
            'post_type' => PSFB_Plugin::CPT,
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);

        return array_map(function ($form) {
            return [
                'id' => $form->ID,
                'title' => $form->post_title,
            ];
        }, $forms);
    }

    private function field_library()
    {
        return [
            ['type' => 'text', 'label' => __('Text', 'psfb'), 'icon' => 'dashicons-editor-textcolor'],
            ['type' => 'email', 'label' => __('Email', 'psfb'), 'icon' => 'dashicons-email'],
            ['type' => 'tel', 'label' => __('Phone', 'psfb'), 'icon' => 'dashicons-phone'],
            ['type' => 'url', 'label' => __('URL', 'psfb'), 'icon' => 'dashicons-admin-links'],
            ['type' => 'number', 'label' => __('Number', 'psfb'), 'icon' => 'dashicons-editor-ol'],
            ['type' => 'date', 'label' => __('Date', 'psfb'), 'icon' => 'dashicons-calendar-alt'],
            ['type' => 'time', 'label' => __('Time', 'psfb'), 'icon' => 'dashicons-clock'],
            ['type' => 'textarea', 'label' => __('Textarea', 'psfb'), 'icon' => 'dashicons-editor-paragraph'],
            ['type' => 'select', 'label' => __('Select', 'psfb'), 'icon' => 'dashicons-arrow-down-alt2'],
            ['type' => 'radio', 'label' => __('Radio', 'psfb'), 'icon' => 'dashicons-marker'],
            ['type' => 'checkbox', 'label' => __('Checkbox', 'psfb'), 'icon' => 'dashicons-yes'],
            ['type' => 'file', 'label' => __('File Upload', 'psfb'), 'icon' => 'dashicons-upload'],
            ['type' => 'hidden', 'label' => __('Hidden', 'psfb'), 'icon' => 'dashicons-hidden'],
            ['type' => 'html', 'label' => __('HTML Block', 'psfb'), 'icon' => 'dashicons-welcome-write-blog'],
            ['type' => 'password', 'label' => __('Password', 'psfb'), 'icon' => 'dashicons-lock'],
            ['type' => 'name', 'label' => __('Name', 'psfb'), 'icon' => 'dashicons-admin-users'],
            ['type' => 'address', 'label' => __('Address', 'psfb'), 'icon' => 'dashicons-admin-home'],
            ['type' => 'repeater', 'label' => __('Repeater', 'psfb'), 'icon' => 'dashicons-list-view'],
        ];
    }
}
