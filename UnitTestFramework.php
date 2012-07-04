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
require_once(FWP_MEMBERSHIP_PATH.'interfaces/IWPMembershipUnitTestClass.php');

if(!class_exists('wp_membership_UnitTestFramework') && class_exists('wp_membership_plugin')) {
	class wp_membership_UnitTestFramework {
		private $mWPMembership;
		function __construct($wpmembership) {
			$this->mWPMembership = $wpmembership;
			add_action('wp_ajax_fwpm_utf_getTestNames', array(&$this, 'get_TestNames'));
			add_action('wp_ajax_fwpm_utf_executeTest', array(&$this, 'ExecuteTest'));
		}
		function get_TestNames() {
			check_admin_referer();
			$test_result->result = 'success';
			if(current_user_can('edit_plugins')) {
				if($dh = opendir(plugin_dir_path(__FILE__).'Tests')) {
					while(($file = readdir($dh)) !== false) {
						$fullFile = plugin_dir_path(__FILE__).'Tests/'.$file;
						if(!is_dir($fullFile)) {
							require_once($fullFile);
						}
					}
					closedir($dh);
				}
				$test_result->tests = array();
				foreach(get_declared_classes() as $class) {
					$reflection = new ReflectionClass($class);
					if($reflection->implementsInterface('IWPMembershipUnitTestClass')) {
						foreach(array_diff(get_class_methods($class), get_class_methods('IWPMembershipUnitTestClass')) as $test) {
							$test_result->tests[] = array('caption' => $class.' - '.$test, 'name' => $class.':'.$test, nonce => wp_create_nonce('execute_unit_test'));
						}
					}
				}
			}
			else {
				$test_result->result = 'error';
				$test_result->errorMessage = 'Access Denied';
			}
			header('Content-Type: application/json');
			echo json_encode($test_result);
			exit;
		}
		function ExecuteTest() {
			check_admin_referer('execute_unit_test', 'unit_test_nonce');
			wp_verify_nonce($_POST['unit_test_nonce'], 'execute_unit_test');
			global $test_result;
			$test_result->result = 'success';
			if(current_user_can('edit_plugins')) {
				if($dh = opendir(plugin_dir_path(__FILE__).'Tests')) {
					while(($file = readdir($dh)) !== false) {
						$fullFile = plugin_dir_path(__FILE__).'Tests/'.$file;
						if(!is_dir($fullFile)) {
							require_once($fullFile);
						}
					}
					closedir($dh);
				}
				$test_result->testName = $_POST['execute_test'];
				$parts = explode(':', $test_result->testName);
				$reflection = new ReflectionClass($parts[0]);
				$test_result->testResult = 'Failed';
				if($reflection->implementsInterface('IWPMembershipUnitTestClass') && !in_array($parts[0], get_class_methods('IWPMembershipUnitTestClass'))) {
					assert_options(ASSERT_ACTIVE, true);
					assert_options(ASSERT_BAIL, false);
					assert_options(ASSERT_WARNING, false);
					assert_options(ASSERT_QUIET_EVAL, true);
					assert_options(ASSERT_CALLBACK, function() use ($file, $line, $code) {
						global $test_result;
						$test_result->testResult = 'Failed';
					});
					$test_result->testResult = 'Passed';
					eval('$class = new '.$parts[0].'();');
					$class->TestInitialize();
					call_user_func_array(array(&$class, $parts[1]), array());
					$class->TestCleanup();
				}
			}
			else {
				$test_result->result = 'error';
				$test_result->errorMessage = 'Access Denied';
			}
			header('Content-Type: application/json');
			echo json_encode($test_result);
			exit;
		}
	}
}
?>