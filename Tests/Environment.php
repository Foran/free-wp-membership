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
$basepath = pathinfo($_SERVER['SCRIPT_FILENAME']);
$basepath = ereg_replace("/wp-admin\$", "", @$basepath['dirname']);
$basepath = ereg_replace("/wp-content/plugins/free-wp-membership\$", "", $basepath);
require_once($basepath.'/wp-content/plugins/free-wp-membership/interfaces/IWPMembershipUnitTestClass.php');

if(!class_exists('FWPM_Test_Environment') && interface_exists('IWPMembershipUnitTestClass')) {
	class FWPM_Test_Envoronment implements IWPMembershipUnitTestClass {
		function TestInitialize() {
			
		}
		function TestCleanup() {
			
		}
		function PHPVersion() {
			global $wp_membership_min_php_version, $test_result;
			assert(version_compare(PHP_VERSION, $wp_membership_min_php_version, '>=')) or $test_result->message = "PHP Version is too old, expected at least $wp_membership_min_php_version actual ".PHP_VERSION;
		}
		function WordPressVersion() {
			global $wp_membership_min_wp_version, $test_result;
			assert(version_compare(get_bloginfo('version', 'raw'), $wp_membership_min_wp_version, '>=')) or $test_result->message = "WordPress Version is too old, expected at least $wp_membership_min_wp_version actual ".get_bloginfo('version', 'raw');
		}
		function MySQLVersion() {
			global $wpdb, $test_result;
			if($version_row = $wpdb->get_row("SELECT VERSION()", ARRAY_A)) {
				assert(version_compare($version_row['VERSION()'], '5.0.0', '>=')) or $test_result->message = "MySQL Version is too old, expected at least 5.0.0 actual ".$version_row['VERSION()'];
			}
		}
		function SimpleXML() {
			global $test_result;
			assert(function_exists('simplexml_load_string')) or $test_result->message = "Unable to find SimpleXML, is it installed?";
		}
		function Curl_Exists() {
			global $test_result;
			assert(function_exists('curl_init')) or $test_result->message = "Unable to find Curl, is it installed?";
		}
		function Curl_BasicSelfGet() {
			global $test_result;
			assert($ch = curl_init(get_bloginfo('wpurl'))) or $test_result->message = "Failed to init Curl";
			if($ch) {
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
				curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
				$buffer = curl_exec($ch);
				assert($buffer !== FALSE) or $test_result->message .= "Failed to execute Curl request";
				assert(strlen($buffer) > 0) or $test_result->message .= "Curl returned an empty buffer";
			}
		}
	}
}

?>