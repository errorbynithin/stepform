<?php
/**
 * Entries manager.
 *
 * @package ProfessionalStepFormBuilder
 */

if (!defined('ABSPATH')) {
    exit;
}

class PSFB_Entries
{
    const TABLE = 'psfb_entries';

    public function __construct()
    {
        add_action('admin_menu', [$this, 'register_menu']);
    }

    public function create_table()
    {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        $table = $wpdb->prefix . self::TABLE;
        $sql = "CREATE TABLE {$table} (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            form_id BIGINT UNSIGNED NOT NULL,
            form_key VARCHAR(191) NOT NULL,
            submitted_at DATETIME NOT NULL,
            user_email VARCHAR(191) NULL,
            user_name VARCHAR(191) NULL,
            user_ip VARCHAR(100) NULL,
            data LONGTEXT NOT NULL,
            PRIMARY KEY (id),
            KEY form_id (form_id)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public function register_menu()
    {
        add_submenu_page(
            'psfb-forms',
            __('Submissions', 'psfb'),
            __('Submissions', 'psfb'),
            'manage_options',
            'psfb-submissions',
            [$this, 'render_submissions']
        );
    }

    public function render_submissions()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        global $wpdb;
        $form_id = isset($_GET['form_id']) ? absint($_GET['form_id']) : 0;
        $table = $wpdb->prefix . self::TABLE;
        $entries = [];
        if ($form_id) {
            $entries = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE form_id = %d ORDER BY submitted_at DESC", $form_id));
        } else {
            $entries = $wpdb->get_results("SELECT * FROM {$table} ORDER BY submitted_at DESC LIMIT 200");
        }

        $forms = get_posts([
            'post_type' => PSFB_Plugin::CPT,
            'posts_per_page' => -1,
            'orderby' => 'title',
            'order' => 'ASC',
        ]);
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Submissions', 'psfb'); ?></h1>
            <form method="get" class="psfb-submission-filter">
                <input type="hidden" name="page" value="psfb-submissions" />
                <label for="psfb-form-filter" class="screen-reader-text"><?php esc_html_e('Filter by form', 'psfb'); ?></label>
                <select name="form_id" id="psfb-form-filter">
                    <option value="0"><?php esc_html_e('All Forms', 'psfb'); ?></option>
                    <?php foreach ($forms as $form) : ?>
                        <option value="<?php echo esc_attr($form->ID); ?>" <?php selected($form_id, $form->ID); ?>><?php echo esc_html($form->post_title); ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="button"><?php esc_html_e('Filter', 'psfb'); ?></button>
            </form>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Form', 'psfb'); ?></th>
                        <th><?php esc_html_e('Submitted', 'psfb'); ?></th>
                        <th><?php esc_html_e('Email', 'psfb'); ?></th>
                        <th><?php esc_html_e('Data', 'psfb'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$entries) : ?>
                        <tr><td colspan="4"><?php esc_html_e('No submissions found', 'psfb'); ?></td></tr>
                    <?php else : ?>
                        <?php foreach ($entries as $entry) : ?>
                            <tr>
                                <td><?php echo esc_html(get_the_title($entry->form_id)); ?></td>
                                <td><?php echo esc_html(get_date_from_gmt($entry->submitted_at)); ?></td>
                                <td><?php echo esc_html($entry->user_email); ?></td>
                                <td><button type="button" class="button-link psfb-view-entry" data-entry='<?php echo wp_json_encode($entry); ?>'><?php esc_html_e('View', 'psfb'); ?></button></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div id="psfb-entry-modal" style="display:none;">
            <div class="psfb-entry-modal-content">
                <button type="button" class="psfb-close button">&times;</button>
                <pre class="psfb-entry-pre"></pre>
            </div>
        </div>
        <?php
    }
}
