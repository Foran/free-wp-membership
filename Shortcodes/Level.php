<?php
/*
 This file is part of Free WP-Membership.

    Free WP-Membership is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Free WP-Membership is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.

*/

$wp_membership_shortcodes_loginform_path = pathinfo(__FILE__);
require_once(ereg_replace("/Shortcodes\$", "", $wp_membership_shortcodes_loginform_path['dirname']).'/interfaces/IWPMembershipShortcode.php');

if(!class_exists('wp_membership_Shortcode_Level')) {
	class wp_membership_Shortcode_Level implements IWPMembershipShortcode {
		/**
		 * @return Returns the tag name to hook
		 */
		function get_Shortcode() {
			return get_option('wp-membership_level_shortcode', 'FWPM_Level');
		}
		
		/**
		 * Shortcode handler for the Login Form
		 *
		 * @param array $atts array of attributes
		 * @param string $content text within enclosing form of shortcode element
		 * @param string $code the shortcode found, when == callback name
		 * @return string content to substitute
		 */
		function Handler($atts, $content=null, $code="") {
			$retval = "";

			global $wpdb, $wp_query, $wp_membership_plugin;
			
			$attributes = shortcode_atts(array('show_forgot_password' => get_option('wp-membership_login_prompt_forgot_password')), $atts);

			load_plugin_textdomain('wp-membership', false, $wp_membership_plugin->language_path);

		    $page_id = isset($wp_query->queried_object->ID) ? $wp_query->queried_object->ID : "";
		    
			if(!is_null($content) && trim($content) == '') {
				$retval .= $content;
			}
			
			return $retval;
		}
	}
}
?>