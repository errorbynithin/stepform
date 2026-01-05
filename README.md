# Professional Step Form Builder

A WordPress plugin that delivers an admin drag-and-drop multi-step form builder with frontend rendering, submissions, and notifications.

## Features
- Admin builder with step manager, field library, and per-field settings.
- Multi-step frontend form layout with sidebar progress, next/back controls, and client-side validation hooks.
- AJAX-powered saves, form loading, submissions, and CSV export.
- Stores submissions in a dedicated database table with modal detail view.
- Email notifications for admins and end users with templating support.

## Usage
1. Upload the plugin folder to `wp-content/plugins/` or package as a ZIP.
2. Activate **Professional Step Form Builder** in WordPress.
3. Build forms under **Step Forms → Add Form**, then embed with `[psfb_form id="123"]`.

## Development
- Core plugin file: `professional-step-form-builder.php`
- Admin UI: `includes/class-psfb-admin.php`, assets in `assets/js/admin.js` and `assets/css/admin.css`
- Frontend UI: `includes/class-psfb-frontend.php`, assets in `assets/js/frontend.js` and `assets/css/frontend.css`
- AJAX/rest handlers: `includes/class-psfb-rest.php`
- Submissions and table creation: `includes/class-psfb-entries.php`
- Notifications: `includes/class-psfb-notifications.php`
