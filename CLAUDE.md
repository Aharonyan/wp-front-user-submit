# CLAUDE.md — WP Front User Submit / Front Editor Plugin

## Project Overview

**WP Front User Submit / Front Editor**  is a WordPress plugin that lets users submit, edit, and manage posts from the front end without wp-admin access. It features a drag-and-drop form builder, guest posting, WooCommerce integration, and multiple content editors (EditorJS, TinyMCE, Markdown).

**Plugin root:** `app/public/wp-content/plugins/front-user-submit/`

All development work happens inside the plugin directory above. The rest of the WordPress installation is standard and should not be modified.

## Architecture

- **Namespace:** `BFE\` — all core PHP classes use this namespace
- **Pattern:** Static-class architecture — each class has static methods and calls `init()` at file bottom
- **Custom Post Type:** `fe_post_form` — stores form configurations (field layout + settings) as posts
- **Licensing:** Freemius SDK (plugin ID `7886`, slug `front-editor`)

## Directory Structure

```
front-user-submit/
├── front-editor.php          # Entry point (WP plugin header, constants, Freemius init)
├── FrontUserSubmit.php        # Core bootstrap class: hooks, script registration
├── functions.php              # Global utility functions
├── inc/                       # PHP classes
│   ├── MenuSettings.php       # Admin menus & settings API
│   ├── Form.php               # CPT registration, form builder REST API, admin CRUD
│   ├── SavePost.php           # REST endpoints for front-end post create/update/upload
│   ├── Editor.php             # Front-end form rendering, edit permissions
│   ├── EditorWidget.php       # "Edit Post" button injection
│   ├── Shortcodes.php         # All shortcode registrations
│   ├── UserAdmin.php          # Front-end post management panel
│   ├── LoginRegisterShortcode.php  # Login/register shortcodes + AJAX
│   ├── Notifications.php      # Email notifications on post events
│   ├── WooIntegration.php     # WooCommerce payment integration
│   ├── Blocks.php             # Gutenberg block registration
│   ├── FormMetaBox.php        # Admin meta box on posts
│   ├── DemoData.php           # Activation demo content creation
│   ├── PostFormsListTable.php # WP_List_Table for form listing
│   ├── fields/                # 23 field type classes (PostTitleField, EditorJsField, etc.)
│   └── shortcodes/            # Additional shortcode classes
├── src/                       # JS/SCSS source files
│   ├── front.js               # Front-end entry
│   ├── admin.js               # Admin entry
│   ├── gutenberg.js           # Gutenberg block entry
│   ├── useradmin.js           # User admin panel entry
│   ├── loginRegister.js       # Login/register entry
│   ├── *.scss                 # Style sources
│   └── js/                    # JS modules (fields/, inc/, blocks/)
├── build/                     # Compiled webpack output (DO NOT edit directly)
├── templates/                 # PHP templates
│   ├── front-editor-form.php  # Main frontend form wrapper
│   ├── admin/                 # Admin templates & settings tabs
│   └── front-editor/          # Per-field front-end templates
├── assets/                    # Static assets (images, vendor JS)
├── vendor/                    # Composer packages (parsedown, html-to-markdown)
├── freemius/                  # Freemius SDK
└── languages/                 # Translations
```

## Build System

### PHP Dependencies (Composer)
```bash
cd app/public/wp-content/plugins/front-user-submit
composer install
```
- `erusev/parsedown` — Markdown parser
- `league/html-to-markdown` — HTML to Markdown converter

### JavaScript Dependencies (npm)
```bash
cd app/public/wp-content/plugins/front-user-submit
npm install
```

### Build Commands
```bash
npm run build    # Production build
npm run start    # Watch mode (development)
```

Webpack config extends `@wordpress/scripts` with multiple entry points:
| Entry | Output | Purpose |
|---|---|---|
| `src/front.js` | `build/front.js` | Front-end form functionality |
| `src/admin.js` | `build/admin.js` | Admin form builder UI |
| `src/useradmin.js` | `build/useradmin.js` | User admin panel |
| `src/gutenberg.js` | `build/gutenberg.js` | Gutenberg block |
| `src/loginRegister.js` | `build/loginRegister.js` | Login/register AJAX |
| `src/*.scss` | `build/*.css` | Corresponding styles |

## Key Shortcodes

| Shortcode | Purpose |
|---|---|
| `[fe_form id="X"]` | Renders submission form for form ID X |
| `[fe_fs_user_admin]` | Front-end user post management panel |
| `[fus_form_login]` | Login form with AJAX |
| `[fus_form_register]` | Registration form with AJAX |
| `[fus_google_map meta_name="X"]` | Render Google Map from saved meta |
| `[fus_custom_field_content meta_name="X"]` | Display post meta value |

## REST API Endpoints

| Route | Method | Purpose |
|---|---|---|
| `/bfe/v1/form` | POST | Fetch form builder config (admin) |
| `/bfe/v1/add-update-form` | POST | Save form settings (admin) |
| `/bfe/v1/add_or_update_post` | POST | Create/update post from front-end |
| `/bfe/v1/post_thumb_uploading_image` | POST | Upload featured image |
| `/bfe/v1/upload_image` | POST | Upload image (EditorJS) |
| `/bfe/v1/upload_file` | POST | Upload file attachment |

## Important Custom Hooks

### Actions
- `bfe_before_post_form_frontend_execution` — Before form renders on front-end
- `bfe_ajax_before_post_update_or_creation` — Validation before post save
- `bfe_ajax_before_front_editor_post_update_or_creation` — Modify `$post_data` before insert/update
- `bfe_ajax_after_front_editor_post_update_or_creation` — After post saved
- `bfe_ajax_after_front_editor_post_inserted` — After new post created
- `bfe_ajax_after_front_editor_post_updated` — After existing post updated
- `bfe_editor_on_front_field_adding` — Each field renders its template

### Filters
- `admin_post_form_formBuilder_settings` — Fields register themselves in form builder
- `bfe_front_editor_localize_data` — Extend JS localization data
- `bfe_ajax_after_successfully_post_redirect` — Filter redirect URL after create
- `bfe_ajax_after_successfully_post_edited` — Filter redirect URL after update

## Key Post Meta

| Meta Key | Purpose |
|---|---|
| `formBuilderData` | JSON of form builder field configuration |
| `fe_form_settings` | Array of per-form settings |
| `BFE_the_post_edited_by` | Form ID used to create/edit a post |
| `bfe_editor_js_data` | Stored EditorJS JSON data |

## Coding Standards

### PHP

**Indentation:** 2 spaces (dominant style in namespace classes). Some legacy files use 1-tab indentation.

**Brace style:** Allman (next line) for classes and methods; K&R (same line) for control structures:
```php
class Form
{
  public static function init()
  {
    if ($condition) {
      // ...
    }
  }
}
```

**Naming conventions:**
| Element | Convention | Example |
|---|---|---|
| Classes | `PascalCase` | `SavePost`, `PostTitleField` |
| Methods | `snake_case` | `add_scripts()`, `get_post_edit_link()` |
| Variables | `snake_case` | `$post_id`, `$form_settings` |
| Constants | `SCREAMING_SNAKE_CASE` with prefix | `FE_PLUGIN_URL`, `FUS__PLUGIN_DIR` |
| Standalone functions | Prefixed `snake_case` | `fe_admin_role()`, `fus_get_current_admin_url()` |
| Hook names | `snake_case` with `bfe_` prefix | `bfe_ajax_after_front_editor_post_inserted` |

**Strings:** Single quotes preferred. Use `sprintf()` over double-quote interpolation:
```php
sprintf('<a href="%s">%s</a>', $url, __('Edit', 'front-editor'));
```

**Arrays:** Short `[]` syntax preferred. `array()` only in legacy/WP-docs-style code.

**Type hints:** Minimal — used only for `int`, `array`, and `\WP_REST_Request` parameters. No return types.

**Class pattern:** All-static, no instantiation. Each file ends with `ClassName::init();`:
```php
class SavePost
{
  public static function init()
  {
    add_action('rest_api_init', [__CLASS__, 'new_endpoints']);
  }
}
SavePost::init();
```

**Hook callbacks:** Always `[__CLASS__, 'method_name']` array format. Cross-class references use `['\BFE\Form', 'method']`.

**REST endpoints:** Registered in `new_endpoints()` method, namespace `bfe/v1`, all POST, route slugs use underscores.

**Template loading:** Use `fe_template_path()` helper + `require`. Use `ob_start()`/`ob_get_clean()` when template must return a string.

**Sanitization (input):** `sanitize_text_field()`, `intval()`, `sanitize_file_name()`, `sanitize_mime_type()`, `esc_url()`, `wp_unslash()`.

**Escaping (output):** `esc_attr()` for attributes, `esc_html()` for text, `wp_kses_post()` for rich HTML.

**Nonce verification:** `wp_verify_nonce()` for REST permission callbacks, `check_ajax_referer()` for AJAX endpoints.

**PHPDoc:** Standard `@param`, `@return`, `@since` tags on public methods. File-level docblocks with `@package bfee`.

**Namespaces:** `BFE\` for core classes, `BFE\Field\` for field classes.

### JavaScript

**Module system:** ES Modules (`import`/`export default`) compiled via Webpack. No CommonJS `require()` in source.

**jQuery:** Accessed as `jQuery` at module level, aliased to `$` via function parameter:
```js
export default (form, $, SlimSelect, Swal) => { ... }
```

**Variable naming:** `camelCase` for JS-native variables; `snake_case` acceptable when mirroring PHP/server data names (`post_form_id`, `post_link`).

**Strings:** Single quotes preferred. Template literals (backticks) for interpolation:
```js
`#${post_form_id} .form-submit`
```

**ES6+ features used:** Arrow functions, `const`/`let` (avoid `var`), template literals, spread operator, `async`/`await`, `for...of`, `Object.entries()`, default parameters.

**AJAX:** Use `fetch()` API with `.then()` chains or `async`/`await`. Always send WP nonce as header:
```js
fetch(url, {
  method: 'POST',
  body: formData,
  headers: { 'X-WP-Nonce': fe_data.nonce }
})
```

**Custom hooks system:** Plugin implements WP-style hooks in JS via `window.fe_hooks`:
```js
window.fe_hooks.do_action('init_post_form');
window.fe_hooks.do_action('on_post_form_save');
```

**WP data bridge:** PHP passes data to JS via `wp_localize_script()` or inline `<script>` tag. Consumed as `window.editor_data` or `window.fe_post_form_data`.

### CSS/SCSS

**Naming:** Custom prefixed kebab-case — `.fus-` and `.bfe-` prefixes. No BEM:
```scss
.fus-form { }
.fus-form-block-header { }
.bfe-edit-post-button { }
```

**Nesting:** Deep nesting (4-5 levels) with `&` for states and modifiers:
```scss
.fus-form {
  .image_loader {
    &:hover { opacity: 0.7; }
    &.chosen { ... }
  }
}
```

**Variables:** Defined in `src/scss/variables.scss` for shared values (`$black`, `$white`, `$button-border`, `$editor-max-width`). Local variables declared inside component blocks.

**File organization:** Partials imported into entry SCSS files. Structure: `variables` -> `mixins/` -> `partials/` -> `blocks/` -> `components/` -> `vendor/`.

## Conventions

- All PHP classes use the `BFE\` namespace (field classes use `BFE\Field\` or `BFE\fields\`)
- Static methods pattern — no class instantiation, each class self-initializes
- Templates support theme overrides via `fe_template_path()` function
- Premium features gated by `fe_fs()->can_use_premium_code__premium_only()`
- Front-end scripts use `wp_localize_script` with `bfe_front_script_data` object
- Admin scripts use `wp_localize_script` with `bfe_admin_script_data` object
- Plugin constants: `FUS__PLUGIN_FILE`, `FUS__PLUGIN_DIR`, `FUS__PLUGIN_URL`, `FE_PLUGIN_URL`, `FE_PLUGIN_DIR_PATH`, `FE_Template_PATH`
- Security: nonce verification on all REST/AJAX endpoints, capability checks for admin actions

## WordPress Environment

- **WordPress path:** `app/public/`
- **Local development:** Local by Flywheel / LocalWP setup
- **PHP:** Standard WordPress PHP (check `phpinfo()` for version)
- **Database:** MySQL (managed by LocalWP)

## Local Test Credentials

### Admin
- **URL:** http://front-user-submit.local/wp-admin
- **Username:** root
- **Password:** root

### Simple User (for testing front-end submissions)
- **Username:** claude
- **Password:** claude

### Key Test Pages
| Page | URL |
|---|---|
| Form Settings (ID 460) | http://front-user-submit.local/wp-admin/admin.php?page=fe-post-forms&action=edit&id=460#post-form-builder |
| Front-end Form Page | http://front-user-submit.local/claude-test-form/ |
| Frontend Login Form | http://front-user-submit.local/login-form/ |

## Playwright E2E Tests

Tests live in `tests/` inside the plugin directory.

### Setup
```bash
cd app/public/wp-content/plugins/front-user-submit
npm install
npx playwright install chromium
```

### Run all tests
```bash
npx playwright test
```

### Run a specific test file
```bash
npx playwright test tests/edit-post-permissions.spec.ts
```

### Run with headed browser (visible)
```bash
npx playwright test --headed
```

### View test report
```bash
npx playwright show-report
```

### Test configuration
- Config: `playwright.config.ts`
- Test credentials: `tests/helpers/config.ts`
- Auth helpers: `tests/helpers/auth.ts`
