?><?php
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
/*
 x_name_of_field=value of field&
 Card Not Present - Intended for transactions where the customer's identify cannot be verified physically
Login: cnpdemo123 (DO NOT USE AS VALUE TO x_login)
Password: Authnet001
Login URL: https://test.authorize.net/
API Account Login ID: Use this login information integrate an application or website to Authorize.net.  Please do not attempt to change the password.
API Login: 6zz6m5N4Et
Transaction Key:  9V9wUv6Yd92t27t5
Secure Server Address: This is the URL you will need to access our test environment.
Transaction POST URL: https://test.authorize.net/gateway/transact.dll
*/


if(!function_exists('hash_hmac')) {
	function hash_hmac($algo, $data, $key, $raw_output = false)
	{
		// RFC 2104 HMAC implementation for php.
		// Creates an md5 HMAC.
		// Eliminates the need to install mhash to compute a HMAC
		// Hacked by Lance Rushing
		$b = 64; // byte length for md5
		if (strlen($key) > $b) {
			$key = pack("H*",md5($key));
		}
		$key  = str_pad($key, $b, chr(0x00));
		$ipad = str_pad('', $b, chr(0x36));
		$opad = str_pad('', $b, chr(0x5c));
		$k_ipad = $key ^ $ipad ;
		$k_opad = $key ^ $opad;
		return md5($k_opad  . pack("H*",md5($k_ipad . $data)));
	}
}

if(interface_exists("wp_membership_payment_gateway")) {
	if(!class_exists("wp_membership_plugin_AuthorizeNet")) {
		class wp_membership_plugin_AuthorizeNet implements wp_membership_payment_gateway {
			function __construct() {
				add_option("wp-membership_plugins_AuthorizeNet_testMode", true);
// TODO: Encrypt transaction key before going live
				add_option("wp-membership_plugins_AuthorizeNet_Transaction_Key", "9V9wUv6Yd92t27t5");
// TODO: Encrypt login ID before going live
				add_option("wp-membership_plugins_AuthorizeNet_Login_ID", "6zz6m5N4Et");
// TODO: Eventually currency type will have to be user-set
				add_option("wp-membership_plugins_AuthorizeNet_Currency", "USD");
				add_option("wp-membership_plugins_AuthorizeNet_NotifyEmail", get_option('admin_email'));
				add_option("wp-membership_plugins_AuthorizeNet_MD5Value", "");
			}
			
			function get_submission_URL() {
				// Uncomment when going live
				//$retval = "https://secure.authorize.net/gateway/transact.dll"
				if(get_option("wp-membership_plugins_AuthorizeNet_testMode") == true) {
					$retval = "https://test.authorize.net/gateway/transact.dll";
				}
				else $retval = "https://secure.authorize.net/gateway/transact.dll";
				return $retval;
			}
			function get_Name() { // Human-readable plugin description, "<Foo> Payment Gateway"
				return "Authorize.Net Payment Gateway";
			}
			
			function get_Description() { // Human-readable description of payment service
				return "";
			}
			
			function do_SettingsEdit() { // Save plugin-specific settings, such as account login IDs and such
				if(isset($_REQUEST['wp-membership_notifyemail'])) update_option('wp-membership_plugins_AuthorizeNet_NotifyEmail', @$_REQUEST['wp-membership_notifyemail']);
				if(isset($_REQUEST['wp-membership_login_id'])) update_option('wp-membership_plugins_AuthorizeNet_Login_ID', @$_REQUEST['wp-membership_login_id']);
				if(isset($_REQUEST['wp-membership_trans_key'])) update_option('wp-membership_plugins_AuthorizeNet_Transaction_Key', @$_REQUEST['wp-membership_trans_key']);
				//if(isset($_REQUEST['wp-membership_currency'])) update_option('wp-membership_plugins_AuthorizeNet_Currency', @$_REQUEST['wp-membership_currency']);
				if(isset($_REQUEST['wp-membership_md5'])) update_option('wp-membership_plugins_AuthorizeNet_MD5Value', @$_REQUEST['wp-membership_md5']);
				update_option("wp-membership_plugins_AuthorizeNet_testMode", @$_REQUEST['wp-membership_plugins_AuthorizeNet_TestMode'] == "1" ? true : false);
				echo "Successfully Updated!";
			}
			
			function show_SettingsEdit() { // HTML for the form where the user can edit service-specific settings such as their login info, which currency to use, etc.
			?>
				<tr valign="top">
				<th scope="row">Notify Email</th>
				<td><input type="text" name="wp-membership_notifyemail" value="<?php echo htmlentities(get_option("wp-membership_plugins_AuthorizeNet_NotifyEmail")); ?>" /></td>
				</tr>
				<tr valign="top">
				<th scope="row">Login ID</th>
				<td><input type="text" name="wp-membership_login_id" value="<?php echo htmlentities(get_option("wp-membership_plugins_AuthorizeNet_Login_ID")); ?>" /></td>
				</tr>
				<tr valign="top">
				<th scope="row">Transaction Key</th>
				<td><input type="text" name="wp-membership_trans_key" value="<?php echo htmlentities(get_option("wp-membership_plugins_AuthorizeNet_Transaction_Key")); ?>" /></td>
				</tr>
<?php /*				<tr valign="top">
				<th scope="row">Currency</th>
<!--				<td><input type="text" name="wp-membership_currency" value="<?php echo htmlentities(get_option("wp-membership_currency")); ?>" /></td> -->
					<td>
						<select name="wp-membership_currency">
<!--							<option value="">[Choose One]</option>
							<option value="AUD"<?php echo get_option("wp-membership_plugins_AuthorizeNet_Currency") == "AUD" ? " SELECTED" : ""; ?>>Australian Dollar</option>
							<option value="CAD"<?php echo get_option("wp-membership_plugins_AuthorizeNet_Currency") == "CAD" ? " SELECTED" : ""; ?>>Canadian Dollar</option>
							<option value="CHF"<?php echo get_option("wp-membership_plugins_AuthorizeNet_Currency") == "CHF" ? " SELECTED" : ""; ?>>Swiss Franc</option>
							<option value="CZK"<?php echo get_option("wp-membership_plugins_AuthorizeNet_Currency") == "CZK" ? " SELECTED" : ""; ?>>Czech Koruna</option>
							<option value="DKK"<?php echo get_option("wp-membership_plugins_AuthorizeNet_Currency") == "DKK" ? " SELECTED" : ""; ?>>Danish Krone</option>
							<option value="EUR"<?php echo get_option("wp-membership_plugins_AuthorizeNet_Currency") == "EUR" ? " SELECTED" : ""; ?>>Euro</option>
							<option value="GBP"<?php echo get_option("wp-membership_plugins_AuthorizeNet_Currency") == "GBP" ? " SELECTED" : ""; ?>>Pound Sterling</option>
							<option value="HKD"<?php echo get_option("wp-membership_plugins_AuthorizeNet_Currency") == "HKD" ? " SELECTED" : ""; ?>>Hong Kong Dollar</option>
							<option value="HUF"<?php echo get_option("wp-membership_plugins_AuthorizeNet_Currency") == "HUF" ? " SELECTED" : ""; ?>>Hungarian Forint</option>
							<option value="JPY"<?php echo get_option("wp-membership_plugins_AuthorizeNet_Currency") == "JPY" ? " SELECTED" : ""; ?>>Japanese Yen</option>
							<option value="NOK"<?php echo get_option("wp-membership_plugins_AuthorizeNet_Currency") == "NOK" ? " SELECTED" : ""; ?>>Norwegian Krone</option>
							<option value="NZD"<?php echo get_option("wp-membership_plugins_AuthorizeNet_Currency") == "NZD" ? " SELECTED" : ""; ?>>New Zealand Dollar</option>
							<option value="PLN"<?php echo get_option("wp-membership_plugins_AuthorizeNet_Currency") == "PLN" ? " SELECTED" : ""; ?>>Polish Zloty</option>
							<option value="SEK"<?php echo get_option("wp-membership_plugins_AuthorizeNet_Currency") == "SEK" ? " SELECTED" : ""; ?>>Swedish Krona</option>
							<option value="SGD"<?php echo get_option("wp-membership_plugins_AuthorizeNet_Currency") == "SGD" ? " SELECTED" : ""; ?>>Singapore Dollar</option> -->
							<option value="USD"<?php echo get_option("wp-membership_plugins_AuthorizeNet_Currency") == "USD" ? " SELECTED" : ""; ?>>U.S. Dollar</option>
						</select>
					</td>
				</tr> */ ?>
				<tr valign="top">
				<th scope="row">MD5 Value (<a href="http://www.authorize.net/support/Merchant/Integration_Settings/Receipt_Page_Options.htm#MD5">What is this?</a>)</th>
				<td><input type="text" name="wp-membership_md5" value="<?php echo htmlentities(get_option("wp-membership_plugins_AuthorizeNet_MD5Value")); ?>" /></td>
				</tr>
				<tr valign="top">
					<th scope="row">Test Mode</th>
					<td><input type="checkbox" name="wp-membership_plugins_AuthorizeNet_TestMode" value="1"<?php echo get_option("wp-membership_plugins_AuthorizeNet_testMode") ? " CHECKED" : ""; ?>></td>
				</tr>
			<?php
			}
			
			function need_PaymentForm() { // Returns bool. Do we need to take the user's CC information and such to complete the transaction (return true), or will the user be sent to a page (such as PayPal or Google Checkout) which has its own form that later sends the user back to us?
				return false;
			}
			
			function has_BuyNow_Button() { // Returns bool. Does the service support a service-hosted button or form to be included in local website which takes them to the gateway website to process the transaction. If service supports it but user does not want to use it and instead use a form on local website, set this to false even though the gateway technically does have a buynow button)
				return true;
			}
			
			// If the service provides a buynow button and the user wants to use it (if optional), how do I get the button from the gateway service?
			// Hidden elements only
			function get_BuyNow_Button($id, $caption, $amount) {
				// TODO: Sequence is the local transaction ID. Make this useful before going live.
				srand(time());
				$sequence = rand(); 
				$timeStamp = time();
			
				$transaction_key = get_option("wp-membership_plugins_AuthorizeNet_Transaction_Key");
				$login_id = get_option("wp-membership_plugins_AuthorizeNet_Login_ID");
				$currency_code = get_option("wp-membership_currency");
				
				$ipn = get_option('siteurl').'/wp-content/plugins/wp-membership/wp-membership.php?gateway_callback=AuthorizeNet';
				$ipn = get_option('siteurl')."/wp-content/plugins/".basename(dirname(__FILE__))."/wp-membership.php?gateway_callback=AuthorizeNet";

				// TODO: Validate order somewhere around here before making a fingerprint
				$fingerprint = hash_hmac("md5", $login_id.'^'.$sequence.'^'.$timeStamp.'^'.$amount.'^'.$currency_code, $transaction_key);

				$retval = '<form action="'.$this->get_submission_URL().'" method="POST">'."\n";
				$retval .= '<input type="hidden" name="x_fp_sequence" value="'.$sequence.'">'."\n";
				$retval .= '<input type="hidden" name="x_fp_timestamp" value="'.$timeStamp.'">'."\n";
				$retval .= '<input type="hidden" name="x_fp_hash" value="'.$fingerprint.'">'."\n";

				$retval .= '<input type="hidden" name="x_description" value="'.$caption.'">'."\n";
				$retval .= '<input type="hidden" name="x_login" value="'.$login_id.'">'."\n";
				$retval .= '<input type="hidden" name="x_amount" value="'.$amount.'">'."\n";
				$retval .= '<input type="hidden" name="x_currency_code" value="'.$currency_code.'">'."\n";
				
				$retval .= '<input type="hidden" name="x_show_form" value="PAYMENT_FORM">'."\n";
				$retval .= '<input type="hidden" name="x_test_request" value="'.(get_option("wp-membership_plugins_AuthorizeNet_testMode") ? 'TRUE' : 'FALSE').'">'."\n";
				$retval .= '<input type="hidden" name="x_relay_response" value="TRUE">'."\n";
				$retval .= '<input type="hidden" name="x_relay_url" value="'.htmlentities($ipn).'">'."\n";

				$retval .= '<input type="hidden" name="id" value="'.htmlentities($id).'">'."\n";
				$retval .= '<input type="submit" value="Buy Now with Authorize.net">'."\n";
				$retval .= '</form>';
				
				return $retval;
			}

			// See has_BuyNow_Button, except asks if gateway supports recurring substription-based purchase
			function has_Subscription_Button() {
				return false;
			}

			// see get_BuyNow_Button
			function get_Subscription_Button($id, $caption, $price, $duration, $delay = null) {
				// TODO: This
			}

			function has_Unsubscribe_Button() {
				return false;
			}
			
			function get_Unsubscribe_Button($id, $caption) {
				$retval = "";
				
				return $retval;
			}

			// Returns array of bools showing capabilities of website. Can't lie.
			function get_Capabilities() {
				return array();
			}

			// (bool) Does the gateway support a direct transaction on the CC (as opposed to having the user go to their website to make the purchase)?
			function has_Process_Charge() {
				return false;
			}

			// What do I do to directly charge the CC?
			function Process_Charge($id, $caption, $amount, $cc_num, $cc_exp, $cc_cvv2, $cc_name, $bill_name, $bill_address, $bill_address2, $bill_city, $bill_state, $bill_zip, $bill_phone, $bill_country) {
				return false;
			}

			// (bool) Does the gateway support a direct refund to the user's credit card?
			function has_Process_Refund() {
				return false;
			}

			// What do I do to refund $amount for the transaction specified? If $amount is null, refund full amount
			function Process_Refund($transactionid, $amount = null) {
				// TODO: This
				return false;
			}

			// (bool) Does the gateway support subscription-based (recurring) transactions?
			function has_Install_Subscription() {
				return true;
			}

			// How are subscriptions handled? $duration (in strtotime format) How often is user billed? "+1 month" for monthly, etc., $delay = null How long to wait before first payment? Ex. "+1 week" for waiting a week. If null, bill for first month immediately. This is their free trial period. 
			function Install_Subscription($id, $caption, $amount, $cc_num, $cc_exp, $cc_cvv2, $cc_name, $bill_name, $bill_address, $bill_address2, $bill_city, $bill_state, $bill_zip, $bill_phone, $bill_country, $duration, $delay = null) {
				$retval = false;
				global $wpdb;
				
				$ids = explode('_', $id);
				$xml = '<?xml version="1.0" encoding="utf-8" ?>'."\r\n";
				$xml .= '<ARBCreateSubscriptionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
				
				//Merchant Authentication
				$xml .= '<merchantAuthentication>';
				$xml .= '<name>'.htmlentities(get_option('wp-membership_plugins_AuthorizeNet_Login_ID')).'</name>';
				$xml .= '<transactionKey>'.htmlentities(get_option('wp-membership_plugins_AuthorizeNet_Transaction_Key')).'</transactionKey>';
				$xml .= '</merchantAuthentication>';
				
				$xml .= '<refId>'.htmlentities($id).'</refId>';
				
				//Subscription
				$xml .= '<subscription>';
				$xml .= '<name>'.htmlentities($caption).'</name>';
				
				//Payment Schedule
				$xml .= '<paymentSchedule>';
				$xml .= '<interval>';
				$increment = (int)$duration;
				$unit = null;
				if(stristr($duration, ' day')) $unit = "days";
				if(stristr($duration, ' month')) $unit = "months";
				if(stristr($duration, ' year')) {
					$increment *= 365;
					$unit = "days";
				}
				$xml .= '<length>'.htmlentities($increment).'</length>';
				$xml .= '<unit>'.htmlentities($unit).'</unit>';
				$xml .= '</interval>';
				$startdate = @strtotime("now");
				if(strlen($delay) > 0) $startdate = @strtotime($delay, $startdate);
				$xml .= '<startDate>'.htmlentities(@date("Y-m-d", $startdate)).'</startDate>';
				$xml .= '<totalOccurrences>9999</totalOccurrences>';
				$xml .= '<trialOccurrences>0</trialOccurrences>';
				$xml .= '</paymentSchedule>';
				$xml .= '<amount>'.htmlentities($amount).'</amount>';
				$xml .= '<trialAmount>0.00</trialAmount>';
				$xml .= '<payment>';
				$xml .= '<creditCard>';
				$xml .= '<cardNumber>'.htmlentities(trim(ereg_replace('[^0-9]', '', $cc_num))).'</cardNumber>';
				$xml .= '<expirationDate>'.htmlentities(@date("Y-m", $cc_exp)).'</expirationDate>';
				$xml .= '</creditCard>';
				$xml .= '</payment>';
				$xml .= '<customer>';
				$xml .= '<id>'.htmlentities($ids[0]).'</id>';
				$user_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_users AS t1 WHERE t1.User_ID=%s", $ids[0]);
				if($user_row = $wpdb->get_row($user_query, ARRAY_A)) {
					$xml .= '<email>'.htmlentities(@$user_row['Email']).'</email>';
				}
				if(strlen(trim($bill_phone)) > 0) $xml .= '<phoneNumber>'.htmlentities($bill_phone).'</phoneNumber>';
				$xml .= '</customer>';
				$xml .= '<billTo>';
				$xml .= '<firstName>'.htmlentities($bill_name).'</firstName>';
				$xml .= '<lastName>'.htmlentities($bill_name).'</lastName>';
				$xml .= '<address>'.htmlentities(trim($bill_address." ".$bill_address2)).'</address>';
				$xml .= '<city>'.htmlentities($bill_city).'</city>';
				$xml .= '<state>'.htmlentities($bill_state).'</state>';
				$xml .= '<zip>'.htmlentities($bill_zip).'</zip>';
				$xml .= '<country>'.htmlentities($bill_country).'</country>';
				$xml .= '</billTo>';
				$xml .= '</subscription>';
				
				$xml .= '</ARBCreateSubscriptionRequest>';
				
				$url = "https://api".(get_option("wp-membership_plugins_AuthorizeNet_testMode") ? "test" : "").".authorize.net/xml/v1/request.api";
				$ch = curl_init($url);
				if($ch) {
					$this->use_https_proxy($ch);
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
					curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/xml'));
					curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
					curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					curl_setopt($ch, CURLOPT_USERAGENT, "WP-Membership User Agent - http".(@$_SERVER['HTTPS'] == "on" ? "s" : "")."://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']} - {$_SERVER['HTTP_USER_AGENT']}");
					$output = curl_exec($ch);
					if($output !== false) {
						$XML = @simplexml_load_string($output);
						if($XML !== false) {
							if(isset($XML->messages) && isset($XML->messages->resultCode) && (string)$XML->messages->resultCode == "Ok") {
								if(isset($XML->messages->message) && isset($XML->messages->message->code) && (string)$XML->messages->message->code == "I00001") {
									if(isset($XML->subscriptionId)) {
										$subID = (string)$XML->subscriptionId;
										$userLevel_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_user_levels AS t1 WHERE t1.User_ID=%s AND t1.Level_Price_ID=%s", $ids[0], $ids[1]);
										if($userLevel_row = $wpdb->get_row($userLevel_query, ARRAY_A)) {
											$insert_query = $wpdb->prepare("INSERT INTO {$wpdb->prefix}wp_membership_authorizenet_subscriptions (Sub_ID, User_Level_ID, Created) VALUES (%s, %s, NOW())", $subID, $userLevel_row['User_Level_ID']);
											if((bool)$wpdb->query($insert_query) !== false) {
												$exp = strlen($delay) <= 0 ? "now" : $delay;
												$update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}wp_membership_user_levels SET Expiration=%s WHERE User_Level_ID=%s", @date("Y-m-d H:i:s", @strtotime($exp)), $userLevel_row['User_Level_ID']);
												if((bool)$wpdb->query($update_query)) {
													$retval = true;
												}
											}
										}
									}
								}
							}
						}
					}
					curl_close($ch);
				}
				
				return $retval;
			}

			// (bool) Does the gateway support cancelling a subscription-based service directly? Paypa 
			function has_Uninstall_Subscription() {
				return true;
			}

			// How is the subscription directly cancelled?
			function Uninstall_Subscription($subscription_id) {
				$retval = false;
				global $wpdb;
				
				$subscription_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_authorizenet_subscriptions AS t1 LEFT JOIN {$wpdb->prefix}wp_membership_user_levels AS t2 ON t1.User_Level_ID=t2.User_Level_ID WHERE t1.Sub_ID=%s", $subscription_id);
				if($subscription_row = $wpdb->get_row($subscription_query, ARRAY_A)) {
					$id = $subscription_row['User_ID'].'_'.$subscription_row['Level_Price_ID'];
					$ids = explode('_', $id);
					$xml = '<?xml version="1.0" encoding="utf-8" ?>'."\r\n";
					$xml .= '<ARBCancelSubscriptionRequest xmlns="AnetApi/xml/v1/schema/AnetApiSchema.xsd">';
					
					//Merchant Authentication
					$xml .= '<merchantAuthentication>';
					$xml .= '<name>'.htmlentities(get_option('wp-membership_plugins_AuthorizeNet_Login_ID')).'</name>';
					$xml .= '<transactionKey>'.htmlentities(get_option('wp-membership_plugins_AuthorizeNet_Transaction_Key')).'</transactionKey>';
					$xml .= '</merchantAuthentication>';
					
					$xml .= '<refId>'.htmlentities($id).'</refId>';
					
					$xml .= '<subscriptionId>'.htmlentities($subscription_id).'</subscriptionId>';
					
					$xml .= '</ARBCancelSubscriptionRequest>';
	
					$url = "https://api".(get_option("wp-membership_plugins_AuthorizeNet_testMode") ? "test" : "").".authorize.net/xml/v1/request.api";
					$ch = curl_init($url);
					if($ch) {
						$this->use_https_proxy($ch);
						curl_setopt($ch, CURLOPT_POST, true);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
						curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/xml'));
						curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
						curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_USERAGENT, "WP-Membership User Agent - http".(@$_SERVER['HTTPS'] == "on" ? "s" : "")."://{$_SERVER['HTTP_HOST']}{$_SERVER['PHP_SELF']} - {$_SERVER['HTTP_USER_AGENT']}");
						$output = curl_exec($ch);
						if($output !== false) {
							$XML = @simplexml_load_string($output);
							if($XML !== false) {
								if(isset($XML->messages) && isset($XML->messages->resultCode) && (string)$XML->messages->resultCode == "Ok") {
									if(isset($XML->messages->message) && isset($XML->messages->message->code) && in_array((string)$XML->messages->message->code, array("I00001", "I00002"))) {
										$userLevel_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_user_levels AS t1 WHERE t1.User_ID=%s AND t1.Level_Price_ID=%s", $ids[0], $ids[1]);
										if($userLevel_row = $wpdb->get_row($userLevel_query, ARRAY_A)) {
											$delete_query = $wpdb->prepare("DELETE FROM {$wpdb->prefix}wp_membership_authorizenet_subscriptions WHERE Sub_ID=%s", $subscription_id);
											if((bool)$wpdb->query($delete_query) !== false) {
												$retval = true;
											}
										}
									}
								}
							}
						}
						curl_close($ch);
					}
				}
				
				return $retval;
			}

			// (bool success/fail) Execute when this plugin is first installed. For creating database tables, etc. needed by this plugin
			function Install() {
				$retval = false;
				global $wpdb;
				
				$create_query = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}wp_membership_authorizenet_subscriptions (Subscription_ID BIGINT UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT, Sub_ID VARCHAR(255) NOT NULL UNIQUE, User_Level_ID BIGINT UNSIGNED, Created DATETIME NOT NULL, Last_Updated TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, CONSTRAINT FOREIGN KEY (User_Level_ID) REFERENCES {$wpdb->prefix}wp_membership_user_levels (User_Level_ID) ON UPDATE CASCADE) ENGINE=INNODB";
				$retval = $wpdb->query($create_query) === false ? false : true;
				
				return $retval;
			}

			// (bool success/fail) Execute when this plugin is UNinstalled. For removing (preferably non-financial) database tables, etc. needed by this plugin
			function Uninstall() {
				return true;
			}

			// Does this plugin store transational data, such as the price, the transaction key provided by the gateway, etc. As opposed to the gateway service handling all of this.
			function has_Transactions() {
				return false;
			}

			// Returns an array of all transaction IDs (primary key's). ALL of them.
			function get_Transactions() {
				return array();
			}

			// Get extended information about $transactionid (the entire row)
			function get_Transaction($transactionid) {
				return false;
			}

			// see has_Transactions, but for subscription data
			function has_Subscriptions() {
				return false;
			}
			
			// see get_Transactions, but for subscription data
			function get_Subscriptions() {
				return array();
			}

			// Get extended information about $subscriptionid (the entire row)
			function get_Subscription($subscriptionid) {
				return array();
			}

			// Get extended information about $subscriptionid (the entire row)
			function find_Subscription($userlevelid) {
				$retval = false;
				global $wpdb;
				
				$subscription_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_authorizenet_subscriptions AS t1 WHERE t1.User_Level_ID=%s", $userlevelid);
				if($subscription_row = $wpdb->get_row($subscription_query, ARRAY_A)) {
					$retval = $subscription_row['Sub_ID'];
				}
				
				return $retval;
			}
			
			function has_Subscription($userlevelid) {
				$retval = false;
				global $wpdb;
				
				$subscription_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_authorizenet_subscriptions AS t1 WHERE t1.User_Level_ID=%s", $userlevelid);
				if($subscription_row = $wpdb->get_row($subscription_query, ARRAY_A)) {
					$retval = true;
				}
				
				return $retval;
			}
			
			function delete_User($userid) {
				global $wpdb;
				$userlevel_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_user_levels AS t1 WHERE t1.User_ID=%s", $userid);
				if($userlevel_rows = $wpdb->get_results($userlevel_query, ARRAY_A)) {
					foreach($userlevel_rows as $userlevel_row) {
						$update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}wp_membership_authorizenet_subscriptions SET User_Level_ID=NULL WHERE User_Level_ID=%s", $userlevel_row['User_Level_ID']);
						$wpdb->query($update_query);
					}
				}
			}
			
			function callback_PostBack($callback) {
				global $wpdb;

				//mail(get_option('wp-membership_plugins_AuthorizeNet_NotifyEmail'), 'Authorize Respose', @file_get_contents("php://input"));
				//echo "Success!";
				if(@$_POST['x_response_code'] == "1") {
					$body = "";
					$body .= "<table>";
					$body .= "<tr>";
					$body .= "<td nowrap>Transaction ID</td>";
					$body .= "<td>".htmlentities(@$_POST['x_trans_id'])."</td>";
					$body .= "</tr>";
					$body .= "<tr>";
					$body .= "<td nowrap>Auth Code</td>";
					$body .= "<td>".htmlentities(@$_POST['x_auth_code'])."</td>";
					$body .= "</tr>";
					$body .= "<tr>";
					$body .= "<td nowrap>Buyer Status</td>";
					$body .= "<td>".htmlentities(@$_POST['x_response_reason_text'])."</td>";
					$body .= "</tr>";
					$body .= "<tr>";
					$body .= "<td nowrap>Amount Charged</td>";
					$body .= "<td>".htmlentities(@$_POST['x_amount'])."</td>";
					$body .= "</tr>";
					$body .= "<tr>";
					$body .= "<td nowrap>Product Purchased</td>";
					$body .= "<td>".htmlentities(@$_POST['x_description']);
					$item_id = explode("_", @$_POST['id']);
					$level_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_levels AS t1 LEFT JOIN {$wpdb->prefix}wp_membership_level_prices AS t2 ON t1.Level_ID=t2.Level_ID WHERE t2.Level_Price_ID=%s", @$item_id[1]);
					//echo $level_query."<br />";
					if($level_row = $wpdb->get_row($level_query, ARRAY_A)) {
						$body .= htmlentities(" (".$level_row['Name']);
						switch($level_row['Duration']) {
							case "+1 month":
								$body .= htmlentities(" 1 month");
								break;
							case "+1 year":
								$body .= htmlentities(" 1 year");
								break;
						}
						$body .= htmlentities(")");
					}
					else {
						$body .= htmlentities(" (Unknown Product)");
					}
					$body .= "</td>";
					$body .= "</tr>";
					$body .= "</table>";
					$link = get_permalink(get_option("wp-membership_login_page_id"));
					$link = eregi("^https?:", $link) ? $link : get_option('siteurl');
					echo '<html><head><title>Thank You</title><meta http-equiv="refresh" content="0;url='.$link.'" /></head><body>'.$body.'<br /><a href="'.$link.'">Click here to login</a></body></html>';
					@mail(get_option("wp-membership_plugins_AuthorizeNet_notifyEmail"), "[WP-Membership - PayPay] Payment Recieved", $body, "From: ".get_option('admin_email')."\r\nContent-type: text/html");
					$user_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_user_levels AS t1 LEFT JOIN {$wpdb->prefix}wp_membership_level_prices AS t2 ON t1.Level_Price_ID=t2.Level_Price_ID WHERE t1.User_ID=%s AND t1.Level_Price_ID=%s", @$item_id[0], @$item_id[1]);
					//echo $user_query."<br />";
					if($user_row = $wpdb->get_row($user_query, ARRAY_A)) {
						if(strlen(trim($user_row['Duration'])) > 0) {
							$expiration = @date("Y-m-d H:i:s", @strtotime($user_row['Duration']));
							if(!is_null($user_row['Expiration']) && @strtotime($user_row['Expiration']) > time()) {
								$expiration = @date("Y-m-d H:i:s", @strtotime($user_row['Duration']), @strtotime($user_row['Expiration']));
							}
							if(strlen($user_row['Delay']) > 0 && (is_null($user_row['Expiration']) || @strtotime($user_row['Expiration']) <= time())) {
								$expiration = @date("Y-m-d H:i:s", @strtotime(@$user_row['Delay'], @strtotime($expiration)));
							}
						}
						else $expiration = null;
						if(is_null($expiration)) {
							$update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}wp_membership_user_levels SET Expiration=NULL WHERE User_Level_ID=%s", $user_row['User_Level_ID']);
						}
						else {
							$update_query = $wpdb->prepare("UPDATE {$wpdb->prefix}wp_membership_user_levels SET Expiration=%s WHERE User_Level_ID=%s", $expiration, $user_row['User_Level_ID']);
						}
						//echo $update_query."<br />";
						$wpdb->query($update_query);
					}
				}
			}
			
			function get_Hidden_Pages() {
				$retval = array();
				
				return $retval;
			}
		
			function use_http_proxy($ch) {
				$address = get_option("wp-membership_httpproxy_address");
				if(strlen(trim($address)) > 0) {
					$port = get_option("wp-membership_httpproxy_port");
					if(strlen(trim($port)) > 0) {
						$address .= ":$port";
						curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
						curl_setopt($ch, CURLOPT_PROXY, $address);
					}
				}
			}
			
			function use_https_proxy($ch) {
				$address = get_option("wp-membership_httpsproxy_address");
				if(strlen(trim($address)) > 0) {
					$port = get_option("wp-membership_httpsproxy_port");
					if(strlen(trim($port)) > 0) {
						$address .= ":$port";
						curl_setopt($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
						curl_setopt($ch, CURLOPT_PROXY, $address);
					}
				}
			}
			
			function is_Currency_Supported($currency = null) {
				$retval = false;
				
				$cur = is_null($currency) ? get_option('wp-membership_currency') : $currency;
				if(in_array($cur, array('USD'))) $retval = true;
				
				return $retval;
			}
		}
	}
}
/*

	$amount = 255;
	$cc_num = "4289681145931342";
	$cc_exp = 1209; //MMYY
	$cc_cvvcode = 123;
	
	
	$foo = array(
				// Merchant account info
				 "x_login=6zz6m5N4Et",
				 "x_tran_key=9V9wUv6Yd92t27t5",
				 "x_version=3.1",
				 
				 // Transaction info
				 "x_type=AUTH_CAPTURE",
//				 "x_recurring_billing=FALSE",
				 "x_amount=$amount",
				 "x_card_num=$cc_num",
				 "x_exp_date=$cc_exp",
				 "x_card_code=$cc_cvvcode",
				 "x_test_request=TRUE", // Test transaction only?
				 "x_invoice_num=0", // Plugin-generated transaction #
				 "x_invoice_num=Test purchase", // Plugin-generated transaction description

				 // Customer information
				 "x_first_name=Bob",
				 "x_last_name=Bobson",
				 "x_company=", // BLANK
				 "x_address=123 4th st",
				 "x_city=Pocatello",
				 "x_state=ID",
				 "x_zip=12345",
				 "x_phone=2085551212"
				 // Et cetera...
				 );
	
	$submission = implode('&', $foo);	
	

	$ch = curl_init("https://test.authorize.net/gateway/transact.dll");
	if($ch) {
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $submission);
		//curl_setopt($ch, CURLOPT_SSLCERT, $Cert); // To send cert to server
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false); // If site may have invalid cert
		//curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		$response = curl_exec($ch);
		if($response) {
			$retval = $response;
		}
		curl_close($ch);
		
		echo "<html>$retval</html>";
	}	
*/
