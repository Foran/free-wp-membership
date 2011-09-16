<?php
/*
Plugin Name: Free WP-Membership Plugin
Plugin URI: http://free-wp-membership.foransrealm.com/
Description: Allows the ability to have a membership based page restriction. (previously by Synergy Software Group LLC)
Version: 1.1.7
Author: Ben M. Ward
Author URI: http://free-wp-membership.foransrealm.com/

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

if(@$_SERVER['SCRIPT_FILENAME'] === __FILE__) {
	if(wp_verify_nonce($_POST['unit_test_nonce'], 'execute_unit_test'))  {
		$test_result->result = 'success';
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