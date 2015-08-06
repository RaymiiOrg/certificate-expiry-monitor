<?php
// Copyright (C) 2015 Remy van Elst

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.

// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

function add_domain_to_pre_check($domain,$email,$visitor_ip) {
    global $current_domain;
    global $pre_check_file;
    global $check_file;
    $result = array();
    $domain = trim($domain);
    $email = trim($email);
    $file = file_get_contents($pre_check_file);
    if ($file === FALSE) {
        $result['errors'][] = "Can't open database.";
        return $result;
    }
    $json_a = json_decode($file, true);
    if ($json_a === null && json_last_error() !== JSON_ERROR_NONE) {
        $result['errors'][] = "Can't read database: " . htmlspecialchars(json_last_error());
        return $result;
    }

    foreach ($json_a as $key => $value) {
        if ($value["domain"] == $domain && $value["email"] == $email) {
            $result['errors'][] = "Domain/email combo for  " . htmlspecialchars($domain) . " already exists. Please confirm your subscription email.";
            return $result;
        }
    }

    $check_json_file = file_get_contents($check_file);
    if ($check_json_file === FALSE) {
        $result['errors'][] = "Can't open database.";
        return $result;
    }
    $check_json_a = json_decode($check_json_file, true);
    if ($check_json_a === null && json_last_error() !== JSON_ERROR_NONE) {
        $result['errors'][] = "Can't read database: " . htmlspecialchars(json_last_error());
        return $result;
    }

    foreach ($check_json_a as $key => $value) {
        if ($value["domain"] == $domain && $value["email"] == $email) {
            $result['errors'][] = "Domain / email combo for  " . htmlspecialchars($domain) . " already exists.";
            return $result;
        }
    }

    $uuid = gen_uuid();

    $json_a[$uuid] = array("domain" => $domain,
        "email" => $email,
        "visitor_pre_register_ip" => $visitor_ip,
        "pre_add_date" => time());

    $json = json_encode($json_a); 
    if(file_put_contents($pre_check_file, $json, LOCK_EX)) {
        $result['success'][] = true;
    } else {
        $result['errors'][] = "Can't write database.";
        return $result;
    }

    $sublink = "https://" . $current_domain . "/confirm.php?id=" . $uuid;

    $to      = $email;
    $subject = "Confirm your Certificate Expiry Monitor subscription for " . htmlspecialchars($domain) . ".";
    $message = "Hello,\r\n\r\nSomeone, hopefully you, has added his website to the Certificate Expiry Monitor. This is a service which monitors an SSL certificate on a website, and notifies you when it is about to expire. This extra notification helps you remember to renew your certificate on time.\r\n\r\nIf you have subscribed to this check, please click the link below to confirm this subscription. If you haven't subscribed to the Certificate Expiry Monitor service, please consider this message as not sent.\r\n\r\n\r\nDomain: " . trim(htmlspecialchars($domain)) . "\r\nEmail: " . trim(htmlspecialchars($email)) . "\r\nIP subscribed from: " . htmlspecialchars($visitor_ip) . "\r\nDate subscribed: " . date("Y-m-d H:i:s T") . "\r\n\r\nPlease click or copy and paste the below link in your browser to subscribe: \r\n\r\n" . $sublink . "\r\n\r\n\r\nHave a nice day,\r\nThe Certificate Expiry Monitor Service.";
    $message = wordwrap($message, 70, "\r\n");
    $headers = 'From: noreply@' . $current_domain . "\r\n" .
        'Reply-To: noreply@' . $current_domain . "\r\n" .
        'Return-Path: noreply@' . $current_domain . "\r\n" .
        'X-Visitor-IP: ' . $visitor_ip . "\r\n" .
        'X-Coffee: Black' . "\r\n" .
        'List-Unsubscribe: <https://' . $current_domain . "/unsubscribe.php?id=" . $uuid . ">" . "\r\n" .
        'X-Mailer: PHP/4.1.1';

    

    if (mail($to, $subject, $message, $headers) === true) {
        $result['success'][] = true;
    } else {
        $result['errors'][] = "Can't send email.";
        return $result;
    }

    return $result;
}