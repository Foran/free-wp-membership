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
?>
<?php
if(interface_exists("wp_membership_payment_gateway")) {
	if(!class_exists("wp_membership_plugin_test")) {
		class wp_membership_plugin_test implements wp_membership_payment_gateway {
			function __construct() {
				add_option("wp-membership_plugins_test_email", "");
			}
			
			function get_Name() {
				return "Test Payment Gateway!";
			}
			
			function get_Description() {
				return "";
			}
			
			function get_Capabilities() {
				return array();
			}
			
			function do_SettingsEdit() {
				$retval = false;
				
				if(trim(@$_REQUEST['wp-membership_email']) == "" || is_email(@$_REQUEST['wp-membership_email'])) {
					update_option("wp-membership_plugins_test_email", @$_REQUEST['wp-membership_email']);
					$retval = true;
				}
				
				return $retval;
			}
			
			function show_SettingsEdit() {
				?>
				<tr valign="top">
				<th scope="row">Email</th>
				<td><input type="text" name="wp-membership_email" value="<?php echo htmlentities(get_option("wp-membership_plugins_test_email")); ?>" /></td>
				</tr>
				<?php
			}
			
			function need_PaymentForm() {
				return false;
			}
			
			function has_BuyNow_Button() {
				return false;
			}
			
			function get_BuyNow_Button($id, $caption, $amount) {
				$retval = "";
				
				return $retval;
			}

			function has_Subscription_Button() {
				return false;
			}
			
			function get_Subscription_Button($id, $caption, $amount, $duration, $delay = null) {
				$retval = "";
				
				return $retval;
			}

			function has_Unsubscribe_Button() {
				return false;
			}
			
			function get_Unsubscribe_Button($id, $caption) {
				$retval = "";
				
				return $retval;
			}
			
			function has_Process_Charge() {
				return false;
			}
			
			function Process_Charge($id, $caption, $amount, $cc_num, $cc_exp, $cc_cvv2, $cc_name, $bill_name, $bill_address, $bill_address2, $bill_city, $bill_state, $bill_zip, $bill_phone, $bill_country) {
				return false;
			}
			
			function has_Process_Refund() {
				return false;
			}
			
			function Process_Refund($transactionid, $amount = null) {
				return false;
			}
			
			function has_Install_Subscription() {
				return false;
			}
			
			function Install_Subscription($id, $caption, $amount, $cc_num, $cc_exp, $cc_cvv2, $cc_name, $bill_name, $bill_address, $bill_address2, $bill_city, $bill_state, $bill_zip, $bill_phone, $bill_country, $duration, $delay = null) {
				return false;
			}
			
			function has_Uninstall_Subscription() {
				return false;
			}
			
			function Uninstall_Subscription($subscription_id) {
				return false;
			}
			
			function Install() {
				return false;
			}
			
			function Uninstall() {
				return false;
			}
			
			function has_Transactions() {
				return false;
			}
			
			function get_Transactions() {
				return false;
			}
			
			function get_Transaction($transactionid) {
				return false;
			}
			
			function has_Subscriptions() {
				return false;
			}
			
			function get_Subscriptions() {
				return false;
			}
			
			function get_Subscription($subscriptionid) {
				return false;
			}
			
			function find_Subscription($userlevelid) {
				$retval = false;
				
				return $retval;
			}
			
			function callback_PostBack($callback) {
				
			}
			
			function delete_User($userid) {
				
			}
			
			function has_Subscription($userlevelid) {
				$retval = false;
				global $wpdb;
				
				return $retval;
			}
			
			function get_Hidden_Pages() {
				$retval = array();
				
				return $retval;
			}
			
			function is_Currency_Supported($currency = null) {
				return false;
			}
		}
	}
}
