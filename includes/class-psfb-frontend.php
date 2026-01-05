<?php
/**
 * Frontend rendering and submission handling.
 *
 * @package ProfessionalStepFormBuilder
 */

if (!defined('ABSPATH')) {
    exit;
}

class PSFB_Frontend
{
    public function __construct()
    {
        add_shortcode('psfb_form', [$this, 'render_form']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue()
    {
        wp_register_style('psfb-frontend', PSFB_URL . 'assets/css/frontend.css', [], PSFB_VERSION);
        wp_register_script('psfb-frontend', PSFB_URL . 'assets/js/frontend.js', ['jquery'], PSFB_VERSION, true);
        wp_localize_script('psfb-frontend', 'PSFB_FRONTEND', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce(PSFB_Plugin::NONCE_ACTION),
        ]);
    }

    public function render_form($atts)
    {
        $atts = shortcode_atts(['id' => 0], $atts, 'psfb_form');
        $form_id = absint($atts['id']);
        if (!$form_id) {
            return '';
        }

        $builder = get_post_meta($form_id, '_psfb_builder', true);
        $settings = get_post_meta($form_id, '_psfb_settings', true);
        $builder = $builder ? json_decode($builder, true) : [];
        $steps = $builder['steps'] ?? [];

        wp_enqueue_style('psfb-frontend');
        wp_enqueue_script('psfb-frontend');

        ob_start();
        ?>
        <div class="psfb-frontend" data-form-id="<?php echo esc_attr($form_id); ?>" data-settings='<?php echo esc_attr(wp_json_encode($settings)); ?>'>
            <div class="psfb-steps-sidebar">
                <ol class="psfb-step-list">
                    <?php foreach ($steps as $index => $step) : ?>
                        <li class="<?php echo $index === 0 ? 'active' : ''; ?>" data-step-index="<?php echo esc_attr($index); ?>"><?php echo esc_html($step['title'] ?? sprintf(__('Step %d', 'psfb'), $index + 1)); ?></li>
                    <?php endforeach; ?>
                </ol>
            </div>
            <div class="psfb-step-content">
                <form class="psfb-form" enctype="multipart/form-data">
                    <?php foreach ($steps as $index => $step) : ?>
                        <div class="psfb-step" data-step="<?php echo esc_attr($index); ?>" style="display: <?php echo $index === 0 ? 'block' : 'none'; ?>;">
                            <?php if (!empty($step['fields'])) : ?>
                                <?php foreach ($step['fields'] as $field) : ?>
                                    <?php echo $this->render_field($field); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div class="psfb-nav">
                                <?php if ($index > 0) : ?>
                                    <button type="button" class="psfb-prev button">&larr; <?php esc_html_e('Back', 'psfb'); ?></button>
                                <?php endif; ?>
                                <?php if ($index + 1 < count($steps)) : ?>
                                    <button type="button" class="psfb-next button button-primary"><?php esc_html_e('Next', 'psfb'); ?> &rarr;</button>
                                <?php else : ?>
                                    <button type="submit" class="psfb-submit button button-primary"><?php esc_html_e('Submit', 'psfb'); ?></button>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </form>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private function render_field($field)
    {
        $type = $field['type'] ?? 'text';
        $label = $field['label'] ?? __('Field', 'psfb');
        $required = !empty($field['required']);
        $name = $field['name'] ?? sanitize_title($label);
        $placeholder = $field['placeholder'] ?? '';
        $options = $field['options'] ?? [];

        $html = '<div class="psfb-field psfb-type-' . esc_attr($type) . '" data-logic="' . esc_attr(wp_json_encode($field['logic'] ?? [])) . '">';
        if ($type !== 'hidden' && $type !== 'html') {
            $html .= '<label for="psfb-' . esc_attr($name) . '">' . esc_html($label);
            if ($required) {
                $html .= ' <span class="required">*</span>';
            }
            $html .= '</label>';
        }

        switch ($type) {
            case 'textarea':
                $html .= '<textarea id="psfb-' . esc_attr($name) . '" name="' . esc_attr($name) . '" placeholder="' . esc_attr($placeholder) . '" ' . ($required ? 'required' : '') . '></textarea>';
                break;
            case 'select':
                $html .= '<select id="psfb-' . esc_attr($name) . '" name="' . esc_attr($name) . '" ' . ($required ? 'required' : '') . '>';
                foreach ($options as $option) {
                    $html .= '<option value="' . esc_attr($option['value'] ?? $option['label'] ?? '') . '">' . esc_html($option['label'] ?? '') . '</option>';
                }
                $html .= '</select>';
                break;
            case 'radio':
            case 'checkbox':
                foreach ($options as $option) {
                    $value = $option['value'] ?? $option['label'] ?? '';
                    $html .= '<label class="psfb-option">';
                    $html .= '<input type="' . esc_attr($type) . '" name="' . esc_attr($name) . ($type === 'checkbox' ? '[]' : '') . '" value="' . esc_attr($value) . '" ' . ($required ? 'required' : '') . ' /> ';
                    $html .= esc_html($option['label'] ?? $value) . '</label>';
                }
                break;
            case 'file':
                $html .= '<input type="file" id="psfb-' . esc_attr($name) . '" name="' . esc_attr($name) . '" ' . ($required ? 'required' : '') . ' />';
                break;
            case 'html':
                $html .= '<div class="psfb-html-block">' . wp_kses_post($field['content'] ?? '') . '</div>';
                break;
            case 'hidden':
                $html .= '<input type="hidden" name="' . esc_attr($name) . '" value="' . esc_attr($field['value'] ?? '') . '" />';
                break;
            case 'address':
                $html .= '<input type="text" id="psfb-' . esc_attr($name) . '-street" name="' . esc_attr($name) . '[street]" placeholder="' . esc_attr__('Street', 'psfb') . '" ' . ($required ? 'required' : '') . ' />';
                $html .= '<input type="text" id="psfb-' . esc_attr($name) . '-city" name="' . esc_attr($name) . '[city]" placeholder="' . esc_attr__('City', 'psfb') . '" ' . ($required ? 'required' : '') . ' />';
                $html .= '<input type="text" id="psfb-' . esc_attr($name) . '-zip" name="' . esc_attr($name) . '[zip]" placeholder="' . esc_attr__('ZIP', 'psfb') . '" ' . ($required ? 'required' : '') . ' />';
                break;
            case 'name':
                $html .= '<input type="text" id="psfb-' . esc_attr($name) . '-first" name="' . esc_attr($name) . '[first]" placeholder="' . esc_attr__('First', 'psfb') . '" ' . ($required ? 'required' : '') . ' />';
                $html .= '<input type="text" id="psfb-' . esc_attr($name) . '-last" name="' . esc_attr($name) . '[last]" placeholder="' . esc_attr__('Last', 'psfb') . '" ' . ($required ? 'required' : '') . ' />';
                break;
            case 'repeater':
                $html .= '<div class="psfb-repeater" data-name="' . esc_attr($name) . '">';
                $html .= '<div class="psfb-repeater-items"></div>';
                $html .= '<button type="button" class="button psfb-repeater-add">' . esc_html__('Add Row', 'psfb') . '</button>';
                $html .= '</div>';
                break;
            default:
                $html .= '<input type="' . esc_attr($type) . '" id="psfb-' . esc_attr($name) . '" name="' . esc_attr($name) . '" placeholder="' . esc_attr($placeholder) . '" ' . ($required ? 'required' : '') . ' />';
                break;
        }

        $html .= '</div>';
        return $html;
    }
}
