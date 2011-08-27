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
	if(!class_exists('wp_membership_SettingsTab_RegisterPages')) {
		class wp_membership_SettingsTab_RegisterPages implements IWPMembershipSettingsTab {
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
			<h2><?php _e('WP-Membership - Register Pages', 'wp-membership'); ?></h2>
			<?php
		
			$switchvar = @$_REQUEST['wp-membership_action'];
			$edit_success = null;
			if(strlen(@$_REQUEST['wp-membership_remove_extra_field']) > 0) {
				$register_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 WHERE t1.Register_Page_ID=%s", @$_REQUEST['wp-membership_register_page_id']);
				if($register_row = $wpdb->get_row($register_query, ARRAY_A)) {
					$extra_fields = @unserialize($register_row['Extra_Fields']);
					if(!is_array($extra_fields)) $extra_fields = array();
					unset($extra_fields[@$_REQUEST['wp-membership_remove_extra_field']]);
					$update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}wp_membership_register_pages SET Extra_Fields=%s WHERE Register_Page_ID=%s", serialize($extra_fields), $register_row['Register_Page_ID']);
					$wpdb->query($update_query);
				}
			}
			if(@$_REQUEST['wp-membership_add_extra_field'] == "1") {
				$register_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 WHERE t1.Register_Page_ID=%s", @$_REQUEST['wp-membership_register_page_id']);
				if($register_row = $wpdb->get_row($register_query, ARRAY_A)) {
					$extra_fields = @unserialize($register_row['Extra_Fields']);
					if(!is_array($extra_fields)) $extra_fields = array();
					$extra_field->version = "0.0.2";
					$extra_field->name = "";
					$extra_field->classes = "";
					$extra_field->default = "";
					$extra_field->caption = "";
					$extra_field->type = "text";
					$extra_field->parameters = "";
					$extra_field->required = false;
					$extra_field->required_regex = ".+";
					$extra_field->required_error = "<%caption%> can not be blank";
					$extra_field->save = true;
					$extra_field->signup = true;
					$extra_field->profile = true;
					$extra_field->admin = true;
					$extra_fields[] = $extra_field;
					$update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}wp_membership_register_pages SET Extra_Fields=%s WHERE Register_Page_ID=%s", serialize($extra_fields), $register_row['Register_Page_ID']);
					$wpdb->query($update_query);
				}
			}
			if(@$_REQUEST['wp-membership_do_edit_register'] == "1") {
				$edit_success = false;
				$edit_register_query = $wpdb->prepare("UPDATE ".$wpdb->prefix."wp_membership_register_pages SET Name=%s, Description=%s, Macro=%s, WP_Page_ID=%s WHERE Register_Page_ID=%s", @$_REQUEST['wp-membership_name'], @$_REQUEST['wp-membership_description'], @$_REQUEST['wp-membership_macro'], @$_REQUEST['wp-membership_page_id'], @$_REQUEST['wp-membership_register_page_id']);
				if($wpdb->query($edit_register_query) !== false) {
					$register_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 WHERE t1.Register_Page_ID=%s", @$_REQUEST['wp-membership_register_page_id']);
					if($register_row = $wpdb->get_row($register_query, ARRAY_A)) {
						$extra_fields = @unserialize($register_row['Extra_Fields']);
						if(!is_array($extra_fields)) $extra_fields = array();
						$tmp = $extra_fields;
						foreach($tmp as $extra_id => $extra_field) {
							$extra_fields[$extra_id]->name = @$_REQUEST['wp-membership_extra_names'][$extra_id];
							$extra_fields[$extra_id]->classes = @$_REQUEST['wp-membership_extra_classes'][$extra_id];
							$extra_fields[$extra_id]->default = @$_REQUEST['wp-membership_extra_defaults'][$extra_id];
							$extra_fields[$extra_id]->caption = @$_REQUEST['wp-membership_extra_captions'][$extra_id];
							$extra_fields[$extra_id]->type = @$_REQUEST['wp-membership_extra_types'][$extra_id];
							$extra_fields[$extra_id]->parameters = @$_REQUEST['wp-membership_extra_parameters'][$extra_id];
							$extra_fields[$extra_id]->required = @$_REQUEST['wp-membership_extra_requireds'][$extra_id] == "1" ? true : false;
							$extra_fields[$extra_id]->required_regex = @$_REQUEST['wp-membership_extra_required_regexs'][$extra_id];
							$extra_fields[$extra_id]->required_error = @$_REQUEST['wp-membership_extra_required_errors'][$extra_id];
							$extra_fields[$extra_id]->save = @$_REQUEST['wp-membership_extra_saves'][$extra_id] == "1" ? true : false;
							$extra_fields[$extra_id]->signup = @$_REQUEST['wp-membership_extra_signups'][$extra_id] == "1" ? true : false;
							$extra_fields[$extra_id]->profile = @$_REQUEST['wp-membership_extra_profiles'][$extra_id] == "1" ? true : false;
							$extra_fields[$extra_id]->admin = @$_REQUEST['wp-membership_extra_admins'][$extra_id] == "1" ? true : false;
						}
						$update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}wp_membership_register_pages SET Extra_Fields=%s WHERE Register_Page_ID=%s", serialize($extra_fields), $register_row['Register_Page_ID']);
						$wpdb->query($update_query);
					}
					$register_id = @$_REQUEST['wp-membership_register_page_id'];
					$edit_success = true;
					$switchvar = "";
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_register_page_gateways WHERE Register_Page_ID=%s", $register_id));
					$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_register_page_levels WHERE Register_Page_ID=%s", $register_id));
					if(is_array(@$_REQUEST['wp-membership_level_ids'])) {
						foreach($_REQUEST['wp-membership_level_ids'] as $level_id) {
							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_register_page_levels (Register_Page_ID, Level_ID) VALUES (%s, %s)", @$register_id, $level_id));
						}
					}
					if(is_array(@$_REQUEST['wp-membership_plugins'])) {
						foreach($_REQUEST['wp-membership_plugins'] as $plugin_id) {
							$wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_register_page_gateways (Register_Page_ID, Payment_Gateway) VALUES (%s, %s)", @$register_id, $plugin_id));
						}
					}
				}
			}
			$delete_success = null;
			if(@$_REQUEST['wp-membership_do_delete_register'] == "1") {
				$delete_success = false;
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_register_page_gateways WHERE Register_Page_ID=%s", @$_REQUEST['wp-membership_register_page_id']));
				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_register_page_levels WHERE Register_Page_ID=%s", @$_REQUEST['wp-membership_register_page_id']));
				if($wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->prefix."wp_membership_register_pages WHERE Register_Page_ID=%s", @$_REQUEST['wp-membership_register_page_id'])) !== false) {
					$delete_success = true;
				}
			}
			switch($switchvar) {
				case "edit_register":
					$register_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 WHERE t1.Register_Page_ID=%s", @$_REQUEST['wp-membership_register_page_id']);
					if($register_row = $wpdb->get_row($register_query, ARRAY_A)) {
						?>
						<h3>Edit Register Page "<?php echo htmlentities($register_row['Name']); ?>"</h3>
						
						<?php
						if($edit_success === false) {
							echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Register Page Failed to Update.</strong></p></div>";
						}
						?>
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						
						<table class="form-table">
						
						<tr valign="top">
						<th scope="row">Name</th>
						<td><input type="text" name="wp-membership_name" value="<?php echo htmlentities($register_row['Name']); ?>" /></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Description</th>
						<td><textarea name="wp-membership_description" rows="5" cols="80"><?php echo htmlentities($register_row['Description']); ?></textarea></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Default Page Macro</th>
						<td><input type="text" name="wp-membership_macro" value="<?php echo htmlentities($register_row['Macro']); ?>" /></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Page</th>
						<td><select name="wp-membership_page_id"><option value="">[Choose a Page]</option><?php
						$pages = get_pages();
						foreach($pages as $page) {
							echo "<option value=\"".htmlentities($page->ID)."\"";
							if($register_row['WP_Page_ID'] == $page->ID) echo " SELECTED";
							echo ">".htmlentities($page->post_title)."</option>";
						}
						?></select></td>
						</tr>
						
						<style type="text/css">
							#RegisterExtraFormFields {
								position: absolute;
								display: none;
								border: 1px solid #000000;
								width: 600px;
								height: 331px;
								overflow: hidden;
								background-color: #FFFFFF;
							}

							#RegisterExtraFormFields a#close {
								color: #000000;
								text-decoration: none;
								position: absolute;
								top: 0px;
								right: 0px;
								padding-top: 0px;
								padding-right: 3px;
							}

							#RegisterExtraFormFields #RegisterExtraFormFields_Window {
								position: absolute;
								width: 580px;
								height: 291px;
								overflow: auto;
								top: 20px;
								right: 0px;
								padding: 10px 10px 10px 10px;
							}
							
							#RegisterExtraFormFields #RegisterExtraFormFields_Window h1 {
								padding-bottom: 3px;
								border-bottom: 1px solid #000000;
							}
							
							#RegisterExtraFormFields #RegisterExtraFormFields_Window h3 {
								color: #FFFFFF;
								background-color: #000000;
							}
							
							#RegisterExtraFormFields #RegisterExtraFormFields_Window table table th:first-child {
								width: 0px;
							}
						</style>
						
						<tr valign="top">
						<th scope="row"><div id="RegisterExtraFormFields">
							<a id="close" href="#" onclick="javascript:document.getElementById('RegisterExtraFormFields').style.display='none';return false;">X</a>
							<div id="RegisterExtraFormFields_Window">
								<h1>Extra Form Fields - Quick Guide</h1>
								<p>This is a quick reference for creating custom form fields.</p>
								<h2>Field Types</h2>
								<h3>Checkbox</h3>
								<p>This is the best for any choice you offer the user for options like "I agree"</p>
								<table>
									<tr valign="top">
										<th>Name:</th>
										<td>This is the internal name of the field, this name must be unique otherwise strange things will happen.</td>
									</tr>
									<tr valign="top">
										<th>Classes:</th>
										<td>You can specify any CSS classes you'd like to have applied to this field (mostly for advanced users).</td>
									</tr>
									<tr valign="top">
										<th>Default:</th>
										<td>This is a value if marked.</td>
									</tr>
									<tr valign="top">
										<th>Caption:</th>
										<td>This is the caption or label that is shown to the user on the left of the field.</td>
									</tr>
									<tr valign="top">
										<th>Parameters:</th>
										<td>
											This is a ';' separated list of name/value pairs. (Name=value;Name2=value2;Name3=value3)
											<table>
												<tr valign="top">
													<th width="0px">checked</th>
													<td>Specify true if you wish it to be checked by default or false if you wish it to not be checked by default.</td>
												</tr>
											</table>
										</td>
									</tr>
									<tr valign="top">
										<th>Required:</th>
										<td>Specifies if this field is required.</td>
									</tr>
									<tr valign="top">
										<th>Required Regular Expression:</th>
										<td>This is ignored for this field type</td>
									</tr>
									<tr valign="top">
										<th>Required Error:</th>
										<td>This specifies the error message used when the field is not marked. (&lt;%caption%&gt; is automatically replaced with the given label)</td>
									</tr>
									<tr valign="top">
										<th>Save</th>
										<td>Specifies if this value is saved. (In the future you will be able to specify that certain values are emailed to you upon registration)</td>
									</tr>
									<tr valign="top">
										<th>Signup</th>
										<td>Specifies if this field is visible on the registration page form.</td>
									</tr>
									<tr valign="top">
										<th>Profile</th>
										<td>Specifies if this field is visible on the user profile.</td>
									</tr>
									<tr valign="top">
										<th>Admin</th>
										<td>Specifies if this field is editable on the Edit User screen.</td>
									</tr>
								</table>
								<h3>Drop Down (Select)</h3>
								<p>This is the best for any choice you offer the user where they can only choose one answer and you have many posible answers. Like (State: Al, AK, AZ, ... WA, WV, WI, WY)</p>
								<table>
									<tr valign="top">
										<th>Name:</th>
										<td>This is the internal name of the field, this name must be unique otherwise strange things will happen.</td>
									</tr>
									<tr valign="top">
										<th>Classes:</th>
										<td>You can specify any CSS classes you'd like to have applied to this field (mostly for advanced users).</td>
									</tr>
									<tr valign="top">
										<th>Default:</th>
										<td>This is a ';' separated list of values. To label a value (to be displayed instead of the value) specify it as a name/value pair like (Male=1;Female=0).</td>
									</tr>
									<tr valign="top">
										<th>Caption:</th>
										<td>This is the caption or label that is shown to the user on the left of the field.</td>
									</tr>
									<tr valign="top">
										<th>Parameters:</th>
										<td>
											This is a ';' separated list of name/value pairs. (Name=value;Name2=value2;Name3=value3)
											<table>
												<tr valign="top">
													<th width="0px">default</th>
													<td>This specifies which option is the default. The value is the numeric 0-based index of the option you wish to use. For example to mark "Male" default in the following list (Male=1;Female=0), you would use "default=0"</td>
												</tr>
											<!--	<tr valign="top">
													<th width="0px">multiple</th>
													<td>The value of the html <a href="http://w3schools.com/tags/tag_select.asp" target="_blank">multiple</a> attribute</td>
												</tr>
												<tr valign="top">
													<th width="0px">size</th>
													<td>The value of the html <a href="http://w3schools.com/tags/tag_select.asp" target="_blank">size</a> attribute</td>
												</tr> -->
											</table>
										</td>
									</tr>
									<tr valign="top">
										<th>Required:</th>
										<td>Specifies if this field is required.</td>
									</tr>
									<tr valign="top">
										<th>Required Regular Expression:</th>
										<td>This is ignored for this field type</td>
									</tr>
									<tr valign="top">
										<th>Required Error:</th>
										<td>This specifies the error message used when the field is not marked. (&lt;%caption%&gt; is automatically replaced with the given label)</td>
									</tr>
									<tr valign="top">
										<th>Save</th>
										<td>Specifies if this value is saved. (In the future you will be able to specify that certain values are emailed to you upon registration)</td>
									</tr>
									<tr valign="top">
										<th>Signup</th>
										<td>Specifies if this field is visible on the registration page form.</td>
									</tr>
									<tr valign="top">
										<th>Profile</th>
										<td>Specifies if this field is visible on the user profile.</td>
									</tr>
									<tr valign="top">
										<th>Admin</th>
										<td>Specifies if this field is editable on the Edit User screen.</td>
									</tr>
								</table>
								<h3>Hidden</h3>
								<p>This allows you to specify hidden form values. This is useful if you need to send specific values for other plug-ins.</p>
								<table>
									<tr valign="top">
										<th>Name:</th>
										<td>This is the internal name of the field, this name must be unique otherwise strange things will happen.</td>
									</tr>
									<tr valign="top">
										<th>Classes:</th>
										<td>N/A</td>
									</tr>
									<tr valign="top">
										<th>Default:</th>
										<td>This is the value of the field.</td>
									</tr>
									<tr valign="top">
										<th>Caption:</th>
										<td>N/A</td>
									</tr>
									<tr valign="top">
										<th>Parameters:</th>
										<td>N/A</td>
									</tr>
									<tr valign="top">
										<th>Required:</th>
										<td>N/A</td>
									</tr>
									<tr valign="top">
										<th>Required Regular Expression:</th>
										<td>N/A</td>
									</tr>
									<tr valign="top">
										<th>Required Error:</th>
										<td>N/A</td>
									</tr>
									<tr valign="top">
										<th>Save</th>
										<td>Specifies if this value is saved. (In the future you will be able to specify that certain values are emailed to you upon registration)</td>
									</tr>
									<tr valign="top">
										<th>Signup</th>
										<td>Specifies if this field is visible on the registration page form.</td>
									</tr>
									<tr valign="top">
										<th>Profile</th>
										<td>Specifies if this field is visible on the user profile.</td>
									</tr>
									<tr valign="top">
										<th>Admin</th>
										<td>Specifies if this field is editable on the Edit User screen.</td>
									</tr>
								</table>
								<h3>Radio</h3>
								<p>This is the best for any choice you offer the user where they can only choose one answer. Like (Gender: Male   Female)</p>
								<table>
									<tr valign="top">
										<th>Name:</th>
										<td>This is the internal name of the field, this name must be unique otherwise strange things will happen.</td>
									</tr>
									<tr valign="top">
										<th>Classes:</th>
										<td>You can specify any CSS classes you'd like to have applied to this field (mostly for advanced users).</td>
									</tr>
									<tr valign="top">
										<th>Default:</th>
										<td>This is a ';' separated list of values. To label a value (on the right of the radio) specify it as a name/value pair like (Male=1;Female=0).</td>
									</tr>
									<tr valign="top">
										<th>Caption:</th>
										<td>This is the caption or label that is shown to the user on the left of the field.</td>
									</tr>
									<tr valign="top">
										<th>Parameters:</th>
										<td>
											This is a ';' separated list of name/value pairs. (Name=value;Name2=value2;Name3=value3)
											<table>
												<tr valign="top">
													<th width="0px">default</th>
													<td>This specifies which option is the default. The value is the numeric 0-based index of the option you wish to use. For example to mark "Male" default in the following list (Male=1;Female=0), you would use "default=0"</td>
												</tr>
											</table>
										</td>
									</tr>
									<tr valign="top">
										<th>Required:</th>
										<td>Specifies if this field is required.</td>
									</tr>
									<tr valign="top">
										<th>Required Regular Expression:</th>
										<td>This is ignored for this field type</td>
									</tr>
									<tr valign="top">
										<th>Required Error:</th>
										<td>This specifies the error message used when the field is not marked. (&lt;%caption%&gt; is automatically replaced with the given label)</td>
									</tr>
									<tr valign="top">
										<th>Save</th>
										<td>Specifies if this value is saved. (In the future you will be able to specify that certain values are emailed to you upon registration)</td>
									</tr>
									<tr valign="top">
										<th>Signup</th>
										<td>Specifies if this field is visible on the registration page form.</td>
									</tr>
									<tr valign="top">
										<th>Profile</th>
										<td>Specifies if this field is visible on the user profile.</td>
									</tr>
									<tr valign="top">
										<th>Admin</th>
										<td>Specifies if this field is editable on the Edit User screen.</td>
									</tr>
								</table>
								<h3>Text</h3>
								<p>This is the most common field type, and the easiest to understand.</p>
								<table>
									<tr valign="top">
										<th>Name:</th>
										<td>This is the internal name of the field, this name must be unique otherwise strange things will happen.</td>
									</tr>
									<tr valign="top">
										<th>Classes:</th>
										<td>You can specify any CSS classes you'd like to have applied to this field (mostly for advanced users).</td>
									</tr>
									<tr valign="top">
										<th>Default:</th>
										<td>This is the default value. This is used on the sign-up form and is the value that is already in the field when it is first shown to the user.</td>
									</tr>
									<tr valign="top">
										<th>Caption:</th>
										<td>This is the caption or label that is shown to the user on the left of the field.</td>
									</tr>
									<tr valign="top">
										<th>Parameters:</th>
										<td>
											This is a ';' separated list of name/value pairs. (Name=value;Name2=value2;Name3=value3)
											<table>
												<tr valign="top">
													<th width="0px">size</th>
													<td>The value of the html <a href="http://w3schools.com/tags/tag_input.asp" target="_blank">size</a> attribute</td>
												</tr>
												<tr valign="top">
													<th>maxlength</th>
													<td>The value of the html <a href="http://w3schools.com/tags/tag_input.asp" target="_blank">maxlength</a> attribute</td>
												</tr>
											</table>
										</td>
									</tr>
									<tr valign="top">
										<th>Required:</th>
										<td>Specifies if this field is required.</td>
									</tr>
									<tr valign="top">
										<th>Required Regular Expression:</th>
										<td>This specifies the rule used to enforce the required field. (The default value of '.+' denotes a general requirement of content.)</td>
									</tr>
									<tr valign="top">
										<th>Required Error:</th>
										<td>This specifies the error message used when the Regular Expression fails. (&lt;%caption%&gt; is automatically replaced with the given label)</td>
									</tr>
									<tr valign="top">
										<th>Save</th>
										<td>Specifies if this value is saved. (In the future you will be able to specify that certain values are emailed to you upon registration)</td>
									</tr>
									<tr valign="top">
										<th>Signup</th>
										<td>Specifies if this field is visible on the registration page form.</td>
									</tr>
									<tr valign="top">
										<th>Profile</th>
										<td>Specifies if this field is visible on the user profile.</td>
									</tr>
									<tr valign="top">
										<th>Admin</th>
										<td>Specifies if this field is editable on the Edit User screen.</td>
									</tr>
								</table>
								<h3>Text Area</h3>
								<p>This is the similar to text only it can have multiple lines of content.</p>
								<table>
									<tr valign="top">
										<th>Name:</th>
										<td>This is the internal name of the field, this name must be unique otherwise strange things will happen.</td>
									</tr>
									<tr valign="top">
										<th>Classes:</th>
										<td>You can specify any CSS classes you'd like to have applied to this field (mostly for advanced users).</td>
									</tr>
									<tr valign="top">
										<th>Default:</th>
										<td>This is the default value. This is used on the sign-up form and is the value that is already in the field when it is first shown to the user.</td>
									</tr>
									<tr valign="top">
										<th>Caption:</th>
										<td>This is the caption or label that is shown to the user on the left of the field.</td>
									</tr>
									<tr valign="top">
										<th>Parameters:</th>
										<td>
											This is a ';' separated list of name/value pairs. (Name=value;Name2=value2;Name3=value3)
											<table>
												<tr valign="top">
													<th width="0px">rows</th>
													<td>The value of the html <a href="http://w3schools.com/tags/tag_textarea.asp" target="_blank">rows</a> attribute</td>
												</tr>
												<tr valign="top">
													<th>cols</th>
													<td>The value of the html <a href="http://w3schools.com/tags/tag_textarea.asp" target="_blank">cols</a> attribute</td>
												</tr>
											</table>
										</td>
									</tr>
									<tr valign="top">
										<th>Required:</th>
										<td>Specifies if this field is required.</td>
									</tr>
									<tr valign="top">
										<th>Required Regular Expression:</th>
										<td>This specifies the rule used to enforce the required field. (The default value of '.+' denotes a general requirement of content.)</td>
									</tr>
									<tr valign="top">
										<th>Required Error:</th>
										<td>This specifies the error message used when the Regular Expression fails. (&lt;%caption%&gt; is automatically replaced with the given label)</td>
									</tr>
									<tr valign="top">
										<th>Save</th>
										<td>Specifies if this value is saved. (In the future you will be able to specify that certain values are emailed to you upon registration)</td>
									</tr>
									<tr valign="top">
										<th>Signup</th>
										<td>Specifies if this field is visible on the registration page form.</td>
									</tr>
									<tr valign="top">
										<th>Profile</th>
										<td>Specifies if this field is visible on the user profile.</td>
									</tr>
									<tr valign="top">
										<th>Admin</th>
										<td>Specifies if this field is editable on the Edit User screen.</td>
									</tr>
								</table>
							</div>
						</div>Extra Form Fields <span class="tooltip">(<a href="#" title="Quick Guide" onclick="javascript:document.getElementById('RegisterExtraFormFields').style.display='block';return false;">?</a>)</span></th>
						<td><?php
						$first = true;
						$extra_fields = @unserialize($register_row['Extra_Fields']);
						if(!is_array($extra_fields)) $extra_fields = array();
						foreach($extra_fields as $extra_id => $extra_field) {
							if($first) {
								$first = false;
							}
							else {
								echo "<hr />";
							}
							echo "<strong>Extra Field</strong><br />";
							echo "<a href=\"{$_SERVER['PHP_SELF']}?page=".urlencode(@$_REQUEST['page'])."&wp-membership_tab=".urlencode(@$_REQUEST['wp-membership_tab'])."&wp-membership_action=".urlencode(@$_REQUEST['wp-membership_action'])."&wp-membership_register_page_id=".urlencode($register_row['Register_Page_ID'])."&wp-membership_remove_extra_field=".urlencode($extra_id)."\">Remove Extra Field</a>";
							if(isset($extra_field->name)) echo "<br />Name: <input type=\"text\" name=\"wp-membership_extra_names[".htmlentities($extra_id)."]\" value=\"".htmlentities($extra_field->name)."\" />";
							if(isset($extra_field->classes)) echo "<br />Classes: <input type=\"text\" name=\"wp-membership_extra_classes[".htmlentities($extra_id)."]\" value=\"".htmlentities($extra_field->classes)."\" />";
							if(isset($extra_field->default)) echo "<br />Default: <input type=\"text\" name=\"wp-membership_extra_defaults[".htmlentities($extra_id)."]\" value=\"".htmlentities($extra_field->default)."\" />";
							if(isset($extra_field->caption)) echo "<br />Caption: <input type=\"text\" name=\"wp-membership_extra_captions[".htmlentities($extra_id)."]\" value=\"".htmlentities($extra_field->caption)."\" />";
							if(isset($extra_field->type)) {
								$types = array('text' => 'Text', 'textarea' => 'Text Area', 'radio' => 'Radio', 'checkbox' => 'Checkbox', 'hidden' => 'Hidden', 'select' => 'Drop Down (Select)');
								asort($types);
								echo "<br />Type: <select name=\"wp-membership_extra_types[".htmlentities($extra_id)."]\">";
								foreach($types as $type => $caption) echo "<option value=\"$type\"".($extra_field->type == "$type" ? " SELECTED" : "").">$caption</option>";
								echo "</select>";
							}
							if(isset($extra_field->parameters)) echo "<br />Parameters: <input type=\"text\" name=\"wp-membership_extra_parameters[".htmlentities($extra_id)."]\" value=\"".htmlentities($extra_field->parameters)."\" />";
							if(isset($extra_field->required)) echo "<br />Required: <input type=\"checkbox\" name=\"wp-membership_extra_requireds[".htmlentities($extra_id)."]\" value=\"1\"".($extra_field->required ? " CHECKED" : "")." />";
							if(isset($extra_field->required_regex)) echo "<br />Regex: <input type=\"text\" name=\"wp-membership_extra_required_regexs[".htmlentities($extra_id)."]\" value=\"".htmlentities($extra_field->required_regex)."\" />";
							if(isset($extra_field->required_error)) echo "<br />Regex: <input type=\"text\" name=\"wp-membership_extra_required_errors[".htmlentities($extra_id)."]\" value=\"".htmlentities($extra_field->required_error)."\" />";
							if(isset($extra_field->save)) echo "<br />Save: <input type=\"checkbox\" name=\"wp-membership_extra_saves[".htmlentities($extra_id)."]\" value=\"1\"".($extra_field->save ? " CHECKED" : "")." />";
							if(isset($extra_field->signup)) echo "<br />Signup: <input type=\"checkbox\" name=\"wp-membership_extra_signups[".htmlentities($extra_id)."]\" value=\"1\"".($extra_field->signup ? " CHECKED" : "")." />";
							if(isset($extra_field->profile)) echo "<br />Profile: <input type=\"checkbox\" name=\"wp-membership_extra_profiles[".htmlentities($extra_id)."]\" value=\"1\"".($extra_field->profile ? " CHECKED" : "")." />";
							if(isset($extra_field->admin)) echo "<br />Admin: <input type=\"checkbox\" name=\"wp-membership_extra_admins[".htmlentities($extra_id)."]\" value=\"1\"".($extra_field->admin ? " CHECKED" : "")." />";
						}
						if(!$first) {
							echo "<hr />";
						}
						echo "<a href=\"{$_SERVER['PHP_SELF']}?page=".urlencode(@$_REQUEST['page'])."&wp-membership_tab=".urlencode(@$_REQUEST['wp-membership_tab'])."&wp-membership_action=".urlencode(@$_REQUEST['wp-membership_action'])."&wp-membership_register_page_id=".urlencode($register_row['Register_Page_ID'])."&wp-membership_add_extra_field=1\">Add new Extra Field</a>";
						?></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Levels</th>
						<td><?php
						$register_levels = array();
						if($register_levels_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_page_levels AS t1 WHERE t1.Register_Page_ID=%s", $register_row['Register_Page_ID']), ARRAY_A)) {
							foreach($register_levels_rows as $register_levels_row) {
								$register_levels[] = $register_levels_row['Level_ID'];
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
								echo "<input type=\"checkbox\" name=\"wp-membership_level_ids[]\" value=\"".htmlentities($level_row['Level_ID'])."\"";
								if(in_array($level_row['Level_ID'], $register_levels)) echo " CHECKED";
								echo " />";
								echo " ".htmlentities($level_row['Name']);
							}
						}
						?></td>
						</tr>
						
						<tr valign="top">
						<th scope="row">Payment Gateways</th>
						<td><?php
						$register_gateways = array();
						if($register_gateways_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_page_gateways AS t1 WHERE t1.Register_Page_ID=%s", $register_row['Register_Page_ID']), ARRAY_A)) {
							foreach($register_gateways_rows as $register_gateways_row) {
								$register_gateways[] = $register_gateways_row['Payment_Gateway'];
							}
						}
						
						$first = true;
						foreach($this->plugins as $name => $plugin) {
							if($first) {
								$first = false;
							}
							else {
								echo "<br />";
							}
							echo "<input type=\"checkbox\" name=\"wp-membership_plugins[".htmlentities($name)."]\" value=\"".htmlentities($name)."\"";
							if(in_array($name, $register_gateways)) echo " CHECKED";
							echo " />";
							echo " ".htmlentities($name);
						}
						?></td>
						</tr>
	
						</table>
						
						<input type="hidden" name="wp-membership_tab" value="4" />
						<input type="hidden" name="wp-membership_register_page_id" value="<?php echo htmlentities($register_row['Register_Page_ID']); ?>" />
						<input type="hidden" name="wp-membership_action" value="edit_register" />
						<input type="hidden" name="wp-membership_do_edit_register" value="1" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Update Register Page', 'wp-membership'); ?>" />
						</p>
						
						</form>
						
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						<input type="hidden" name="wp-membership_tab" value="4" />
						<input type="hidden" name="wp-membership_register_page_id" value="<?php echo htmlentities($register_row['Register_Page_ID']); ?>" />
						<input type="hidden" name="wp-membership_action" value="delete_register" />
						<input type="hidden" name="wp-membership_do_delete_register" value="1" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Delete Register Page', 'wp-membership'); ?>" />
						</p>
						
						</form>
						<?php
						break;
					}
					else {
						echo "<div id=\"message\" class=\"error\"><p><strong>Failed to load Specified WP-Membership Register Page to Edit</strong></p></div>";
					}
				default:
					if($edit_success === true) {
						echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Register Page Updated.</strong></p></div>";
					}
					if($delete_success === true) {
						echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Register Page Deleted.</strong></p></div>";
					}
					else if($delete_success === false) {
						echo "<div id=\"message\" class=\"error\"><p><strong>WP-Membership Register Page Failed to Delete.</strong></p></div>";
					}
					if(@$_REQUEST['wp-membership_action'] == "add_register") {
						$insert_query = $wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_register_pages (Name, Description, Macro, WP_Page_ID) VALUES (%s, %s, %s, %s)", @$_REQUEST['wp-membership_name'], @$_REQUEST['wp-membership_description'], @$_REQUEST['wp-membership_macro'], @$_REQUEST['wp-membership_page_id']);
						if($wpdb->query($insert_query) !== false) {
							$register_id = $wpdb->insert_id;
							$success = true;
							if(is_array(@$_REQUEST['wp-membership_level_ids'])) {
								foreach($_REQUEST['wp-membership_level_ids'] as $level_id) {
									if($wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_register_page_levels (Register_Page_ID, Level_ID) VALUES (%s, %s)", @$register_id, $level_id)) === false) $success = false;
								}
							}
							if(is_array(@$_REQUEST['wp-membership_plugins'])) {
								foreach($_REQUEST['wp-membership_plugins'] as $plugin_id) {
									if($wpdb->query($wpdb->prepare("INSERT INTO ".$wpdb->prefix."wp_membership_register_page_gateways (Register_Page_ID, Payment_Gateway) VALUES (%s, %s)", @$register_id, $plugin_id)) === false) $success = false;
								}
							}
							if($success) {
								echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Level Added.</strong></p></div>";
							}
							else {
								echo "<div id=\"message\" class=\"error\"><p><strong>WP-Membership Level Added, but not all of the pages where added</strong></p></div>";
							}
						}
					}
					?>
					<h3>Add Register Page</h3>
					
					<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
					
					<table class="form-table">
					
					<tr valign="top">
					<th scope="row">Name</th>
					<td><input type="text" name="wp-membership_name" /></td>
					</tr>
					
					<tr valign="top">
					<th scope="row">Description</th>
					<td><textarea name="wp-membership_description" rows="5" cols="80"></textarea></td>
					</tr>
					
					<tr valign="top">
					<th scope="row">Default Page Macro</th>
					<td><input type="text" name="wp-membership_macro" value="[Register Page]" /></td>
					</tr>
					
					<tr valign="top">
					<th scope="row">Page</th>
					<td><select name="wp-membership_page_id"><option value="">[Choose a Page]</option><?php
					$pages = get_pages();
					foreach($pages as $page) {
						echo "<option value=\"".htmlentities($page->ID)."\"";
						echo ">".htmlentities($page->post_title)."</option>";
					}
					?></select></td>
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
							echo "<input type=\"checkbox\" name=\"wp-membership_level_ids[".htmlentities($level_row['Level_ID'])."]\" value=\"".htmlentities($level_row['Level_ID'])."\" />";
							echo " ".htmlentities($level_row['Name']);
						}
					}
					?></td>
					</tr>
					
					<tr valign="top">
					<th scope="row">Payment Gateways</th>
					<td><?php
					$first = true;
					foreach($this->plugins as $name => $plugin) {
						if($first) {
							$first = false;
						}
						else {
							echo "<br />";
						}
						echo "<input type=\"checkbox\" name=\"wp-membership_plugins[".htmlentities($name)."]\" value=\"".htmlentities($name)."\" />";
						echo " ".htmlentities($name);
					}
					?></td>
					</tr>
					
					</table>
					
					<input type="hidden" name="wp-membership_tab" value="4" />
					<input type="hidden" name="wp-membership_action" value="add_register" />
					
					<p class="submit">
					<input type="submit" name="Submit" value="<?php _e('Add Register Page', 'wp-membership'); ?>" />
					</p>
					
					</form>
					<?php
					if($register_rows = $wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 ORDER BY t1.Name"), ARRAY_A)) {
						?>
						<h3>View Register Pages</h3>
						
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						<?php wp_nonce_field('update-options'); ?>
						
						<table class="form-table">
						
						<tr valign="top">
						<th scope="row">Register Pages</th>
						<td><select name="wp-membership_register_page_id"><?php
						foreach($register_rows as $register_row) {
							echo "<option value=\"".htmlentities($register_row['Register_Page_ID'])."\"";
							echo ">".htmlentities($register_row['Name'])."</option>";
						}
						?></select></td>
						</tr>
						
						</table>
						
						<input type="hidden" name="wp-membership_tab" value="4" />
						<input type="hidden" name="wp-membership_action" value="edit_register" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Edit Register Page', 'wp-membership'); ?>" />
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