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

if(!interface_exists('IWPMembershipSettingsTab')) {
	/**
	 * Interface for all Admin settings pages
	 * Anything that implements this interface will appear in the Settings section/tab
	 */
	interface IWPMembershipSettingsTab {
		/**
		 * @return The filename of the file cantaining the source of the concrete implementation
		 */
		function get_File();
		/**
		 * Display the contents of the tab
		 *
		 */
		function DisplayTab();
	}
}
?>