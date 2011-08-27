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
	if(!class_exists('wp_membership_SettingsTab_PaymentGateways')) {
		class wp_membership_SettingsTab_PaymentGateways implements IWPMembershipSettingsTab {
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
			<h2><?php _e('WP-Membership - Payment Gateways', 'wp-membership'); ?></h2>
			<?php
			$switchvar = @$_REQUEST['wp-membership_action'];
			$edit_success = null;
			if(@$_REQUEST['wp-membership_do_edit_gateway'] == "1") {
				$edit_success = false;
				if(isset($this->plugins[@$_REQUEST['wp-membership_payment_gateway_id']])) {
					$edit_success = $this->plugins[@$_REQUEST['wp-membership_payment_gateway_id']]->do_SettingsEdit();
					//if($edit_success) $switchvar = "";
				}
			}
			switch($switchvar) {
				case "edit_gateway":
					if(isset($this->plugins[@$_REQUEST['wp-membership_payment_gateway_id']])) {
						?>
						<h3>Edit Register Page "<?php echo htmlentities($this->plugins[@$_REQUEST['wp-membership_payment_gateway_id']]->get_Name()); ?>"</h3>
						
						<?php
						if($edit_success === false) {
							echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Payment Gateway Failed to Update.</strong></p></div>";
						}
						?>
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						
						<table class="form-table">
						<?php
						$this->plugins[@$_REQUEST['wp-membership_payment_gateway_id']]->show_SettingsEdit();
						?>
						</table>
						
						<input type="hidden" name="wp-membership_tab" value="5" />
						<input type="hidden" name="wp-membership_payment_gateway_id" value="<?php echo htmlentities(@$_REQUEST['wp-membership_payment_gateway_id']); ?>" />
						<input type="hidden" name="wp-membership_action" value="edit_gateway" />
						<input type="hidden" name="wp-membership_do_edit_gateway" value="1" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Update Payment Gateway', 'wp-membership'); ?>" />
						</p>
						
						</form>
						<?php
//						echo $this->plugins[@$_REQUEST['wp-membership_payment_gateway_id']]->get_Subscription_Button("123test", "This is a test Caption", 12.95, "1 month");
//						echo $this->plugins[@$_REQUEST['wp-membership_payment_gateway_id']]->get_Subscription_Button("123test", "This is a test Caption", 12.95, "1 month", "3 days");
						break;
					}
					else {
						echo "<div id=\"message\" class=\"error\"><p><strong>Failed to load Specified WP-Membership Payment Gateway to Edit</strong></p></div>";
					}
				default:
					if(is_array($this->plugins) && count($this->plugins) > 0) {
						?>
						<h3>View Payment Gateways</h3>
						<?php
						if($edit_success === true) {
							echo "<div style=\"background-color: rgb(255, 251, 204);\" id=\"message\" class=\"updated fade\"><p><strong>WP-Membership Payment Gateway Updated.</strong></p></div>";
						}
						?>
						<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
						
						<table class="form-table">
						
						<tr valign="top">
						<th scope="row">Payment Gateways</th>
						<td><select name="wp-membership_payment_gateway_id"><?php
						foreach($this->plugins as $name => $plugin) {
							echo "<option value=\"".htmlentities($name)."\"";
							echo ">".htmlentities($name)."</option>";
						}
						?></select></td>
						</tr>
						
						</table>
						
						<input type="hidden" name="wp-membership_tab" value="5" />
						<input type="hidden" name="wp-membership_action" value="edit_gateway" />
						
						<p class="submit">
						<input type="submit" name="Submit" value="<?php _e('Edit Payment Gateway', 'wp-membership'); ?>" />
						</p>
						
						</form>
						<?php
					}
					else {
						echo "<div id=\"message\" class=\"error\"><p><strong>There are currently no Payment Gateways to configure.</strong></p></div>";
					}
					break;
			}
			if($div_wrapper) echo '</div>';
			}
		}
	}
}
?>