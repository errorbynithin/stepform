<?php
namespace StepForm;

if (!defined('ABSPATH')) {
    exit;
}

class Render
{
    public static function frontend($form, array $config): void
    {
        $steps = $config['steps'] ?? [];
        wp_enqueue_script('stepform-frontend');
        wp_enqueue_style('stepform-frontend');
        echo '<div class="stepform" data-form="' . esc_attr($form->ID) . '" data-config="' . esc_attr(wp_json_encode($config)) . '">';
        echo '<div class="stepform-sidebar">';
        echo '<div class="stepform-logo"></div>';
        echo '<ol class="stepform-steps">';
        foreach ($steps as $index => $step) {
            echo '<li class="stepform-step" data-step-index="' . esc_attr($index) . '">';
            echo '<span class="step-number">' . esc_html($index + 1) . '</span>';
            echo '<div class="step-meta"><strong>' . esc_html($step['title'] ?? __('Step', 'stepform')) . '</strong></div>';
            echo '</li>';
        }
        echo '</ol></div>';

        echo '<div class="stepform-body">';
        echo '<div class="stepform-header">';
        echo '<p class="step-count">' . esc_html(sprintf(__('Step %d of %d', 'stepform'), 1, count($steps))) . '</p>';
        echo '<div class="step-progress"><span class="bar"></span></div>';
        echo '<h2 class="step-title"></h2>';
        echo '<p class="step-description"></p>';
        echo '</div>';

        echo '<form class="stepform-form">';
        echo '<div class="stepform-fields"></div>';
        echo '<div class="stepform-actions">';
        echo '<button type="button" class="stepform-prev">' . esc_html__('Previous', 'stepform') . '</button>';
        echo '<button type="button" class="stepform-next button-primary">' . esc_html__('Next Step', 'stepform') . '</button>';
        echo '<button type="submit" class="stepform-submit button-primary">' . esc_html__('Submit', 'stepform') . '</button>';
        echo '</div></form></div></div>';
    }
}
