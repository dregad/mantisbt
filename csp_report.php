<?php
/**
 * Basic Content Security Policy report.
 *
 * Usage: enable reporting in CSP header, e.g.
 * Content-Security-Policy-Report-Only: report-uri /mantis/csp_report.php;
 *
 * Received CSP report requests are saved to
 */

const LOGFILE = '/tmp/csp_report.log';

setlocale( LC_ALL, 'en_US.UTF8' );
date_default_timezone_set( 'Europe/Berlin' );


if( $_SERVER['REQUEST_METHOD'] == 'GET' ) {
	header( 'Content-type: text/plain' );
	if( file_exists( LOGFILE ) ) {
		echo "CSP Report " . LOGFILE . "\n\n";
		echo file_get_contents( LOGFILE );
	} else {
		echo "CSP report does not exist.\n";
	}
	exit;
}

if( $_SERVER['CONTENT_TYPE'] !== 'application/csp-report' ) {
	http_response_code( 400 ); // Bad Request
	exit( "Invalid content type: " . $_SERVER['CONTENT_TYPE'] );
}

$data = file_get_contents( 'php://input' );

if( $data ) {
	$obj = json_decode( $data, true );
	if( json_last_error() === JSON_ERROR_NONE && isset( $obj['csp-report'] ) ) {
		$report = $obj['csp-report'];
		$fields = [
			"Document URI:\t\t" => "document-uri",
			"Status Code:\t\t" => "status-code",
			"Disposition:\t\t" => "disposition",
			"Effective Directive:\t" => "effective-directive",
			"Violated Directive:\t" => "violated-directive",
			"Original Policy:\t" => "original-policy",
			"Blocked URI:\t\t" => "blocked-uri",
			"Script Sample:\n" => "script-sample",
		];

		$log = "=============== " . date( "Y-m-d H:i:s" ) . " ===============\n";
		$log .= "Report type:\t\t" . $obj['type'] . "\n";
		foreach( $fields as $label => $key ) {
			if( isset( $report[$key] ) ) {
				$log .= "$label" . ( $report[$key] ) . "\n";
			}
		}

		$res = file_put_contents( LOGFILE, $log, FILE_APPEND );

		// Dumping the report's whole json for troubleshooting
		//file_put_contents( LOGFILE, var_export($obj, true), FILE_APPEND );

		if( $res === false ) {
			http_response_code( 500 );
			exit( "Could not write log file: " . $logFile );
		}
	} else {
		http_response_code( 400 ); // Bad Request
		exit( "Invalid JSON payload." );
	}
} else {
	http_response_code( 204 ); // No Content
	exit();
}

