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
	if(!class_exists('wp_membership_SettingsTab_Feedback')) {
		class wp_membership_SettingsTab_Feedback implements IWPMembershipSettingsTab {
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
				<h2>WP-Membership - Feedback</h2>
				<h3>Send Feedback</h3>
				<?php
				?>
				<script type="text/javascript">
function sendFeedback() {
        jQuery.ajax(
                {
                        type: 'POST',
                        url: 'http://free-wp-membership.foransrealm.com/feedback',
                        data: {
                                wp_membership_company: document.getElementById('wp-membership_company').value,
                                wp_membership_Name: document.getElementById('wp-membership_Name').value,
                                wp_membership_Email: document.getElementById('wp-membership_Email').value,
                                wp_membership_Feedback: document.getElementById('wp-membership_Feedback').innerText
                        },
                        success: function(data) {
				alert(data.message);
				document.getElementById('feedbackForm').reset();
			},
			error: function() {
				alert('Failed to submit feedback');
			}
                }
        );
}
				</script>
				<form id="feedbackForm" method="post" action="<?php echo $_SERVER['PHP_SELF']."?page=".urlencode(@$_REQUEST['page']); ?>">
				<table class="form-table">
					<tr valign="top">
						<th scope="row">Company</th>
						<td><input type="text" id="wp-membership_company" name="wp-membership_company" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Name</th>
						<td><input type="text" id="wp-membership_Name" name="wp-membership_Name" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">Email</th>
						<td><input type="text" id="wp-membership_Email" name="wp-membership_Email" /></td>
					</tr>
					<tr valign="top">
						<th scope="row">*Feedback</th>
						<td><textarea id="wp-membership_Feedback" name="wp-membership_Feedback" rows="5" cols="60"></textarea></td>
					</tr>
				</table>
				
				<input type="hidden" name="wp-membership_tab" value="6" />
				<input type="hidden" name="wp-membership_action" value="send_feedback" />
				
				<p class="submit">
				<input type="submit" name="Submit" value="<?php _e('Send Feedback', 'wp-membership'); ?>" onclick="sendFeedback();return false;" />
				</p>
				</form>
				<?php
				if($div_wrapper) echo '</div>';
			}
		}
	}
}
?>
