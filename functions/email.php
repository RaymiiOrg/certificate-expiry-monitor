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

function validate_email($email) {
  if (!filter_var(mb_strtolower($email), FILTER_VALIDATE_EMAIL)) {
    return false;
  } else {
    return true;
  }
}

function send_error_mail($domain, $email, $errors) {
  echo "\t\tSending error mail to $email for $domain.\n";
  global $current_domain;
  global $check_file;
  $domain = trim($domain);
  $errors = implode("\r\n", $errors);
  $json_file = file_get_contents($check_file);
  if ($check_file === FALSE) {
      echo "\t\tCan't open database.\n";
      return false;
  }
  $json_a = json_decode($json_file, true);
  if ($json_a === NULL || json_last_error() !== JSON_ERROR_NONE) {
      echo "\t\tCan't read database.\n";
      return false;
  }

  foreach ($json_a as $key => $value) {
    if ($value["domain"] == $domain && $value["email"] == $email) {
      $id = $key;
      $failures = $value['errors'];
      $unsublink = "https://" . $current_domain . "/unsubscribe.php?id=" . $id;
      $to      = $email;
      $subject = "Certificate monitor " . htmlspecialchars($domain) . " failed.";
      $message = "Hello,\r\n\r\nYou have a subscription to monitor the certificate of " . htmlspecialchars($domain) . " with the the Certificate Expiry Monitor. This is a service which monitors an SSL certificate on a website, and notifies you when it is about to expire. This extra notification helps you remember to renew your certificate on time.\r\n\r\nWe've noticed that the check for the following domain has failed: \r\n\r\nDomain: " . htmlspecialchars($domain) . "\r\nError(s): " . htmlspecialchars($errors) . "\r\n\r\nFailure(s): " . htmlspecialchars($failures) . "\r\n\r\nPlease check this website or it's certificate. If the check fails 7 times we will remove it from our monitoring. If the check succeeds again within 7 failures, the failure count will reset.\r\n\r\nTo unsubscribe from notifications for this domain please click or copy and paste the below link in your browser:\r\n\r\n" . $unsublink . "\r\n\r\n\r\n Have a nice day,\r\nThe Certificate Expiry Monitor Service.\r\nhttps://" . $current_domain . "";
      $message = wordwrap($message, 70, "\r\n");
      $headers = 'From: noreply@' . $current_domain . "\r\n" .
          'Reply-To: noreply@' . $current_domain . "\r\n" .
          'Return-Path: noreply@' . $current_domain . "\r\n" .
          'X-Visitor-IP: ' . $visitor_ip . "\r\n" .
          'X-Coffee: Black' . "\r\n" .
          'List-Unsubscribe: <https://' . $current_domain . "/unsubscribe.php?id=" . $id . ">" . "\r\n" .
          'X-Mailer: PHP/4.1.1';  

      if (mail($to, $subject, $message, $headers) === true) {
          echo "\t\tEmail sent to $to.\n";
          return true;
      } else {
          echo "\t\tCan't send email.\n";
          return false;
      }
    } 
  }
}

function send_cert_expired_email($days, $domain, $email, $raw_cert) {
  global $current_domain;
  global $check_file;
  $domain = trim($domain);
  echo "\t\tDomain " . $domain . " expired " . $days . " ago.\n";

  $file = file_get_contents($check_file);
  if ($file === FALSE) {
      echo "\t\tCan't open database.\n";
      return false;
  }
  $json_a = json_decode($file, true);
  if ($json_a === null && json_last_error() !== JSON_ERROR_NONE) {
      echo "\t\tCan't read database.\n";
      return false;
  }

  foreach ($json_a as $key => $value) {

    if ($value["domain"] == $domain && $value["email"] == $email) {

      $id = $key;
      $cert_cn = cert_cn($raw_cert);
      $cert_subject = cert_subject($raw_cert);
      $cert_serial = cert_serial($raw_cert);
      $cert_expiry_date = cert_expiry_date($raw_cert);
      $cert_validfrom_date = cert_valid_from($raw_cert);

      $now = time();
      $datefromdiff = $now - $cert_validfrom_date;
      $datetodiff = $now - $cert_expiry_date;
      $cert_valid_days_ago = floor($datefromdiff/(60*60*24));
      $cert_valid_days_ahead = floor($datetodiff/(60*60*24));

      $unsublink = "https://" . $current_domain . "/unsubscribe.php?id=" . $id;

      $to      = $email;
      $subject = "A certificate for " . htmlspecialchars($domain) . " expired " . htmlspecialchars($days) . " days ago";
      $message = "Hello,\r\n\r\nYou have a subscription to monitor the certificate of " . htmlspecialchars($domain) . " with the the Certificate Expiry Monitor. This is a service which monitors an SSL certificate on a website, and notifies you when it is about to expire. This extra notification helps you remember to renew your certificate on time.\r\n\r\nWe've noticed that the following domain has a certificate in it's chain that has expired " . htmlspecialchars($days) . " days ago:\r\n\r\nDomain: " . htmlspecialchars($domain) . "\r\nCertificate Common Name: " . htmlspecialchars($cert_cn) . "\r\nCertificate Subject: " . htmlspecialchars($cert_subject) . "\r\nCertificate Serial: " . htmlspecialchars($cert_serial) . "\r\nCertificate Valid From: " . htmlspecialchars(date("Y-m-d  H:i:s T", $cert_validfrom_date)) . " (" . $cert_valid_days_ago . " days ago)\r\nCertificate Valid Until: " . htmlspecialchars(date("Y-m-d  H:i:s T", $cert_expiry_date)) . " (" . $cert_valid_days_ahead . " days ago)\r\n\r\nYou should renew and replace your certificate right now. If you haven't set up the certificate yourself, please forward this email to the person/company that did this for you.\r\n\rThis website is now  non-functional and displays errors to it's users. Please fix this issue as soon as possible.\r\n\r\nTo unsubscribe from notifications for this domain please click or copy and paste the below link in your browser:\r\n\r\n" . $unsublink . "\r\n\r\n\r\n Have a nice day,\r\nThe Certificate Expiry Monitor Service.\r\nhttps://" . $current_domain . "";
      $message = wordwrap($message, 70, "\r\n");
      $headers = 'From: noreply@' . $current_domain . "\r\n" .
          'Reply-To: noreply@' . $current_domain . "\r\n" .
          'Return-Path: noreply@' . $current_domain . "\r\n" .
          'X-Visitor-IP: ' . $visitor_ip . "\r\n" .
          'X-Coffee: Black' . "\r\n" .
          'List-Unsubscribe: <https://' . $current_domain . "/unsubscribe.php?id=" . $id . ">" . "\r\n" .
          'X-Mailer: PHP/4.1.1';  

      if (mail($to, $subject, $message, $headers) === true) {
          echo "\t\tEmail sent to $to.\n";
          return true;
      } else {
          echo "\t\tCan't send email.\n";
          return false;
      }
    } 
  }
  
}

function send_expires_in_email($days, $domain, $email, $raw_cert) {
  global $current_domain;
  global $check_file;
  $domain = trim($domain);
  echo "\t\tDomain " . $domain . " expires in " . $days . " days.\n";

  $file = file_get_contents($check_file);
  if ($file === FALSE) {
      echo "\t\tCan't open database.\n";
      return false;
  }
  $json_a = json_decode($file, true);
  if ($json_a === null && json_last_error() !== JSON_ERROR_NONE) {
      echo "\t\tCan't read database.\n";
      return false;
  }

  foreach ($json_a as $key => $value) {

    if ($value["domain"] == $domain && $value["email"] == $email) {

      $id = $key;
      $cert_cn = cert_cn($raw_cert);
      $cert_subject = cert_subject($raw_cert);
      $cert_serial = cert_serial($raw_cert);
      $cert_expiry_date = cert_expiry_date($raw_cert);
      $cert_validfrom_date = cert_valid_from($raw_cert);

      $now = time();
      $datefromdiff = $now - $cert_validfrom_date;
      $datetodiff = $cert_expiry_date - $now;
      $cert_valid_days_ago = floor($datefromdiff/(60*60*24));
      $cert_valid_days_ahead = floor($datetodiff/(60*60*24));

      $unsublink = "https://" . $current_domain . "/unsubscribe.php?id=" . $id;

      $to      = $email;
      $subject = "A certificate for " . htmlspecialchars($domain) . " expires in " . htmlspecialchars($days) . " days";
      $message = "Hello,\r\n\r\nYou have a subscription to monitor the certificate of " . htmlspecialchars($domain) . " with the the Certificate Expiry Monitor. This is a service which monitors an SSL certificate on a website, and notifies you when it is about to expire. This extra notification helps you remember to renew your certificate on time.\r\n\r\nWe've noticed that the following domain has a certificate in it's chain that will expire in " . htmlspecialchars($days) . " days:\r\n\r\nDomain: " . htmlspecialchars($domain) . "\r\nCertificate Common Name: " . htmlspecialchars($cert_cn) . "\r\nCertificate Subject: " . htmlspecialchars($cert_subject) . "\r\nCertificate Serial: " . htmlspecialchars($cert_serial) . "\r\nCertificate Valid From: " . htmlspecialchars(date("Y-m-d  H:i:s T", $cert_validfrom_date)) . " (" . $cert_valid_days_ago . " days ago)\r\nCertificate Valid Until: " . htmlspecialchars(date("Y-m-d  H:i:s T", $cert_expiry_date)) . " (" . $cert_valid_days_ahead . " days left)\r\n\r\nYou should renew and replace your certificate before it expires. If you haven't set up the certificate yourself, please forward this email to the person/company that did this for you.\r\n\r\nNot replacing your certificate before the expiry date will result in a non-functional website with errors.\r\n\r\nTo unsubscribe from notifications for this domain please click or copy and paste the below link in your browser:\r\n\r\n" . $unsublink . "\r\n\r\n\r\n Have a nice day,\r\nThe Certificate Expiry Monitor Service.\r\nhttps://" . $current_domain . "";
      $message = wordwrap($message, 70, "\r\n");
      $headers = 'From: noreply@' . $current_domain . "\r\n" .
          'Reply-To: noreply@' . $current_domain . "\r\n" .
          'Return-Path: noreply@' . $current_domain . "\r\n" .
          'X-Visitor-IP: ' . $visitor_ip . "\r\n" .
          'X-Coffee: Black' . "\r\n" .
          'List-Unsubscribe: <https://' . $current_domain . "/unsubscribe.php?id=" . $id . ">" . "\r\n" .
          'X-Mailer: PHP/4.1.1';  

      if (mail($to, $subject, $message, $headers) === true) {
          echo "\t\tEmail sent to $to.\n";
          return true;
      } else {
          echo "\t\tCan't send email.\n";
          return false;
      }
    } 
  }
}


?>