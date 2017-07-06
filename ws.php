<?php

error_reporting(0);
//error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);

require_once 'connect.php';

$response = array();
mysql_query("SET NAMES 'utf8'");
mysql_query('SET CHARACTER SET utf8');

$selectMedics = " medics.id AS medic_id, medics.name_eng AS name_eng, medics.name_ara AS name_ara, "
        . "        medics.unit_package AS unit_package, medics.units_number AS units_number, medics.price_per_unit AS price_per_unit, "
        . "        price, medics.medic_form_id AS medic_form_id, medic_forms.name_eng AS medic_form_eng, medic_forms.name_ara AS medic_form_ara, "
        . "        medics.local_import_id AS local_import_id, local_import.name_eng AS local_import_eng, local_import.name_ara AS local_import_ara, "
        . "        medics.dose_id AS dose_id, doses.name_eng AS dose_eng, doses.name_ara AS dose_ara, medics.main_group_id AS main_group_id, "
        . "        main_groups.name_eng AS main_group_eng, main_groups.name_ara AS main_group_ara, "
        . "        medics.sub_group_id AS sub_group_id, sub_groups.name_eng AS sub_group_eng, sub_groups.name_ara AS sub_group_ara, "
        . "        medics.producer_id AS producer_id, producers.name_eng AS producer_eng, producers.name_ara AS producer_ara, producer_address, "
        . "        producer_tel1, producer_tel2, producer_tel3, producer_emails, img_url ";

$innerJoin = "LEFT JOIN main_groups "
        . "     ON medics.main_group_id=main_groups.id "
        . "LEFT JOIN sub_groups "
        . "     ON medics.sub_group_id=sub_groups.id "
        . "LEFT JOIN producers"
        . "     ON medics.producer_id=producers.id "
        . "LEFT JOIN medic_forms "
        . "     ON medics.medic_form_id=medic_forms.id "
        . "LEFT JOIN local_import "
        . "     ON medics.local_import_id=local_import.id "
        . "LEFT JOIN doses "
        . "     ON medics.dose_id=doses.id "
        . "LEFT JOIN materials "
        . "     ON medics.id=materials.medic_id ";

$loginChecks = login_check();

if (!$loginChecks) {
    switch ($_POST['request_type']) {
        case "quick_search":
        case "alternatives":
        case "advanced_search":
            $response['medics'] = array();
            $response['error_message'] = "login required";
            break;
        case "get_data":
            $response['main_groups'] = array();
            $response['sub_groups'] = array();
            $response['active_materials'] = array();
            $response['producers'] = array();
            $response['error_message'] = "login required";
            break;
        case "get_ads":
            $response['ads'] = array();
            $response['error_message'] = "login required";
            break;
        default :break;
    }
} else {
    switch ($_POST['request_type']) {
        case "quick_search":
            $medicNameCond = $_POST['medic_name'] != '' ? " (medics.name_eng LIKE '%$_POST[text_box]%' OR medics.name_ara LIKE '%$_POST[text_box]%') " : " 1!=1 ";
            $mainGroupCond = $_POST[main_group_id] != '' ? " OR (main_groups.name_eng LIKE '%$_POST[text_box]%' OR main_groups.name_ara LIKE '%$_POST[text_box]%') " : "";
            $subGroupCond = $_POST[sub_group_id] != '' ? " OR (sub_groups.name_eng LIKE '%$_POST[text_box]%' OR sub_groups.name_ara LIKE '%$_POST[text_box]%') " : "";

            $activeMaterialCond = "";
            if ($_POST[active_material_id] != '') {
                $activeMaterialsIds = mysql_result(mysql_query("SELECT GROUP_CONCAT(id SEPARATOR ', ') FROM active_materials WHERE active_name LIKE '%$_POST[text_box]%'"), 0);

                $activeMaterialCond .= " OR ( ";
                for ($i = 1; $i < 32; $i++) {
                    $active = "active_$i";
                    $activeMaterialCond .= $i == 1 ? "" : " OR ";
                    $activeMaterialCond .= " ($active IN ($activeMaterialsIds) ) ";
                }
                $activeMaterialCond .= " ) ";
            }

            if (trim($_POST['text_box']) != '') {
                if ($_POST['medic_name'] != "" || $mainGroupCond != "" || $subGroupCond != "" || $activeMaterialCond != "") {
                    $query = mysql_query("SELECT $selectMedics FROM medics "
                            . $innerJoin
                            . "WHERE $medicNameCond $mainGroupCond $subGroupCond $activeMaterialCond "
                            . "$priceCond $producerCond "
                            . "GROUP BY medic_id LIMIT 50");

                    $response['medics'] = array();
                    while ($row = mysql_fetch_assoc($query)) {
                        $response['medics'][] = $row;
                    }
                }
            }
            break;

        case "alternatives":
            $material = mysql_fetch_array(mysql_query("SELECT * FROM materials WHERE medic_id = '$_POST[medic_id]'"), 0);
            $medic = mysql_fetch_array(mysql_query("SELECT * FROM medics WHERE id = '$_POST[medic_id]'"), 0);

            $mainGroupCond = $_POST[main_group_id] != '' ? " medics.main_group_id='$_POST[main_group_id]' " : " 1=1 ";
            $subGroupCond = $_POST[sub_group_id] != '' ? " AND medics.sub_group_id='$_POST[sub_group_id]' " : "";
            $priceCond = $_POST[price] != '' ? " AND ROUND(price, 2)='" . round($_POST[price], 2) . "' " : "";
            $producerCond = $_POST[producer_id] != '' ? " AND medics.producer_id='$_POST[producer_id]' " : "";

            $materialCond = "";
            if ($_POST[is_identical] == 1) {
                $materialCond .= " AND ( ";
                for ($i = 1; $i < 32; $i++) {
                    $active = "active_$i";
                    $materialCond .= $i == 1 ? "" : " AND ";
                    $materialCond .= " $active='$material[$active]' ";
                }
                $materialCond .= " AND medics.main_group_id='$medic[main_group_id]' ";
                $materialCond .= " ) ";
            }

            $activeMaterialCond = "";
            if ($_POST[active_material_id] != '') {
                $activeMaterialCond .= " AND ( ";
                for ($i = 1; $i < 32; $i++) {
                    $active = "active_$i";
                    $activeMaterialCond .= $i == 1 ? "" : " OR ";
                    $activeMaterialCond .= " $active='$_POST[active_material_id]' ";
                }
                $activeMaterialCond .= " ) ";
            }

            $query = mysql_query("SELECT $selectMedics FROM medics "
                    . $innerJoin
                    . "WHERE $mainGroupCond $subGroupCond $materialCond "
                    . "$priceCond $producerCond $activeMaterialCond "
                    . "GROUP BY medic_id LIMIT 50");

            $response['medics'] = array();
            while ($row = mysql_fetch_assoc($query)) {
                $response['medics'][] = $row;
            }
            break;

        case "advanced_search":
            $medicNameCond = $_POST['medic_name'] != '' ? " (medics.name_eng LIKE '%$_POST[medic_name]%' OR medics.name_ara LIKE '%$_POST[medic_name]%') " : " 1=1 ";

            $mainGroupCond = $_POST[main_group_id] != '' ? " AND medics.main_group_id='$_POST[main_group_id]' " : "";
            $subGroupCond = $_POST[sub_group_id] != '' ? " AND medics.sub_group_id='$_POST[sub_group_id]' " : "";
            $priceCond = $_POST[price] != '' ? " AND ROUND(price, 2)='" . round($_POST[price], 2) . "' " : "";
            $producerCond = $_POST[producer_id] != '' ? " AND medics.producer_id='$_POST[producer_id]' " : "";

            $activeMaterialCond = "";
            if ($_POST[active_material_id] != '') {
                $activeMaterialCond .= " AND ( ";
                for ($i = 1; $i < 32; $i++) {
                    $active = "active_$i";
                    $activeMaterialCond .= $i == 1 ? "" : " OR ";
                    $activeMaterialCond .= " $active='$_POST[active_material_id]' ";
                }
                $activeMaterialCond .= " ) ";
            }

            $query = mysql_query("SELECT $selectMedics FROM medics "
                    . $innerJoin
                    . "WHERE $medicNameCond $mainGroupCond $subGroupCond $activeMaterialCond "
                    . "$priceCond $producerCond "
                    . "GROUP BY medic_id LIMIT 50");

            $response['medics'] = array();
            while ($row = mysql_fetch_assoc($query)) {
                $response['medics'][] = $row;
            }
            break;

        case "get_data":
            $query = mysql_query("SELECT * FROM main_groups");
            while ($row = mysql_fetch_assoc($query)) {
                $response['main_groups'][] = $row;
            }

            $query = mysql_query("SELECT * FROM sub_groups");
            while ($row = mysql_fetch_assoc($query)) {
                $response['sub_groups'][] = $row;
            }

            $query = mysql_query("SELECT * FROM active_materials");
            while ($row = mysql_fetch_assoc($query)) {
                $response['active_materials'][] = $row;
            }

            $query = mysql_query("SELECT * FROM producers");
            while ($row = mysql_fetch_assoc($query)) {
                $response['producers'][] = $row;
            }
            break;

        case "get_ads":
            $query = mysql_query("SELECT * FROM ads WHERE ad_type='$_POST[adType]'");
            while ($row = mysql_fetch_assoc($query)) {
                $response['ads'][] = $row;
            }
            break;

        case "register":
            $device_already_exist = mysql_result(mysql_query("SELECT COUNT(*) FROM users WHERE registered_device='$_POST[imei]'"), 0);
            $email_already_exist = mysql_result(mysql_query("SELECT COUNT(*) FROM users WHERE email='$_POST[email]'"), 0);

            $facebookId = $_POST['facebook_id'] != "" ? md5($_POST['facebook_id']) : "";
            $pass = $_POST['pass'] != "" ? md5($_POST['pass']) : "";

            if ($facebookId != "") {
                $user_already_exist = mysql_result(mysql_query("SELECT COUNT(*) FROM users WHERE facebook_id='$facebookId'"), 0);
            } else {
                $user_already_exist = mysql_result(mysql_query("SELECT COUNT(*) FROM users WHERE user_name='$_POST[user_name]'"), 0);
            }

            if ($device_already_exist > 0) {
                $response = "device registered before";
            } else if ($user_already_exist > 0) {
                $response = "user already exists";
            } else if ($email_already_exist > 0) {
                $response = "email already exists";
            } else {
                if ($_POST['is_paid'] == 1) {
                    $expireDate = date("Y-m-d", strtotime("+2 years"));
                    $user_group_id = 4;
                    $is_paid_to_google = 1;
                } else {
                    $expireDate = date("Y-m-d", strtotime("+3 months"));
                    $user_group_id = 1;
                    $is_paid_to_google = 0;
                }

                $query = mysql_query("INSERT INTO users (first_name, last_name, user_name, password, "
                        . " mobile_num, email, expire_date, is_active, facebook_id, registered_device, "
                        . " user_group_id, registration_date, is_paid_to_google) "
                        . " VALUES ('$_POST[first_name]', '$_POST[last_name]', '$_POST[user_name]', '$pass', "
                        . " '$_POST[mobile_num]', '$_POST[email]', '$expireDate', '1', '$facebookId', "
                        . " '$_POST[imei]', '$user_group_id', NOW(), '$is_paid_to_google') ");

                if (mysql_error() != "") {
                    $response = "registration error";
                } else {
                    $user_id = mysql_insert_id();
                    $afterLoginQuery = mysql_query("UPDATE users SET last_login_date=NOW(), last_login_from='$_POST[imei]', is_logged_in='1' WHERE id='$user_id' ");
                    $response = $user_id;
                }
            }
            break;

        case "login":
            if ($_POST['facebook_id'] != "") {
                $facebookId = md5($_POST['facebook_id']);
                $query = mysql_query("SELECT * FROM users WHERE facebook_id='$facebookId'");
            } else {
                $pass = md5($_POST['pass']);
                $query = mysql_query("SELECT * FROM users WHERE user_name='$_POST[user_name]' AND password='$pass'");
            }

            if (mysql_num_rows($query) > 0) {
                while ($row = mysql_fetch_assoc($query)) {
                    if ($row['is_active'] != '1') {
                        $response = "user deactivated";
                    } else if (strtotime($row['expire_date']) < strtotime(date("Y-m-d"))) {
                        $response = "user expired";
                    } else {
                        // first time when user logs in from a paid application
                        if ($_POST['is_paid'] == 1 && $row['is_paid_to_google'] != 1) {
                            $expireDate = date("Y-m-d", strtotime("+2 years"));
                            $updateExpireDateQuery = mysql_query("UPDATE users SET expire_date='$expireDate', user_group_id='4', is_paid_to_google='1' WHERE id='$row[id]'");
                        }

                        $afterLoginQuery = mysql_query("UPDATE users SET last_login_date=NOW(), last_login_from='$_POST[imei]', is_logged_in='1' WHERE id='$row[id]'");
                        $response = $row[id];
                    }
                }
            } else {
                $response = "invalid user or pass";
            }
            break;

        case "forgot_password":
            $usersCnt = mysql_result(mysql_query("SELECT COUNT(*) FROM users WHERE email='$_POST[email]' AND password != '' "), 0);
            $response['feedback'] = array();
            if ($usersCnt > 0) {
                $newPass = random_password(8);
                mysql_query("UPDATE users SET password='" . md5($newPass) . "' WHERE email='$_POST[email]'");
                if (!mysql_error()) {
                    // Send email with the new password
                    $query = mysql_query("SELECT * FROM users WHERE email='$_POST[email]'");
                    while ($row = mysql_fetch_assoc($query)) {
                        $response['feedback'] = sendMail($_POST[email], $row['first_name'], $row['last_name'], $row['user_name'], $newPass);
                    }
                }
            } else {
                $response['feedback'] = "Email not found";
            }
            break;

        default: break;
    }
}

function sendMail($emailTo, $firstName, $lastName, $userName, $pass) {
    require 'sendMail/PHPMailerAutoload.php';
    $mail = new PHPMailer();
    $mail->IsSMTP();
    $mail->CharSet = 'UTF-8';
    $mail->Host = "mail.egyptiandrugatlas.com"; // SMTP server example
    $mail->SMTPDebug = 0;                     // enables SMTP debug information (for testing)
    $mail->Timeout = 60;
    $mail->SMTPAuth = true;                  // enable SMTP authentication
    $mail->Port = 25;                    // set the SMTP port for the GMAIL server
    $mail->Username = "support_email@egyptiandrugatlas.com"; // SMTP account username example
    $mail->Password = ",LQr@uOr_&ET";        // SMTP account password example
    $mail->setFrom('support_email@egyptiandrugatlas.com', 'Atlas drug index');
    $mail->addAddress($emailTo, "$firstName $lastName");
    $mail->Subject = 'Reset password';
    $mail->msgHTML("Dear $firstName $lastName <br />According to your request, your password has been reset. <br />Your user name and password are as follows:<br />User: $userName <br />Password: $pass <br /> Best regards, <br />Atlas drug index - http://egyptiandrugatlas.com/");
    $mail->AltBody = 'This is a plain-text message body';
    //send the message, check for errors
    if (!$mail->send()) {
        return "Mailer Error";
    } else {
        return "Message sent";
    }
}

function login_check() {
    switch ($_POST['request_type']) {
        case "quick_search":
        case "alternatives":
        case "advanced_search":
        case "get_data":
        case "get_ads":
            list ($last_login_from, $expire_date, $is_active) = mysql_fetch_array(mysql_query("SELECT last_login_from, expire_date, is_active FROM users WHERE id='$_POST[user_id]'"), 0);
            if ($is_active != '1') {
                return false;
            } else if (strtotime($expire_date) < strtotime(date("Y-m-d"))) {
                return false;
            } else {
                return (($last_login_from == $_POST['imei']) && ($_POST['user_id'] != "") && ($_POST['imei'] != ""));
            }
            break;

        case "register":
        case "login":
        case "forgot_password":
            return true;
            break;

        default:
            return false;
            break;
    }
}

function final_format($response) {
    switch ($_POST['request_type']) {
        case "quick_search":
        case "alternatives":
        case "advanced_search":
            if (count($response['medics']) > 0) {
                $activesJoin = "";
                $concentrationsJoin = "";
                $activesSelect = "";
                $concentrationsSelect = "";
                for ($i = 1; $i < 31; $i++) {
                    $activesJoin .= " LEFT JOIN active_materials a_$i ON a_$i.id = active_$i ";
                    $concentrationsJoin .= " LEFT JOIN concentrations c_$i ON c_$i.id = concentration_$i ";

                    $activesSelect .= " a_$i.id AS id_$i, a_$i.active_name AS active_name_$i, "
                            . " a_$i.medscape_url AS medscape_url_$i, a_$i.drugs_url AS drugs_url_$i, "
                            . " a_$i.comments AS comments_$i, ";

                    $concentrationsSelect .= " c_$i.concentration AS concentration_$i, ";
                }
                $activesSelect = trim($activesSelect, ', ');
                $concentrationsSelect = trim($concentrationsSelect, ', ');

                foreach ($response['medics'] as $key => $value) {
                    $query = mysql_query("SELECT $activesSelect, $concentrationsSelect "
                            . "         FROM materials "
                            . "         $activesJoin $concentrationsJoin "
                            . "         WHERE medic_id='$value[medic_id]'");

                    $active_materials = array();
                    $active_materials_concatenation = "";
                    while ($row = mysql_fetch_assoc($query)) {
                        for ($i = 1; $i < 32; $i++) {
                            if ($row["id_$i"] != "") {
                                $arr = array();
                                $arr['id'] = $row["id_$i"];
                                $arr['active_name'] = $row["active_name_$i"];
                                $arr['medscape_url'] = $row["medscape_url_$i"];
                                $arr['drugs_url'] = $row["drugs_url_$i"];
                                $arr['comments'] = $row["comments_$i"];
                                $arr['concentration'] = $row["concentration_$i"];
                                $active_materials[] = $arr;
                                $active_materials_concatenation .= $row["active_name_$i"] != "" ? $row["active_name_$i"] : "";
                                $active_materials_concatenation .= $row["concentration_$i"] != "" ? (": " . $row["concentration_$i"]) : "";
                                $active_materials_concatenation .= ", ";
                            }
                        }
                    }

                    $response['medics'][$key]['active_materials_concatenation'] = trim($active_materials_concatenation, ", ");
                    $response['medics'][$key]['active_materials'] = $active_materials;
                }
                return $response;
            } else {
                return $response;
            }
            break;

        default:
            return $response;
            break;
    }
}

function random_password($length = 8) {
    $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_-=+;:,.?";
    $password = substr(str_shuffle($chars), 0, $length);
    return $password;
}

$responseFormated = final_format($response);
$responseJSON = json_encode($responseFormated);
header('Content-Type: application/json; charset=utf-8');
echo $responseJSON;
?>