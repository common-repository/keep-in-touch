<?php

defined('ABSPATH') or die ('No direct access to this file.');

require_once('class-keep-in-touch-utils.php');

class Keep_In_Touch_Widget extends WP_Widget
{
	public function Keep_In_Touch_Widget()
	{
		$this->WP_Widget(
			'wp_keepintouch',
			__('Keep in Touch', 'keep-in-touch'),
			array('description' => __('Displays a form that allows visitors to subscribe for updates.', 'keep-in-touch'),)
		);
	}

	public function widget($args, $instance)
	{
		extract( $args );
		$page_url = Keep_In_Touch_Utils::get_page_path_from_slug(Keep_In_Touch_Utils::$PAGE_SLUG);
		$feed_url = home_url(Keep_In_Touch_Utils::get_page_path_from_slug('feed'));
		$icon_url = plugins_url( 'rss-logo.png', __FILE__ );
		$user_email = '';
		if (is_user_logged_in())
		{
			global $current_user;
			get_currentuserinfo();
			$user_email = $current_user->user_email;
		}
		$div_table = 'display:table;table-layout:auto;width:100%;border-spacing:5px;';
		$div_table_row = 'display:table-row;width:auto;clear:both;';
		$div_table_col = 'float:left;display:table-column';

		echo $before_widget;
		echo $before_title;
		echo __('Keep in Touch', 'keep-in-touch');
		echo $after_title;?>
		<a name="keep_in_touch_widget"></a>
		<p><?php  echo __('Receive a weekly digest of the posts.', 'keep-in-touch'); ?></p>
		<form method="post" action="<?php echo $page_url; ?>">
			<table style="<?php echo $div_table; ?>">
				<tr style="<?php echo $div_table_row; ?>">
					<input placeholder="<?php echo __('Enter email', 'keep-in-touch'); ?>" name="keep_in_touch_email" onClick="this.setSelectionRange(0, this.value.length)" value="<?php echo $user_email; ?>" style="display:table-cell;width:100%" />
				</tr>
				<?php if (!Keep_In_Touch_Options::get_option_use_anti_robot_page(false) and Keep_In_Touch_Options::get_option_use_full_name(false)) { ?>
				<tr style="<?php echo $div_table_row; ?>">
					<input placeholder="<?php echo __('Enter your name', 'keep-in-touch'); ?>" name="keep_in_touch_full_name" onClick="this.setSelectionRange(0, this.value.length)" value="" style="display:table-cell;width:100%" />
				</tr>
				<?php } ?>
				<tr style="<?php echo $div_table_row; ?>">
					<input type="submit" name="keep_in_touch_submit" value="<?php echo __('Sign up', 'keep-in-touch'); ?>"/>
					<a href="<?php echo $feed_url; ?>" style="display:block;float:right;">
						<img src="<?php echo $icon_url; ?>" style="width:auto;height:52px;"/>
					</a>
				</tr>
			</table>
		</form><?php
		echo $after_widget;
	}

	public function form($instance)
	{
	}

	public function update($new_instance, $old_instance)
	{
	}

	public static function register()
	{
		register_widget('Keep_In_Touch_Widget');
	}
}
