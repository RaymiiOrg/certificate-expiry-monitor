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

function add_domain_check($id,$visitor_ip) {
    global $current_domain;
    global $pre_check_file;
    global $check_file;
    $result = array();

    $pre_check_json_file = file_get_contents($pre_check_file);
    if ($file === FALSE) {
        $result['errors'][] = "Can't open database.";
        return $result;
    }
    $pre_check_json_a = json_decode($pre_check_json_file, true);
    if ($pre_check_json_a === null && json_last_error() !== JSON_ERROR_NONE) {
        $result['errors'][] = "Can't read database: " . htmlspecialchars(json_last_error());
        return $result;
    }

    if (!is_array($pre_check_json_a[$id]) ) {
      $result['errors'][] = "Can't find record in database for: " . htmlspecialchars($id);
        return $result;
    }

    $file = file_get_contents($check_file);
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
      if ($key == $id) {
          $result['errors'][] = "Domain/email combo for  " . htmlspecialchars($pre_check_json_a[$id]['domain']) . " already exists.";
          return $result;
      }
      if ($value["domain"] == $pre_check_json_a[$id]['domain'] && $value["email"] == $pre_check_json_a[$id]['email']) {
          $result['errors'][] = "Domain / email combo for  " . htmlspecialchars($pre_check_json_a[$id]['domain']) . " already exists.";
          return $result;
      }
    }

    $domains = validate_domains($pre_check_json_a[$id]['domain']);
    if (count($domains['errors']) >= 1 ) {
      $result['errors'][] = $domains['errors'];
      return $result;
    } 

    $json_a[$id] = array("domain" => $pre_check_json_a[$id]['domain'],
        "email" => $pre_check_json_a[$id]['email'],
        "errors" => 0,
        "visitor_pre_register_ip" => $pre_check_json_a[$id]['visitor_pre_register_ip'],
        "pre_add_date" => $pre_check_json_a[$id]['pre_add_date'],
        "visitor_confirm_ip" => $visitor_ip,
        "confirm_date" => time());

    $json = json_encode($json_a); 
    if(file_put_contents($check_file, $json, LOCK_EX)) {
        $result['success'][] = true;
    } else {
        $result['errors'][] = "Can't write database.";
        return $result;
    }

    unset($pre_check_json_a[$id]);
    $pre_check_json = json_encode($pre_check_json_a); 
    if(file_put_contents($pre_check_file, $pre_check_json, LOCK_EX)) {
        $result['success'][] = true;
    } else {
        $result['errors'][] = "Can't write database.";
        return $result;
    }

    $unsublink = "https://" . $current_domain . "/unsubscribe.php?id=" . $id;

    $to      = $json_a[$id]['email'];
    $subject = "Certificate Expiry Monitor subscription confirmed for " . htmlspecialchars($json_a[$id]['domain']) . ".";
    $message = "Hello,

Someone, hopefully you, has confirmed the subscription of their website to the Certificate Expiry Monitor. This is a service which monitors an SSL certificate on a website, and notifies you when it is about to expire. This extra notification helps you remember to renew your certificate on time.
  
Domain : " . trim(htmlspecialchars($json_a[$id]['domain'])) . "
Email  : " . trim(htmlspecialchars($json_a[$id]['email'])) . "
IP subscription confirmed from: " . htmlspecialchars($visitor_ip) . "
Date subscribed confirmed: " . date("Y-m-d H:i:s T") . "

We will monitor the certificates for this website. You will receive emails when it is about to expire as described in the FAQ on our website. You can view the FAQ here: https://" . $current_domain . ".

To unsubscribe from notifications for this domain please click or copy and paste the below link in your browser:

  " . $unsublink . "

Have a nice day,
The Certificate Expiry Monitor Service.
https://" . $current_domain . "";
    $message = wordwrap($message, 70, "\r\n");
    $headers = 'From: noreply@' . $current_domain . "\r\n" .
        'Reply-To: noreply@' . $current_domain . "\r\n" .
        'Return-Path: noreply@' . $current_domain . "\r\n" .
        'X-Visitor-IP: ' . $visitor_ip . "\r\n" .
        'X-Coffee: Black' . "\r\n" .
        'List-Unsubscribe: <https://' . $current_domain . "/unsubscribe.php?id=" . $id . ">" . "\r\n" .
        'X-Mailer: PHP/4.1.1';

    

    if (mail($to, $subject, $message, $headers) === true) {
        $result['success'][] = true;
    } else {
        $result['errors'][] = "Can't send email.";
        return $result;
    }

    return $result;
}