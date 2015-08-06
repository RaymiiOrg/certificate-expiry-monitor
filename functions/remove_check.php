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

function remove_domain_check($id,$visitor_ip) {
    global $current_domain;
    global $check_file;
    global $deleted_check_file;
    $result = array();

    $deleted_check_json_file = file_get_contents($deleted_check_file);
    if ($file === FALSE) {
        $result['errors'][] = "Can't open database.";
        return $result;
    }
    $deleted_check_json_a = json_decode($deleted_check_json_file, true);
    if ($deleted_check_json_a === null && json_last_error() !== JSON_ERROR_NONE) {
        $result['errors'][] = "Can't read database: " . htmlspecialchars(json_last_error());
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

    if (!is_array($json_a[$id]) ) {
      $result['errors'][] = "Can't find record in database for: " . htmlspecialchars($id);
        return $result;
    }

    foreach ($json_a as $key => $value) {
      if ($key == $id) {
        $deleted_json_a[$id] = array("domain" => $json_a[$id]['domain'],
            "email" => $json_a[$id]['email'],
            "visitor_pre_register_ip" => $json_a[$id]['visitor_pre_register_ip'],
            "pre_add_date" => $json_a[$id]['pre_add_date'],
            "visitor_confirm_ip" => $json_a[$id]['visitor_confirm_ip'],
            "confirm_date" => $json_a[$id]['confirm_date'],
            "visitor_delete_ip" => $visitor_ip,
            "delete_date" => time(),
            );

        $deleted_json = json_encode($deleted_json_a); 
        if(file_put_contents($deleted_check_file, $deleted_json, LOCK_EX)) {
            $result['success'][] = true;
        } else {
            $result['errors'][] = "Can't write database.";
            return $result;
        }

        unset($json_a[$id]);
        $check_json = json_encode($json_a); 
        if(file_put_contents($check_file, $check_json, LOCK_EX)) {
            $result['success'][] = true;
        } else {
            $result['errors'][] = "Cannot write database.";
            return $result;
        }

        $link = "https://" . $current_domain . "/";

        $to      = $deleted_json_a[$id]['email'];
        $subject = "Certificate Expiry Monitor subscription removed for " . htmlspecialchars($deleted_json_a[$id]['domain']) . ".";
        $message = "Hello,\r\n\r\nYou have removed the subscription of a  website to the Certificate Expiry Monitor.\r\n\r\nDomain: " . trim(htmlspecialchars($deleted_json_a[$id]['domain'])) . "\r\nEmail: " . trim(htmlspecialchars($deleted_json_a[$id]['email'])) . "\r\nIP subscription removed from: " . htmlspecialchars($visitor_ip) . "\r\nDate subscribed removed: " . date("Y-m-d H:i:s") . "\r\n\r\nWe will not monitor this website any longer and you will not receive any emails whatsoever from us again for this domain. Do note that you might miss an expiring certificate.\r\n\r\nTo re-subscribe this domain please add it again on the Certificate Expiry Monitor website: \r\n\r\n  " . $link . "\r\n\r\nHave a nice day,\r\nThe Certificate Expiry Monitor Service.\r\nhttps://" . $current_domain . "";
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
  }
}