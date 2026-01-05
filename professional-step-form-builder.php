<?php
/**
 * Plugin Name: Professional Step Form Builder
 * Description: Drag-and-drop multi-step form builder with frontend multi-step rendering, submissions, and notifications.
 * Version: 1.0.0
 * Author: StepForm
 * Text Domain: psfb
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('PSFB_Plugin')) {
    require_once plugin_dir_path(__FILE__) . 'includes/class-psfb-plugin.php';
    PSFB_Plugin::get_instance();
}
