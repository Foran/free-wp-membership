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
if(!class_exists('wp_membership_Models_Level') && class_exists('wp_membership_plugin')) {
    class wp_membership_Models_Level {
        private $mLevel_ID;
        
        function get_Level_ID() {
            return $this->mLevel_ID;
        }
        
        function Load($levelID) {
            global $wpdb;
            $retval = false;
            if(($level_row = $wpdb->get_row($wpdb->prepare('SELECT * FROM '.$wpdb->prefix.'wp_membership_levels WHERE Level_ID=%s"', $levelID))) !== false) {
                $this->mLevel_ID = $level_row['Level_ID'];
            }
            return $retval;
        }
    }
}
?>