<?php

defined('ABSPATH') or die ('No direct access to this file.');

class Keep_In_Touch_Options
{
	static function update_option_use_anti_robot_page($value)
	{
    update_option('keep_in_touch_use_anti_robot_page', self::options_bool($value));
  }

  static function get_option_use_anti_robot_page($default = null)
  {
    $value = get_option('keep_in_touch_use_anti_robot_page', $default);
    if ( $value == null ) return $default;
    return self::bool($value);
  }

	static function update_option_use_full_name($value)
	{
    update_option('keep_in_touch_use_full_name', self::options_bool($value));
  }

  static function get_option_use_full_name($default = null)
  {
    $value = get_option('keep_in_touch_use_full_name', $default);
    if ( $value == null ) return $default;
    return self::bool($value);
  }

  static function update_option_subscription_confirmation_text($value)
  {
    update_option('keep_in_touch_subscription_confirmation_text', stripslashes(trim($value)));
	}

  static function get_option_subscription_confirmation_text($default = null)
  {
    return get_option('keep_in_touch_subscription_confirmation_text', $default);
  }

  static function update_option_send_empty_digest_message($value)
  {
    update_option('keep_in_touch_send_empty_digest_message', self::options_bool($value));
  }

  static function get_option_send_empty_digest_message($default = null)
  {
    $value = get_option('keep_in_touch_send_empty_digest_message', $default);
    if ( $value == null ) return $default;
    return self::bool($value);
  }

  static function update_option_empty_digest_message_text($value)
  {
    update_option('keep_in_touch_empty_digest_message_text', stripslashes(trim($value)));
  }

  static function get_option_empty_digest_message_text($default = null)
  {
    return get_option('keep_in_touch_empty_digest_message_text', $default);
  }

  static function update_option_delivery_weekday($value)
  {
    update_option('keep_in_touch_delivery_weekday', $value);
  }

  static function get_option_delivery_weekday($default = null)
  {
    return get_option('keep_in_touch_delivery_weekday', $default);
  }

  static function convert_option_weekday_to_weekday_name($option)
	{
		$days = array('sunday','monday','tuesday','wednesday','thursday','friday','saturday');
    return $days[$option];
	}

  static function update_option_delivery_time($hour, $minute)
  {
    update_option('keep_in_touch_delivery_time', self::format_time($hour * 3600 + $minute * 60));
  }

  static function get_option_delivery_time($default_hour = null, $default_minute = null)
  {
    if ( $default_hour == null or $default_minute == null )
        return get_option('keep_in_touch_delivery_time', null);
    return get_option('keep_in_touch_delivery_time', self::format_time($default_hour * 3600 + $default_minute * 60));
  }

  static function update_option_header_image_option($value)
  {
    update_option('keep_in_touch_header_image_option', $value);
  }

  static function get_option_header_image_option($default)
  {
    return get_option('keep_in_touch_header_image_option', $default);
  }

  static function update_option_header_image_custom_path($value)
  {
    update_option('keep_in_touch_header_image_custom_path', trim($value));
  }

	static function get_option_header_image_custom_path($default)
	{
		return get_option('keep_in_touch_header_image_custom_path', $default);
	}

  static private function bool($option)
	{
		if (isset($option) and ($option == 'no')) return false;
		return (bool)$option;
	}

	static private function options_bool($option)
	{
		return (self::bool($option) ? 'yes' : 'no');
	}

  static private function format_time($seconds)
	{
		return date('H:i', abs($seconds));
	}
}
