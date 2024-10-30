<?php

defined('ABSPATH') or die ('No direct access to this file.');

class Keep_In_Touch_Db
{
	private static $TABLE_NAME = 'keep_in_touch';

	static function create_table()
	{
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . self::$TABLE_NAME;

		$sql = "CREATE TABLE $table_name (
			full_name TINYTEXT DEFAULT '' NOT NULL,
			email TINYTEXT DEFAULT '' NOT NULL,
			status SET('pending_activation','active','pending_removal') DEFAULT 'pending_activation' NOT NULL,
			code TINYTEXT DEFAULT '' NOT NULL,
			categories TINYTEXT DEFAULT '' NOT NULL,
			daily BOOL DEFAULT 0 NOT NULL,
			weekly BOOL DEFAULT 0 NOT NULL,
			newsletter BOOL DEFAULT 0 NOT NULL
			) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	static private function find_row_by_email($email)
	{
		global $wpdb;

		return $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM " . $wpdb->prefix . self::$TABLE_NAME . " WHERE email = %s",
			$email
		));
	}

	static function find_row_by_code($code)
	{
		global $wpdb;

		return $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM " . $wpdb->prefix . self::$TABLE_NAME . " WHERE code = %s",
			$code
		));
	}

	static function register_subscription_request($email, $code, $full_name)
	{
		global $wpdb;

		// race condition?

		if (self::find_row_by_email($email))
		{
			return $wpdb->update(
				$wpdb->prefix . self::$TABLE_NAME,
				array(
					'code' => $code,
					'status' => 'pending_activation',
					'full_name' => $full_name ? $full_name : '',
				),
				array(
					'email' => $email,
				)
			);
		}

		return $wpdb->insert(
			$wpdb->prefix . self::$TABLE_NAME,
			array(
				'email' => $email,
				'status' => 'pending_activation',
				'code' => $code,
				'categories' => '',
				'daily' => false,
				'weekly' => true,
				'newsletter' => true,
				'full_name' => $full_name ? $full_name : '',
			)
		);
	}

	static function activate_subscription_by_code($code)
	{
		global $wpdb;

		return $wpdb->update(
			$wpdb->prefix . self::$TABLE_NAME,
			array(
				'code' => '',
				'status' => 'active',
			),
			array(
				'code' => $code,
				'status' => 'pending_activation',
			)
		);
	}

	static function register_cancellation_request($email, $code)
	{
		global $wpdb;

		// race condition?

		// this is silly, but we do it for symmetry
		if (!self::find_row_by_email($email))
		{
			return $wpdb->insert(
				$wpdb->prefix . self::$TABLE_NAME,
				array(
					'email' => $email,
					'status' => 'pending_removal',
					'code' => $code,
					'categories' => '',
					'daily' => false,
					'weekly' => true,
					'newsletter' => false,
				)
			);
		}

		return $wpdb->update(
			$wpdb->prefix . self::$TABLE_NAME,
			array(
				'code' => $code,
				'status' => 'pending_removal',
			),
			array(
				'email' => $email,
			)
		);
	}

	static function remove_subscription_by_code($code)
	{
		global $wpdb;

		return $wpdb->delete(
			$wpdb->prefix . self::$TABLE_NAME,
			array(
				'code' => $code,
				'status' => 'pending_removal',
			)
		);
	}

	static function get_all_confirmed_subscribers()
	{
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM " . $wpdb->prefix . self::$TABLE_NAME . " WHERE status != 'pending_activation'"
		);
	}

	static function get_all_confirmed_daily_digest_subscribers()
	{
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM " . $wpdb->prefix . self::$TABLE_NAME . " WHERE status != 'pending_activation' AND daily = 1"
		);
	}

	static function get_emails_of_all_confirmed_daily_digest_subscribers()
	{
		return Keep_In_Touch_Utils::object_list_column(self::get_all_confirmed_daily_digest_subscribers(), 'email');
	}

	static function get_all_confirmed_weekly_digest_subscribers()
	{
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM " . $wpdb->prefix . self::$TABLE_NAME . " WHERE status != 'pending_activation' AND weekly = 1"
		);
	}

	static function get_emails_of_all_confirmed_weekly_digest_subscribers()
	{
		return Keep_In_Touch_Utils::object_list_column(self::get_all_confirmed_weekly_digest_subscribers(), 'email');
	}

	static function get_all_confirmed_newsletter_subscribers()
	{
		global $wpdb;

		return $wpdb->get_results(
			"SELECT * FROM " . $wpdb->prefix . self::$TABLE_NAME . " WHERE status != 'pending_activation' AND newsletter = 1"
		);
	}

	static function get_emails_of_all_confirmed_newsletter_subscribers()
	{
		return Keep_In_Touch_Utils::object_list_column(self::get_all_confirmed_newsletter_subscribers(), 'email');
	}
}
