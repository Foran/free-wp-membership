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
			global $wp_membership_min_php_version;
			assert(version_compare(PHP_VERSION, $wp_membership_min_php_version, '>='));
		}
	}
}

?>