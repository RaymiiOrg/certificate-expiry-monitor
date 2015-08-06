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

function validate_domains($domains) {
  $errors = array();
  $domains = explode("\n", $domains);
  $domains = array_map('strtolower', $domains);
  $domains = array_filter($domains);
  $domains = array_unique($domains);

  foreach ($domains as $key => $value) {
    $value = trim(mb_strtolower($value));
    // check if reasonably valid domain
    if ( !preg_match("/^([a-z\d](-*[a-z\d])*)(\.([a-z\d](-*[a-z\d])*))*$/i", $value) && !preg_match("/^.{1,253}$/", $value) && !preg_match("/^[^\.]{1,63}(\.[^\.]{1,63})*$/", $value) ) {
      $errors[] = "Invalid domain name: " . htmlspecialchars($value) . ".";
    }

    // check valid dns record
    $ips = dns_get_record($value, DNS_A + DNS_AAAA);
    sort($ips);
    if ( count($ips) >= 1 ) {
      if (!empty($ips[0]['type']) ) {
        if ($ips[0]['type'] === "AAAA") {
          $ip = $ips[0]['ipv6'];
          if( !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
            $errors[] = "Invalid domain AAAA record for: " . htmlspecialchars($value) . ".";
          }
        } elseif ($ips[0]['type'] === "A") {
          $ip = $ips[0]['ip'];
          if( !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
            $errors[] = "Invalid domain A record for: " . htmlspecialchars($value) . ".";
          }
        }
      } else {
        $errors[] = "No DNS A/AAAA records for: " . htmlspecialchars($value) . ".";
      }
    } else {
      $errors[] = "Error resolving domain: " . htmlspecialchars($value) . ".";
    }
  }

  if (is_array($errors) && count($errors) == 0) {
    foreach ($domains as $key => $value) {
      $raw_chain = get_raw_chain(trim($value));
      if (!$raw_chain) {
        $errors[] = "Domain has invalid or no certificate: " . htmlspecialchars($value) . ".";
      } else {
        foreach ($raw_chain['chain'] as $raw_key => $raw_value) {
          $cert_expiry = cert_expiry($raw_value);
          $cert_subject = cert_subject($raw_value);
          if ($cert_expiry['cert_expired']) {
            $errors[] = "Domain has expired certificate in chain: " . htmlspecialchars($value) . ". Cert Subject: " . htmlspecialchars($cert_subject) . ".";
          }
        }
      }
    }
  }


  if (is_array($errors) && count($errors) >= 1) {
    $result = array();
    foreach ($errors as $key => $value) {
      $result['errors'][] = $value;
    }
    return $result;
  } else {
    $result = array();
    foreach ($domains as $key => $value) {
      $result['domains'][] = $value;
    }
    return $result;
  }
}

?>