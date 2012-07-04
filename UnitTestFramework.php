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
if(!function_exists('check_admin_referer')) {
	chdir("../../../");
	include_once("wp-config.php");
}

check_admin_referer('execute_unit_test', 'unit_test_nonce');
$basepath = pathinfo($_SERVER['SCRIPT_FILENAME']);
$basepath = ereg_replace("/wp-admin\$", "", @$basepath['dirname']);
$basepath = ereg_replace("/wp-content/plugins/free-wp-membership\$", "", $basepath);
require_once($basepath.'/wp-content/plugins/free-wp-membership/interfaces/IWPMembershipUnitTestClass.php');

if(@$_SERVER['SCRIPT_FILENAME'] === __FILE__) {
	if(wp_verify_nonce($_POST['unit_test_nonce'], 'execute_unit_test'))  {
		$test_result->result = 'success';
		if($dh = opendir($basepath.'/wp-content/plugins/free-wp-membership/Tests')) {
			while(($file = readdir($dh)) !== false) {
				$fullFile = $basepath.'/wp-content/plugins/free-wp-membership/Tests/'.$file;
				if(!is_dir($fullFile)) {
					require_once($fullFile);
				}
			}
			closedir($dh);
		}
		if(isset($_POST['execute_test'])) {
			$test_result->testName = $_POST['execute_test'];
			$parts = explode(':', $test_result->testName);
			$reflection = new ReflectionClass($parts[0]);
			$test_result->testResult = 'Failed';
			if($reflection->implementsInterface('IWPMembershipUnitTestClass') && !in_array($parts[0], get_class_methods('IWPMembershipUnitTestClass'))) {
				global $test_result;
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
			$test_result->tests = array();
			foreach(get_declared_classes() as $class) {
				$reflection = new ReflectionClass($class);
				if($reflection->implementsInterface('IWPMembershipUnitTestClass')) {
					foreach(array_diff(get_class_methods($class), get_class_methods('IWPMembershipUnitTestClass')) as $test) {
						$test_result->tests[] = $class.':'.$test;
					}
				}
			}
		}
		echo json_encode($test_result);
	}
	else {
		$error->result = 'error';
		$error->message = 'Invalid nonce';
		echo json_encode($error);
	}
}
else {
	$error->result = 'error';
	$error->message = 'was not called directly';
	echo json_encode($error);
}
?>