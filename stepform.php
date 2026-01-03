<?php
/**
 * Plugin Name: StepForm Builder
 * Description: A lightweight multi-step form builder with submission management.
 * Version: 1.0.0
 * Author: OpenAI
 * Text Domain: stepform
 */

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/class-stepform-plugin.php';

\StepForm\Plugin::instance();
