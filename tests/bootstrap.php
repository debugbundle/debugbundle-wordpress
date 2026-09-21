<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
	define('ABSPATH', dirname(__DIR__) . '/');
}

if (!class_exists('WP_Error')) {
	class WP_Error
	{
		public function __construct(private readonly string $message)
		{
		}

		public function get_error_message(): string
		{
			return $this->message;
		}
	}
}

if (!function_exists('sanitize_text_field')) {
	function sanitize_text_field(string $value): string
	{
		$withoutHtml = preg_replace('/<[^>]*>/', '', $value);
		return trim(is_string($withoutHtml) ? $withoutHtml : $value);
	}
}

if (!function_exists('sanitize_key')) {
	function sanitize_key(string $key): string
	{
		return preg_replace('/[^a-z0-9_\-]/', '', strtolower($key)) ?? '';
	}
}

if (!function_exists('esc_url_raw')) {
	function esc_url_raw(string $url): string
	{
		$sanitized = filter_var(trim($url), FILTER_SANITIZE_URL);
		return is_string($sanitized) ? $sanitized : '';
	}
}

if (!function_exists('absint')) {
	function absint(mixed $maybeint): int
	{
		return abs((int) $maybeint);
	}
}

if (!function_exists('wp_parse_url')) {
	function wp_parse_url(string $url, int $component = -1): array|string|int|null|false
	{
		return $component === -1 ? parse_url($url) : parse_url($url, $component);
	}
}

if (!function_exists('wp_unslash')) {
	function wp_unslash(mixed $value): mixed
	{
		if (is_array($value)) {
			return array_map('wp_unslash', $value);
		}

		return is_string($value) ? stripslashes($value) : $value;
	}
}

if (!function_exists('wp_mkdir_p')) {
	function wp_mkdir_p(string $target): bool
	{
		return is_dir($target) || mkdir($target, 0777, true);
	}
}

if (!function_exists('wp_delete_file')) {
	function wp_delete_file(string $file): bool
	{
		return @unlink($file);
	}
}

if (!function_exists('wp_create_nonce')) {
	function wp_create_nonce(string $action): string
	{
		return 'nonce-for-' . $action;
	}
}

if (!function_exists('wp_verify_nonce')) {
	function wp_verify_nonce(string $nonce, string $action): int|false
	{
		return $nonce === 'nonce-for-' . $action ? 1 : false;
	}
}

if (!function_exists('wp_remote_request')) {
	function wp_remote_request(string $url, array $args = []): mixed
	{
		$GLOBALS['debugbundle_wp_test_last_remote_request'] = [
			'url' => $url,
			'args' => $args,
		];
		return $GLOBALS['debugbundle_wp_test_remote_request_response'] ?? [];
	}
}

if (!function_exists('is_wp_error')) {
	function is_wp_error(mixed $thing): bool
	{
		return $thing instanceof WP_Error;
	}
}

if (!function_exists('get_option')) {
	function get_option(string $option, mixed $default = false): mixed
	{
		return $GLOBALS['debugbundle_wp_test_options'][$option] ?? $default;
	}
}

if (!function_exists('add_action')) {
	function add_action(string $hook, callable $callback, int $priority = 10): void
	{
		$GLOBALS['debugbundle_wp_test_actions'][$hook][$priority][] = $callback;
	}
}

if (!function_exists('apply_filters')) {
	function apply_filters(string $hook, mixed $value): mixed
	{
		$filter = $GLOBALS['debugbundle_wp_test_filters'][$hook] ?? null;
		return is_callable($filter) ? $filter($value) : $value;
	}
}

if (!function_exists('register_activation_hook')) {
	function register_activation_hook(string $file, callable $callback): void
	{
		$GLOBALS['debugbundle_wp_test_activation_hooks'][$file] = $callback;
	}
}

if (!function_exists('register_deactivation_hook')) {
	function register_deactivation_hook(string $file, callable $callback): void
	{
		$GLOBALS['debugbundle_wp_test_deactivation_hooks'][$file] = $callback;
	}
}

if (!function_exists('wp_clear_scheduled_hook')) {
	function wp_clear_scheduled_hook(string $hook): int
	{
		$GLOBALS['debugbundle_wp_test_cleared_hooks'][] = $hook;
		return 1;
	}
}

if (!function_exists('wp_die')) {
	function wp_die(string $message): never
	{
		throw new RuntimeException($message);
	}
}

if (!function_exists('esc_html')) {
	function esc_html(string $value): string
	{
		return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('esc_attr')) {
	function esc_attr(string $value): string
	{
		return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
	}
}

if (!function_exists('get_bloginfo')) {
	function get_bloginfo(string $show): mixed
	{
		return $show === 'version'
			? ($GLOBALS['debugbundle_wp_test_wordpress_version'] ?? '6.8')
			: null;
	}
}

if (!function_exists('is_multisite')) {
	function is_multisite(): bool
	{
		return (bool) ($GLOBALS['debugbundle_wp_test_multisite'] ?? false);
	}
}

if (!function_exists('wp_get_theme')) {
	function wp_get_theme(): object
	{
		return new class {
			public function get(string $key): string
			{
				return match ($key) {
					'Name' => 'DebugBundle Test Theme',
					'Version' => '1.0.0',
					default => '',
				};
			}

			public function get_stylesheet(): string
			{
				return 'debugbundle-test-theme';
			}
		};
	}
}

if (!function_exists('get_current_user_id')) {
	function get_current_user_id(): int
	{
		return (int) ($GLOBALS['debugbundle_wp_test_current_user_id'] ?? 0);
	}
}

if (!function_exists('wp_salt')) {
	function wp_salt(string $scheme = 'auth'): string
	{
		return 'debugbundle-test-' . $scheme;
	}
}

if (!function_exists('home_url')) {
	function home_url(string $path = '/'): string
	{
		return ($GLOBALS['debugbundle_wp_test_home_url'] ?? 'https://app.example.com') . $path;
	}
}

if (!function_exists('site_url')) {
	function site_url(string $path = '/'): string
	{
		return ($GLOBALS['debugbundle_wp_test_site_url'] ?? 'https://app.example.com') . $path;
	}
}

if (!function_exists('wp_upload_dir')) {
	function wp_upload_dir(): array
	{
		return ['basedir' => $GLOBALS['debugbundle_wp_test_upload_basedir'] ?? sys_get_temp_dir()];
	}
}

if (!function_exists('wp_json_encode')) {
	function wp_json_encode(mixed $value): string|false
	{
		return json_encode($value);
	}
}

if (!function_exists('get_transient')) {
	function get_transient(string $key): mixed
	{
		return $GLOBALS['debugbundle_wp_test_transients'][$key] ?? false;
	}
}

if (!function_exists('set_transient')) {
	function set_transient(string $key, mixed $value, int $expiration): bool
	{
		$GLOBALS['debugbundle_wp_test_transients'][$key] = $value;
		return true;
	}
}

if (!function_exists('wp_remote_post')) {
	function wp_remote_post(string $url, array $args = []): mixed
	{
		$GLOBALS['debugbundle_wp_test_last_remote_post'] = ['url' => $url, 'args' => $args];
		return $GLOBALS['debugbundle_wp_test_remote_response'] ?? [
			'response' => ['code' => 202],
			'body' => json_encode(['accepted' => 1, 'rejected' => 0, 'errors' => []]),
		];
	}
}

if (!function_exists('wp_remote_retrieve_response_code')) {
	function wp_remote_retrieve_response_code(mixed $response): int
	{
		return is_array($response) ? (int) ($response['response']['code'] ?? 500) : 500;
	}
}

if (!function_exists('wp_remote_retrieve_body')) {
	function wp_remote_retrieve_body(mixed $response): string
	{
		return is_array($response) && is_string($response['body'] ?? null) ? $response['body'] : '';
	}
}

if (!function_exists('wp_remote_retrieve_headers')) {
	function wp_remote_retrieve_headers(mixed $response): mixed
	{
		return is_array($response) ? ($response['headers'] ?? []) : [];
	}
}

if (!function_exists('register_rest_route')) {
	function register_rest_route(string $namespace, string $route, array $args): bool
	{
		$GLOBALS['debugbundle_wp_test_rest_routes'][] = [$namespace, $route, $args];
		return true;
	}
}

if (!function_exists('wp_next_scheduled')) {
	function wp_next_scheduled(string $hook): int|false
	{
		return $GLOBALS['debugbundle_wp_test_scheduled_hooks'][$hook] ?? false;
	}
}

if (!function_exists('wp_schedule_single_event')) {
	function wp_schedule_single_event(int $timestamp, string $hook): bool
	{
		$GLOBALS['debugbundle_wp_test_scheduled_hooks'][$hook] = $timestamp;
		return true;
	}
}

if (!function_exists('current_user_can')) {
	function current_user_can(string $capability): bool
	{
		return (bool) ($GLOBALS['debugbundle_wp_test_current_user_can'] ?? ($capability === 'manage_options'));
	}
}

if (!function_exists('add_options_page')) {
	function add_options_page(
		string $pageTitle,
		string $menuTitle,
		string $capability,
		string $menuSlug,
		callable $callback
	): string {
		$GLOBALS['debugbundle_wp_test_options_pages'][] = [
			$pageTitle,
			$menuTitle,
			$capability,
			$menuSlug,
			$callback,
		];
		return 'settings_page_' . $menuSlug;
	}
}

if (!function_exists('register_setting')) {
	function register_setting(string $optionGroup, string $optionName, array $args = []): void
	{
		$GLOBALS['debugbundle_wp_test_registered_settings'][] = [$optionGroup, $optionName, $args];
	}
}

require dirname(__DIR__) . '/vendor/autoload.php';

// Until the PHP SDK protection release is published, exercise this plugin against
// its checked-out source implementation; production must install that SDK line.
if (!class_exists(\DebugBundle\TelemetryPrivacy::class)) {
    require dirname(__DIR__, 2) . '/debugbundle-php/src/TelemetryPrivacy.php';
}
