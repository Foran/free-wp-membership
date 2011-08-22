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
    along with Foobar.  If not, see <http://www.gnu.org/licenses/>.
*/
if(interface_exists("wp_membership_payment_gateway")) {
	if(!class_exists("wp_membership_plugin_PayPal")) {
		class wp_membership_plugin_PayPal implements wp_membership_payment_gateway {
			private $m_Default_BuyNowButton = "https://www.paypal.com/en_US/i/btn/btn_buynow_LG.gif";
			private $m_Default_SubscribeButton = "https://www.paypal.com/en_US/i/btn/btn_subscribe_LG.gif";

			function __construct() {
				add_option("wp-membership_plugins_PayPal_Cert", null);
				add_option("wp-membership_plugins_PayPal_Our_Cert", null);
				add_option("wp-membership_plugins_PayPal_Our_Private", null);
				add_option("wp-membership_plugins_PayPal_Cert_ID", null);
				add_option("wp-membership_plugins_PayPal_BuyNowButton", null);
				add_option("wp-membership_plugins_PayPal_SubscribeButton", null);
				add_option("wp-membership_plugins_PayPal_email", "");
				add_option("wp-membership_plugins_PayPal_notifyEmail", get_option('admin_email'));
				add_option("wp-membership_plugins_PayPal_Currency", "USD");
				add_option("wp-membership_plugins_PayPal_TestMode", "true");
			}
			
			function get_Name() {
				return "PayPal Payment Gateway!";
			}
			
			function get_Capabilities() {
				return array();
			}

			function get_Description() {
				return "";
			}

			function do_SettingsEdit() {
				$retval = false;
				
				if(trim(@$_REQUEST['wp-membership_email']) == "" || is_email(@$_REQUEST['wp-membership_email'])) {
					if(strlen(trim(@$_REQUEST['wp-membership_paypal_cert'])) > 0) {
						update_option("wp-membership_plugins_PayPal_Cert", trim(@$_REQUEST['wp-membership_paypal_cert']));
						update_option("wp-membership_plugins_PayPal_Cert_ID", strlen(trim(@$_REQUEST['wp-membership_cert_id'])) > 0 ? trim(@$_REQUEST['wp-membership_cert_id']) : null);
					}
					else {
						update_option("wp-membership_plugins_PayPal_Cert", null);
						update_option("wp-membership_plugins_PayPal_Our_Cert", null);
						update_option("wp-membership_plugins_PayPal_Our_Private", null);
						update_option("wp-membership_plugins_PayPal_Cert_ID", null);
					}
					update_option("wp-membership_plugins_PayPal_email", @$_REQUEST['wp-membership_email']);
					update_option("wp-membership_plugins_PayPal_BuyNowButton", @$_REQUEST['wp-membership_BuyNowButton']);
					update_option("wp-membership_plugins_PayPal_SubscribeButton", @$_REQUEST['wp-membership_SubscribeButton']);
					update_option("wp-membership_plugins_PayPal_notifyEmail", @$_REQUEST['wp-membership_notifyEmail']);
					//update_option("wp-membership_plugins_PayPal_Currency", @$_REQUEST['wp-membership_currency']);
					update_option("wp-membership_plugins_PayPal_TestMode", @$_REQUEST['wp-membership_plugins_PayPal_TestMode'] == "1" ? true : false);
					$retval = true;
				}
				
				return $retval;
			}
			
			function show_SettingsEdit() {
				?>
				<tr valign="top">
				<th scope="row">PayPal ID</th>
				<td><input type="text" name="wp-membership_email" value="<?php echo htmlentities(get_option("wp-membership_plugins_PayPal_email")); ?>" /></td>
				</tr>
				<tr valign="top">
				<th scope="row">Notification Email</th>
				<td><input type="text" name="wp-membership_notifyEmail" value="<?php echo htmlentities(get_option("wp-membership_plugins_PayPal_notifyEmail")); ?>" /></td>
				</tr>
				<tr valign="top">
				<th scope="row">Custom Buy Now Button Image</th>
				<td><input type="text" name="wp-membership_BuyNowButton" value="<?php echo htmlentities(get_option("wp-membership_plugins_PayPal_BuyNowButton")); ?>" /></td>
				</tr>
				<tr valign="top">
				<th scope="row">Custom Subscribe Button Image</th>
				<td><input type="text" name="wp-membership_SubscribeButton" value="<?php echo htmlentities(get_option("wp-membership_plugins_PayPal_SubscribeButton")); ?>" /></td>
				</tr>
<?php
/*				<tr valign="top">
					<th scope="row">Currency</th>
					<td>
						<select name="wp-membership_currency">
<!--							<option value="">[Choose One]</option>
							<option value="AUD"<?php echo get_option("wp-membership_plugins_PayPal_Currency") == "AUD" ? " SELECTED" : ""; ?>>Australian Dollar</option>
							<option value="CAD"<?php echo get_option("wp-membership_plugins_PayPal_Currency") == "CAD" ? " SELECTED" : ""; ?>>Canadian Dollar</option>
							<option value="CHF"<?php echo get_option("wp-membership_plugins_PayPal_Currency") == "CHF" ? " SELECTED" : ""; ?>>Swiss Franc</option>
							<option value="CZK"<?php echo get_option("wp-membership_plugins_PayPal_Currency") == "CZK" ? " SELECTED" : ""; ?>>Czech Koruna</option>
							<option value="DKK"<?php echo get_option("wp-membership_plugins_PayPal_Currency") == "DKK" ? " SELECTED" : ""; ?>>Danish Krone</option>
							<option value="EUR"<?php echo get_option("wp-membership_plugins_PayPal_Currency") == "EUR" ? " SELECTED" : ""; ?>>Euro</option>
							<option value="GBP"<?php echo get_option("wp-membership_plugins_PayPal_Currency") == "GBP" ? " SELECTED" : ""; ?>>Pound Sterling</option>
							<option value="HKD"<?php echo get_option("wp-membership_plugins_PayPal_Currency") == "HKD" ? " SELECTED" : ""; ?>>Hong Kong Dollar</option>
							<option value="HUF"<?php echo get_option("wp-membership_plugins_PayPal_Currency") == "HUF" ? " SELECTED" : ""; ?>>Hungarian Forint</option>
							<option value="JPY"<?php echo get_option("wp-membership_plugins_PayPal_Currency") == "JPY" ? " SELECTED" : ""; ?>>Japanese Yen</option>
							<option value="NOK"<?php echo get_option("wp-membership_plugins_PayPal_Currency") == "NOK" ? " SELECTED" : ""; ?>>Norwegian Krone</option>
							<option value="NZD"<?php echo get_option("wp-membership_plugins_PayPal_Currency") == "NZD" ? " SELECTED" : ""; ?>>New Zealand Dollar</option>
							<option value="PLN"<?php echo get_option("wp-membership_plugins_PayPal_Currency") == "PLN" ? " SELECTED" : ""; ?>>Polish Zloty</option>
							<option value="SEK"<?php echo get_option("wp-membership_plugins_PayPal_Currency") == "SEK" ? " SELECTED" : ""; ?>>Swedish Krona</option>
							<option value="SGD"<?php echo get_option("wp-membership_plugins_PayPal_Currency") == "SGD" ? " SELECTED" : ""; ?>>Singapore Dollar</option> -->
							<option value="USD"<?php echo get_option("wp-membership_plugins_PayPal_Currency") == "USD" ? " SELECTED" : ""; ?>>U.S. Dollar</option>
						</select>
					</td>
				</tr>
						*/ ?>
				<tr valign="top">
					<th scope="row">Test Mode</th>
					<td><input type="checkbox" name="wp-membership_plugins_PayPal_TestMode" value="1"<?php echo get_option("wp-membership_plugins_PayPal_TestMode") ? " CHECKED" : ""; ?>></td>
				</tr>
				<?php
				if(function_exists("tempnam") && function_exists("sys_get_temp_dir") && function_exists("unlink") && function_exists("file_put_contents") && function_exists("file_get_contents") && function_exists("file_exists") && file_exists("/usr/bin/openssl")) {
					$test = @tempnam(@sys_get_temp_dir(), 'wp-membership');
					@file_put_contents($test, "This is a test");
					if(@file_get_contents($test) == "This is a test") {
						if(strlen(get_option("wp-membership_plugins_PayPal_Our_Cert")) <= 0 && strlen(get_option("wp-membership_plugins_PayPal_Cert")) > 0) {
							$temp = new wp_membership_plugin_PayPal_OpenSSL(get_option("wp-membership_plugins_PayPal_Cert"));
							update_option("wp-membership_plugins_PayPal_Our_Cert", $temp->get_Our_PrivateCert());
							update_option("wp-membership_plugins_PayPal_Our_Private", $temp->get_Our_PrivateKey());
						}
						?>
						</table>
						<h3>Encrypted Buttons</h3>
						<table class="form-table">
							<tr valign="top">
								<th scope="row">Status</th>
								<td><?php echo strlen(get_option("wp-membership_plugins_PayPal_Cert_ID")) <= 0 ? "Disabled" : "Enabled"; ?></td>
							</tr>
							<tr valign="top">
								<th scope="row">PayPal Public Certificate</th>
								<td><textarea rows="5" cols="80" name="wp-membership_paypal_cert"><?php $paypal_cert = get_option("wp-membership_plugins_PayPal_Cert"); echo strlen($paypal_cert) <= 0 ? "" : htmlentities($paypal_cert); ?></textarea></td>
							</tr>
							<?php
							$our_cert = get_option("wp-membership_plugins_PayPal_Our_Cert");
							if(strlen($our_cert) > 0) {
							?>
							<tr valign="top">
								<th scope="row">Your Public Certificate</th>
								<td><textarea rows="5" cols="80" readonly name="wp-membership_our_cert"><?php echo htmlentities($our_cert); ?></textarea></td>
							</tr>
							<tr valign="top">
								<th scope="row">Cert ID</th>
								<td><input type="text" name="wp-membership_cert_id" value="<?php echo strlen(get_option("wp-membership_plugins_PayPal_Cert_ID")) <= 0 ? "" : htmlentities(get_option("wp-membership_plugins_PayPal_Cert_ID")); ?>" /></td>
							</tr>
							<?php
							}
							?>
						<?php
					}
					@unlink($test);
				}
				else {
					?>
					<tr valign="top">
						<th scope="row">Encrypted Buttons</th>
						<td>Support for Encrypted Buttons is not yet available for this server type.</td>
					</tr>
					<?php
				}
			}
			
			function need_PaymentForm() {
				return false;
			}
			
			function has_BuyNow_Button() {
				return true;
			}
			
			function get_BuyNow_Button($id, $caption, $amount) {
				$retval = "";
				
				$basepath = pathinfo($_SERVER['SCRIPT_FILENAME']);
				$basepath = ereg_replace("/wp-admin\$", "", @$basepath['dirname']);
				$relPath = __FILE__;//$basepath."/wp-content/plugins/wp-membership/wp-membership.php";
				$filePathName = realpath($relPath);
			    $filePath = realpath(dirname($relPath));
			    $basePath = realpath($_SERVER['DOCUMENT_ROOT']);
				//$ipn = 'http://' . $_SERVER['HTTP_HOST'] . substr($filePathName, strlen($basePath))."?gateway_callback=PayPal";
				$ipn = get_option('siteurl')."/wp-content/plugins/".basename(dirname(__FILE__))."/".basename(__FILE__)."?gateway_callback=PayPal";
				$ipn = get_option('siteurl')."/wp-content/plugins/".basename(dirname(__FILE__))."/wp-membership.php?gateway_callback=PayPal";

				if(strlen(get_option("wp-membership_plugins_PayPal_Cert_ID")) > 0) {
					$ssl = new wp_membership_plugin_PayPal_OpenSSL(get_option("wp-membership_plugins_PayPal_Cert"), get_option("wp-membership_plugins_PayPal_Our_Private"));
					$form = array();
					$form['cert_id'] = get_option('wp-membership_plugins_PayPal_Cert_ID');
					$form['cmd'] = "_xclick";
					$form['business'] = get_option('wp-membership_plugins_PayPal_email');
					$form['notify_url'] = $ipn;
					$form['item_name'] = $caption;
					$form['item_number'] = $id;
					$form['amount'] = $amount;
					$form['currency_code'] = get_option('wp-membership_currency');//get_option('wp-membership_plugins_PayPal_Currency');
					$encrypted = $ssl->paypal_shell_encrypt($form);
					if(strlen($encrypted) > 0) {
						$retval .= '<form target="paypal" action="https://www.'.(get_option("wp-membership_plugins_PayPal_TestMode") ? "sandbox." : "").'paypal.com/cgi-bin/webscr" method="post"> 
		 
		    <!-- Identify your business so that you can collect the payments. --> 
		    <input type="hidden" name="business" value="'.htmlentities(get_option('wp-membership_plugins_PayPal_email')).'"> 
		 
		    <!-- Specify a Buy Now button. --> 
		    <input type="hidden" name="cmd" value="_s-xclick"> 
			<input type="hidden" name="encrypted" value="'.htmlentities($encrypted).'"> 
		 
		    <!-- Display the payment button. --> 
		    <input type="image" name="submit" border="0" 
		        src="'.(strlen(get_option("wp-membership_plugins_PayPal_BuyNowButton")) > 0 ? get_option("wp-membership_plugins_PayPal_BuyNowButton") : $this->m_Default_BuyNowButton).'" 
		        alt="PayPal - The safer, easier way to pay online"> 
		    <img alt="" border="0" width="1" height="1" 
		        src="https://www.paypal.com/en_US/i/scr/pixel.gif" > 
		</form>';
					}
					else $retval .= '<span class="error">There was an error generating the PayPal Button, please contact the administrator.</span>';
				}
				else {
					$retval .= '<form target="paypal" action="https://www.'.(get_option("wp-membership_plugins_PayPal_TestMode") ? "sandbox." : "").'paypal.com/cgi-bin/webscr" method="post"> 
	 
	    <!-- Identify your business so that you can collect the payments. --> 
	    <input type="hidden" name="business" value="'.htmlentities(get_option('wp-membership_plugins_PayPal_email')).'"> 
	 
	    <!-- Specify a Buy Now button. --> 
	    <input type="hidden" name="cmd" value="_xclick"> 
		<input type="hidden" name="notify_url" value="'.htmlentities($ipn).'"> 
	 
	    <!-- Specify details about the item that buyers will purchase. --> 
	    <input type="hidden" name="item_name" value="'.htmlentities($caption).'"> 
	    <input type="hidden" name="item_number" value="'.htmlentities($id).'"> 
	    <input type="hidden" name="amount" value="'.htmlentities($amount).'"> 
	    <input type="hidden" name="currency_code" value="'.htmlentities(get_option('wp-membership_currency')).'"> 
	 
	    <!-- Display the payment button. --> 
	    <input type="image" name="submit" border="0" 
	        src="'.(strlen(get_option("wp-membership_plugins_PayPal_BuyNowButton")) > 0 ? get_option("wp-membership_plugins_PayPal_BuyNowButton") : $this->m_Default_BuyNowButton).'" 
	        alt="PayPal - The safer, easier way to pay online"> 
	    <img alt="" border="0" width="1" height="1" 
	        src="https://www.paypal.com/en_US/i/scr/pixel.gif" > 
	</form>';
				}
				return $retval;
			}
			
			function has_Subscription_Button() {
				return true;
			}
			
			function get_Subscription_Button($id, $caption, $amount, $duration, $delay = null) {
				$retval = "";
				
				$basepath = pathinfo($_SERVER['SCRIPT_FILENAME']);
				$basepath = ereg_replace("/wp-admin\$", "", @$basepath['dirname']);
				$relPath = __FILE__;//$basepath."/wp-content/plugins/wp-membership/wp-membership.php";
				$filePathName = realpath($relPath);
			    $filePath = realpath(dirname($relPath));
			    $basePath = realpath($_SERVER['DOCUMENT_ROOT']);
				//$ipn = 'http://' . $_SERVER['HTTP_HOST'] . substr($filePathName, strlen($basePath))."?gateway_callback=PayPal";
				$ipn = get_option('siteurl')."/wp-content/plugins/".basename(dirname(__FILE__))."/".basename(__FILE__)."?gateway_callback=PayPal";
				$ipn = get_option('siteurl')."/wp-content/plugins/".basename(dirname(__FILE__))."/wp-membership.php?gateway_callback=PayPal";

				if(is_null($delay)) {
					if(strlen(get_option("wp-membership_plugins_PayPal_Cert_ID")) > 0) {
						$ssl = new wp_membership_plugin_PayPal_OpenSSL(get_option("wp-membership_plugins_PayPal_Cert"), get_option("wp-membership_plugins_PayPal_Our_Private"));
						$form = array();
						$form['cert_id'] = get_option('wp-membership_plugins_PayPal_Cert_ID');
						$form['cmd'] = "_xclick-subscriptions";
						$form['business'] = get_option('wp-membership_plugins_PayPal_email');
						$form['notify_url'] = $ipn;
						$form['item_name'] = $caption;
						$form['item_number'] = $id;
						$form['a3'] = $amount;
						$form['p3'] = (int)$duration;
						$form['t3'] = strstr($duration, "year") ? "Y" : (strstr($duration, "month") ? "M" : (strstr($duration, "week") ? "W" : (strstr($duration, "day") ? "D" : "")));
						$form['currency_code'] = get_option('wp-membership_currency');//get_option('wp-membership_plugins_PayPal_Currency');
						$encrypted = $ssl->paypal_shell_encrypt($form);
						if(strlen($encrypted) > 0) {
							$retval .= '<form target="paypal" action="https://www.'.(get_option("wp-membership_plugins_PayPal_TestMode") ? "sandbox." : "").'paypal.com/cgi-bin/webscr" 
		        method="post"> 
		 <!-- Identify your business so that you can collect the payments. --> 
		<!-- Specify a Subscribe button. --> 
		<input type="hidden" name="cmd" value="_s-xclick"> 
		<input type="hidden" name="encrypted" value="'.htmlentities($encrypted).'"> 
		<!-- Display the payment button. --> 
		<input type="image" name="submit" border="0" 
		src="'.(strlen(get_option("wp-membership_plugins_PayPal_SubscribeButton")) > 0 ? get_option("wp-membership_plugins_PayPal_SubscribeButton") : $this->m_Default_SubscribeButton).'" 
		alt="PayPal - The safer, easier way to pay online"> 
		<img alt="" border="0" width="1" height="1" 
		src="https://www.paypal.com/en_US/i/scr/pixel.gif" > 
		</form>
		';
						}
						else $retval .= '<span class="error">There was an error generating the PayPal Button, please contact the administrator.</span>';
					}
					else {
						$retval .= '<form target="paypal" action="https://www.'.(get_option("wp-membership_plugins_PayPal_TestMode") ? "sandbox." : "").'paypal.com/cgi-bin/webscr" 
	        method="post"> 
	 <!-- Identify your business so that you can collect the payments. --> 
	<input type="hidden" name="business" value="'.htmlentities(get_option('wp-membership_plugins_PayPal_email')).'"> 
	<!-- Specify a Subscribe button. --> 
	<input type="hidden" name="cmd" value="_xclick-subscriptions"> 
	<input type="hidden" name="notify_url" value="'.htmlentities($ipn).'"> 
	<! -- Identify the subscription. --> 
	<input type="hidden" name="item_name" value="'.htmlentities($caption).'"> 
	<input type="hidden" name="item_number" value="'.htmlentities($id).'"> 
	<! -- Set the terms of the 1st trial period. --> 
	<input type="hidden" name="a3" value="'.htmlentities($amount).'"> 
	<input type="hidden" name="p3" value="'.htmlentities((int)$duration).'"> 
	<input type="hidden" name="t3" value="'.htmlentities(strstr($duration, "year") ? "Y" : (strstr($duration, "month") ? "M" : (strstr($duration, "week") ? "W" : (strstr($duration, "day") ? "D" : "")))).'"> 
	<!-- Display the payment button. --> 
	<input type="image" name="submit" border="0" 
	src="'.(strlen(get_option("wp-membership_plugins_PayPal_SubscribeButton")) > 0 ? get_option("wp-membership_plugins_PayPal_SubscribeButton") : $this->m_Default_SubscribeButton).'" 
	alt="PayPal - The safer, easier way to pay online"> 
	<img alt="" border="0" width="1" height="1" 
	src="https://www.paypal.com/en_US/i/scr/pixel.gif" > 
	</form>
	';
					}
				}
				else {
					if(strlen(get_option("wp-membership_plugins_PayPal_Cert_ID")) > 0) {
						$ssl = new wp_membership_plugin_PayPal_OpenSSL(get_option("wp-membership_plugins_PayPal_Cert"), get_option("wp-membership_plugins_PayPal_Our_Private"));
						$form = array();
						$form['cert_id'] = get_option('wp-membership_plugins_PayPal_Cert_ID');
						$form['cmd'] = "_xclick-subscriptions";
						$form['business'] = get_option('wp-membership_plugins_PayPal_email');
						$form['notify_url'] = $ipn;
						$form['item_name'] = $caption;
						$form['item_number'] = $id;
						$form['a1'] = 0;
						$form['p1'] = (int)$delay;
						$form['t1'] = strstr($delay, "year") ? "Y" : (strstr($delay, "month") ? "M" : (strstr($delay, "week") ? "W" : (strstr($delay, "day") ? "D" : "")));
						$form['a3'] = $amount;
						$form['p3'] = (int)$duration;
						$form['t3'] = strstr($duration, "year") ? "Y" : (strstr($duration, "month") ? "M" : (strstr($duration, "week") ? "W" : (strstr($duration, "day") ? "D" : "")));
						$form['currency_code'] = get_option('wp-membership_currency');//get_option('wp-membership_plugins_PayPal_Currency');
						$encrypted = $ssl->paypal_shell_encrypt($form);
						if(strlen($encrypted) > 0) {
							$retval .= '<form target="paypal" action="https://www.'.(get_option("wp-membership_plugins_PayPal_TestMode") ? "sandbox." : "").'paypal.com/cgi-bin/webscr" 
		        method="post"> 
		 <!-- Identify your business so that you can collect the payments. --> 
		<!-- Specify a Subscribe button. --> 
		<input type="hidden" name="cmd" value="_s-xclick"> 
		<input type="hidden" name="encrypted" value="'.htmlentities($encrypted).'"> 
		<!-- Display the payment button. --> 
		<input type="image" name="submit" border="0" 
		src="'.(strlen(get_option("wp-membership_plugins_PayPal_SubscribeButton")) > 0 ? get_option("wp-membership_plugins_PayPal_SubscribeButton") : $this->m_Default_SubscribeButton).'" 
		alt="PayPal - The safer, easier way to pay online"> 
		<img alt="" border="0" width="1" height="1" 
		src="https://www.paypal.com/en_US/i/scr/pixel.gif" > 
		</form>
		';
						}
						else $retval .= '<span class="error">There was an error generating the PayPal Button, please contact the administrator.</span>';
					}
					else {
						$retval .= '<form target="paypal" action="https://www.'.(get_option("wp-membership_plugins_PayPal_TestMode") ? "sandbox." : "").'paypal.com/cgi-bin/webscr" 
		        method="post"> 
		 <!-- Identify your business so that you can collect the payments. --> 
		<input type="hidden" name="business" value="'.htmlentities(get_option('wp-membership_plugins_PayPal_email')).'"> 
		<!-- Specify a Subscribe button. --> 
		<input type="hidden" name="cmd" value="_xclick-subscriptions"> 
		<input type="hidden" name="notify_url" value="'.htmlentities($ipn).'"> 
		<! -- Identify the subscription. --> 
		<input type="hidden" name="item_name" value="'.htmlentities($caption).'"> 
		<input type="hidden" name="item_number" value="'.htmlentities($id).'"> 
		<! -- Set the terms of the 1st trial period. --> 
		<input type="hidden" name="a1" value="0"> 
		<input type="hidden" name="p1" value="'.htmlentities((int)$delay).'"> 
		<input type="hidden" name="t1" value="'.htmlentities(strstr($delay, "year") ? "Y" : (strstr($delay, "month") ? "M" : (strstr($delay, "week") ? "W" : (strstr($delay, "day") ? "D" : "")))).'"> 
		<! -- Set the terms of the 2nd trial period. --> 
		<input type="hidden" name="a3" value="'.htmlentities($amount).'"> 
		<input type="hidden" name="p3" value="'.htmlentities((int)$duration).'"> 
		<input type="hidden" name="t3" value="'.htmlentities(strstr($duration, "year") ? "Y" : (strstr($duration, "month") ? "M" : (strstr($duration, "week") ? "W" : (strstr($duration, "day") ? "D" : "")))).'"> 
		<!-- Display the payment button. --> 
		<input type="image" name="submit" border="0" 
		src="'.(strlen(get_option("wp-membership_plugins_PayPal_SubscribeButton")) > 0 ? get_option("wp-membership_plugins_PayPal_SubscribeButton") : $this->m_Default_SubscribeButton).'" 
		alt="PayPal - The safer, easier way to pay online"> 
		<img alt="" border="0" width="1" height="1" 
		src="https://www.paypal.com/en_US/i/scr/pixel.gif" > 
		</form>
		';
					}
				}
								
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
				global $wpdb;
				
				$url = 'https://www.'.(get_option("wp-membership_plugins_PayPal_TestMode") ? "sandbox." : "").'paypal.com/cgi-bin/webscr';
				$ch = @curl_init($url);
				if($ch) {
					curl_setopt($ch, CURLOPT_POST, true);
					curl_setopt($ch, CURLOPT_POSTFIELDS, $_SERVER['QUERY_STRING']."&cmd=_notify-validate");
					curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
					$callback->use_https_proxy($ch);
					$result = @curl_exec($ch);
					if($result !== false) {
						if(@$_POST['payment_status'] == "Completed") {
							$body = "";
							$body .= "<table>";
							$body .= "<tr>";
							$body .= "<td nowrap>Buyer ID</td>";
							$body .= "<td>".htmlentities(@$_POST['payer_id'])."</td>";
							$body .= "</tr>";
							$body .= "<tr>";
							$body .= "<td nowrap>Email</td>";
							$body .= "<td>".htmlentities("\"".trim(@$_POST['first_name']." ".@$_POST['last_name'])."\" <".@$_POST['payer_email'].">")."</td>";
							$body .= "</tr>";
							$body .= "<tr>";
							$body .= "<td nowrap>Buyer Status</td>";
							$body .= "<td>".htmlentities(@$_POST['payer_status'])."</td>";
							$body .= "</tr>";
							$body .= "<tr>";
							$body .= "<td nowrap>Amount Charged</td>";
							$body .= "<td>".htmlentities(@$_POST['mc_gross'])."</td>";
							$body .= "</tr>";
							$body .= "<tr>";
							$body .= "<td nowrap>Amount Received</td>";
							$body .= "<td>".htmlentities(@$_POST['mc_gross1'])."</td>";
							$body .= "</tr>";
							$body .= "<tr>";
							$body .= "<td nowrap>Product Purchased</td>";
							$body .= "<td>".htmlentities(@$_POST['item_name']);
							$item_id = explode("_", @$_POST['item_number']);
							$level_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_levels AS t1 LEFT JOIN {$wpdb->prefix}wp_membership_level_prices AS t2 ON t1.Level_ID=t2.Level_ID WHERE t2.Level_Price_ID=%s", @$item_id[1]);
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
							@mail(get_option("wp-membership_plugins_PayPal_notifyEmail"), "[WP-Membership - PayPay] Payment Recieved", $body, "From: ".get_option('admin_email')."\r\nContent-type: text/html");
							$user_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_user_levels AS t1 LEFT JOIN {$wpdb->prefix}wp_membership_level_prices AS t2 ON t1.Level_Price_ID=t2.Level_Price_ID WHERE t1.User_ID=%s AND t1.Level_Price_ID=%s", @$item_id[0], @$item_id[1]);
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
								$wpdb->query($update_query);
							}
						}
					}
					curl_close($ch);
				}
			}
			
			function has_Subscription($userlevelid) {
				$retval = false;
				global $wpdb;
				
				return $retval;
			}
			
			function delete_User($userid) {
				
			}
			
			function get_Hidden_Pages() {
				$retval = array();
				
				return $retval;
			}
			
			function is_Currency_Supported($currency = null) {
				$retval = false;
				$currencies = array(	'AUD', 'CAD', 'CHF', 'CZK', 'DKK', 'EUR',
										'GBP', 'HKD', 'HUF', 'JPY', 'NOK', 'NZD',
										'PLN', 'SEK', 'SGD', 'USD');
/*					<option value="AUD"<?php echo get_option("wp-membership_currency") == "AUD" ? " SELECTED" : ""; ?>>Australian Dollar</option>
					<option value="CAD"<?php echo get_option("wp-membership_currency") == "CAD" ? " SELECTED" : ""; ?>>Canadian Dollar</option>
					<option value="CHF"<?php echo get_option("wp-membership_currency") == "CHF" ? " SELECTED" : ""; ?>>Swiss Franc</option>
					<option value="CZK"<?php echo get_option("wp-membership_currency") == "CZK" ? " SELECTED" : ""; ?>>Czech Koruna</option>
					<option value="DKK"<?php echo get_option("wp-membership_currency") == "DKK" ? " SELECTED" : ""; ?>>Danish Krone</option>
					<option value="EUR"<?php echo get_option("wp-membership_currency") == "EUR" ? " SELECTED" : ""; ?>>Euro</option>
					<option value="GBP"<?php echo get_option("wp-membership_currency") == "GBP" ? " SELECTED" : ""; ?>>Pound Sterling</option>
					<option value="HKD"<?php echo get_option("wp-membership_currency") == "HKD" ? " SELECTED" : ""; ?>>Hong Kong Dollar</option>
					<option value="HUF"<?php echo get_option("wp-membership_currency") == "HUF" ? " SELECTED" : ""; ?>>Hungarian Forint</option>
					<option value="JPY"<?php echo get_option("wp-membership_currency") == "JPY" ? " SELECTED" : ""; ?>>Japanese Yen</option>
					<option value="NOK"<?php echo get_option("wp-membership_currency") == "NOK" ? " SELECTED" : ""; ?>>Norwegian Krone</option>
					<option value="NZD"<?php echo get_option("wp-membership_currency") == "NZD" ? " SELECTED" : ""; ?>>New Zealand Dollar</option>
					<option value="PLN"<?php echo get_option("wp-membership_currency") == "PLN" ? " SELECTED" : ""; ?>>Polish Zloty</option>
					<option value="SEK"<?php echo get_option("wp-membership_currency") == "SEK" ? " SELECTED" : ""; ?>>Swedish Krona</option>
					<option value="SGD"<?php echo get_option("wp-membership_currency") == "SGD" ? " SELECTED" : ""; ?>>Singapore Dollar</option>
					<option value="USD"<?php echo get_option("wp-membership_currency") == "USD" ? " SELECTED" : ""; ?>>U.S. Dollar</option> */
				
				$cur = is_null($currency) ? get_option('wp-membership_currency') : $currency;
				if(in_array($cur, $currencies)) $retval = true;
				
				return $retval;
			}
		}
	}
	if(!class_exists("wp_membership_plugin_PayPal_OpenSSL")) {
		class wp_membership_plugin_PayPal_OpenSSL {
			private $m_PrivateKey = null;
			private $m_PrivateKey_Raw = null;
			private $m_PayPal_Cert = null;
			private $m_PayPal_Cert_Raw = null;
			
			function __construct($PayPal_Cert, $Private_Cert = null) {
				if(is_null($Private_Cert)) {
					$this->m_PrivateKey = openssl_pkey_new();
					$this->m_PrivateKey_Raw = $this->get_Our_PrivateCert();
				}
				else {
					$this->m_PrivateKey_Raw = $Private_Cert;
					$this->m_PrivateKey = openssl_pkey_get_private($this->m_PrivateKey_Raw);
				}
				$this->m_PayPal_Cert_Raw = $PayPal_Cert;
				$this->m_PayPal_Cert = openssl_pkey_get_public($this->m_PayPal_Cert_Raw);
			}
			
			function get_Our_PrivateKey() {
				openssl_pkey_export($this->m_PrivateKey, $retval);
		
				return $retval;
			}
			
			function get_Our_PublicKey() {
				$retval = openssl_pkey_get_details($this->m_PrivateKey);
				$retval = $retval['key'];
				
				return $retval;
			}
			
			function get_Our_PrivateCert() {
				$retval = openssl_csr_new(array(), $this->m_PrivateKey);
				$retval = openssl_csr_sign($retval, null, $this->m_PrivateKey, 365);
				openssl_x509_export($retval, $retval);
				return $retval;
			}
			
			function get_Our_PublicCert() {
				$retval = openssl_csr_new(array(), $this->get_Our_PublicKey());
				$retval = openssl_csr_sign($retval, null, $this->get_Our_PublicKey(), 365);
				openssl_x509_export($retval, $retval);
				return $retval;
			}
			
			function get_PayPal_PublicCert() {
				openssl_x509_export($this->m_PayPal_Cert, $retval);
				return $retval;
			}
			
			function get_PayPal_PublicKey() {
				$retval = openssl_pkey_get_details($this->m_PayPal_Cert);
				$retval = $retval['key'];
		
				return $retval;
			}
			
			function get_PayPal_PublicKey_Size() {
				$retval = openssl_pkey_get_details($this->m_PayPal_Cert);
				$retval = $retval['bits'] / 8;
		
				return $retval;
			}
			
			private function encrypt($source,$pubkey) {
				$retval = "";
				$len = strlen($source);
				$size = 117;//$this->get_PayPal_PublicKey_Size($pubkey);
				for($i=0;$i<$len;$i+=$size) {
					if(!openssl_public_encrypt(substr($source,$i,$size),$new_out,$pubkey)) {
						echo openssl_error_string()."<br />";
						$retval = false;
						break;
					}
					$retval .= $new_out;
				}
				return $retval;
			}
			
			function PayPal_Encrypt($data) {
				$infile = tempnam(sys_get_temp_dir(), 'wp-membership');
				$outfile = tempnam(sys_get_temp_dir(), 'wp-membership');
				file_put_contents($infile, $data);
				openssl_pkcs7_encrypt($infile, $outfile, $this->get_Our_PublicCert(), array(), PKCS7_BINARY);
		//echo $data;
				//var_dump(openssl_public_encrypt($data, $retval, $this->get_PayPal_PublicKey()));
				//echo "<br />".openssl_error_string()."<br />";
				//$retval = $this->encrypt($data, $this->get_PayPal_PublicKey());
				$retval = file_get_contents($outfile);
				unlink($infile);
				unlink($outfile);
		
				$output = explode("\n\n", $retval);
				$retval = $output[1];//"-----BEGIN PKCS7-----\n".$output[1]."\n-----END PKCS7-----\n";
				
				return $retval;
			}
			
			function Decode($data) {
				$retval = explode("\n", $data);
				$retval = trim($retval[1]);
				return $data;//base64_decode($retval);
			}
			
			function Our_Sign($data) {
				$infile = tempnam(sys_get_temp_dir(), 'wp-membership');
				$outfile = tempnam(sys_get_temp_dir(), 'wp-membership');
				file_put_contents($infile, $data);
				openssl_pkcs7_sign($infile, $outfile, $this->get_Our_PublicCert(), $this->m_PrivateKey, array(), PKCS7_BINARY);
				$retval = file_get_contents($outfile);
				unlink($infile);
				unlink($outfile);
				
				$output = explode("\n\n", $retval);
				$retval = $output[1];//"-----BEGIN PKCS7-----\n".$output[1]."\n-----END PKCS7-----\n";
				
				//openssl_sign($data, $retval, $this->m_PrivateKey, OPENSSL_ALGO_SHA1);
				return $retval;
			}
		
			function paypal_shell_encrypt($hash)
			{
				$retval = "ERROR";
				
				$MY_KEY_FILE = tempnam(sys_get_temp_dir(), 'wp-membership');
				$MY_CERT_FILE = tempnam(sys_get_temp_dir(), 'wp-membership');
				$PAYPAL_CERT_FILE = tempnam(sys_get_temp_dir(), 'wp-membership');
				$OPENSSL = "/usr/bin/openssl";
		
				file_put_contents($MY_KEY_FILE, $this->get_Our_PrivateKey());
				file_put_contents($MY_CERT_FILE, $this->get_Our_PrivateCert());
				file_put_contents($PAYPAL_CERT_FILE, $this->m_PayPal_Cert_Raw);
				//echo $this->get_Our_PrivateKey()."<br />";
				//echo $this->get_Our_PrivateCert()."<br />";
				//echo $this->m_PayPal_Cert_Raw."<br />";
				
				if (!file_exists($MY_KEY_FILE)) {
					$retval = "ERROR: MY_KEY_FILE $MY_KEY_FILE not found\n";
				}
				if (!file_exists($MY_CERT_FILE)) {
					$retval = "ERROR: MY_CERT_FILE $MY_CERT_FILE not found\n";
				}
				if (!file_exists($PAYPAL_CERT_FILE)) {
					$retval = "ERROR: PAYPAL_CERT_FILE $PAYPAL_CERT_FILE not found\n";
				}
				if (!file_exists($OPENSSL)) {
					$retval = "ERROR: OPENSSL $OPENSSL not found\n";
				}
			
				//Assign Build Notation for PayPal Support
				$hash['bn']= 'WP-Membership';
				
				$openssl_cmd = "$OPENSSL smime -sign -signer $MY_CERT_FILE -inkey $MY_KEY_FILE " .
			                "-outform der -nodetach -binary | $OPENSSL smime -encrypt " .
			                "-des3 -binary -outform pem $PAYPAL_CERT_FILE";

			    $descriptors = array(
			        	0 => array("pipe", "r"),
					1 => array("pipe", "w"),
				);
			
				$process = proc_open($openssl_cmd, $descriptors, $pipes);
			    
				if(is_resource($process)) {
					foreach($hash as $key => $value) {
						if ($value != "") {
							//echo "Adding to blob: $key=$value\n";
							fwrite($pipes[0], "$key=$value\n");
						}
					}
					fflush($pipes[0]);
			        	fclose($pipes[0]);
			
					$output = "";
					while(!feof($pipes[1])) {
						$output .= fgets($pipes[1]);
					}
					//echo $output;
					fclose($pipes[1]); 
					$return_value = proc_close($process);
					$retval = $output;
				}
				
				unlink($MY_KEY_FILE);
				unlink($MY_CERT_FILE);
				unlink($PAYPAL_CERT_FILE);
				
				return $retval;
			}
		
		}
		
	}
}
