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
require_once(FWP_MEMBERSHIP_PATH.'interfaces/IWPMembershipSettingsTab.php');

global $wp_membership_plugin;
if(isset($wp_membership_plugin) && class_exists('wp_membership_plugin') && is_a($wp_membership_plugin, 'wp_membership_plugin')) {
	if(!class_exists('wp_membership_SettingsTab_Users')) {
		class wp_membership_SettingsTab_Users implements IWPMembershipSettingsTab {
			function get_File() {
				return __FILE__;
			}
			function DisplayTab() {
				global $wpdb;
				load_plugin_textdomain('wp-membership', false, $this->language_path);

				$div_wrapper = false;
				if(!isset($query_string)) {
				    $query_string = "page=".urlencode(@$_REQUEST['page']);//ereg_replace("[&?]?wp-membership_tab[=][^&]*", "", @$_SERVER['QUERY_STRING']);
				    $div_wrapper = true;
				}
				if($div_wrapper) echo '<div class="wrap">';
				?>
				<h2><?php _e('WP-Membership Plugin - Users', 'wp-membership'); ?></h2>
				<?php
				$switchvar = @$_REQUEST['wp-membership_action'];
				$edit_success = null;
				if(@$_REQUEST['wp-membership_do_reset_password'] == "1") {
					$user_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_users AS t1 WHERE t1.User_ID=%s", @$_REQUEST['wp-membership_userid']);
					if($user_row = $wpdb->get_row($user_query, ARRAY_A)) {
						$reset_success = false;
				    		$password = "";
				    		while(strlen($password) < 8) {
				    			$password .= chr(rand(ord("0"), ord("z")));
				    		}
					    	$update_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET Password=PASSWORD(%s) WHERE User_ID=%s", $password, $user_row['User_ID']);
					    	if($wpdb->query($update_query)) {
						    	$update_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET WP_Password=%s WHERE User_ID=%s", wp_hash_password($password), $user_row['User_ID']);
					    		$wpdb->query($update_query);
					    		wp_mail($user_row['Email'], "Password was reset by management", "Management has reset your password. Your new password is $password. This is case sensitive. You can login at: http://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."\n--\nManagement");
					    		$reset_success = true;
					    		$switchvar = "";
					    	}
					}
				}
				if(@$_REQUEST['wp-membership_do_edit_user'] == "1") {
					$edit_success = false;
					$edit_user_query = "";
					if(strlen(trim(@$_REQUEST['wp-membership_password'])) > 0) {
						if(@$_REQUEST['wp-membership_password'] == @$_REQUEST['wp-membership_password2']) {
							if(strlen(trim(@$_REQUEST['wp-membership_username'])) > 0) $edit_user_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET Email=%s, Username=%s, Password=PASSWORD(%s), Active=%s WHERE User_ID=%s", @$_REQUEST['wp-membership_email'], @$_REQUEST['wp-membership_username'], @$_REQUEST['wp-membership_password'], @$_REQUEST['wp-membership_active'] == "1" ? "1" : "0", @$_REQUEST['wp-membership_userid']);
							else $edit_user_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET Email=%s, Username=NULL, Password=PASSWORD(%s), Active=%s WHERE User_ID=%s", @$_REQUEST['wp-membership_email'], @$_REQUEST['wp-membership_password'], @$_REQUEST['wp-membership_active'] == "1" ? "1" : "0", @$_REQUEST['wp-membership_userid']);
						    	$update_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET WP_Password=%s WHERE User_ID=%s", wp_hash_password(@$_REQUEST['wp-membership_password']), @$_REQUEST['wp-membership_userid']);
					    		$wpdb->query($update_query);
						}
					}
					else {
						if(strlen(trim(@$_REQUEST['wp-membership_username'])) > 0) $edit_user_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET Email=%s, Username=%s, Active=%s WHERE User_ID=%s", @$_REQUEST['wp-membership_email'], @$_REQUEST['wp-membership_username'], @$_REQUEST['wp-membership_active'] == "1" ? "1" : "0", @$_REQUEST['wp-membership_userid']);
						else $edit_user_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET Email=%s, Username=NULL, Active=%s WHERE User_ID=%s", @$_REQUEST['wp-membership_email'], @$_REQUEST['wp-membership_active'] == "1" ? "1" : "0", @$_REQUEST['wp-membership_userid']);
					}
					if(strlen($edit_user_query) > 0 && $wpdb->query($edit_user_query) !== false) {
						$user_id = @$_REQUEST['wp-membership_userid'];
						$edit_success = true;
						$switchvar = "";
/*						$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_user_levels WHERE User_ID=%s", $user_id));
						if(is_array(@$_REQUEST['wp-membership_level_ids'])) {
							foreach($_REQUEST['wp-membership_level_ids'] as $level_id) {
								$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_user_levels (User_ID, Level_ID) VALUES (%s, %s)", @$user_id, @$level_id));
							}
						} */
					}
				}
				$delete_success = null;
				if(@$_REQUEST['wp-membership_do_delete_user'] == "1") {
					$delete_success = false;
					if(is_array($this->plugins)) {
						foreach($this->plugins as $plugin) {
							$plugin->delete_User(@$_REQUEST['wp-membership_userid']);
						}
					}
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_user_levels WHERE User_ID=%s", @$_REQUEST['wp-membership_userid']));
					if($wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_users WHERE User_ID=%s", @$_REQUEST['wp-membership_userid'])) !== false) {
						$delete_success = true;
					}
				}
				switch($switchvar) {
					case "edit_user":
						$user_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_users AS t1 WHERE t1.User_ID=%s", @$_REQUEST['wp-membership_userid']);
						if($user_row = $wpdb->get_row($user_query, ARRAY_A)) {
							?>
							<h3>Edit User "<?php echo htmlentities($user_row['Email']); ?>"</h3>
							
							<?php
							if(@$_REQUEST['sync_password'] == "1") {
								$wp_user_id = email_exists($user_row['Email']);
								if($wp_user_id !== false) {
									if(@$user_row['WP_Password'] != null) {
									        $user = get_userdata($wp_user_id);
									        $user = add_magic_quotes(get_object_vars($user));
								                $user['user_pass'] = @$user_row['WP_Password'];
									        $user_id = wp_insert_user($user);
									        $current_user = wp_get_current_user();
									        if($current_user->id == $user_id) {
								                        wp_clear_auth_cookie();
								                        wp_set_auth_cookie($user_id);
									        }
										if($user_id !== false) {
											echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WordPress User Password Synced with WP-Membership User Password.</strong></p></div>";
										}
									}
								}
							}
						
							if($reset_success === false) {
								echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Failed to reset password.</strong></p></div>";
							}
							?>
							<?php
							if($edit_success === false) {
								echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership User Failed to Update.</strong></p></div>";
							}
							?>
							<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						
							<table class="form-table">
						
								<tr valign="top">
									<th scope="row">Email</th>
									<td><input type="text" name="wp-membership_email" value="<?php echo htmlentities($user_row['Email']); ?>" /></td>
								</tr>
						
								<tr valign="top">
									<th scope="row">Username</th>
									<td><input type="text" name="wp-membership_username" value="<?php echo htmlentities($user_row['Username']); ?>" /></td>
								</tr>
						
								<tr valign="top">
									<th scope="row">Change Password</th>
									<td><input type="password" name="wp-membership_password" /></td>
								</tr>
						
								<tr valign="top">
									<th scope="row">Confirm Password</th>
									<td><input type="password" name="wp-membership_password2" /></td>
								</tr>
						
								<tr valign="top">
									<th scope="row">Extra Fields</th>
									<td><table><?php
							$extra_fields = @unserialize($user_row['Extra_Fields']);
							if(!is_array($extra_fields)) $extra_fields = array();
							foreach($extra_fields as $extra_id => $data) {
//								$extra_field = $this->get_ExtraFieldFromUserData($data);
								$name = isset($data->name) ? $data->name : '';
//								$caption = isset($extra_field->caption) ? $data->caption : '';
								$value = isset($data->value) ? $data->value : '';
//								$admin = isset($extra_field->admin) ? $extra_field->admin : false;
//								$type = isset($extra_field->type) ? $extra_field->type : 'text';
								echo '<tr valign="top"><th>'.htmlentities($name)."</th><td>".htmlentities($value)."</td></tr>";
							}
									?></table></td>
								</tr>
						
								<tr valign="top">
									<th scope="row">Levels</th>
									<td><?php
							$user_levels = array();
							if($user_levels_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_user_levels AS t1 WHERE t1.User_ID=%s", $user_row['User_ID']), ARRAY_A)) {
								foreach($user_levels_rows as $user_levels_row) {
									$user_levels[] = $user_levels_row['Level_ID'];
								}
							}
						
							if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_levels AS t1 ORDER BY t1.Name"), ARRAY_A)) {
								$first = true;
								foreach($level_rows as $level_row) {
									if($first) {
										$first = false;
									}
									else {
										echo "<br />";
									}
									echo "<input type=\"checkbox\" name=\"wp-membership_level_ids[]\" disabled value=\"".htmlentities($level_row['Level_ID'])."\"";
									if(in_array($level_row['Level_ID'], $user_levels)) echo " CHECKED";
									echo " />";
									echo " ".htmlentities($level_row['Name']);
								}
							}
									?></td>
								</tr>
						
								<tr valign="top">
									<th scope="row">Active</th>
									<td><input type="checkbox" name="wp-membership_active" value="1"<?php echo $user_row['Active'] == "1" ? " CHECKED" : ""; ?> /></td>
								</tr>
						
							</table>
						
							<input type="hidden" name="wp-membership_tab" value="2" />
							<input type="hidden" name="wp-membership_userid" value="<?php echo htmlentities($user_row['User_ID']); ?>" />
							<input type="hidden" name="wp-membership_action" value="edit_user" />
							<input type="hidden" name="wp-membership_do_edit_user" value="1" />
						
							<p class="submit">
								<input type="submit" name="Submit" value="<?php _e('Update User', 'wp-membership'); ?>" />
							</p>
						
							</form>
						
							<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
							<input type="hidden" name="wp-membership_tab" value="2" />
							<input type="hidden" name="wp-membership_userid" value="<?php echo htmlentities($user_row['User_ID']); ?>" />
							<input type="hidden" name="wp-membership_action" value="edit_user" />
							<input type="hidden" name="wp-membership_do_reset_password" value="1" />
						
							<p class="submit">
								<input type="submit" name="Submit" value="<?php _e('Reset Password', 'wp-membership'); ?>" />
							</p>
						
							</form>
						
							<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
							<input type="hidden" name="wp-membership_tab" value="2" />
							<input type="hidden" name="wp-membership_userid" value="<?php echo htmlentities($user_row['User_ID']); ?>" />
							<input type="hidden" name="wp-membership_action" value="delete_user" />
							<input type="hidden" name="wp-membership_do_delete_user" value="1" />
							
							<p class="submit">
								<input type="submit" name="Submit" value="<?php _e('Delete User', 'wp-membership'); ?>" />
							</p>
						
							</form>

							<h3>WordPress Sync</h3>
							<table class="form-table">
							
								<tr valign="top">
									<th scope="row">Sync Password</th>
									<td><?php
							$wp_user_id = email_exists($user_row['Email']);
							if($wp_user_id !== false) {
								$user_data = get_userdata($wp_user_id);
								echo htmlentities($user_data->user_login." (".$user_data->user_email.") ");
								if(@$user_row['WP_Password'] != null) {
									?><a href="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>&wp-membership_tab=2&wp-membership_action=edit_user&wp-membership_userid=<?php echo urlencode($user_row['User_ID']); ?>&sync_password=1">Set WordPress User Password to WP-Membership Password</a><?php
								}
							}
							else {
								?>User not found in WordPress User Database<?php
							}
									?></td>
								</tr>
						
							</table>
							<?php
							break;
						}
						else {
							echo "<div id=\"message\" class=\"error\"><p><strong>Failed to load Specified WP-Membership User to Edit</strong></p></div>";
						}
					default:
						if($reset_success === true) {
							echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership User Password Reset to ".htmlentities($password).".</strong></p></div>";
						}
						if($edit_success === true) {
							echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership User Updated.</strong></p></div>";
						}
						if($delete_success === true) {
							echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership User Deleted.</strong></p></div>";
						}
						else if($delete_success === false) {
							echo "<div id=\"message\" class=\"error\"><p><strong>WP-Membership User Failed to Delete.</strong></p></div>";
						}
						if(@$_REQUEST['wp-membership_action'] == "add_user") {
							if(strlen(trim(@$_REQUEST['wp-membership_email'])) > 0) {
								if(is_email(@$_REQUEST['wp-membership_email'])) {
									if(strlen(trim(@$_REQUEST['wp-membership_password'])) > 0) {
										if(@$_REQUEST['wp-membership_password'] == @$_REQUEST['wp-membership_password2']) {
											$insert_query = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_users (Email, Username, Password) VALUES (%s, %s, PASSWORD(%s))", @$_REQUEST['wp-membership_email'], @$_REQUEST['wp-membership_username'], @$_REQUEST['wp-membership_password']);
											if($wpdb->query($insert_query) !== false) {
												$user_id = $wpdb->insert_id;
											    	$update_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_users SET WP_Password=%s WHERE User_ID=%s", wp_hash_password(@$_REQUEST['wp-membership_password']), $user_id);
										    		$wpdb->query($update_query);
												$success = true;
												if(is_array(@$_REQUEST['wp-membership_level_ids'])) {
													foreach($_REQUEST['wp-membership_level_ids'] as $level_id) {
														if($wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_user_levels (User_ID, Level_ID) VALUES (%s, %s)", @$user_id, @$level_id)) === false) $success = false;
													}
												}
												if($success) {
													echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership User Added.</strong></p></div>";
												}
												else {
													echo "<div id=\"message\" class=\"error\"><p><strong>WP-Membership User Added, but not all of the levels where added</strong></p></div>";
												}
											}
										}
										else {
											echo "<div id=\"message\" class=\"error\"><p><strong>Passwords must match</strong></p></div>";
										}
									}
									else {
										echo "<div id=\"message\" class=\"error\"><p><strong>Password can not be empty</strong></p></div>";
									}
								}
								else {
									echo "<div id=\"message\" class=\"error\"><p><strong>Email must be valid</strong></p></div>";
								}
							}
							else {
								echo "<div id=\"message\" class=\"error\"><p><strong>Email can not be empty</strong></p></div>";
							}
						}

						$download = get_option('siteurl')."/wp-content/plugins/wp-membership/wp-membership.php?fetch_user_list=1";
						?>
						<h3>Export User List</h3>
						<form method="post" action="<?php echo $download; ?>">
					
						<p class="submit">
							<input type="submit" name="Submit" value="<?php _e('Download User List', 'wp-membership'); ?>" />
						</p>
					
						</form>
					
						<h3>Add User</h3>
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
					
						<table class="form-table">
					
							<tr valign="top">
								<th scope="row">Email</th>
								<td><input type="text" name="wp-membership_email" /></td>
							</tr>
					
							<tr valign="top">
								<th scope="row">Username</th>
								<td><input type="text" name="wp-membership_username" /></td>
							</tr>
					
							<tr valign="top">
								<th scope="row">Password</th>
								<td><input type="password" name="wp-membership_password" /></td>
							</tr>
					
							<tr valign="top">
								<th scope="row">Confirm Password</th>
								<td><input type="password" name="wp-membership_password2" /></td>
							</tr>
					
							<tr valign="top">
								<th scope="row">Levels</th>
								<td><?php
						if($level_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_levels AS t1 ORDER BY t1.Name"), ARRAY_A)) {
							$first = true;
							foreach($level_rows as $level_row) {
								if($first) {
									$first = false;
								}
								else {
									echo "<br />";
								}
								echo "<input type=\"checkbox\" name=\"wp-membership_level_ids[]\" value=\"".htmlentities($level_row['Level_ID'])."\" />";
								echo " ".htmlentities($level_row['Name']);
							}
						}
								?></td>
							</tr>

						</table>
					
						<input type="hidden" name="wp-membership_tab" value="2" />
						<input type="hidden" name="wp-membership_action" value="add_user" />
					
						<p class="submit">
							<input type="submit" name="Submit" value="<?php _e('Add User', 'wp-membership'); ?>" />
						</p>
					
						</form>
						<?php
						if($user_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_users AS t1 ORDER BY t1.Email"), ARRAY_A)) {
							?>
							<h3>View Users</h3>
						
							<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						
							<table class="form-table">
						
								<tr valign="top">
									<th scope="row">Users</th>
									<td><select name="wp-membership_userid"><?php
							foreach($user_rows as $user_row) {
								echo "<option value=\"".htmlentities($user_row['User_ID'])."\"";
								echo ">".htmlentities($user_row['Email'])."</option>";
							}
							?></select></td>
							</tr>
						
							</table>
						
							<input type="hidden" name="wp-membership_tab" value="2" />
							<input type="hidden" name="wp-membership_action" value="edit_user" />
						
							<p class="submit">
								<input type="submit" name="Submit" value="<?php _e('Edit User', 'wp-membership'); ?>" />
							</p>
						
							</form>
							<?php
						}
						break;
				}
				if($div_wrapper) echo '</div>';
			}
		}
	}
}
?>
