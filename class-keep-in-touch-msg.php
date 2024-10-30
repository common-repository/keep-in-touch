<?php

defined('ABSPATH') or die ('No direct access to this file.');

require_once('class-keep-in-touch-utils.php');
require_once('class-keep-in-touch-options.php');
require_once('class-keep-in-touch-db.php');
require_once('class-virtual-page.php');

class Keep_In_Touch_Msg
{
	static $style_table = 'width: 100%; font-family: \'Arial\'; border-collapse: collapse; border: 1px solid #eee; border-bottom: 2px solid #000000; ';
	static $style_table_th = 'background: none; color: #000000; border-top: 2px solid #000000; border-bottom: 2px solid #000000; padding: 10px; border-collapse: collapse; text-align: left; ';
	static $style_table_td = 'background: none; color: #000000; padding: 10px; border-collapse: collapse; text-align: left; ';
	static $style_table_td_last = 'background: none; color: #000000; border-top: 2px solid #000000; padding: 10px; border-collapse: collapse; text-align: left; ';
	static $style_col_1 = 'text-align: left; width: 30%; ';
	static $style_col_2 = 'text-align: left; ';
	static $style_a = 'color: #000000; text-decoration: underline; ';

	static private function send_email_to_admin($title, $message)
	{
		wp_mail(
			get_bloginfo('admin_email'),
			Keep_In_Touch_Utils::get_blog_marker() . Keep_In_Touch_Utils::get_plugin_marker() . $title,
			self::get_email_heading() . $message,
			'Content-type: text/html'
		);
	}

	static function emit_subscription_anti_robot($email)
	{
		$use_full_name = (Keep_In_Touch_Options::get_option_use_full_name(false) == true);

		Virtual_Page::create_from_content(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' =>
				'<p>' . __('To complete the subscription request, please retype your email address below.', 'keep-in-touch') .
				($use_full_name ? __('We would also like to know your name so please also fill it in.', 'keep-in-touch') . '</p>' : '') . '</p>' .

				'<form method="post" action="' . Keep_In_Touch_Utils::get_page_path_from_slug(Keep_In_Touch_Utils::$PAGE_SLUG) . '">' .
				'<input type="hidden" name="keep_in_touch_email_reference" value="' . $email . '" />' .
				'<input type="text" placeholder="' . __('Enter email', 'keep-in-touch') . '" name="keep_in_touch_email" value="" style="width:100%" />' .
				($use_full_name ? '<br /><input type="text" placeholder="' . __('Enter your name', 'keep-in-touch') . '" name="keep_in_touch_full_name" value="" style="width:100%"/>' : '') .
				'<br /><input type="submit" name="keep_in_touch_submit" value="' . __('Sign up', 'keep-in-touch') . '" />' .
				'</form>' .
				''
		));
	}

	static function emit_invalid_anti_robot_check($email, $email_reference)
	{
		self::send_email_to_admin(
			__('Anti-robot check failed', 'keep-in-touch'), wpautop(sprintf(__(
			"An attempt to subscribe for updates using the following e-mail addresses has been rejected: %s, %s\n\n" .
			"This may be a legitimate attempt to register or an attack.\n" .
			"You should probably look into it either way.",
			'keep-in-touch'), $email_reference, $email)));

		Virtual_Page::create_from_content(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => wpautop(__(
				'The anti-robot check has failed. Your subscription request cannot be considered.',
				'keep-in-touch'))
		));
	}

	static function emit_confirm_subscription($email, $confirmation_code)
	{
		$link = home_url(Keep_In_Touch_Utils::get_page_path_from_slug(Keep_In_Touch_Utils::$PAGE_SLUG)) . '?confirmation_code=' . $confirmation_code;
		$common_content = wpautop(__('Thank you for subscribing to updates from us.', 'keep-in-touch'));

		wp_mail(
			$email,
			Keep_In_Touch_Utils::get_blog_marker() . Keep_In_Touch_Utils::get_plugin_marker() . __('Confirm subscription', 'keep-in-touch'),
			self::get_email_heading() . wpautop(
				$common_content .
				"\n\n" .
				sprintf(__('To confirm the subscription, use the following link: %s', 'keep-in-touch'), $link)),
			'Content-type: text/html'
		);

		// emit page or redirect? otherwise we get a 404
		Virtual_Page::create_from_content(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => wpautop(
				$common_content .
				"\n\n" .
				__('You will shortly receive an email message to confirm your subscription request.', 'keep-in-touch')) .
				'<p>' . self::get_junk_mail_notice_text() . '</p>',
		));
	}

	static function emit_subscription_request_failed($email)
	{
		self::send_email_to_admin(
			__('Subscription request failed', 'keep-in-touch'), wpautop(sprintf(__(
			"Subscription request failed for email address: %s\n\n" .
			"The cause of this error is not clear.\n" .
			"It may indicate a problem with your site or on the part of the user.\n" .
			"It may also indicate an attack.",
			'keep-in-touch'), $email)));

		$common_content = wpautop(__(
			"Your subscription request failed.\n\n" .
			"An email has been sent to us and we will handle your request shortly.",
			'keep-in-touch'));

		Virtual_Page::create_from_content(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => $common_content,
		));
	}

	static function emit_subscription_confirmation($email, $confirmation_code)
	{
		$common_content = wpautop(Keep_In_Touch_Options::get_option_subscription_confirmation_text(''));

		wp_mail(
			$email,
			Keep_In_Touch_Utils::get_blog_marker() . Keep_In_Touch_Utils::get_plugin_marker() . __('Subscription confirmed', 'keep-in-touch'),
			self::get_email_heading() .
				$common_content .
				'<p>&nbsp;</p>' .
				self::get_unsubscribe_text_from_email($email),
			'Content-type: text/html'
		);

		self::send_email_to_admin(
			__('New subscription successfully completed', 'keep-in-touch'), wpautop(sprintf(__(
			"Subscription request for email address %s has been successfully completed.\n\n" .
			"Congratulations! You have a new follower.\n\n" .
			"If the e-mail address looks suspicious you should check its genuineness.",
			'keep-in-touch'), $email)));

		Virtual_Page::create_from_content(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => $common_content,
		));
	}

	static function emit_confirm_cancellation($email, $confirmation_code)
	{
		$link = home_url(Keep_In_Touch_Utils::get_page_path_from_slug(Keep_In_Touch_Utils::$PAGE_SLUG) . '?confirmation_code=' . $confirmation_code);

		wp_mail(
			$email,
			Keep_In_Touch_Utils::get_blog_marker() . Keep_In_Touch_Utils::get_plugin_marker() . __('Confirm cancellation', 'keep-in-touch'),
			self::get_email_heading() . wpautop(sprintf(__(
				"We have received a request to cancel your subscription.\n\n" .
				"\n\n" .
				"To confirm the request, use the following link: %s",
				'keep-in-touch'), $link)),
			'Content-type: text/html'
		);

		Virtual_Page::create_from_content(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' =>
				__('You will shortly receive an email message to confirm the cancellation request.', 'keep-in-touch') .
				'<p>' . self::get_junk_mail_notice_text() . '</p>',
		));
	}

	static function emit_cancellation_request_failed($email)
	{
		self::send_email_to_admin(
			__('Cancellation request failed', 'keep-in-touch'), wpautop(sprintf(__(
			"Cancellation request failed for e-mail address: %s\n\n" .
			"The cause of this error is not clear.\n" .
			"It may indicate a problem with your site or on the part of the user.\n" .
			"It may also indicate an attack.",
			'keep-in-touch'), $email)));

		Virtual_Page::create_from_content(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => wpautop(__(
				"Your subscription cancellation request failed.\n\n" .
				"An email has been sent to us and we will handle your request shortly.",
				'keep-in-touch'))
		));
	}

	static function emit_cancellation_confirmation($email, $confirmation_code)
	{
		$common_content = wpautop(__(
			"Your subscription cancellation request has been confirmed.\n\n" .
			"You will no longer be receiving updates from us.\n\n" .
			"\n\n" .
			"Hope you change your mind and come back soon",
			'keep-in-touch'));

		wp_mail(
			$email,
			Keep_In_Touch_Utils::get_blog_marker() . Keep_In_Touch_Utils::get_plugin_marker() . __('Subscription cancellation confirmed', 'keep-in-touch'),
			self::get_email_heading() . $common_content,
			'Content-type: text/html'
		);

		self::send_email_to_admin(
			__('Subscription cancellation successfully completed', 'keep-in-touch'), wpautop(sprintf(__(
			"Subscription cancellation request for email address %s has been successfully completed.\n\n" .
			"Bummer! You have lost a follower.",
			'keep-in-touch'), $email)));

		Virtual_Page::create_from_content(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => $common_content,
		));
	}

	static function emit_invalid_code($email, $confirmation_code)
	{
		Virtual_Page::create_from_content(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => wpautop(sprintf(__(
				"Code %s is invalid.\n\n" .
				"You may have used an expired confirmation link.",
				'keep-in-touch'), $confirmation_code))
		));
	}

	static function emit_invalid_page()
	{
		Virtual_Page::create_from_content(array(
			'slug' => Keep_In_Touch_Utils::$PAGE_SLUG,
			'title' => __('Keep in Touch', 'keep-in-touch'),
			'content' => 'Nothing here!',
		));
	}

	static private function make_digest($query)
	{
		$message = $message .
			'<p>' . __('These are the articles and pages published or updated lately on our site:', 'keep-in-touch') . '</p>' .
			'<table style="' . self::$style_table . '">' .
			'<col style="' . self::$style_col_1 . '"><col style="' . self::$style_col_2 . '">' .
			'<thead>' .
			'<tr>' .
			'<th style="' . self::$style_table_th . self::$style_table_th . '">' . __('Date', 'keep-in-touch') . '</th>' .
			'<th style="' . self::$style_table_th . self::$style_table_th . '">' . __('Title', 'keep-in-touch') . '</th>' .
			'</tr>' .
			'</thead>' .
			'<tbody>';
		while ($query->have_posts())
		{
			$query->next_post();
			$message = $message .
				'<tr>' .
				'<td style="' . self::$style_table_td . '">' . get_the_modified_date('', $query->post->ID) . '</td>' .
				'<td style="' . self::$style_table_td . '"><a style="' . self::$style_a . '" href="' . get_permalink($query->post->ID) . '">' . get_the_title($query->post->ID) . '</a></td>' .
				'</tr>';
		}
		$message = $message .
			'<tr>' .
			'<td style="' . self::$style_table_td_last . '">&nbsp;</td>' .
			'<td style="' . self::$style_table_td_last . '">&nbsp;</td>' .
			'</tr>';
		$message = $message .
			'</tbody>' .
			'</table>';

		return $message;
	}

	static private function send_digest($query_args, $recipients, $title)
	{
		$query = new WP_Query($query_args);

		if ($query->found_posts == 0)
		{
			if (!Keep_In_Touch_Options::get_option_send_empty_digest_message(false))
				return;
			$message = wpautop(Keep_In_Touch_Options::get_option_empty_digest_message_text(''));
		}
		else
		{
			$message = self::make_digest($query);
		}

		self::send_mail_to_recipients(
			$recipients,
			Keep_In_Touch_Utils::get_blog_marker() . $title,
			$message
		);
	}

	static private function send_mail_to_recipients($recipients, $title, $message)
	{
		foreach ($recipients as $recipient)
			wp_mail(
				$recipient,
				$title,
				self::get_email_heading() .
					$message .
					'<p><small>' . self::get_unsubscribe_text_from_email($recipient) . '</small></p>',
				'Content-type: text/html'
			);
	}

	static private function get_configured_header_image_url()
	{
		if (Keep_In_Touch_Options::get_option_header_image_option('') == 'get_header_image')
		{
			return get_header_image();
		}

		if (Keep_In_Touch_Options::get_option_header_image_option('') == 'custom_path')
		{
			$custom_path = Keep_In_Touch_Options::get_option_header_image_custom_path('');
			if (Keep_In_Touch_Utils::starts_with('/', $custom_path))
				return get_home_url(null, $custom_path);
			else
				return $custom_path;
		}

		return "";
	}

	static private function get_email_heading()
	{
		return '<p><a href="' . get_home_url() . '" alt="' . get_bloginfo('name') . '"><img src="' . self::get_configured_header_image_url() . '"></a></p>';
	}

	static private function get_junk_mail_notice_text()
	{
		return __('Also check your junk mail folder as the message is sometimes place there.', 'keep-in-touch');
	}

	static private function get_unsubscribe_text_from_email($email)
	{
		$link = Keep_In_Touch_Utils::get_unsubscribe_link_from_email($email);
		$a = sprintf('<a href="%s" style="%s">%s</a>', $link, self::$style_a, $link);
		return sprintf(__('To cancel your subscription. use the following link: %s', 'keep-in-touch'), $a);
	}

	static function send_newsletter($title, $text, $recipients)
	{
		self::send_mail_to_recipients(
			empty($recipients) ? Keep_In_Touch_Db::get_emails_of_all_confirmed_newsletter_subscribers() : $recipients,
			Keep_In_Touch_Utils::get_blog_marker() . sprintf(__('Newsletter: %s', 'keep-in-touch'), $title),
			$text
		);
	}

	static function send_daily_digest($recipients)
	{
		$digest = self::send_digest(array(
			'post_type' => array( 'post', 'page' ),
			'date_query' => array(
				array(
					//'column'    => 'post_modified_gmt',
					'column'    => 'post_date_gmt',
					'after'     => date('Y-m-d', strtotime('yesterday')),
					//'before'    => date('Y-m-d', strtotime('today')),
					'inclusive' => true,
				),
			),
			'posts_per_page' => -1,
		), empty($recipients) ? Keep_In_Touch_Db::get_emails_of_all_confirmed_daily_digest_subscribers() : $recipients, __('Daily digest', 'keep-in-touch'));
	}

	static function send_weekly_digest($recipients)
	{
		$digest = self::send_digest(array(
			'post_type' => array( 'post', 'page' ),
			'date_query' => array(
				array(
					//'column'    => 'post_modified_gmt',
					'column'    => 'post_date_gmt',
					'after'     => date('Y-m-d', strtotime('-1 week')),
					'inclusive' => true,
				),
			),
			'posts_per_page' => -1,
		), empty($recipients) ? Keep_In_Touch_Db::get_emails_of_all_confirmed_weekly_digest_subscribers() : $recipients, __('Weekly digest', 'keep-in-touch'));
	}
}
