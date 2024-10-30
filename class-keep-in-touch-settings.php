<?php

defined('ABSPATH') or die ('No direct access to this file.');

require_once(ABSPATH . 'wp-includes/locale.php');
require_once('class-keep-in-touch-utils.php');
require_once('class-keep-in-touch-options.php');
require_once('class-keep-in-touch-msg.php');

class Keep_In_Touch_Settings
{
	static function plugin_options()
	{
		self::generate_plugin_settings_page();
	}

	static private function generate_subscription_settings_section()
	{
		if ($_POST['form'] == 'keep_in_touch_subscription_settings')
		{
			Keep_In_Touch_Options::update_option_use_anti_robot_page($_POST['use_anti_robot_page']);
			Keep_In_Touch_Options::update_option_use_full_name($_POST['use_full_name']);
			Keep_In_Touch_Options::update_option_subscription_confirmation_text($_POST['subscription_confirmation_text']);
			Keep_In_Touch_Options::update_option_send_empty_digest_message($_POST['send_empty_digest_message']);
			Keep_In_Touch_Options::update_option_empty_digest_message_text($_POST['empty_digest_message_text']);
		}
		?>
		<div class="postbox">
			<h3 class="hndle"><?php echo __('Subscription settings', 'keep-in-touch'); ?></h3>
			<div class="inside">
				<form method="POST" action="<?php echo admin_url('options-general.php?page=keep-in-touch'); ?>">
					<input type="hidden" name="form" value="keep_in_touch_subscription_settings" />
					<table class="form-table">
						<tr>
							<th scope="row"><?php echo __('Use anti-robot page', 'keep-in-touch'); ?></th>
							<td><input type="checkbox" name="use_anti_robot_page" value="yes" <?php $checked = Keep_In_Touch_Options::get_option_use_anti_robot_page(false); echo Keep_In_Touch_Utils::checkbox_state($checked); ?> /></td>
						</tr>
						<tr>
							<th scope="row"><?php echo __('Request full name', 'keep-in-touch'); ?></th>
							<td><input type="checkbox" name="use_full_name" value="yes" <?php $checked = Keep_In_Touch_Options::get_option_use_full_name(false); echo Keep_In_Touch_Utils::checkbox_state($checked); ?> /></td>
						</tr>
						<tr>
							<th scope="row"><?php echo __('Successful subscription message', 'keep-in-touch'); ?></th>
							<td><textarea type="text" name="subscription_confirmation_text" style="height:8em"><?php echo Keep_In_Touch_Options::get_option_subscription_confirmation_text(''); ?></textarea></td>
						</tr>
						<tr>
							<th scope="row"><?php echo __('Send empty digest message', 'keep-in-touch'); ?></th>
							<td><input type="checkbox" name="send_empty_digest_message" value="yes" <?php $checked = Keep_In_Touch_Options::get_option_send_empty_digest_message(false); echo Keep_In_Touch_Utils::checkbox_state($checked); ?> /></td>
						</tr>
						<tr>
							<th scope="row"><?php echo __('Empty digest message', 'keep-in-touch'); ?></th>
							<td><textarea type="text" name="empty_digest_message_text" style="height:8em"><?php echo Keep_In_Touch_Options::get_option_empty_digest_message_text(''); ?></textarea></td>
						</tr>
						<tr>
							<th scope="row"></th>
							<td><input type="submit" class="button-primary" value="<?php echo __('Save settings', 'keep-in-touch'); ?>"/></td>
						</tr>
					</table>
				</form>
			</div>
		</div>
		<?php
	}

	static private function generate_weekday_selection_options()
	{
		$wp_locale = new WP_Locale();
		for ($wdi = 0; $wdi < 7; $wdi++)
		{
			$selected = (($wdi == Keep_In_Touch_Options::get_option_delivery_weekday(0)) ? 'selected="selected" ' : '');
			echo '<option ' . $selected . 'value="' . $wdi . '">' . $wp_locale->get_weekday($wdi) . '</option>';
		}
	}

	static private function generate_hour_selection_options()
	{
		for ($h = 0; $h < 24; $h++)
			echo '<option ' . (($h==self::get_hour(Keep_In_Touch_Options::get_option_delivery_time(0, 0))) ? 'selected="selected" ' : '') . 'value="' . sprintf('%02d', $h) . '">' . sprintf('%02d', $h) . '</option>';
	}

	static private function generate_minute_selection_options()
	{
		foreach (array(0, 15, 30, 45) as $m)
		{
			$selected = (($m==self::get_minute(Keep_In_Touch_Options::get_option_delivery_time(0, 0))) ? 'selected="selected" ' : '');
			$value = sprintf('%02d', $m);
			echo '<option ' . $selected . 'value="' . $value . '">' . $value . '</option>';
		}
	}


	static private function generate_digest_delivery_settings_section()
	{
		if ($_POST['form'] == 'keep_in_touch_digest_delivery_settings')
		{
			Keep_In_Touch_Options::update_option_delivery_weekday($_POST['delivery_weekday']);
			Keep_In_Touch_Options::update_option_delivery_time($_POST['delivery_hour'], $_POST['delivery_minute']);
			Keep_In_Touch_Schedule::reschedule_daily_event();
			Keep_In_Touch_Schedule::reschedule_weekly_event();
			Keep_In_Touch_Options::update_option_header_image_option($value);
			Keep_In_Touch_Options::update_option_header_image_custom_path($_POST['header_image_custom_path']);
		}
		?>
		<div class="postbox">
			<h3 class="hndle"><?php echo __('Digest delivery settings', 'keep-in-touch'); ?></h3>
			<div class="inside">
				<form method="POST" action="<?php echo admin_url('options-general.php?page=keep-in-touch'); ?>">
					<input type="hidden" name="form" value="keep_in_touch_digest_delivery_settings"></input>
					<table class="form-table">
						<tr>
							<th scope="row"><?php echo __('Delivery weekday', 'keep-in-touch'); ?></th>
							<td>
								<select name="delivery_weekday">
									<?php self::generate_weekday_selection_options(); ?>
								</select>
								<p class="description"><?php echo __('The day of the week in which the weekly digest will be delivered', 'keep-in-touch'); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php echo __('Delivery time', 'keep-in-touch'); ?></th>
							<td>
								<select name="delivery_hour">
									<?php self::generate_hour_selection_options(); ?>
								</select>
								&nbsp;:&nbsp;
								<select name="delivery_minute">
									<?php self::generate_minute_selection_options(); ?>
								</select>
								<p class="description"><?php echo __('The time of the day in which the daily and weekly digests will be delivered', 'keep-in-touch'); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row"><?php echo __('Header image', 'keep-in-touch'); ?></th>
							<td>
								<select name="header_image_option">
									<option value="get_header_image" <?php echo ((Keep_In_Touch_Options::get_option_header_image_option('') == 'get_header_image') ? 'selected="selected"' : ''); ?>><?php echo __('Use get_header_image()', 'keep-in-touch'); ?></option>
									<option value="custom_path" <?php echo ((Keep_In_Touch_Options::get_option_header_image_option('') == 'custom_path') ? 'selected="selected"' : ''); ?>><?php echo __('Use custom path', 'keep-in-touch'); ?></option>
								</select>
								<input type="text" name="header_image_custom_path" placeholder="<?php echo __('Enter custom path to header image', 'keep-in-touch'); ?>" size="80" value="<?php echo Keep_In_Touch_Options::get_option_header_image_custom_path(''); ?>" />
							</td>
						</tr>

						<tr>
							<th scope="row"></th>
							<td><input type="submit" name="save_settings" class="button-primary" value="<?php echo __('Save settings', 'keep-in-touch'); ?>"/></td>
						</tr>
					</table class="form-table">
				</form>
			</div>
		</div>
		<?php
	}

	static private function generate_send_digest_section()
	{
		if ($_POST['form'] == 'keep_in_touch_send_digest_now')
		{
			if (isset($_POST['send_daily_digest']))
			{
				Keep_In_Touch_Msg::send_daily_digest(self::explode_recipients($_POST['digest_recipients']));
			}
			else if (isset($_POST['send_weekly_digest']))
			{
				Keep_In_Touch_Msg::send_weekly_digest(self::explode_recipients($_POST['digest_recipients']));
			}
		}
		$email_list_text = __('Enter comma-separated list of email addresses', 'keep-in-touch');
		$email_list_explanation = sprintf(__('or leave empty to send to %d daily digest or %d weekly digest confirmed subscribers', 'keep-in-touch'), count(Keep_In_Touch_Db::get_all_confirmed_daily_digest_subscribers()), count(Keep_In_Touch_Db::get_all_confirmed_weekly_digest_subscribers()));
		?>
		<div class="postbox">
			<h3 class="hndle"><?php echo __('Send digest now', 'keep-in-touch'); ?></h3>
			<div class="inside">
				<form method="POST" action="<?php echo admin_url('options-general.php?page=keep-in-touch'); ?>">
					<input type="hidden" name="form" value="keep_in_touch_send_digest_now" />
					<table class="form-table">
						<tr>
							<th scope="row"><?php echo __('Email addresses to send to', 'keep-in-touch'); ?></th>
							<td>
								<input type="text" name="digest_recipients" placeholder="<?php echo $email_list_text; ?>"/>
								<p class="description"><?php echo $email_list_explanation; ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"></th>
							<td>
								<input type="submit" name="send_daily_digest" class="button-primary" value="<?php echo __('Send daily digest now', 'keep-in-touch') ?>"/>&nbsp;
								<input type="submit" name="send_weekly_digest" class="button-primary" value="<?php echo __('Send weekly digest now', 'keep-in-touch') ?>"/>
							</td>
						</tr>
					</table>
				</form>
			</div>
		</div>
		<?php
	}

	static private function generate_send_newsletter_section()
	{
		if ($_POST['form'] == 'keep_in_touch_send_newsletter' and isset($_POST['send_newsletter']))
		{
			Keep_In_Touch_Msg::send_newsletter(
				$_POST['newsletter_message_title'],
				stripcslashes($_POST['newsletter_message_text']),
				self::explode_recipients($_POST['newsletter_recipients'])
			);
		}
		$email_list_text = __('Enter comma-separated list of email addresses', 'keep-in-touch');
		$email_list_explanation = sprintf(__('or leave empty to send to %d confirmed newsletter subscribers', 'keep-in-touch'), count(Keep_In_Touch_Db::get_all_confirmed_newsletter_subscribers()));
		?>
		<div class="postbox">
			<h3 class="hndle"><?php echo __('Newsletter', 'keep-in-touch'); ?></h3>
			<div class="inside">
				<form method="POST" action="<?php echo admin_url('options-general.php?page=keep-in-touch'); ?>">
					<input type="hidden" name="form" value="keep_in_touch_send_newsletter"/>
					<table class="form-table">
						<tr>
							<th scope="row"><?php echo __('Email addresses to send to', 'keep-in-touch'); ?></th>
							<td>
								<input type="text" name="newsletter_recipients" placeholder="<?php echo $email_list_text; ?>"/>
								<p class="description"><?php echo $email_list_explanation; ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php echo __('Subject', 'keep-in-touch'); ?></th>
							<td>
								<input type="text" name="newsletter_message_title" placeholder="<?php echo __('Enter the subject of the newsletter message', 'keep-in-touch'); ?>"/>
						</tr>
						<tr>
							<th scope="row"><?php echo __('Message', 'keep-in-touch'); ?></th>
							<td>
								<?php
								wp_editor(
									'',
									'newsletter_message_text',
									array(
										'textarea_name' => 'newsletter_message_text',
										'drag_drop_upload' => 'true',
										'editor_css' => '<style>textarea {height: 15em}</style>',
										'media_buttons' => true,
									)
								);
								?>
							</td>
						</tr>
						<tr>
							<th scope="row"></th>
							<td><input type="submit" name="send_newsletter" class="button-primary" value="<?php echo __('Send newsletter message', 'keep-in-touch'); ?>" /></td>
						</tr>
					</table>
				</form>
			</div>
		</div>
		<?php
	}

	static private function generate_subscribers_list()
	{
		$use_full_name = Keep_In_Touch_Options::get_option_use_full_name(false);
		$subscribers = Keep_In_Touch_Db::get_all_confirmed_subscribers();
		echo '<div class="postbox">';
		echo '<h3 class="hndle">' . __('Subscribers list', 'keep-in-touch') . ' (' . count($subscribers) . ')</h3>';
		echo '<div class="inside">';
		foreach ($subscribers as $subscriber)
		{
			$full_name = (!Keep_In_Touch_Utils::is_null_or_empty_string($subscriber->full_name)) ? $subscriber->full_name : __('[not available]', 'keep-in-touch');
			echo '<p style="padding-left:1.5em;text-indent:-1.5em;">' .
				($use_full_name ? $full_name . '<br/>' : '') .
				$subscriber->email .
				'</p>';
		}
		echo '</div>';
		echo '</div>';
	}

	static private function generate_plugin_settings_page()
	{
		if (!current_user_can('manage_options'))
			wp_die(__('You do not have sufficient permissions to access this page.'));
		?>
		<div class="wrap">
			<div id="poststuff">
				<div id="post-body" class="metabox-holder columns-2">

					<div id="postbox-container-2" class="postbox-container">
						<?php self::generate_subscription_settings_section(); ?>
						<?php self::generate_digest_delivery_settings_section(); ?>
						<?php self::generate_send_digest_section(); ?>
						<?php self::generate_send_newsletter_section(); ?>
					</div>

					<div id="postbox-container-1" class="postbox-container">
						<?php self::generate_subscribers_list(); ?>
					</div>

				</div>
			</div>
		</div>
		<?php
	}

	static private function get_hour($time, $default)
	{
		if (empty($time))
			return $default;
		return intval(date('H', strtotime($time)));
	}

	static private function get_minute($time, $default)
	{
		if (empty($time))
			return $default;
		return intval(date('i', strtotime($time)));
	}

	static private function explode_recipients($input)
	{
		return array_map('trim', Keep_In_Touch_Utils::explode(',', trim($input)));
	}
}
