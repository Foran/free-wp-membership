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
if(isset($wp_membership_plugin) && class_exists('wp_membership_plugin') && is_a($wp_membership_plugin, 'wp_membership_plugin') && !class_exists('wp_membership_SettingsTab_NewsInfo')) {
	class wp_membership_SettingsTab_NewsInfo implements IWPMembershipSettingsTab {
		function get_File() {
			return __FILE__;
		}
		function DisplayTab() {
			global $wpdb;
			
			$div_wrapper = false;
			if(!isset($query_string)) {
			    load_plugin_textdomain('wp-membership', false, 'wp-membership');
			    $query_string = "page=".urlencode(@$_REQUEST['page']);//ereg_replace("[&?]?wp-membership_tab[=][^&]*", "", @$_SERVER['QUERY_STRING']);
			    $div_wrapper = true;
			}
			if($div_wrapper) echo '<div class="wrap">';
			?>

			<h3>Important Links</h3>
			<table class="form-table">
			<tr valign="top">
			<th scope="row">Source Code</th>
			<td><a href="https://github.com/Foran/free-wp-membership" title="Free WP_Membership's Official Repository">https://github.com/Foran/free-wp-membership</a></td>
			</tr>
			<tr valign="top">
			<th scope="row">WordPress.org Plugin Page</th>
			<td><a href="http://wordpress.org/extend/plugins/free-wp-membership/">http://wordpress.org/extend/plugins/free-wp-membership/</a></td>
			</tr>
			<tr valign="top">
			<th scope="row">Home Page</th>
			<td><a href="http://free-wp-membership.foransrealm.com/">http://free-wp-membership.foransrealm.com/</a></td>
			</tr>
			</table>
			
			<h3>System Information</h3>
			
			<table class="form-table">
			
			<tr valign="top">
			<th scope="row">Free WP-Membership Version</th>
			<td>1.1.5</td>
			</tr>
			
			<tr valign="top">
			<th scope="row">Installed Add-ons</th>
			<td><?php
			$first = true;
			foreach($wp_membership_plugin->plugins as $plugin => $file) {
				if($first) $first = false;
				else echo '<br />';
				echo htmlentities($plugin);
			}
			?></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">WordPress Version</th>
			<td><?php
			if(isset($GLOBALS['wp_version'])) {
				echo htmlentities($GLOBALS['wp_version']);
				if(eregi("^([0-9a-z.]+)", $GLOBALS['wp_version'], $regs)) {
					if(version_compare($regs[1], '2.6.0', '<')) {
						echo "<br />Warning: WP-Membership Requires WordPress 2.6.0 or greater";
					}
				}
			}
			else {
				echo 'Failed to get WordPress Version (Possibly not 2.6, 2.7 or 2.8)';
			}
			?></td>
			</tr>

			<tr valign="top">
			<th scope="row">PHP Version</th>
			<td><?php echo htmlentities(PHP_VERSION); ?></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">MySQL Version</th>
			<td><?php
			$version_query = $wpdb->prepare("SELECT VERSION() AS Version");
			if($version_row = $wpdb->get_row($version_query, ARRAY_A)) {
				echo htmlentities($version_row['Version']);
				if(eregi("^([0-9a-z.]+)", $version_row['Version'], $regs)) {
					if(version_compare($regs[1], '5.0.0', '<')) {
						echo "<br />Warning: WP-Membership Requires MySQL 5.0.0 or greater";
					}
				}
			}
			else {
				echo 'Failed to get MySQL Version';
			}
			?></td>
			</tr>
			
			<tr valign="top">
			<th scope="row">HTTPS Support</th>
			<td><?php
			$https_data = get_option("wp-membership_info_https");
			$https = null;
			if(is_array($https_data) && isset($https_data['last_check']) && isset($https_data['https']) && is_array($https_data['https']) && $https_data['last_check'] > strtotime("-1 hour")) {
				$https = $https_data['https'];
			}
			if(!is_array($https)) {
				$ch = curl_init(str_ireplace("http://", "https://", get_option('siteurl')));
				if($ch) {
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$buffer = curl_exec($ch);
					if($buffer !== false) {
						echo 'HTTPS detected';
						update_option("wp-membership_info_https", array('last_check' => time(), 'https' => array(true, 'HTTPS detected')));
					}
					else {
						switch(curl_errno($ch)) {
							case CURLE_SSL_CIPHER:
							case CURLE_SSL_CONNECT_ERROR:
							case CURLE_SSL_ENGINE_NOTFOUND:
							case CURLE_SSL_ENGINE_SETFAILED:
								echo 'Failed to detect HTTPS (may still be present, may be a proxy error)';
						update_option("wp-membership_info_https", array('last_check' => time(), 'https' => array(false, 'Failed to detect HTTPS (may still be present, may be a proxy error)')));
								break;
							case CURLE_SSL_CACERT:
							case CURLE_SSL_CERTPROBLEM:
							case CURLE_SSL_PEER_CERTIFICATE:
								echo 'HTTPS Detected (SSL certificate problem)';
						update_option("wp-membership_info_https", array('last_check' => time(), 'https' => array(false, 'HTTPS Detected (SSL certificate problem)')));
								break;
							default:
								echo 'Failed to detect HTTPS';
						update_option("wp-membership_info_https", array('last_check' => time(), 'https' => array(false, 'Failed to detect HTTPS')));
								break;
						}
					}
					curl_close($ch);
				}
				else {
					echo 'Failed to detect HTTPS (may still be present)';
					update_option("wp-membership_info_https", array('last_check' => 0, 'https' => array(false, 'HTTPS detected')));
				}
			}
			else {
				echo @$https[1];
			}
			?></td>
			</tr>
			
			</table>

			<?php
			$ch = curl_init('https://api.github.com/repos/Foran/free-wp-membership/issues');
			if($ch) {
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				$buffer = curl_exec($ch);
				if($buffer) {
					$issues = json_decode($buffer);
					usort($issues, function ($a, $b) {
						return isset($a->milestone) && isset($b->milestone) ? version_compare($a->milestone->title, $b->milestone->title, '>=') : (isset($b->milestone) ? 1 : 0);
					});
			?>
			<h3>Issues</h3>
				<?php
				foreach($issues as $issue) {
				?>
				<h4><?php echo htmlentities($issue->title); ?></h4>
			<table class="form-table">
				<tr valign="top">
					<th scope="row">Milestone</th>
					<td><?php echo htmlentities(isset($issue->milestone) ? $issue->milestone->title : 'N/A'); ?></td>
				</tr>
				<tr valign="top">
					<th scope="row">Description</th>
					<td><?php echo htmlentities($issue->body); ?></td>
				</tr>
				<tr valign="top">
					<th scope="row">Assigned to</th>
					<td><?php echo isset($issue->assignee) ? '<a href="https://github.com/'.$issue->assignee->login.'" title="'.$issue->assignee->login.'"><img src="'.$issue->assignee->avatar_url.'" /></a>' : htmlentities('Unassigned'); ?></td>
				</tr>
			</table>
				<?php
				}
				?>
			<?php
				}
			}
			if($div_wrapper) echo '</div>';				
		}
	}
}
?>