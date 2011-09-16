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
	if(!class_exists('wp_membership_SettingsTab_Troubleshooting')) {
		class wp_membership_SettingsTab_Troubleshooting implements IWPMembershipSettingsTab {
			function get_File() {
				return __FILE__;
			}
			function DisplayTab() {
			$div_wrapper = false;
			if(!isset($query_string)) {
			    load_plugin_textdomain('wp-membership', false, 'wp-membership');
			    $query_string = "page=".urlencode(@$_REQUEST['page']);//ereg_replace("[&?]?wp-membership_tab[=][^&]*", "", @$_SERVER['QUERY_STRING']);
			    $div_wrapper = true;
			}
			if($div_wrapper) echo '<div class="wrap">';
			?>
			<h2>WP-Membership - Troubleshooting</h2>
			<?php
			?>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF']."?".$query_string; ?>">
			
			<h3>Database</h3>
			
			<table class="form-table">
			
			<tr valign="top">
			<th scope="row">Recreate</th>
			<td><a href="<?php echo $_SERVER['PHP_SELF'].'?'.$query_string.'&wp-membership_recreatedb=1'; ?>">Recreate the database</a> (does not delete existing data)</td>
			</tr>
			
			</table>
			
			<h3>Unit Tests</h3>
			<table class="form-table">
			<tr valign="top">
			<th scope="row">Unit Test Actions</th>
			<td><input type="button" value="Execute" onclick="javascript:jQuery.ajax({type: 'POST', url:'http://foransrealm.com/~foran/wp_sandbox/wp-content/plugins/free-wp-membership/UnitTestFramework.php', data: {unit_test_nonce: '<?php echo wp_create_nonce('execute_unit_test'); ?>'}, success: function(data){alert(data);}});" /></td>
			</tr>
			</table>
			
			<input type="hidden" name="wp-membership_tab" value="7" />
			<input type="hidden" name="wp-membership_action" value="update_troubleshooting" />
			
			<p class="submit">
<!--			<input type="submit" name="Submit" value="<?php _e('Update Troubleshooting Options', 'wp-membership'); ?>" /> -->
			</p>
			</form>
			<?php
			if($div_wrapper) echo '</div>';
			}
		}
	}
}
?>