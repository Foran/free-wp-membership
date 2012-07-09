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
    along with Free WP-Membership.  If not, see <http://www.gnu.org/licenses/>.

*/
require_once(FWP_MEMBERSHIP_PATH.'interfaces/IWPMembershipShortcode.php');

if(!class_exists('wp_membership_Shortcode_UserProfileForm')) {
	class wp_membership_Shortcode_UserProfileForm implements IWPMembershipShortcode {
		/**
		 * @return Returns the tag name to hook
		 */
		function get_Shortcode() {
			return get_option('wp-membership_user_profile_from_shortcode');
		}
		
		/**
		 * Shortcode handler for the User Profile Form
		 *
		 * @param array $atts array of attributes
		 * @param string $content text within enclosing form of shortcode element
		 * @param string $code the shortcode found, when == callback name
		 * @return string content to substitute
		 */
		function Handler($atts, $content=null, $code="") {
			$retval = "";
			
			global $wpdb, $wp_membership_plugin;

			$user_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_users AS t1 WHERE t1.User_ID=%s", @$_SESSION['wp-membership_plugin']['wp-membership_user_id']);
			if($user_row = $wpdb->get_row($user_query, ARRAY_A)) {
				if(@$_REQUEST['do_userprofile'] == "1") {
					if(strlen(trim(@$_REQUEST['password'])) > 0 || strlen(trim(@$_REQUEST['password2'])) > 0) {
						if(strlen(trim(@$_REQUEST['password'])) > 0) {
							if(trim(@$_REQUEST['password']) == trim(@$_REQUEST['password2'])) {
								$update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}wp_membership_users SET Password=PASSWORD(%s) WHERE User_ID=%s", trim(@$_REQUEST['password']), $user_row['User_ID']);
								if($wpdb->query($update_query) !== false) {
							    	$update_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET WP_Password=%s WHERE User_ID=%s", wp_hash_password(trim(@$_REQUEST['password'])), $user_row['User_ID']);
						    		$wpdb->query($update_query);
									$retval .= "<p>Successfully Updated Password</p>";
								}
								else {
									$retval .= "<p>Failed to update password</p>";
								}
							}
							else {
								$retval .= "<p>Passwords must match</p>";
							}
						}
						else {
							$retval .= "<p>Password can not be blank</p>";
						}
					}
					$update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}wp_membership_users SET Email=%s WHERE User_ID=%s", trim(@$_REQUEST['email']), $user_row['User_ID']);
					if($wpdb->query($update_query) !== false) {
						$retval .= "<p>Successfully Updated Profile</p>";
						$user_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_users AS t1 WHERE t1.User_ID=%s", @$_SESSION['wp-membership_plugin']['wp-membership_user_id']);
						$tmp = $user_row;
						if(!($user_row = $wpdb->get_row($user_query, ARRAY_A))) {
							$user_row = $tmp;
						}
					}
					else {
						$retval .= "<p>Failed to update profile</p>";
					}
				}
				else if(@$_REQUEST['do_unsubscribe'] == "1") {
					$userlevel_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_user_levels AS t1 WHERE t1.User_ID=%s AND t1.User_Level_ID=%s", @$_SESSION['wp-membership_plugin']['wp-membership_user_id'], $_REQUEST['userlevelid']);
					if($userlevel_row = $wpdb->get_row($userlevel_query, ARRAY_A)) {
						$plugin = null;
						if(is_array($wp_membership_plugin->plugins)) {
							foreach($wp_membership_plugin->plugins as $p) {
								if($p->has_Subscription(@$_REQUEST['userlevelid'])) {
									$plugin = $p;
								}
							}
							if(!is_null($plugin)) {
								if($plugin->Uninstall_Subscription($plugin->find_Subscription(@$_REQUEST['userlevelid'])) !== false) {
									$retval .= '<p>Successfully unsubscribed</p>';
								}
								else $retval .= '<p>Failed to unsubscribe</p>';
							}
							else $retval .= '<p>Failed to unsubscribe</p>';
						}
						else $retval .= '<p>Failed to unsubscribe</p>';
					}
					else $retval .= '<p>Failed to unsubscribe</p>';
				}
				$retval .= "<table border=\"0\">";
				$retval .= "<form action=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode(@$_REQUEST['page_id'])."\" method=\"post\"> <input name=\"do_userprofile\" type=\"hidden\" value=\"1\" />";
				$retval .= "<tr>";
				$retval .= "<td>Email</td>";
				$retval .= "<td><input name=\"email\" type=\"text\" value=\"".htmlentities($user_row['Email'])."\" /></td>";
				$retval .= "</tr>";
				if(!is_null($user_row['Username']) && strlen(trim($user_row['Username'])) > 0) {
					$retval .= "<tr>";
					$retval .= "<td>Username</td>";
					$retval .= "<td>".htmlentities($user_row['Username'])."</td>";
					$retval .= "</tr>";
				}
				$retval .= "<tr>";
				$retval .= "<td>Password</td>";
				$retval .= "<td><input name=\"password\" type=\"password\" /></td>";
				$retval .= "</tr>";
				$retval .= "<tr>";
				$retval .= "<td>Confirm Password</td>";
				$retval .= "<td><input name=\"password2\" type=\"password\" /></td>";
				$retval .= "</tr>";
				$retval .= "<tr>";
				$retval .= "<td colspan=\"2\"><input type=\"submit\" value=\"Update\" /></td>";
				$retval .= "</tr>";
				$retval .= "</form>";
				$sub_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_user_levels AS t1, {$wpdb->prefix}wp_membership_levels AS t2 WHERE t1.Level_ID=t2.Level_ID AND t1.User_ID=%s", @$_SESSION['wp-membership_plugin']['wp-membership_user_id']);
				if($sub_rows = $wpdb->get_results($sub_query, ARRAY_A)) {
					$retval .= "<tr><td colspan=\"2\"><h3>Subscription".(count($sub_rows) == 1 ? "" : "s")."</h3></td></tr>";
					foreach($sub_rows as $id => $sub_row) {
						$retval .= "<tr>";
						$retval .= "<td>".htmlentities($sub_row['Name'])."</td>";
						$value = (is_null($sub_row['Expiration']) ? "Never Expires" : ((@strtotime($sub_row['Expiration']) - time()) < 0 ? (@strtotime($sub_row['Expiration']) == 0 ? "Pending" : "Expired") : "Expires ".@date("m-d-Y", @strtotime($sub_row['Expiration']))));
						$plugin = null;
						if(is_array($wp_membership_plugin->plugins)) {
							foreach($wp_membership_plugin->plugins as $p) {
								if($p->has_Subscription($sub_row['User_Level_ID'])) {
									$plugin = $p;
								}
							}
						}
						if(strlen($value) > 0 && !is_null($plugin)) $value .= "&nbsp;&nbsp;&nbsp;";
						if(!is_null($plugin)) {
							if($plugin->has_Unsubscribe_Button()) $value .= $plugin->get_Unsubscribe_Button($sub_row['User_ID'].'_'.$sub_row['Level_Price_ID'], $sub_row['Name'].' @ '.@$_SERVER['HTTP_HOST']);
							else if($plugin->has_Uninstall_Subscription()) {
								$value .= "<form action=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode(@$_REQUEST['page_id'])."\" method=\"post\">";
								$value .= '<input type="hidden" name="do_unsubscribe" value="1" />';
								$value .= '<input type="hidden" name="userlevelid" value="'.htmlentities($sub_row['User_Level_ID']).'" />';
								$value .= "<input type=\"submit\" value=\"Unsubscribe\" />";
								$value .= "</form>";
							}
						}
						$retval .= "<td>$value</td>";
						$retval .= "</tr>";
					}
				}
				$retval .= "</table>";
			}
			
			return $retval;
		}
	}
}
?>
