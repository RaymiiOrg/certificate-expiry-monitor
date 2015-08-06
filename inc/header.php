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

?>

<!doctype html>
<html lang="en">
<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $title;?></title>
  <link rel="stylesheet" href="<?php echo(htmlspecialchars($current_folder)); ?>css/bootstrap.min.css">
  <link rel="stylesheet" href="<?php echo(htmlspecialchars($current_folder)); ?>css/ssl.css">
</head>
<body>

<?php

echo "<div id='page-content-wrapper'>";
echo "<div class='container-fluid'>";
echo "<div class='row'>";

echo "<div class='col-md-10 col-md-offset-1'><div class='page-header'><h1>";
echo htmlspecialchars($title);
echo "</h1></div>";

?>