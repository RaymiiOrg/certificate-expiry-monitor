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
// along with this program.  If not, see <http://www.gnu.org/licenses/>.

error_reporting(E_ALL & ~E_NOTICE);
foreach (glob("functions/*.php") as $filename) {
  require($filename);
}

require('inc/header.php');

if ( isset($_GET['id']) && !empty($_GET['id'])  ) {
  $id = htmlspecialchars($_GET['id']);
  $uuid_pattern = "/([a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12})/";
  if (preg_match($uuid_pattern, $id)) {
    $add_domain = add_domain_check($id, $_SERVER['REMOTE_ADDR']);
    if (is_array($add_domain["errors"]) && count($add_domain["errors"]) != 0) {
      $errors = array_unique($add_domain["errors"]);
      foreach ($add_domain["errors"] as $key => $err_value) {
        echo "<div class='alert alert-danger' role='alert'>";
        echo htmlspecialchars($err_value);
        echo "</div>";
      }
    } else {
      echo "<div class='alert alert-success' role='alert'>";
      echo "Check added. You will now receive notifications on certificate expiration events as described in the FAQ.<br>";
      echo "</div>";
    }
  } else {
      echo "<div class='alert alert-danger' role='alert'>";;
      echo "Error. ID is invalid.<br>";
      echo "Please return and try again.<br>";
      echo "</div>";
  }
} else {
  echo "<div class='alert alert-danger' role='alert'>";;
  echo "Error. ID is required.<br>";
  echo "Please return and try again.<br>";
  echo "</div>";
}

require('inc/faq.php');

require('inc/footer.php');

?>