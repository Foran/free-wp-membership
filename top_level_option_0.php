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

if(isset($wp_membership_plugin) && class_exists('wp_membership_plugin') && is_a($wp_membership_plugin, 'wp_membership_plugin')) {
	function wp_membership_old_admin_menu() {
		$wp_membership_plugin->display_options_tab_0();
	}
}
?>