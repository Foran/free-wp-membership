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

if(!interface_exists("wp_membership_payment_gateway")) {
	interface wp_membership_payment_gateway {
		function get_Name();
		function get_Description();
		function get_Capabilities();
		function do_SettingsEdit();
		function show_SettingsEdit();
		function need_PaymentForm();
		function has_BuyNow_Button();
		function get_BuyNow_Button($id, $caption, $amount);
		function has_Subscription_Button();
		function get_Subscription_Button($id, $caption, $amount, $duration, $delay = null);
		function has_Unsubscribe_Button();
		function get_Unsubscribe_Button($id, $caption);
		function has_Process_Charge();
		function Process_Charge($id, $caption, $amount, $cc_num, $cc_exp, $cc_cvv2, $cc_name, $bill_name, $bill_address, $bill_address2, $bill_city, $bill_state, $bill_zip, $bill_phone, $bill_country);
		function has_Process_Refund();
		function Process_Refund($transactionid, $amount = null);
		function has_Install_Subscription();
		function Install_Subscription($id, $caption, $amount, $cc_num, $cc_exp, $cc_cvv2, $cc_name, $bill_name, $bill_address, $bill_address2, $bill_city, $bill_state, $bill_zip, $bill_phone, $bill_country, $duration, $delay = null);
		function has_Uninstall_Subscription();
		function Uninstall_Subscription($subscription_id);
		function Install();
		function Uninstall();
		function has_Transactions();
		function get_Transactions();
		function get_Transaction($transactionid);
		function has_Subscriptions();
		function get_Subscriptions();
		function get_Subscription($subscriptionid);
		function find_Subscription($userlevelid);
		function callback_PostBack($callback);
		function has_Subscription($userlevelid);
		function delete_User($userid);
		function get_Hidden_Pages();
		function is_Currency_Supported($currency = null);
	}
}
?>