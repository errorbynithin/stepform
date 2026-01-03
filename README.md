# StepForm Builder

StepForm is a lightweight WordPress plugin that lets you design multi-step forms that match the modern UI shown in the reference screenshots. It includes a drag-and-drop inspired admin builder, a front-end multi-step experience, and an entries table with CSV export.

## Features

- Custom post type for forms and submissions.
- Visual admin builder with step navigation, field previews, and inline field settings.
- Front-end shortcode `[stepform id="123"]` that renders a multi-step form with progress, validation, and AJAX submissions.
- Submission storage in WordPress with an entries table and CSV export.

## Getting started

1. Copy the plugin folder into `wp-content/plugins/stepform`.
2. Activate **StepForm Builder** in the WordPress admin.
3. Create a new form from **Step Forms → Add New** and customize fields in the builder panel.
4. Embed the form on any page using the generated shortcode.
5. Review submissions from **Step Forms → Entries** or export them as CSV.

## Development notes

- Assets are plain CSS/JS (no build step required).
- AJAX endpoints are exposed under the REST namespace `stepform/v1`.
- Submissions are saved as the `stepform_entry` post type and linked to their parent form.
