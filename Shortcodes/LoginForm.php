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

if(!class_exists('wp_membership_Shortcode_LoginForm')) {
	class wp_membership_Shortcode_LoginForm implements IWPMembershipShortcode {
		/**
		 * @return Returns the tag name to hook
		 */
		function get_Shortcode() {
			return get_option('wp-membership_loginform_shortcode');
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
		    
		    if(@$_REQUEST['forgot_password'] == "1") {
				$retval .= "<form action=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."\" method=\"post\"> <input name=\"do_forgot_password\" type=\"hidden\" value=\"1\" />";
				$retval .= "<table border=\"0\">";
				$retval .= "<tbody>";
				$retval .= "<tr>";
				$retval .= "<td>".__('Email', 'wp-membership');
				$username_query = $wpdb->prepare("SELECT COUNT(*) AS Total FROM {$wpdb->prefix}wp_membership_users WHERE Username!=NULL");
				if($username_row = $wpdb->get_row($username_query, ARRAY_A)) {
					if($username_row['Total'] > 0) $retval .= " / ".__('Username', 'wp-membership');
				}
				$retval .= "</td>";
				$retval .= "<td><input style=\"background-color: #ffffa0;\" name=\"email\" type=\"text\" value=\"".htmlentities(@$_REQUEST['email'])."\" /></td>";
				$retval .= "</tr>";
				$retval .= "<tr>";
				$retval .= "<td colspan=\"2\"><input type=\"submit\" value=\"".__('Forgot Password', 'wp-membership')."\" /></td>";
				$retval .= "</tr>";
				$retval .= "</tbody></table>";
				$retval .= "</form>";
			}
			else {
				if(is_null($content) || trim($content) == '') {
					$retval .= "<form action=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."\" method=\"post\"> <input name=\"do_login\" type=\"hidden\" value=\"1\" />";
					$retval .= "<table border=\"0\">";
					$retval .= "<tbody>";
					$retval .= "<tr>";
					$retval .= "<td>".__('Email', 'wp-membership');
					$username_query = $wpdb->prepare("SELECT COUNT(*) AS Total FROM {$wpdb->prefix}wp_membership_users WHERE Username!=NULL");
					if($username_row = $wpdb->get_row($username_query, ARRAY_A)) {
						if($username_row['Total'] > 0) $retval .= " / ".__('Username', 'wp-membership');
					}
					$retval .= "</td>";
					$retval .= "<td><input style=\"background-color: #ffffa0;\" name=\"email\" type=\"text\" /></td>";
					$retval .= "</tr>";
					$retval .= "<tr>";
					$retval .= "<td>".__('Password', 'wp-membership')."</td>";
					$retval .= "<td><input name=\"password\" type=\"password\" /></td>";
					$retval .= "</tr>";
					$retval .= "<tr>";
					$retval .= "<td colspan=\"2\"><input type=\"submit\" value=\"".__('Login', 'wp-membership')."\" /></td>";
					$retval .= "</tr>";
					$retval .= "</tbody></table>";
					$retval .= "</form>";
				}
				else $retval .= $content;
	    		if($attributes['show_forgot_password'] == "1") {
	    			$retval .= "<div class=\"prompt_password\"><a href=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."&forgot_password=1&email=".urlencode(@$_REQUEST['email'])."\">Forgot Password?</a></div>";
	    		}
			}
			
			return $retval;
		}
	}
}
?>