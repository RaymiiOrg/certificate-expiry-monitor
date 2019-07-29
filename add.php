<?php
// Copyright (C) 2019 Remy van Elst

// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU Affero General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU Affero General Public License for more details.

// You should have received a copy of the GNU Affero General Public License
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

error_reporting(E_ALL & ~E_NOTICE);
foreach (glob("functions/*.php") as $filename) {
  require($filename);
}

require('inc/header.php');

echo "<div class='content'><section id='result'>";

if ( isset($_POST['email']) && !empty($_POST['email']) && isset($_POST['domains']) && !empty($_POST['domains']) ) {

  $errors = array();
  if (validate_email($_POST['email'])) {
    $email = htmlspecialchars($_POST['email']);
  } else {
    $errors[] = "Invalid email address.";
  }

  $domains = validate_domains($_POST['domains']);
  if ( count($domains['errors']) >= 1 ) {
    foreach ($domains['errors'] as $key => $value) {
      $errors[] = $value;
    }
  } 
  
  if (is_array($errors) && count($errors) != 0) {
    $errors = array_unique($errors);
    foreach ($errors as $key => $value) {
      echo "<div class='alert alert-danger' role='alert'>";
      echo htmlspecialchars($value);
      echo "</div>";
    }
    echo "Please return and try again.<br>";
  } elseif ( is_array($errors) && count($errors) == 0 && is_array($domains['domains']) && count($domains['domains']) != 0 && count($domains['domains']) < 21) {
    echo "<div class='alert alert-info' role='alert'>";
    echo "Email: " . htmlspecialchars($email) . ".<br>";
    echo "</div>";
    foreach ($domains['domains'] as $key => $value) {
      $userip = $_SERVER["HTTP_X_FORWARDED_FOR"] ? $_SERVER["HTTP_X_FORWARDED_FOR"] : $_SERVER["REMOTE_ADDR"];
      $add_domain = add_domain_to_pre_check($value, $email, $userip);
      if (is_array($add_domain["errors"]) && count($add_domain["errors"]) != 0) {
        $errors = array_unique($add_domain["errors"]);
        foreach ($add_domain["errors"] as $key => $err_value) {
          echo "<div class='alert alert-danger' role='alert'>";
          echo htmlspecialchars($err_value);
          echo "</div>";
        }
      } else {
        echo "<div class='alert alert-success' role='alert'>";
        echo "Confirmation email sent. Please confirm your subscription email to complete the process.<br>";
        echo "</div>";
      }
    }
  } else {
    echo "<div class='alert alert-danger' role='alert'>";
    echo "Too many domains.<br>";
    echo "Please return and try again.<br>";
    echo "</div>";
  }
} else {

  echo "<div class='alert alert-danger' role='alert'>";;
  echo "Error. Domain(s) and email address are required.<br>";
  echo "Please return and try again.<br>";
  echo "</div>";
}


require('inc/faq.php');

require('inc/footer.php');

?>
