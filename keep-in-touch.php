<?php

/*
Plugin Name: Keep in Touch
Plugin URI: https://wordpress.org/plugins/keep-in-touch/
Description: Maintains a list of subscribers to updates and newsletter.
Version: 1.3
Author: Racanu
Author URI: https://profiles.wordpress.org/racanu/
Text Domain: keep-in-touch
#Domain Path: Optional. Plugin's relative directory path to .mo files. Example: /locale/
#Network: Optional. Whether the plugin can only be activated network wide. Example: true
License: GPL2
*/

defined('ABSPATH') or die ('No direct access to this file.');

//define('WP_DEBUG', true);
//define('WP_DEBUG_LOG', true);

require_once(ABSPATH . 'wp-includes/locale.php');
require_once('class-keep-in-touch-utils.php');
require_once('class-keep-in-touch-options.php');
require_once('class-keep-in-touch-db.php');
require_once('class-keep-in-touch-msg.php');
require_once('class-keep-in-touch-settings.php');
require_once('class-keep-in-touch-schedule.php');
require_once('class-keep-in-touch-widget.php');


class Keep_In_Touch
{
	static private function set_initial_options()
	{
		if (Keep_In_Touch_Options::get_option_use_anti_robot_page() == null)
			Keep_In_Touch_Options::update_option_use_anti_robot_page(true);
		if (Keep_In_Touch_Options::get_option_use_full_name() == null)
				Keep_In_Touch_Options::update_option_use_full_name(true);
		if (Keep_In_Touch_Options::get_option_subscription_confirmation_text() == null)
			Keep_In_Touch_Options::update_option_subscription_confirmation_text(__(
				'Your subscription is now confirmed. You will be receiving updates from us. Welcome and enjoy!', 'keep-in-touch'));
		if (Keep_In_Touch_Options::get_option_send_empty_digest_message() == null)
			Keep_In_Touch_Options::update_option_send_empty_digest_message(true);
		if (Keep_In_Touch_Options::get_option_empty_digest_message_text() == null)
			Keep_In_Touch_Options::update_option_empty_digest_message_text(__(
				"As it seems, we haven't been very active lately.\n\n" .
				"There are no new posts.\n\n" .
				"Maybe you can contribute some yourself ;)",
				'keep-in-touch'));
	}

	static private function is_virtual_page()
	{
		$request_path = wp_parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$virtual_path = Keep_In_Touch_Utils::get_page_path_from_slug(Keep_In_Touch_Utils::$PAGE_SLUG);
		return ($request_path == $virtual_path);
	}

	static private function handle_virtual_page()
	{
		if (isset($_POST['keep_in_touch_submit']))
			self::handle_submit();
		else if (isset($_GET['unsubscribe']))
			self::handle_unsubscribe(sanitize_text_field($_GET['unsubscribe']));
		else if (isset($_GET['confirmation_code']))
			self::handle_confirmation_code(sanitize_text_field($_GET['confirmation_code']));
	}

	static private function handle_submit()
	{
		if (Keep_In_Touch_Utils::is_null_or_empty_string($_POST['keep_in_touch_email']))
		{
			Keep_In_Touch_Utils::return_same_page();
			return;
		}

		$email = sanitize_email($_POST['keep_in_touch_email']);
		if (!Keep_In_Touch_Utils::is_null_or_empty_string($_POST['keep_in_touch_email_reference']))
		{
			//$is_valid = apply_filters('google_invre_is_valid_request_filter', true);
			//if( ! $is_valid )
			//{
			//	Keep_In_Touch_Msg::emit_invalid_page();
			//	return;
			//}
			//else
			{
				$email_reference = sanitize_email($_POST['keep_in_touch_email_reference']);
			}
		}

		if (!Keep_In_Touch_Utils::is_null_or_empty_string($_POST['keep_in_touch_full_name']))
			$full_name = sanitize_text_field($_POST['keep_in_touch_full_name']);

		if (Keep_In_Touch_Options::get_option_use_anti_robot_page(false))
		{
			if (!isset($email_reference))
			{
				self::handle_subscribe_first_step($email);
				return;
			}
			else if ($email_reference == $email)
			{
				self::handle_subscribe_second_step($email, $full_name);
				return;
			}
			else
			{
				Keep_In_Touch_Msg::emit_invalid_anti_robot_check($email, $email_reference);
				return;
			}
		}
		else
		{
			self::handle_subscribe_second_step($email, $full_name);
			return;
		}
	}

	static private function handle_subscribe_first_step($email)
	{
		Keep_In_Touch_Msg::emit_subscription_anti_robot($email);
	}

	static private function handle_subscribe_second_step($email, $full_name)
	{
		$confirmation_code = Keep_In_Touch_Utils::generate_unique_id(20);
		if (Keep_In_Touch_Db::register_subscription_request($email, $confirmation_code, $full_name))
			Keep_In_Touch_Msg::emit_confirm_subscription($email, $confirmation_code);
		else
			Keep_In_Touch_Msg::emit_subscription_request_failed($email);
	}

	static private function handle_unsubscribe($email)
	{
		$confirmation_code = Keep_In_Touch_Utils::generate_unique_id(20);

		if (Keep_In_Touch_Db::register_cancellation_request($email, $confirmation_code))
			Keep_In_Touch_Msg::emit_confirm_cancellation($email, $confirmation_code);
		else
			Keep_In_Touch_Msg::emit_cancellation_request_failed($email);
	}

	static private function handle_confirmation_code($confirmation_code)
	{
		$db_row = Keep_In_Touch_Db::find_row_by_code($confirmation_code);

		if (Keep_In_Touch_Db::activate_subscription_by_code($confirmation_code))
			Keep_In_Touch_Msg::emit_subscription_confirmation($db_row->email, $confirmation_code);

		else if (Keep_In_Touch_Db::remove_subscription_by_code($confirmation_code))
			Keep_In_Touch_Msg::emit_cancellation_confirmation($db_row->email, $confirmation_code);

		else
			Keep_In_Touch_Msg::emit_invalid_code($db_row->email, $confirmation_code);
	}

	static function activate()
	{
		Keep_In_Touch_Db::create_table();
		Keep_In_Touch_Schedule::schedule_events();
	}

	static function deactivate()
	{
		Keep_In_Touch_Schedule::unschedule_events();
	}

	static function init()
	{
		load_plugin_textdomain('keep-in-touch', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/');
		self::set_initial_options();

		Keep_In_Touch_Schedule::init();

		if (self::is_virtual_page())
			self::handle_virtual_page();
	}

	static function plugin_menu()
	{
		add_menu_page(__('Keep in Touch', 'keep-in-touch'), __('Keep in Touch', 'keep-in-touch'), 'activate_plugins', 'keep-in-touch', array('Keep_In_Touch_Settings', 'plugin_options'), 'dashicons-admin-links', 26);
	}

	static function load_css()
	{
	    $plugin_url = plugin_dir_url( __FILE__ );
	    wp_enqueue_style( 'style', $plugin_url . 'css/style.css' );
	}
}

register_activation_hook(__FILE__, array('Keep_In_Touch', 'activate'));
register_deactivation_hook(__FILE__, array('Keep_In_Touch', 'deactivate'));

add_action( 'init', array( 'Keep_In_Touch', 'init' ) );
add_action( 'widgets_init', array( 'Keep_In_Touch_Widget', 'register' ) );
add_action( 'admin_enqueue_scripts', array( 'Keep_In_Touch', 'load_css' ) );
add_action( 'admin_menu', array( 'Keep_In_Touch', 'plugin_menu' ) );

//How-to:
//http://wordpress.stackexchange.com/questions/139071/plugin-generated-virtual-pages
