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
$wp_membership_shortcodes_registerform_path = pathinfo(__FILE__);
require_once(ereg_replace("/Shortcodes\$", "", $wp_membership_shortcodes_registerform_path['dirname']).'/interfaces/IWPMembershipShortcode.php');

if(!class_exists('wp_membership_Shortcode_RegisterForm')) {
	class wp_membership_Shortcode_RegisterForm implements IWPMembershipShortcode {
		private $m_Register_Row = array();
		function __construct(array $register_row = array()) {
			$this->m_Register_Row = $register_row;
		}
		
		/**
		 * @return Returns the tag name to hook
		 */
		function get_Shortcode() {
			return @$this->m_Register_Row['Macro'];
		}
		
		/**
		 * Shortcode handler for the Login Form
		 *
		 * @param array $atts array of attributes
		 * @param string $content text within enclosing form of shortcode element
		 * @param string $code the shortcode found, when == callback name
		 * @return string content to substitute
		 */
		function Handler($atts, $content=null, $code="") {
			$retval = "";
			
			global $wpdb, $wp_query, $wp_membership_plugin;
		    load_plugin_textdomain('wp-membership', false, $wp_membership_plugin->language_path);

			$page_id = isset($wp_query->queried_object->ID) ? $wp_query->queried_object->ID : @$_REQUEST['page_id'];
			if(is_array($this->m_Register_Row)) {
				switch(@$_REQUEST['do_register']) {
					case -1:
						break;
					case 0:
						break;
					case 1:
						if(is_email(@$_REQUEST['email']) && strlen(trim(@$_REQUEST['password'])) > 0 && @$_REQUEST['password'] == @$_REQUEST['password2']) {
							
						}
						else {
							$_REQUEST['do_register'] = 0;
							if(@$_REQUEST['password'] != @$_REQUEST['password2']) $retval = "Passwords do not match<br />".$retval;
							if(trim(@$_REQUEST['password']) == "") $retval = "Password cannot be blank<br />".$retval;
							if(!is_email(@$_REQUEST['email'])) $retval = "A valid email address is required<br />".$retval;
						}
						break;
					case 2:
						if(is_email(@$_REQUEST['email']) && strlen(trim(@$_REQUEST['password'])) > 0 && @$_REQUEST['password'] == @$_REQUEST['password2']) {
							$levels = explode("_", @$_REQUEST['wp-membership_level_id']);
							if(count($levels) > 1) {
							}
							else {
								$_REQUEST['do_register'] = "-1";
							}
						}
						else {
							$_REQUEST['do_register'] = 0;
							if(@$_REQUEST['password'] != @$_REQUEST['password2']) $retval = "Passwords do not match<br />".$retval;
							if(trim(@$_REQUEST['password']) == "") $retval = "Password cannot be blank<br />".$retval;
							if(!is_email(@$_REQUEST['email'])) $retval = "A valid email address is required<br />".$retval;
						}
						break;
					case 3:
						$reqs = array(	'billing_name' => "Billing Name",
										'billing_address' => "Billing Address",
										'billing_city' => "Billing City",
										'billing_address' => "Billing Address",
										'billing_state' => "Billing State",
										'billing_zip' => "Billing Zip/Postal Code",
										'billing_country' => "Billing Country",
										'payment_name' => "Payment Name",
										'payment_ccnum' => "Payment Card Number",
										'payment_ccexp_month' => "Payment Card Expiration Month",
										'payment_ccexp_year' => "Payment Card Expiration Year",
										'payment_cvv2' => "Payment Security Code");
						$errors = '';
						foreach($reqs as $name => $caption) {
							if(strlen(trim(@$_REQUEST[$name])) <= 0) {
								if(strlen($errors) > 0) $errors .= '<br />';
								$errors .= $caption.' can not be empty.';
								
							}
						}
						if(strlen($errors) > 0) {
							$_REQUEST['do_register'] = 2;
							$retval = '<p class="errors">'.$errors.'</p>'.$retval;
						}
						else {
							$level_id = @$_REQUEST['level_id'];
							$price_id = @$_REQUEST['price_id'];
							$level = null;
							$price = null;
							$duration = null;
							$delay = null;
							$level_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_levels AS t1 LEFT JOIN {$wpdb->prefix}wp_membership_level_prices AS t2 ON t1.Level_ID=t2.Level_ID WHERE t1.Level_ID=%s AND t2.Level_Price_ID=%s", $level_id, $price_id);
							if($level_row = $wpdb->get_row($level_query, ARRAY_A)) {
								$level = $level_row['Name'];
								$price = $level_row['Price'];
								$duration = $level_row['Duration'];
								$delay = $level_row['Delay'];
							}
							$user_id = @$_REQUEST['user_id'];
							$user_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_users AS t1 WHERE t1.User_ID=%s", @$_REQUEST['user_id']);
							if($user_row = $wpdb->get_row($user_query, ARRAY_A)) {
								$user_id = $user_row['User_ID'];
							}
							$subscription = false;
							$name = "";
							switch($duration) {
								case "+1 month":
									$subscription = true;
									break;
								case "+1 year":
									$subscription = true;
									break;
							}
							$charged = false;
							$date = @strtotime(@$_REQUEST['payment_ccexp_year'].'-'.@$_REQUEST['payment_ccexp_month'].'-01');
							$gateway_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 LEFT JOIN ".$wpdb->prefix."wp_membership_register_page_gateways AS t2 ON t1.Register_Page_ID=t2.Register_Page_ID WHERE t1.Register_Page_ID=%s AND t2.Payment_Gateway=%s ORDER BY t2.Payment_Gateway", $this->m_Register_Row['Register_Page_ID'], @$_REQUEST['processor']);
							if($gateway_row = $wpdb->get_row($gateway_query, ARRAY_A)) {
								if(isset($this->plugins[$gateway_row['Payment_Gateway']])) {
									if($subscription) {
										if($this->plugins[$gateway_row['Payment_Gateway']]->has_Install_Subscription()) {
											$charged = $this->plugins[$gateway_row['Payment_Gateway']]->Install_Subscription($user_id."_".$price_id, $level." Membership at {$_SERVER['HTTP_HOST']}", $price, @$_REQUEST['payment_ccnum'], $date, @$_REQUEST['payment_cvv2'], @$_REQUEST['payment_name'], @$_REQUEST['billing_name'], @$_REQUEST['billing_address'], @$_REQUEST['billing_address2'], @$_REQUEST['billing_city'], @$_REQUEST['billing_state'], @$_REQUEST['billing_zip'], @$_REQUEST['billing_phone'], @$_REQUEST['billing_country'], $duration, $delay);
										}
									}
									else {
										if($this->plugins[$gateway_row['Payment_Gateway']]->has_Process_Charge()) {
											$charged = $this->plugins[$gateway_row['Payment_Gateway']]->Process_Charge($user_id."_".$price_id, $level." Membership at {$_SERVER['HTTP_HOST']}", $price, @$_REQUEST['payment_ccnum'], $date, @$_REQUEST['payment_cvv2'], @$_REQUEST['payment_name'], @$_REQUEST['billing_name'], @$_REQUEST['billing_address'], @$_REQUEST['billing_address2'], @$_REQUEST['billing_city'], @$_REQUEST['billing_state'], @$_REQUEST['billing_zip'], @$_REQUEST['billing_phone'], @$_REQUEST['billing_country']);
										}
									}
								}
							}
							if($charged) {
								$_REQUEST['do_register'] = -1;
							}
							else {
								$_REQUEST['do_register'] = 2;
								$retval = '<p class="errors">Failed to process card</p>'.$retval;
							}
						}
						break;
				}
				$step = is_numeric(@$_REQUEST['do_register']) ? (int)@$_REQUEST['do_register'] : 0;
				switch($step) {
					case -1:
						$retval .= "<p>".__('Thank you for signing up', 'wp-membership')."</p>";
						break;
					case 0:
						$retval .= "<form action=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."\" method=\"post\">";
						$retval .= "<input name=\"do_register\" type=\"hidden\" value=\"".($step + 1)."\" />";
						$retval .= "<input name=\"member_register_page_id\" type=\"hidden\" value=\"".urlencode($this->m_Register_Row['Register_Page_ID'])."\" />";
						$retval .= "<table class=\"wp_membership register step0\">";
						$retval .= "<tr>";
						$retval .= "<td>".__('Email', 'wp-membership')."</td>";
						$retval .= "<td><input name=\"email\" type=\"text\" value=\"".htmlentities(@$_REQUEST['email'])."\" /></td>";
						$retval .= "</tr>";
						$retval .= "<tr>";
						$retval .= "<td>".__('Password', 'wp-membership')."</td>";
						$retval .= "<td><input name=\"password\" type=\"password\" /></td>";
						$retval .= "</tr>";
						$retval .= "<tr>";
						$retval .= "<td>".__('Confirm Password', 'wp-membership')."</td>";
						$retval .= "<td><input name=\"password2\" type=\"password\" /></td>";
						$retval .= "</tr>";
						$retval .= "<tr>";
						$retval .= "<td colspan=\"2\"><input type=\"submit\" value=\"".__('Continue', 'wp-membership')."\" /></td>";
						$retval .= "</tr>";
						$retval .= "</table>";
						$retval .= "</form>";
						break;
					case 1:
						if(isset($wp_membership_plugin->public_messages['extra_fields_message'])) $retval .= $wp_membership_plugin->public_messages['extra_fields_message'];
						$retval .= "<form action=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."\" method=\"post\">";
						$retval .= "<input name=\"do_register\" type=\"hidden\" value=\"".($step + 1)."\" />";
						$retval .= "<input name=\"member_register_page_id\" type=\"hidden\" value=\"".urlencode($this->m_Register_Row['Register_Page_ID'])."\" />";
						$retval .= "<table class=\"wp_membership register step1\">";
						foreach(array("email", "password", "password2") as $key) $retval .= "<input type=\"hidden\" name=\"$key\" value=\"".htmlentities(@$_REQUEST[$key])."\" />";
						$extra_fields = @unserialize($this->m_Register_Row['Extra_Fields']);
						if(!is_array($extra_fields)) $extra_fields = array();
						foreach($extra_fields as $extra_id => $extra_field) {
							$caption = isset($extra_field->caption) ? $extra_field->caption : "";
							$classes = isset($extra_field->classes) ? $extra_field->classes : "";
							$name = isset($extra_field->name) ? $extra_field->name : "";
							$default = isset($extra_field->default) ? $extra_field->default : "";
							$signup = isset($extra_field->signup) ? $extra_field->signup : false;
							$type = isset($extra_field->type) ? $extra_field->type : "";
							$parameters = array();
							$keys = array();
							$raw_parameters = explode(";", isset($extra_field->parameters) ? $extra_field->parameters : "");
							foreach($raw_parameters as $raw_parameter) {
								$values = explode("=", $raw_parameter);
								if(count($values) == 1) $parameters[] = $values[0];
								else if(count($values) == 2) {
									if(isset($parameters[$values[0]])) $parameters[$values[0].(++$keys[$values[0]])] = $values[1];
									else {
										$keys[$values[0]] = 1;
										$parameters[$values[0]] = $values[1];
									}
								}
							}
							if(!$signup) $type = 'hidden';
							switch($type) {
								case "hidden":
									$retval .= "<input type=\"".htmlentities($type)."\"".(strlen($classes) > 0 ? " classes=\"".htmlentities($classes)."\"" : "")." name=\"wp-membership-extra_fields-".htmlentities($name)."\" value=\"".htmlentities($default)."\" />";
									break;
								case 'textarea':
									$retval .= "<tr valign=\"top\">";
									$retval .= "<td>".htmlentities($caption)."</td>";
									$retval .= "<td><textarea name=\"wp-membership-extra_fields-".htmlentities($name)."\"".(strlen($classes) > 0 ? " classes=\"".htmlentities($classes)."\"" : "").(isset($parameters['rows']) ? " rows=\"".htmlentities($parameters['rows'])."\"" : "").(isset($parameters['cols']) ? " cols=\"".htmlentities($parameters['cols'])."\"" : "").">".htmlentities(isset($_REQUEST['wp-membership-extra_fields-'.$name]) ? $_REQUEST['wp-membership-extra_fields-'.$name] : $default)."</textarea></td>";
									$retval .= "</tr>";
									break;
								case 'select':
									$retval .= "<tr valign=\"top\">";
									$retval .= "<td>".htmlentities($caption)."</td>";
									$values = explode(";", $default);
									$retval .= "<td>";
									$defaults = explode(',', @$parameters['default']);
									$retval .= '<select name="wp-membership-extra_fields-'.htmlentities($name).'"'.(strlen($classes) > 0 ? " classes=\"".htmlentities($classes)."\"" : "").(isset($parameters['multiple']) ? " MULTIPLE" : "").(strlen(@$parameters['size']) > 0 ? " size=\"".htmlentities($parameters['size'])."\"" : "").'>';
									foreach($values as $key => $content) {
										$data = explode('=', $content);
										$label = count($data) >= 2 ? $data[0] : null;
										$value = count($data) >= 2 ? $data[1] : $data[0];
										$retval .= "<option value=\"".htmlentities($value)."\"";
										if(isset($_REQUEST['do_register'])) $retval .= @$_REQUEST['wp-membership-extra_fields-'.$name] == $value ? ' SELECTED' : '';
										else $retval .= (in_array($key, $defaults) ? " SELECTED" : "");
										$retval .= ">";
										$retval .= htmlentities(is_null($label) ? $value : $label);
										$retval .= '</option>';
									}
									$retval .= "</td>";
									$retval .= "</tr>";
									break;
								case 'radio':
									$retval .= "<tr valign=\"top\">";
									$retval .= "<td>".htmlentities($caption)."</td>";
									$values = explode(";", $default);
									$retval .= "<td>";
									$defaults = explode(',', @$parameters['default']);
									foreach($values as $key => $content) {
										$data = explode('=', $content);
										$label = count($data) >= 2 ? $data[0] : null;
										$value = count($data) >= 2 ? $data[1] : $data[0];
										$retval .= "<input name=\"wp-membership-extra_fields-".htmlentities($name)."\"".(strlen($classes) > 0 ? " classes=\"".htmlentities($classes)."\"" : "");
										if(isset($_REQUEST['do_register'])) $retval .= @$_REQUEST['wp-membership-extra_fields-'.$name] == $value ? ' CHECKED' : '';
										else $retval .= (in_array($key, $defaults) ? " CHECKED" : "");
										$retval .= " type=\"radio\" value=\"".htmlentities($value)."\" />";
										if(!is_null($label)) $retval .= ' '.htmlentities($label);
									}
									$retval .= "</td>";
									$retval .= "</tr>";
									break;
								case 'checkbox':
									$retval .= "<tr valign=\"top\">";
									$retval .= "<td>".htmlentities($caption)."</td>";
									$retval .= "<td>";
									$data = explode('=', $default);
									$label = count($data) >= 2 ? $data[0] : null;
									$value = count($data) >= 2 ? $data[1] : $data[0];
									$retval .= "<input name=\"wp-membership-extra_fields-".htmlentities($name)."\"".(strlen($classes) > 0 ? " classes=\"".htmlentities($classes)."\"" : "");
									if(isset($_REQUEST['do_register'])) $retval .= @$_REQUEST['wp-membership-extra_fields-'.$name] == $value ? ' CHECKED' : '';
									else $retval .= (isset($parameters['checked']) && in_array($parameters['checked'], array(1, '1', 't', 'true', true)) ? " CHECKED" : "");
									$retval .= " type=\"checkbox\" value=\"".htmlentities($value)."\" />";
									if(!is_null($label)) $retval .= ' '.htmlentities($label);
									$retval .= "</td>";
									$retval .= "</tr>";
									break;
								case 'text':
								default:
									$retval .= "<tr valign=\"top\">";
									$retval .= "<td>".htmlentities($caption)."</td>";
									$retval .= "<td><input name=\"wp-membership-extra_fields-".htmlentities($name)."\"".(strlen($classes) > 0 ? " classes=\"".htmlentities($classes)."\"" : "").(isset($parameters['size']) ? " size=\"".htmlentities($parameters['size'])."\"" : "").(isset($parameters['maxlength']) ? " maxlength=\"".htmlentities($parameters['maxlength'])."\"" : "")." type=\"text\" value=\"".htmlentities(isset($_REQUEST['wp-membership-extra_fields-'.$name]) ? $_REQUEST['wp-membership-extra_fields-'.$name] : $default)."\" /></td>";
									$retval .= "</tr>";
									break;
							}
						}
						$level_query = $wpdb->prepare("SELECT t1.Register_Page_ID, t3.Level_ID, t3.Name, t3.Description, t4.Level_Price_ID, t4.Price, t4.Duration, t4.Delay FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 LEFT JOIN ".$wpdb->prefix."wp_membership_register_page_levels AS t2 ON t1.Register_Page_ID=t2.Register_Page_ID LEFT JOIN ".$wpdb->prefix."wp_membership_levels AS t3 ON t2.Level_ID=t3.Level_ID LEFT JOIN ".$wpdb->prefix."wp_membership_level_prices AS t4 ON t3.Level_ID=t4.Level_ID WHERE t3.Level_ID IS NOT NULL AND t1.Register_Page_ID=%s ORDER BY t3.Name, t4.Price", $this->m_Register_Row['Register_Page_ID']);
						if($level_rows = $wpdb->get_results($level_query, ARRAY_A)) {
							$retval .= "<tr valign=\"top\">";
							$retval .= "<td>".__('Membership Level', 'wp-membership')."</td>";
							$retval .= "<td>";
							$first = true;
							$only_free = true;
							foreach($level_rows as $level_row) {
								if(!$first) $retval .= "<br />";
								$retval .= "<input type=\"radio\" name=\"wp-membership_level_id\" value=\"".htmlentities($level_row['Level_ID']);
								if($level_row['Level_Price_ID'] !== null) $retval .= "_".htmlentities($level_row['Level_Price_ID']);
								$retval .= "\"".($first ? " CHECKED" : "")." />";
								$retval .= " ".htmlentities($level_row['Name']);
								if($level_row['Level_Price_ID'] !== null) {
									$only_free = false;
									$retval .= " - ".trim($wp_membership_plugin->my_money_format('%(n', $level_row['Price']));
								    load_plugin_textdomain('wp-membership', false, 'wp-membership');
									switch($level_row['Duration']) {
										case "":
											$retval .= " ".__('one time charge', 'wp-membership');
											break;
										case "+1 week":
											$retval .= " ".__('per week', 'wp-membership');
											break;
										case "+1 month":
											$retval .= " ".__('per month', 'wp-membership');
											break;
										case "+1 year":
											$retval .= " ".__('per year', 'wp-membership');
											break;
									}
									switch($level_row['Delay']) {
										case "+3 days":
											$retval .= ", ".__('with a 3 day free trial', 'wp-membership');
											break;
										case "+1 week":
											$retval .= ", ".__('with a 1 week free trial', 'wp-membership');
											break;
										case "+1 month":
											$retval .= ", ".__('with a 1 month free trial', 'wp-membership');
											break;
										case "+1 year":
											$retval .= ", ".__('with a 1 year free trial', 'wp-membership');
											break;
									}
								}
								else $retval .= " - ".__('Free', 'wp-membership');
								if($first) $first = false;
							}
							$retval .= "</td>";
							$retval .= "</tr>";
						}
						$retval .= "<tr>";
						$retval .= "<td colspan=\"2\"><input type=\"submit\" value=\"".($only_free ? __('Register', 'wp-membership') : __('Continue', 'wp-membership'))."\" /></td>";
						$retval .= "</tr>";
						$retval .= "</table>";
						$retval .= "</form>";
						break;
					case 2:
						$retval .= __('Email', 'wp-membership').": ".htmlentities(@$_REQUEST['email'])."<br />";
						$level_id = null;
						$price_id = null;
						$levelprice = explode("_", @$_REQUEST['wp-membership_level_id']);
						if(count($levelprice) == 1) {
							$level_id = $levelprice[0];
						}
						else if(count($levelprice) == 2) {
							$level_id = $levelprice[0];
							$price_id = $levelprice[1];
						}
						$level = null;
						$price = null;
						$duration = null;
						$delay = null;
						$level_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_levels AS t1 LEFT JOIN {$wpdb->prefix}wp_membership_level_prices AS t2 ON t1.Level_ID=t2.Level_ID WHERE t1.Level_ID=%s AND t2.Level_Price_ID=%s", $level_id, $price_id);
						if($level_row = $wpdb->get_row($level_query, ARRAY_A)) {
							$level = $level_row['Name'];
							$price = $level_row['Price'];
							$duration = $level_row['Duration'];
							$delay = $level_row['Delay'];
						}
						$user_id = null;
						$user_query = $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wp_membership_users AS t1 WHERE t1.Email=%s", @$_REQUEST['email']);
						if($user_row = $wpdb->get_row($user_query, ARRAY_A)) {
							$user_id = $user_row['User_ID'];
						}
						$retval .= __('Level', 'wp-membership').": ".htmlentities($level." - ".trim($wp_membership_plugin->my_money_format('%(n', $price)));
					    load_plugin_textdomain('wp-membership', false, $wp_membership_plugin->language_path);
						$subscription = false;
						$name = "";
						switch($duration) {
							case "":
								$retval .= " ".__('one time charge', 'wp-membership');
								break;
							case "+1 week":
								$retval .= " ".__('per week', 'wp-membership');
								$subscription = true;
								break;
							case "+1 month":
								$retval .= " ".__('per month', 'wp-membership');
								$subscription = true;
								break;
							case "+1 year":
								$retval .= " ".__('per year', 'wp-membership');
								$subscription = true;
								break;
						}
						switch($delay) {
							case "+3 days":
								$retval .= ", ".__('with a 3 day free trial', 'wp-membership');
								break;
							case "+1 week":
								$retval .= ", ".__('with a 1 week free trial', 'wp-membership');
								break;
							case "+1 month":
								$retval .= ", ".__('with a 1 month free trial', 'wp-membership');
								break;
							case "+1 year":
								$retval .= ", ".__('with a 1 year free trial', 'wp-membership');
								break;
						}
						$gateway_query = $wpdb->prepare("SELECT * FROM ".$wpdb->prefix."wp_membership_register_pages AS t1 LEFT JOIN ".$wpdb->prefix."wp_membership_register_page_gateways AS t2 ON t1.Register_Page_ID=t2.Register_Page_ID WHERE t1.Register_Page_ID=%s ORDER BY t2.Payment_Gateway", $this->m_Register_Row['Register_Page_ID']);
						if($gateway_rows = $wpdb->get_results($gateway_query, ARRAY_A)) {
							$buttons = false;
							foreach($gateway_rows as $gateway_row) {
								if(isset($wp_membership_plugin->plugins[$gateway_row['Payment_Gateway']])) {
									if($subscription) {
										if($wp_membership_plugin->plugins[$gateway_row['Payment_Gateway']]->has_Subscription_Button()) {
											$retval .= $wp_membership_plugin->plugins[$gateway_row['Payment_Gateway']]->get_Subscription_Button($user_id."_".$price_id, $level." ".__('Membership at', 'wp-membership')." {$_SERVER['HTTP_HOST']}", $price, $duration, $delay);
											$buttons = true;
										}
									}
									else {
										if($wp_membership_plugin->plugins[$gateway_row['Payment_Gateway']]->has_BuyNow_Button()) {
											$retval .= $wp_membership_plugin->plugins[$gateway_row['Payment_Gateway']]->get_BuyNow_Button($user_id."_".$price_id, $level." ".__('Membership at', 'wp-membership')." {$_SERVER['HTTP_HOST']}", $price);
											$buttons = true;
										}
									}
								}
							}
							$gateways = array();
							foreach($gateway_rows as $gateway_row) {
								if(isset($wp_membership_plugin->plugins[$gateway_row['Payment_Gateway']])) {
									if($subscription) {
										if($wp_membership_plugin->plugins[$gateway_row['Payment_Gateway']]->has_Install_Subscription()) {
											$gateways[$gateway_row['Payment_Gateway']] = $wp_membership_plugin->plugins[$gateway_row['Payment_Gateway']];
										}
									}
									else {
										if($wp_membership_plugin->plugins[$gateway_row['Payment_Gateway']]->has_Process_Charge()) {
											$gateways[$gateway_row['Payment_Gateway']] = $wp_membership_plugin->plugins[$gateway_row['Payment_Gateway']];
										}
									}
								}
							}
							if($buttons && count($gateways) > 0) $retval .= "<p>Or</p>";
							if(count($gateways) > 0) {
								$retval .= "<p class=\"required\">* ".__('indicates a required field', 'wp-membership')."</p>";
								$retval .= "<form action=\"{$_SERVER['PHP_SELF']}?page_id=".urlencode($page_id)."\" method=\"post\">";
								$retval .= "<input name=\"do_register\" type=\"hidden\" value=\"".($step + 1)."\" />";
								$retval .= "<input name=\"user_id\" type=\"hidden\" value=\"".($user_id)."\" />";
								$retval .= "<input name=\"price_id\" type=\"hidden\" value=\"".($price_id)."\" />";
								$retval .= "<input name=\"level_id\" type=\"hidden\" value=\"".($level_id)."\" />";
								foreach(array("email", "password", "password2", "wp-membership_level_id") as $key) $retval .= "<input type=\"hidden\" name=\"$key\" value=\"".htmlentities(@$_REQUEST[$key])."\" />";
								$retval .= "<table>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\" colspan=\"2\">".__('Billing Address', 'wp-membership')."</th>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Name', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"billing_name\" value=\"".htmlentities(@$_REQUEST['billing_name'])."\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Address', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"billing_address\" value=\"".htmlentities(@$_REQUEST['billing_address'])."\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\">".__('Address (Line 2)', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"billing_address2\" value=\"".htmlentities(@$_REQUEST['billing_address2'])."\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('City', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"billing_city\" value=\"".htmlentities(@$_REQUEST['billing_city'])."\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr align=\"left\">";
								$retval .= "<th><span class=\"required\">*</span>".__('State', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"billing_state\" value=\"".htmlentities(@$_REQUEST['billing_state'])."\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Zip / Postal Code', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"billing_zip\" value=\"".htmlentities(@$_REQUEST['billing_zip'])."\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Country', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"billing_country\" value=\"".htmlentities(@$_REQUEST['billing_country'])."\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\">".__('Phone', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"billing_phone\" value=\"".htmlentities(@$_REQUEST['billing_phone'])."\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\" colspan=\"2\">".__('Payment Information', 'wp-membership')."</th>";
								$retval .= "</tr>";
								if(count($gateways) > 1) {
									$retval .= "<tr valign=\"top\">";
									$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Payment Processor', 'wp-membership')."</th>";
									$retval .= "<td>";
									$processors = "";
									foreach($gateways as $key => $gateway) {
										if(strlen($processors) > 0) $processors .= "<br />";
										$processors .= "<input type=\"radio\" name=\"processor\" value=\"".htmlentities($key)."\" /> ".htmlentities($gateway->get_Name());
									}
									$retval .= "</td>";
									$retval .= "</tr>";
								}
								else $retval .= "<input type=\"hidden\" name=\"processor\" value=\"".htmlentities(@implode("", array_keys($gateways)))."\" />";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Name on the Card', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"payment_name\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Card Number', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"payment_ccnum\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Card Expiration', 'wp-membership')."</th>";
								$retval .= "<td>".__('Month', 'wp-membership').": <select name=\"payment_ccexp_month\"><option value=\"\">[Choose One]</option>";
								$start = strtotime("Jan");
								for($date = $start; $date < strtotime("+1 year", $start); $date = strtotime("+1 month", $date)) {
									$retval .= "<option value=\"".date("m", $date)."\">".date("n - F", $date)."</option>";
								}
								$retval .= "</select>";
								$retval .= " / ".__('Year', 'wp-membership').": <select name=\"payment_ccexp_year\"><option value=\"\">[Choose One]</option>";
								$start = strtotime("now");
								for($date = $start; $date < strtotime("+20 years", $start); $date = strtotime("+1 year", $date)) {
									$retval .= "<option value=\"".date("Y", $date)."\">".date("Y", $date)."</option>";
								}
								$retval .= "</select>";
								$retval .= "</td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<th align=\"left\"><span class=\"required\">*</span>".__('Security Code', 'wp-membership')."</th>";
								$retval .= "<td><input type=\"text\" name=\"payment_cvv2\" size=\"3\" /></td>";
								$retval .= "</tr>";
								$retval .= "<tr>";
								$retval .= "<td colspan=\"2\" align=\"right\"><input type=\"submit\" value=\"".__('Process', 'wp-membership')."\" /></td>";
								$retval .= "</tr>";
								$retval .= "</table>";
								$retval .= "</form>";
							}
						}
						break;
					case 3:
						break;
					default:
						break;
				}
			}
			
			return $retval;
		}
	}
}
?>
