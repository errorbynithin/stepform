<?php
namespace StepForm;

if (!defined('ABSPATH')) {
    exit;
}

class Admin
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
        add_action('admin_menu', [$this, 'register_menu']);
        add_action('add_meta_boxes', [$this, 'register_meta_boxes']);
        add_action('save_post_stepform_form', [$this, 'save_form']);
    }

    public function register_menu(): void
    {
        add_menu_page(
            __('Step Forms', 'stepform'),
            __('Step Forms', 'stepform'),
            'manage_options',
            'stepform',
            [$this, 'render_forms_page'],
            'dashicons-feedback',
            26
        );

        add_submenu_page('stepform', __('All Forms', 'stepform'), __('All Forms', 'stepform'), 'manage_options', 'stepform', [$this, 'render_forms_page']);
        add_submenu_page('stepform', __('Entries', 'stepform'), __('Entries', 'stepform'), 'manage_options', 'stepform-entries', [$this, 'render_entries_page']);
        add_submenu_page('stepform', __('Settings', 'stepform'), __('Settings', 'stepform'), 'manage_options', 'stepform-settings', [$this, 'render_settings_page']);
    }

    public function register_meta_boxes(): void
    {
        add_meta_box(
            'stepform-builder',
            __('Form Builder', 'stepform'),
            [$this, 'render_builder_metabox'],
            'stepform_form',
            'normal',
            'high'
        );
    }

    public function render_forms_page(): void
    {
        if (isset($_GET['action']) && $_GET['action'] === 'edit' && !empty($_GET['post'])) {
            wp_safe_redirect(admin_url('post.php?post=' . absint($_GET['post']) . '&action=edit'));
            exit;
        }
        if (isset($_GET['action']) && $_GET['action'] === 'new') {
            wp_safe_redirect(admin_url('post-new.php?post_type=stepform_form'));
            exit;
        }

        echo '<div class="wrap stepform-list">';
        echo '<h1 class="wp-heading-inline">' . esc_html__('Forms', 'stepform') . '</h1> ';
        echo '<a class="page-title-action" href="' . esc_url(admin_url('post-new.php?post_type=stepform_form')) . '">' . esc_html__('Add New', 'stepform') . '</a>';

        $forms = get_posts([
            'post_type' => 'stepform_form',
            'posts_per_page' => -1,
            'post_status' => 'any',
        ]);

        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead><tr>'; 
        echo '<th>' . esc_html__('Title', 'stepform') . '</th>'; 
        echo '<th>' . esc_html__('Shortcode', 'stepform') . '</th>'; 
        echo '<th>' . esc_html__('Submissions', 'stepform') . '</th>'; 
        echo '<th>' . esc_html__('Status', 'stepform') . '</th>'; 
        echo '<th>' . esc_html__('Date', 'stepform') . '</th>'; 
        echo '</tr></thead><tbody>';

        if ($forms) {
            foreach ($forms as $form) {
                $entries = get_posts([
                    'post_type' => 'stepform_entry',
                    'meta_key' => '_stepform_form_id',
                    'meta_value' => $form->ID,
                    'posts_per_page' => -1,
                    'fields' => 'ids',
                ]);
                echo '<tr>';
                echo '<td><a href="' . esc_url(get_edit_post_link($form->ID)) . '">' . esc_html(get_the_title($form)) . '</a></td>';
                echo '<td><code>[stepform id="' . esc_html($form->ID) . '"]</code></td>';
                echo '<td>' . esc_html(count($entries)) . '</td>';
                echo '<td>' . esc_html($form->post_status) . '</td>';
                echo '<td>' . esc_html(get_the_date('', $form)) . '</td>';
                echo '</tr>';
            }
        } else {
            echo '<tr><td colspan="5">' . esc_html__('No forms found.', 'stepform') . '</td></tr>';
        }

        echo '</tbody></table></div>';
    }

    public function render_entries_page(): void
    {
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $this->export_csv();
            return;
        }

        echo '<div class="wrap stepform-entries">';
        echo '<h1 class="wp-heading-inline">' . esc_html__('Submissions', 'stepform') . '</h1>';
        echo '<a class="page-title-action" href="' . esc_url(add_query_arg('export', 'csv')) . '">' . esc_html__('Export CSV', 'stepform') . '</a>';

        require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

        $table = new class extends \WP_List_Table {
            public function get_columns()
            {
                return [
                    'title' => __('Submission', 'stepform'),
                    'form' => __('Form', 'stepform'),
                    'status' => __('Status', 'stepform'),
                    'date' => __('Date', 'stepform'),
                ];
            }

            protected function column_default($item, $column_name)
            {
                return $item[$column_name] ?? '';
            }

            public function prepare_items()
            {
                $entries = get_posts([
                    'post_type' => 'stepform_entry',
                    'posts_per_page' => 20,
                    'post_status' => 'publish',
                ]);

                $data = [];
                foreach ($entries as $entry) {
                    $data[] = [
                        'title' => '<a href="' . esc_url(get_edit_post_link($entry->ID)) . '">' . esc_html($entry->post_title) . '</a>',
                        'form' => esc_html(get_the_title((int) get_post_meta($entry->ID, '_stepform_form_id', true))),
                        'status' => '<span class="status-dot"></span> ' . esc_html__('Read', 'stepform'),
                        'date' => esc_html(get_the_date('', $entry)),
                    ];
                }

                $this->items = $data;
                $columns = $this->get_columns();
                $hidden = [];
                $sortable = [];
                $this->_column_headers = [$columns, $hidden, $sortable];
            }
        };

        $table->prepare_items();
        echo '<form method="get">';
        $table->search_box(__('Search submissions', 'stepform'), 'stepform');
        $table->display();
        echo '</form></div>';
    }

    private function export_csv(): void
    {
        $entries = get_posts([
            'post_type' => 'stepform_entry',
            'posts_per_page' => -1,
            'post_status' => 'publish',
        ]);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment;filename="stepform-submissions.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['ID', 'Form', 'Date', 'Data']);

        foreach ($entries as $entry) {
            $data = get_post_meta($entry->ID, '_stepform_entry', true);
            fputcsv($output, [
                $entry->ID,
                get_the_title((int) get_post_meta($entry->ID, '_stepform_form_id', true)),
                get_the_date('Y-m-d H:i:s', $entry),
                wp_json_encode($data),
            ]);
        }
        fclose($output);
        exit;
    }

    public function render_settings_page(): void
    {
        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Settings', 'stepform') . '</h1>';
        echo '<p>' . esc_html__('Global options for StepForm. AJAX submissions are enabled by default.', 'stepform') . '</p>';
        echo '</div>';
    }

    public function render_builder_metabox($post): void
    {
        $config = get_post_meta($post->ID, '_stepform_config', true);
        $config = is_array($config) ? $config : $this->get_default_config();

        echo '<div id="stepform-builder" class="stepform-builder" data-config="' . esc_attr(wp_json_encode($config)) . '"></div>';
        echo '<input type="hidden" id="stepform-config-input" name="stepform_config" value="' . esc_attr(wp_json_encode($config)) . '" />';
        echo '<p class="description">' . esc_html__('Build multi-step experiences with drag-and-drop fields. Changes are saved with the post.', 'stepform') . '</p>';
        wp_nonce_field('stepform_save_form', 'stepform_nonce');
    }

    private function get_default_config(): array
    {
        return [
            'title' => __('Contact Form', 'stepform'),
            'description' => __('Collect basic information from the user.', 'stepform'),
            'steps' => [
                [
                    'id' => uniqid('step_'),
                    'title' => __('Personal Info', 'stepform'),
                    'description' => __('Collect basic information from the user.', 'stepform'),
                    'fields' => [
                        [
                            'id' => uniqid('field_'),
                            'type' => 'text',
                            'label' => __('First Name', 'stepform'),
                            'placeholder' => __('Enter your first name...', 'stepform'),
                            'required' => true,
                            'help' => __('This name will be used for correspondence.', 'stepform'),
                        ],
                        [
                            'id' => uniqid('field_'),
                            'type' => 'text',
                            'label' => __('Last Name', 'stepform'),
                            'placeholder' => __('Enter your last name...', 'stepform'),
                            'required' => false,
                        ],
                        [
                            'id' => uniqid('field_'),
                            'type' => 'email',
                            'label' => __('Email Address', 'stepform'),
                            'placeholder' => __('john@example.com', 'stepform'),
                            'required' => true,
                        ],
                    ],
                ],
                [
                    'id' => uniqid('step_'),
                    'title' => __('Message Details', 'stepform'),
                    'description' => __('What should we know?', 'stepform'),
                    'fields' => [
                        [
                            'id' => uniqid('field_'),
                            'type' => 'textarea',
                            'label' => __('Message', 'stepform'),
                            'placeholder' => __('Tell us more...', 'stepform'),
                            'required' => true,
                        ],
                    ],
                ],
            ],
        ];
    }

    public function save_form($post_id): void
    {
        if (!isset($_POST['stepform_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['stepform_nonce'])), 'stepform_save_form')) {
            return;
        }
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $raw = isset($_POST['stepform_config']) ? wp_unslash($_POST['stepform_config']) : '';
        $config = json_decode($raw, true);
        if (!$config) {
            return;
        }

        update_post_meta($post_id, '_stepform_config', $config);
    }
}
