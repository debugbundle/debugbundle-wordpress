<?php

declare(strict_types=1);

class WP_REST_Response
{
    public function __construct(public array $data = [], public int $status = 200)
    {
    }
}

class WP_Theme
{
    public function get(string $header): string
    {
        return '';
    }

    public function get_stylesheet(): string
    {
        return '';
    }
}

function add_action(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): bool { return true; }
function register_rest_route(string $namespace, string $route, array $args): bool { return true; }
function register_activation_hook(string $file, callable $callback): bool { return true; }
function register_deactivation_hook(string $file, callable $callback): bool { return true; }
function add_options_page(string $page_title, string $menu_title, string $capability, string $menu_slug, callable $callback): string { return ''; }
function register_setting(string $option_group, string $option_name, array $args = []): bool { return true; }
function settings_fields(string $option_group): void {}
function submit_button(string $text = 'Save Changes'): void {}
function current_user_can(string $capability): bool { return true; }
function checked(bool $checked, bool $current = true, bool $display = true): string { return $checked === $current ? 'checked="checked"' : ''; }
function esc_attr(string $text): string { return $text; }
function esc_html(string $text): string { return $text; }
function wp_add_inline_script(string $handle, string $data, string $position = 'after'): bool { return true; }
function wp_enqueue_script(string $handle): bool { return true; }
function wp_register_script(string $handle, string $src, array $deps = [], string|bool|null $ver = false, array|bool $args = []): bool { return true; }
function plugins_url(string $path = '', string $plugin = ''): string { return $path; }
function get_option(string $option, mixed $default = false): mixed { return $default; }
function delete_option(string $option): bool { return true; }
function wp_get_environment_type(): string { return 'production'; }
function home_url(string $path = '', string|null $scheme = null): string { return 'https://example.com' . $path; }
function site_url(string $path = '', string|null $scheme = null): string { return 'https://example.com' . $path; }
function sanitize_text_field(string $text): string { return trim($text); }
function wp_json_encode(mixed $value, int $flags = 0, int $depth = 512): string|false { return json_encode($value, $flags, $depth); }
function wp_remote_request(string $url, array $args = []): array { return []; }
function wp_remote_post(string $url, array $args = []): array { return []; }
function wp_remote_retrieve_response_code(array $response): int { return 200; }
function wp_remote_retrieve_body(array $response): string { return '{}'; }
function wp_remote_retrieve_headers(array $response): array { return []; }
function is_wp_error(mixed $thing): bool { return false; }
function wp_upload_dir(): array { return ['basedir' => sys_get_temp_dir()]; }
function wp_next_scheduled(string $hook): int|false { return false; }
function wp_schedule_single_event(int $timestamp, string $hook, array $args = [], bool $wp_error = false): bool { return true; }
function wp_clear_scheduled_hook(string $hook, array $args = []): int|false { return 0; }
function wp_die(string $message): void { throw new RuntimeException($message); }
function get_bloginfo(string $show = '', string $filter = 'raw'): string { return '6.8'; }
function is_multisite(): bool { return false; }
function wp_get_theme(): WP_Theme { return new WP_Theme(); }
function get_current_user_id(): int { return 0; }
function wp_salt(string $scheme = 'auth'): string { return 'salt'; }
