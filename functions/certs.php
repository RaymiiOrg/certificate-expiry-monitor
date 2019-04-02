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


function get_raw_chain($host,$port=443) {
  global $timeout;
  $data = [];
  $stream = stream_context_create (array("ssl" => 
    array("capture_peer_cert" => true,
    "capture_peer_cert_chain" => true,
    "verify_peer" => false,
    "peer_name" => $host,
    "verify_peer_name" => false,
    "allow_self_signed" => true,
    "sni_enabled" => true)));
  $read_stream = stream_socket_client("ssl://$host:$port", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $stream);
  if ( $read_stream === false ) {
    return false;
  } else {
    $context = stream_context_get_params($read_stream);
    $context_meta = stream_get_meta_data($read_stream)["crypto"];
    $cert_data = openssl_x509_parse($context["options"]["ssl"]["peer_certificate"]);
    $chain_data = $context["options"]["ssl"]["peer_certificate_chain"];
    $chain_length = count($chain_data);
    if (isset($chain_data) && $chain_length < 10) {
      foreach($chain_data as $key => $value) {
        $data["chain"][$key] = $value;
      } 
    } else {
      $data["error"] = ["Chain too long."];
      return $data;
    }
  }
  return $data;
}

function cert_expiry_date($raw_cert_data) {
  $cert_data = openssl_x509_parse($raw_cert_data);
  if (!empty($cert_data['validTo_time_t'])) { 
    return(strtotime(date(DATE_RFC2822,$cert_data['validTo_time_t'])));
  } else {
    return false;
  }
}

function cert_valid_from($raw_cert_data) {
  $cert_data = openssl_x509_parse($raw_cert_data);
  if (!empty($cert_data['validFrom_time_t'])) { 
    return(strtotime(date(DATE_RFC2822,$cert_data['validFrom_time_t'])));
  } else {
    return false;
  }
}

function cert_serial($raw_cert_data) {
  $cert_data = openssl_x509_parse($raw_cert_data);
  if ( isset($cert_data['serialNumber']) ) { 
    $serial = [];
    $sn = str_split(strtoupper(bcdechex($cert_data['serialNumber'])), 2);
    $sn_len = count($sn);
    foreach ($sn as $key => $s) {
      $serial[] = htmlspecialchars($s);
      if ( $key != $sn_len - 1) {
        $serial[] = ":";
      }
    }
    $result = implode("", $serial);
    return $result;
  }
}

function cert_cn($raw_cert_data) {
  $cert_data = openssl_x509_parse($raw_cert_data);
  if (!empty($cert_data['subject']['CN'])) { 
    return($cert_data['subject']['CN']);
  } else {
    return false;
  }
}

function cert_subject($raw_cert_data) {
  $cert_data = openssl_x509_parse($raw_cert_data);
  if (!empty($cert_data['name'])) { 
    return($cert_data['name']);
  } else {
    return false;
  }
}

function cert_expiry($raw_cert) {
  $result = array();
  $today = strtotime(date("Y-m-d"));
  $cert_expiry_date = cert_expiry_date($raw_cert);
  $cert_expiry_date = strtotime(date("Y-m-d",$cert_expiry_date));
  // expired 
  if ($today < $cert_expiry_date) {
    $result['cert_expired'] = false;
  } else {
    $result['cert_expired'] = true;
    $result['cert_time_expired'] = $today - $cert_expiry_date;
  }
  if ( $result['cert_expired'] == false ) {
    $cert_expiry_diff = $cert_expiry_date - $today;
    $result['cert_time_to_expiry'] = $cert_expiry_diff;
  }
  return $result;
}


function cert_expiry_emails($domain, $email, $cert_expiry, $raw_cert) {
  if ($cert_expiry['cert_expired'] === true) {
    switch ($cert_expiry['cert_time_expired']) {
      case "0":
        # 0 days...
        send_cert_expired_email(1, $domain, $email, $raw_cert);
        break;
      case "84600":
        # 1 days...
        send_cert_expired_email(1, $domain, $email, $raw_cert);
        break;
      case "172800":
        # 2 days...
        send_cert_expired_email(2, $domain, $email, $raw_cert);
        break;
      case "604800":
        # 7 days...
        send_cert_expired_email(7, $domain, $email, $raw_cert);
        break;
      // default:
      //   send_cert_expired_email($cert_expiry['cert_time_expired']/24/60/60, $domain, $email, $raw_cert);
      //   break;
      }
    
  } else {
    switch ($cert_expiry['cert_time_to_expiry']) {
      case "7776000":
        # 90 days...
        send_expires_in_email(90, $domain, $email, $raw_cert);
        break;
      case "5184000":
        # 60 days...
        send_expires_in_email(60, $domain, $email, $raw_cert);
        break;
      case "2592000":
        # 30 days...
        send_expires_in_email(30, $domain, $email, $raw_cert);
        break;
      case "1209600":
        # 14 days...
        send_expires_in_email(14, $domain, $email, $raw_cert);
        break;
      case "604800":
        # 7 days...
        send_expires_in_email(7, $domain, $email, $raw_cert);
        break;
      case "432000":
        # 5 days...
        send_expires_in_email(5, $domain, $email, $raw_cert);
        break;
      case "259200":
        # 3 days...
        send_expires_in_email(3, $domain, $email, $raw_cert);
        break;
      case "172800":
        # 2 days...
        send_expires_in_email(2, $domain, $email, $raw_cert);
        break;
      case "86400":
        # 1 days...
        send_expires_in_email(1, $domain, $email, $raw_cert);
        break;
      case "0":
        # 0 days...
        send_expires_in_email(0, $domain, $email, $raw_cert);
        break;
    }
  }
}

?>