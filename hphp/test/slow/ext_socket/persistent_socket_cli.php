<?php


$schemes = array("tcp", "tls", "ssl");
$ports = array(51000,51001,51002);

//    $sslclientcontext = stream_context_create();
//    stream_context_set_option($sslclientcontext, 'ssl', 'allow_self_signed', true);
//    stream_context_set_option($sslclientcontext, 'ssl', 'verify_peer', false);

for ( $n = 0; $n < 500000; $n++ ) {
foreach ($schemes as $i => $scheme) {
  $port = $ports[$i];

    echo "$n Testing $scheme://\n";
    test_client($scheme, $port);
}
}



function read_all_data( $conn, $bytes ) {
  $all_data = '';
  $data = '';

  // Loop until we read all the bytes we expected or we hit an error.
  stream_set_timeout($conn, 1);
  while( $bytes > 0 && $data = fread($conn, $bytes) ) {
    $bytes -= strlen($data);
    $all_data .= $data;
  }

  return $bytes == 0 ? $all_data : false;
}

function test_server($server) {
  // The server only accepts once, but the client will call
  // stream_socket_client multiple times with the persistent flag.
  if( $conn = stream_socket_accept($server) ) {
    $requests_remaining = 3;
    while( $requests_remaining > 0 ) {
      $requests_remaining--;
      $data = read_all_data($conn, 4);
      if( $data === false ) {
        break;
      }

      echo "Server received request: $data\n";

      // Send response back to the client
      fwrite($conn, "pong", 4);
    }
  }

  fclose($server);
}

function test_client($scheme, $port) {
  //global $sslclientcontext;

  /*if ($scheme != "tcp") {
    do_request($scheme, $port, $sslclientcontext);
    do_request($scheme, $port, $sslclientcontext);
    do_request($scheme, $port, $sslclientcontext);
  } else {*/
    do_request($scheme, $port, null);
    do_request($scheme, $port, null);
    do_request($scheme, $port, null);
  //}

}

function do_request($scheme, $port, $context) {
  $client = stream_socket_client(
    "$scheme://127.0.0.1:$port",
    $errno,
    $errstr,
    1.0,
    STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT
  );

  if( $client === FALSE ) {
    echo "Failed to connect to server $errstr\n";
  }

  echo "Sending request to server...\n";
  if( fwrite($client, "ping", 4) == 0 ) {
    echo "Failed writing to socket.\n";
  }

  $data = read_all_data($client, 4);
  if( $data === false ) {
    return false;
  }

  echo "Client received response: $data\n";

  return true;
}
