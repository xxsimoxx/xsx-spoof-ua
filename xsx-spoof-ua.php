<?php

/**
 * -----------------------------------------------------------------------------
 * Plugin Name: Spoof User Agent
 * Description: Change ClassicPress User Agent
 * Version: 1.0.0
 * Requires PHP: 5.6
 * Requires CP: 2.3
 * Author: Simone Fioravanti
 * Author URI: https://software.gieffeedizioni.it
 * Plugin URI: https://software.gieffeedizioni.it
 * Text Domain: xsx-spoof-ua
 * Domain Path: /languages
 * License: GPL2
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * -----------------------------------------------------------------------------
 * This is free software released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.txt.
 * -----------------------------------------------------------------------------
 */

namespace XXSimoXX\SpoofUserAgent;



class SpoofUserAgent {

	public function __construct() {
		add_action('admin_menu', [$this, 'create_settings_menu'], 100);
		add_filter('http_headers_useragent', [$this, 'spoof']);
		register_uninstall_hook(__FILE__, [__CLASS__, 'uninstall']);
	}

	public function create_settings_menu() {
		$screen = add_submenu_page(
			'options-general.php',
			esc_html__('Spoof User Agent', 'xsx-spoof-ua'),
			esc_html__('Spoof User Agent', 'xsx-spoof-ua'),
			'manage_options',
			'xsx-spoof-ua',
			[$this, 'render_menu']
		);
	}

	public function spoof($user_agent) {
		return get_option('xsx_spoof_ua_ua', $this->wp_user_agent());
	}

	function display_notice($message, $failure = false) {
		$notice = wp_get_admin_notice(
			$message,
			[
				'type'        => $failure ? 'error' : 'success',
				'dismissible' => true,
			]
		);
		echo wp_kses_post($notice);
	}

	public function render_menu () {
		echo '<div class="wrap">';
		echo '<h1>'.esc_html__('Spoof ClassicPress User Agent', 'xsx-spoof-ua').'</h1>';

		$ua = get_option('xsx_spoof_ua_ua', $this->wp_user_agent());

		if (isset($_REQUEST['update_ua']) && check_admin_referer('spoofua', '_xsua')) {
			$new_ua = sanitize_text_field(wp_unslash($_REQUEST['update_ua']));
			if (wp_unslash($_REQUEST['update_ua']) !== $new_ua) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$this->display_notice(__('User Agent is not valid.', 'xsx-spoof-ua'), true);
			} elseif ($new_ua === $ua) {
				$this->display_notice(__('User Agent is not changed.', 'xsx-spoof-ua'), true);
			} else {
				update_option('xsx_spoof_ua_ua', $new_ua);
				$this->display_notice(__('User Agent updated.', 'xsx-spoof-ua'));
			}
		}

		echo '<form action="'.esc_url_raw(admin_url('options-general.php?page=xsx-spoof-ua')).'" method="POST">';
		wp_nonce_field('spoofua', '_xsua');

		$ua = get_option('xsx_spoof_ua_ua', $this->wp_user_agent());

		echo '<label for="new_ua">'.esc_html__('User Agent: ', 'xsx-spoof-ua').'</label>';
		echo '<input type="text" size="50" name="update_ua" id="update_ua" value="'.esc_html($ua).'"></input>';
		echo '<input type="submit" class="button button-primary" id="submit_button" value="'.esc_html__('Update User Agent', 'xsx-spoof-ua').'"></input> ';
		// echo '<input type="button" class="button" id="reset_button" value="'.esc_html__('Use WordPress', 'xsx-spoof-ua').'"></input> ';

		echo '<p>'.esc_html('Default ClassicPress User Agent: ').'<code>'.esc_html($this->classicpress_user_agent()).'</code></p>';
		echo '<p>'.esc_html('Default ClassicPress User Agent with site ID: ').'<code>'.esc_html($this->classicpress_user_agent(true)).'</code></p>';
		echo '<p>'.esc_html('Default WordPress User Agent: ').'<code>'.esc_html($this->wp_user_agent()).'</code></p>';
		echo '<p><b>'.esc_html('Filtered User Agent: ').'</b><code>'.esc_html(classicpress_user_agent()).'</code></p>';

		echo '</div>';
	}

	private function classicpress_user_agent($include_site_id = false) {
		$url = 'https://www.classicpress.net/?wp_compatible=true&ver='.classicpress_version_short();
		if ($include_site_id) {
			$url .= '&site='.sha1(preg_replace(
				'#^https?:#',
				'',
				strtolower(untrailingslashit(home_url('/')))
			));
		}
		return 'WordPress/'.get_bloginfo('version').'; '.$url;
	}

	private function wp_user_agent() {
		return 'WordPress/'.get_bloginfo('version').'; '.get_bloginfo('url');
	}

	public static function uninstall() {
		delete_option('xsx_spoof_ua_ua');
	}

}

new SpoofUserAgent;

