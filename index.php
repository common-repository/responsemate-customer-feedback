<?php
/*
Plugin Name: ResponseMate
Plugin URI: http://responsemate.com
Description: Customer Feedback, Simplified
Author: ResponseMate
Version: 1.1
Author URI: http://responsemate.com
*/
/*  Copyright 2016  ResponseMate  (email: gabrie@responsemate.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Definition of helpful constants
 */
const RESPONSEMATE_FOLDER_NAME = 'responsemate-customer-feedback';

/**
 * Get websitecode and additional parameter
 * @return array|null|object
 */
function responsemate_getParams()
{
    global $wpdb;
    $rspnmte_results = null;
    $rspnmte_table_name = $wpdb->prefix . 'responseMate';

    if ($wpdb->get_var("SHOW TABLES LIKE $rspnmte_table_name") != $rspnmte_table_name) {
        $rspnmte_sql = "SELECT websitecode, email, settings FROM `".$wpdb->prefix."responseMate` ORDER BY id DESC LIMIT 1";
        $rspnmte_results = $wpdb->get_results($rspnmte_sql);
    }

    return $rspnmte_results;
}

function responsemate_getSiteCode(){
    if(responsemate_getParams() != NULL){
        return responsemate_getParams()[0]->websitecode;
    }else{
        return "";
    }
}
function responsemate_getEmail(){
    if(responsemate_getParams() != NULL){
        return responsemate_getParams()[0]->email;
    }else{
        return "";
    }
}
function responsemate_getSettings(){
    if(responsemate_getParams() != NULL){
        return responsemate_getParams()[0]->settings;
    }else{
        return "";
    }
}
function responsemate_cleanData($rspnmte_data){
    $rspnmte_data = strip_tags($rspnmte_data);
    $rspnmte_data = htmlspecialchars($rspnmte_data);
    return $rspnmte_data;
}
//Find _buttonR parameter
function responsemate_checkSettingsLine($rspnmte_input_line){
    $rspnmte_input_line = preg_replace("/'/", '"', $rspnmte_input_line);
    preg_match('/(_buttonR:[ ]*"[\d]*px")/', $rspnmte_input_line, $rspnmte_output_array);
    return $rspnmte_output_array;
}

/**
 * Wrap the main function responsemate_enterPoint
 */
function responsemate_wrapperresponsemate_enterPoint()
{
    add_menu_page('ResponseMate', 'ResponseMate', 8, 'responseMate', 'responsemate_enterPoint');
}
add_action('admin_menu', 'responsemate_wrapperresponsemate_enterPoint');

/**
 * Main function contain:
 * Rendering menu page;
 * Listener POST request (AJAX) for insert params to DB
 */
function responsemate_enterPoint()
{
    ?>
    <style>
        @media (min-device-width: 600px) {
            #websitecode {
                padding: 0;
                width: 200px;
                margin-left: 45px;
            }

            #additionalSettings {
                padding: 0;
                width: 200px;
                margin-left: 20px;
            }

            #email{
                padding: 0;
                width: 200px;
                margin-left: 90px;
            }

            #generateCode{
                padding: 0;
                margin-left: 125px;
            }
        }

        @media (max-device-width: 600px) {
            #websitecode {
                padding: 0;
                margin-left: 45px;
            }

            #additionalSettings {
                padding: 0;
                margin-left: 20px;
            }

            #email{
                padding: 0;
                margin-left: 20px;
            }

            #generateCode{
                padding: 0;
                margin-left: 20px;
            }
        }

        .divInput {
            padding: 5px 0;
        }

        #buttonSave {
            margin: 0 0 0 130px
        }
        #showAdvancedOptions{
            cursor: pointer;
        }
    </style>

    <form id="formX">
        <h1><img alt="ResponseMate" src="http://responsemate.com/wp-content/uploads/2015/02/logo-testmate.png" style="height: 45px;"></h1><br>
		<h2>Customer Feedback, Simplified</h2><br>
		<div>With just a few clicks, your customers can help improve website usability and foresee issues before they impact your conversion rates!</div><br>
        <div>To get started please sign up at <a target="_blank"
                                                 href="http://responsemate.com/">responsemate.com</a></div>
        <div class="divInput">Email <input id="email" type="text" value="<?=responsemate_getEmail()?>"></div>
        <button id="generateCode">Generate Code</button>
        <br><br>
        <div class="divInput">Website code <input id="websitecode" readonly type="text" value="<?=responsemate_getSiteCode()?>"></div>
        <br>
        <a id="showAdvancedOptions">Show advanced options</a>
        <br>
        <div id="containerAdditionalSettings" class="divInput" style="display:none">Additional settings<input id="additionalSettings" type="text" value='<?=responsemate_getSettings()?>'></div>
        <br>
        <button id="buttonSave">Save</button>
    </form>

    <script>
        function getXmlHttp() {
            var xmlhttp;
            try {
                xmlhttp = new ActiveXObject("Msxml2.XMLHTTP");
            } catch (e) {
                try {
                    xmlhttp = new ActiveXObject("Microsoft.XMLHTTP");
                } catch (E) {
                    xmlhttp = false;
                }
            }
            if (!xmlhttp && typeof XMLHttpRequest != 'undefined') {
                xmlhttp = new XMLHttpRequest();
            }
            return xmlhttp;
        }

        function sendAJAX(url, data) {
            var xmlhttp = getXmlHttp();
            xmlhttp.open('POST', url, false);
            xmlhttp.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            xmlhttp.send(data);
            return xmlhttp
        }

        var websitecode = null;
        var additionalSettings = null;
        var email = null;
        var emailElement = document.getElementById('email');
        var host = null;

        function validateEmail(email) {
            var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(email);
        }

        function generateCode(){
            event.preventDefault();

            email = document.getElementById("email").value;
            host = window.location.origin;
            var data = {
                "email": email,
                "host": host
            };

            if(validateEmail(email)){
                emailElement.style.borderColor = "green";
                var verifyResult = sendAJAX('https://feedback.saltandfuessel.com.au/src/generateCode.php', JSON.stringify(data));

                if (verifyResult.status == 201) {

                    document.getElementById("websitecode").value = verifyResult.responseText;
                    getInputs(event);
//                  alert('Plugin successfully installed!\nYour Website Code = ' + verifyResult.responseText);
//				    document.getElementById("formX").reset();


                }
                else if (verifyResult.status == 200) {

                    document.getElementById("websitecode").value = verifyResult.responseText;
                    getInputs(event);
//                  alert('Plugin successfully installed!\nYour Website Code = ' + verifyResult.responseText);

                }
            }else{
                emailElement.style.borderColor = "red";
            }

        }

        document.getElementById("generateCode").addEventListener("click", generateCode);

        function getInputs(event) {

            event.preventDefault();

            websitecode = document.getElementById("websitecode").value;

            email = document.getElementById("email").value;

            additionalSettings = document.getElementById("additionalSettings").value;

            var data = {
                "websitecode": websitecode,
                "email": email,
                "additionalSettings": additionalSettings
            };

            var verifyResult = sendAJAX('https://feedback.saltandfuessel.com.au/src/verifySiteCode.php', websitecode);

            if (verifyResult.status == 200 && verifyResult.responseText == "valid") {

                verifyResult = sendAJAX('<?php echo $_SERVER['PHP_SELF'];?>?page=responseMate', JSON.stringify(data));

                if (verifyResult.status == 200) {

                    alert('Plugin successfully installed!');

                }

//				document.getElementById("formX").reset();

            } else if (verifyResult.status == 204) {

                alert('Wrong website code!');

            }

        }

        document.getElementById("buttonSave").addEventListener("click", getInputs);

        function toggleAdvancedOptions(){
            if(containerAdditionalSettings.style.display == "none"){
                containerAdditionalSettings.style.display = "block";
            }else{
                containerAdditionalSettings.style.display = "none";
            }
        }

        var showAdvancedOptions = document.getElementById("showAdvancedOptions");
        var containerAdditionalSettings = document.getElementById("containerAdditionalSettings");
        showAdvancedOptions.addEventListener('click', toggleAdvancedOptions);

    </script>
    <?php

    $rspnmte_parameter = json_decode(file_get_contents('php://input'));
    responsemate_fillDatabase($rspnmte_parameter);

}

/**
 * Listen AJAX-request and fill params to database
 * @param $rspnmte_parameter
 */
function responsemate_fillDatabase($rspnmte_parameter){
    $rspnmte_siteCode = responsemate_cleanData($rspnmte_parameter->websitecode);
    $rspnmte_email = $rspnmte_parameter->email;
    $rspnmte_additionalSettings = responsemate_checkSettingsLine($rspnmte_parameter->additionalSettings)[0];
    if ($rspnmte_parameter->websitecode != NULL) {
        global $wpdb;
        $rspnmte_table_name = $wpdb->prefix . 'responseMate';

        if ($wpdb->get_var("SHOW TABLES LIKE $rspnmte_table_name") != $rspnmte_table_name) {
            $rspnmte_sql = "INSERT INTO `".$rspnmte_table_name."`(`websitecode`,`email`, `settings`) 
                    VALUES ('$rspnmte_siteCode','$rspnmte_email','$rspnmte_additionalSettings');";
            $wpdb->query($rspnmte_sql);
        }
    }
}

/**
 * Create table wp_responseMate after activate plugin
 */
function responsemate_install_responseMate_table()
{
    global $wpdb;
    $rspnmte_table_name = $wpdb->prefix . 'responseMate';

    if ($wpdb->get_var("SHOW TABLES LIKE $rspnmte_table_name") != $rspnmte_table_name) {
        $rspnmte_sql = "CREATE TABLE IF NOT EXISTS `$rspnmte_table_name` (
  				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `websitecode` varchar(11) DEFAULT NULL,
				  `email` varchar(50) DEFAULT NULL,
				  `settings` varchar(200) DEFAULT NULL,
				  PRIMARY KEY (`id`)
				) ENGINE=InnoDB DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;";
        $wpdb->query($rspnmte_sql);
    }
}

register_activation_hook(WP_PLUGIN_DIR.'/'.RESPONSEMATE_FOLDER_NAME.'/index.php', 'responsemate_install_responseMate_table');

/**
 * Drop table wp_responseMate after uninstall plugin
 */ 
function responsemate_uninstall_responseMate_table()
{
    global $wpdb;
    $rspnmte_table_name = $wpdb->prefix . 'responseMate';

    if ($wpdb->get_var("SHOW TABLES LIKE $rspnmte_table_name") != $rspnmte_table_name) {
        $rspnmte_sql = "DROP TABLE IF EXISTS `$rspnmte_table_name`;";
        $wpdb->query($rspnmte_sql);
    }
}

register_uninstall_hook(WP_PLUGIN_DIR.'/'.RESPONSEMATE_FOLDER_NAME.'/index.php', 'responsemate_uninstall_responseMate_table');

/**
 * Install feedback code to all pages
 */
function responsemate_install_feedback_plugin()
{
    if (responsemate_getParams() != NULL) { ?>
        <script type="text/javascript">
            var _fbq = _fbq || [];
            _fbq.push({
                _setAccount: '<?php echo responsemate_getParams()[0]->websitecode;?>',
                <?php echo responsemate_getParams()[0]->settings;?>
            });
            (function () {
                var ga = document.createElement('script');
                ga.type = 'text/javascript';
                ga.async = true;
                ga.src = ('https:' == document.location.protocol ? 'https://' : 'http://') + 'feedback.saltandfuessel.com.au/js/feedback.js';
                var s = document.getElementsByTagName('script')[0];
                s.parentNode.insertBefore(ga, s);
            })();
        </script>
    <?php }
}
add_action('wp_head', 'responsemate_install_feedback_plugin');
