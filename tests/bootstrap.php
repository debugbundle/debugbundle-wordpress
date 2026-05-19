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
		return trim($value);
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
		return $GLOBALS['debugbundle_wp_test_remote_request_response'] ?? [];
	}
}

if (!function_exists('is_wp_error')) {
	function is_wp_error(mixed $thing): bool
	{
		return $thing instanceof WP_Error;
	}
}

require dirname(__DIR__) . '/vendor/autoload.php';
