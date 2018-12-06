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

echo "<div class='content'><section id='result'>";

if ($showListOfDomains) {  
  $listOfChecks = get_domain_checks();
  
  if ($listOfChecks == null) {
    echo "<div class='alert alert-danger' role='alert'>";
    echo "Unable to connect to DB.<br>";
    echo "</div>";
  } else {
    echo '<div class="table-responsive"><table class="table-hover table"><thead><tr><th>Domain</th><th>Errors</th><th>Email</th>' . ($showClickToUnsubscribeOnListOfDomains ? '<th>Email</th>' : '') . '</tr></thead><tbody>';
    foreach ($listOfChecks as $key => $value) {
      echo '<tr class="' . ($listOfChecks[$key]["errors"] > 0 ? 'danger' : '') . '"><td>' . $listOfChecks[$key]["domain"] . '</td><td>' . $listOfChecks[$key]["errors"] . '</td><td>' . ($showEmailsOnListOfDomains ? $listOfChecks[$key]["email"] : '****') . ($showClickToUnsubscribeOnListOfDomains ? '<td><a href="/unsubscribe.php?id=' . $key . '" >Remove from checks</a></td>' : '') . '</td></tr>';
    }
    echo '</tbody></table></div>';
  }  
} else {
  echo "<div class='alert alert-warning' role='alert'>";;
  echo "Listing domains has been disabled on this server<br>";
  echo "</div>";
}

require('inc/footer.php');

?>
