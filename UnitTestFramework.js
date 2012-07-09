/*
Plugin Name: Free WP-Membership Plugin
Plugin URI: http://free-wp-membership.foransrealm.com/
Description: Allows the ability to have a membership based page restriction. (previously by Synergy Software Group LLC)
Version: 1.1.9
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
    along with Free WP-Membership.  If not, see <http://www.gnu.org/licenses/>.

*/
function getTestNames() {
        jQuery.ajax(
                {
                        type: 'POST',
                        url: FWPM_UTF.ajaxurl,
                        data: {
                                action: 'fwpm_utf_getTestNames'
                        },
                        success: getTestNames_onSuccess
                }
        );
}
function executeTest(nonce, test) {
        var output = document.getElementById('unittest_' + test);
        if(output) {
                output.innerHTML = 'Executing...';
        }
        jQuery.ajax(
                {
                        type: 'POST',
                        url: FWPM_UTF.ajaxurl,
                        data: {
                                action: 'fwpm_utf_executeTest',
                                unit_test_nonce: nonce,
                                execute_test: test
                        },
                        success: executeTest_onSuccess
                }
        );					
}
function getTestNames_onSuccess(data) {
        var result = data;
        if(result.result == 'success') {
                var output = document.getElementById('testList');
                var buffer = "<table class=\"form-table\">";
                buffer += "<tr valign=\"top\">";
                buffer += "<th scope=\"row\">Test Name</th>";
                buffer += "<th>Status</th>";
                buffer += "</tr>";
                for(var test in result.tests) {
                        buffer += "<tr><td>" + result.tests[test].caption + "</td><td id=\"unittest_" + result.tests[test].name + "\">Pending...</td></tr>";
                }
                buffer += "</table>";
                output.innerHTML = buffer;
                for(var test in result.tests) {
                    executeTest(result.tests[test].nonce, result.tests[test].name);
                }
        }
}
function executeTest_onSuccess(data) {
        var result = data;
        if(result.result == 'success') {
                var output = document.getElementById('unittest_' + result.testName);
                if(output) {
                        output.innerHTML = result.testResult;
                        if(result.message) output.innerHTML += ' - ' + result.message;
                }
        }
}
