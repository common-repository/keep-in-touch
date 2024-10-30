<?php

defined('ABSPATH') or die ('No direct access to this file.');

require_once(ABSPATH . 'wp-includes/locale.php');
require_once('class-keep-in-touch-utils.php');
require_once('class-keep-in-touch-options.php');
require_once('class-keep-in-touch-msg.php');

class Keep_In_Touch_Schedule
{
	static function schedule_events()
	{
		self::reschedule_daily_event();
		self::reschedule_weekly_event();
	}

	static function unschedule_events()
	{
		wp_clear_scheduled_hook('keep_in_touch_daily_event_hook');
		wp_clear_scheduled_hook('keep_in_touch_weekly_event_hook');
	}

	static function handle_daily_event()
	{
		Keep_In_Touch_Msg::send_daily_digest();
	}

	static function handle_weekly_event()
	{
		Keep_In_Touch_Msg::send_weekly_digest();
	}

  static function add_my_schedules($schedules)
	{
		$schedules['every_minute'] = array(
			'interval' => MINUTE_IN_SECONDS,
			'display' => __('Every minute')
		);
		$schedules['weekly'] = array(
			'interval' => 7 * DAY_IN_SECONDS,
			'display' => __('Weekly')
		);
		return $schedules;
	}

	static function reschedule_daily_event()
	{
		wp_clear_scheduled_hook('keep_in_touch_daily_event_hook');
		$t = strtotime(Keep_In_Touch_Options::get_option_delivery_time(0, 0));
		wp_schedule_event(($t < time()) ? $t + DAY_IN_SECONDS : $t, 'daily', 'keep_in_touch_daily_event_hook');
	}

	static function reschedule_weekly_event()
	{
		wp_clear_scheduled_hook('keep_in_touch_weekly_event_hook');
		//$t0 = Keep_In_Touch_Options::get_option_delivery_time(0, 0) . ' ' . Keep_In_Touch_Utils::format_time_offset(get_option('gmt_offset') * 3600);
		$t0 = Keep_In_Touch_Options::convert_option_weekday_to_weekday_name(Keep_In_Touch_Options::get_option_delivery_weekday(0)) . ' ' . Keep_In_Touch_Options::get_option_delivery_time(0, 0);
		$t1 = strtotime($t0);
		$t2 = strtotime('next ' . $t0);
		$t = ($t1 > floor(time() / 60) * 60) ? $t1 : $t2;

		wp_schedule_event($t2, 'weekly', 'keep_in_touch_weekly_event_hook');
	}

	static function init()
	{
		add_action( 'keep_in_touch_daily_event_hook', array( 'Keep_In_Touch_Schedule', 'handle_daily_event' ) );
		add_action( 'keep_in_touch_weekly_event_hook', array( 'Keep_In_Touch_Schedule', 'handle_weekly_event' ) );
		add_filter( 'cron_schedules', array('Keep_In_Touch_Schedule', 'add_my_schedules' ) );
	}
}
