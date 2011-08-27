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
require_once('../wp-content/plugins/free-wp-membership/interfaces/IWPMembershipSettingsTab.php');

global $wp_membership_plugin;
if(isset($wp_membership_plugin) && class_exists('wp_membership_plugin') && is_a($wp_membership_plugin, 'wp_membership_plugin')) {
	if(!class_exists('wp_membership_SettingsTab_General')) {
		class wp_membership_SettingsTab_General implements IWPMembershipSettingsTab {
			function get_File() {
				return __FILE__;
			}
			function DisplayTab() {
				global $wp_membership_plugin;
				$div_wrapper = false;
				if(!isset($query_string)) {
				    load_plugin_textdomain('wp-membership', false, 'wp-membership');
				    $query_string = "page=".urlencode(@$_REQUEST['page']);//ereg_replace("[&?]?wp-membership_tab[=][^&]*", "", @$_SERVER['QUERY_STRING']);
				    $div_wrapper = true;
				}
				if($div_wrapper) echo '<div class="wrap">';
				?>
				<h2>Free WP-Membership Plugin - General Options</h2>
				
				<?php
				if(@$_REQUEST['do_update'] == "1") {
					$update_success = true;
					
					$set_country = false;
					if($wp_membership_plugin->set_Country_Code(@$_REQUEST['wp-membership_country'])) {
						$info = @localeconv();
						if(strlen(trim(@$info['int_curr_symbol'])) > 0) {
							$set_country = true;
							update_option('wp-membership_country', @$_REQUEST['wp-membership_country']);
							update_option('wp-membership_currency', trim(@$info['int_curr_symbol']));
						}
					}
					if(!$set_country) {
						update_option('wp-membership_country', 'usa');
						update_option('wp-membership_currency', 'USD');
					}
					$options = array(	"wp-membership_access_denied_page_id",
										"wp-membership_logout_page_id",
										"wp-membership_loginfrom_macro",
										"wp-membership_login_page_id",
										"wp-membership_login_prompt_forgot_password",
										"wp-membership_logged_in_page_id",
										"wp-membership_user_profile_from_macro",
										"wp-membership_user_profile_page_id",
										"wp-membership_httpproxy_address",
										"wp-membership_httpproxy_port",
										"wp-membership_httpsproxy_address",
										"wp-membership_httpsproxy_port");
					foreach($options as $option) {
						update_option($option, @$_REQUEST[$option]);
					}
					
					update_option("wp-membership_cache", @$_REQUEST['wp-membership_cache'] == "true" ? true : false);
					
					$admin_menu_location = array();
					switch(@$_REQUEST['wp-membership_admin_menu_location']) {
						case '1':
							$admin_menu_location[] = 'Top Level';
							break;
						case '2':
							$admin_menu_location[] = 'Settings';
							break;
						case '3':
							$admin_menu_location[] = 'Top Level';
							$admin_menu_location[] = 'Settings';
							break;
						default:
							break;
					}
					
					if(count($admin_menu_location) <= 0) {
						$admin_menu_location[] = 'Settings';
					}
					
					update_option("wp-membership_admin_menu_location", $admin_menu_location);
					
					if($update_success === true) {
						echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>Free WP-Membership General Settings Updated Successfully.</strong></p></div>";
					}
					else if($update_success === false) {
						echo "<div id=\"message\" class=\"error\"><p><strong>Free WP-Membership General Settings Failed to Delete.</strong></p></div>";
					}
				}
				?>
				
				<form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>?page=<?php echo @$_REQUEST['page']; ?>">
				<?php
	/*			if(function_exists('wpmu_create_blog')) {
					wp_nonce_field('magic_name-options');
				}
				else {
					wp_nonce_field('update-options');
				} */
				?>
				
				<h3>Standard Page Settings</h3>
				
				<table class="form-table">
				
				<tr valign="top">
				<th scope="row">Access Denied</th>
				<td><select name="wp-membership_access_denied_page_id"><?php
				$pages = get_pages();
				foreach($pages as $page) {
					echo "<option value=\"".htmlentities($page->ID)."\"";
					if(get_option('wp-membership_access_denied_page_id') == $page->ID) echo " SELECTED";
					echo ">".htmlentities($page->post_title)."</option>";
				}
				?></select></td>
				</tr>
				
				<tr valign="top">
				<th scope="row">Logout Page</th>
				<td><select name="wp-membership_logout_page_id"><?php
				foreach($pages as $page) {
					echo "<option value=\"".htmlentities($page->ID)."\"";
					if(get_option('wp-membership_logout_page_id') == $page->ID) echo " SELECTED";
					echo ">".htmlentities($page->post_title)."</option>";
				}
				?></select></td>
				</tr>
	
				<tr valign="top">
				<th scope="row">Login Form Macro</th>
				<td><input type="text" name="wp-membership_loginfrom_macro" value="<?php echo get_option('wp-membership_loginfrom_macro'); ?>" /></td>
				</tr>
				
				<tr valign="top">
				<th scope="row">Login Page</th>
				<td><select name="wp-membership_login_page_id"><?php
				foreach($pages as $page) {
					echo "<option value=\"".htmlentities($page->ID)."\"";
					if(get_option('wp-membership_login_page_id') == $page->ID) echo " SELECTED";
					echo ">".htmlentities($page->post_title)."</option>";
				}
				?></select></td>
				</tr>
	
				<tr valign="top">
				<th scope="row">Prompt for forgot password</th>
				<td>Yes <input type="radio" name="wp-membership_login_prompt_forgot_password" value="1"<?php echo get_option('wp-membership_login_prompt_forgot_password') == "1" ? " CHECKED" : ""; ?> /> No <input type="radio" name="wp-membership_login_prompt_forgot_password" value="0"<?php echo get_option('wp-membership_login_prompt_forgot_password') == "0" ? " CHECKED" : ""; ?> /></td>
				</tr>
				
				<tr valign="top">
				<th scope="row">Logged In Page</th>
				<td><select name="wp-membership_logged_in_page_id"><?php
				foreach($pages as $page) {
					echo "<option value=\"".htmlentities($page->ID)."\"";
					if(get_option('wp-membership_logged_in_page_id') == $page->ID) echo " SELECTED";
					echo ">".htmlentities($page->post_title)."</option>";
				}
				?></select></td>
				</tr>
				
				<tr valign="top">
				<th scope="row">User Profile Form Macro</th>
				<td><input type="text" name="wp-membership_user_profile_from_macro" value="<?php echo get_option('wp-membership_user_profile_from_macro'); ?>" /></td>
				</tr>
				
				<tr valign="top">
				<th scope="row">User Profile Page</th>
				<td><select name="wp-membership_user_profile_page_id"><?php
				foreach($pages as $page) {
					echo "<option value=\"".htmlentities($page->ID)."\"";
					if(get_option('wp-membership_user_profile_page_id') == $page->ID) echo " SELECTED";
					echo ">".htmlentities($page->post_title)."</option>";
				}
				?></select></td>
				</tr>
				</table>
				
				<h3>Performance</h3>
				
				<table class="form-table">
	
				<tr valign="top">
				<th scope="row">Caching Membership Levels</th>
				<td>Enabled <input type="radio" name="wp-membership_cache" value="true"<?php echo get_option('wp-membership_cache') == true ? " CHECKED" : ""; ?> /> Disabled <input type="radio" name="wp-membership_cache" value="false"<?php echo get_option('wp-membership_cache') == false ? " CHECKED" : ""; ?> /></td>
				</tr>
	
				</table>
				
				<h3>Credit Merchant Account(s)</h3>

				<table class="form-table">
				<?php
				$countries = array(	'aus' => 'Australia',
									'aut' => 'Austria',
									'bel' => 'Belgium',
									'bra' => 'Brazil',
									'can' => 'Canada',
									'chn' => 'China',
									'cze' => 'Czech Republic',
									'dnk' => 'Denmark',
									'fin' => 'Finland',
									'fra' => 'France',
									'deu' => 'Germany',
									'grc' => 'Greece',
									'hkg' => 'Hong Kong SAR',
									'hun' => 'Hungary',
									'isl' => 'Iceland',
									'irl' => 'Ireland',
									'ita' => 'Italy',
									'jpn' => 'Japan',
									'kor' => 'Korea',
									'mex' => 'Mexico',
									'nld' => 'The Netherlands',
									'nzl' => 'New Zealand',
									'nor' => 'Norway',
									'pol' => 'Poland',
									'prt' => 'Portugal',
									'rus' => 'Russia',
									'sgp' => 'Singapore',
									'svk' => 'Slovakia',
									'esp' => 'Spain',
									'swe' => 'Sweden',
									'che' => 'Switzerland',
									'twn' => 'Taiwan',
									'tur' => 'Turkey',
									'gbr' => 'United Kingdom',
									'usa' => 'United States');
				$options = '';
				foreach($countries as $code => $country) {
					if($wp_membership_plugin->set_Country_Code($code)) {
						$info = localeconv();
						if(strlen(trim(@$info['int_curr_symbol'])) > 0) {
							$options .= '
						<option value="'.htmlentities($code).'"'.(get_option("wp-membership_country") == $code ? " SELECTED" : "").'>'.htmlentities($country).'</option>';
						}
					}
				}
				if(strlen(options) > 0) {
				?>
				<tr valign="top">
				<th scope="row">Currency Symbol</th>
				<td><?php
				$wp_membership_plugin->set_Country_Code(get_option("wp-membership_country"));
				$info = localeconv();
				echo htmlentities(trim(@$info['int_curr_symbol']).' ('.trim(@$info['currency_symbol']).')');
				?></td>
				</tr>
				
				<tr valign="top">
				<th scope="row">Currency</th>
				<td>
					<select name="wp-membership_country">
					<?php echo $options; ?>
					</select>
				</td>
				</tr>
				<?php
				}
				?>
				</table>
				
				<h3>Admin Menu Settings</h3>
				<table class="form-table">
				
				<tr valign="top">
				<th scope="row">Menu Location</th>
					<td>
						Top Level <input type="radio" name="wp-membership_admin_menu_location" value="1"<?php echo in_array('Top Level', get_option('wp-membership_admin_menu_location')) ? " CHECKED" : ""; ?> />
						Settings <input type="radio" name="wp-membership_admin_menu_location" value="2"<?php echo in_array('Settings', get_option('wp-membership_admin_menu_location')) ? " CHECKED" : ""; ?> />
						Both <input type="radio" name="wp-membership_admin_menu_location" value="3"<?php echo in_array('Top Level', get_option('wp-membership_admin_menu_location')) && in_array('Settings', get_option('wp-membership_admin_menu_location')) ? " CHECKED" : ""; ?> />
					</td>
				</tr>
	
				</table>
				
				<input type="hidden" name="wp-membership_tab" value="1" />
				<input type="hidden" name="do_update" value="1" />
				
				<p class="submit">
				<input type="submit" name="Submit" value="<?php _e('Save Changes', 'wp-membership'); ?>" />
				</p>
				
				</form>
				<?php
				if($div_wrapper) echo '</div>';
			}
		}
	}
}
?>