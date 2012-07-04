function getTestNames(nonce) {
        jQuery.ajax(
                {
                        type: 'POST',
                        url: FWPM_UTF.ajaxurl,
                        data: {
                                action: 'fwpm_utf_getTestNames',
                                unit_test_nonce: nonce
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
