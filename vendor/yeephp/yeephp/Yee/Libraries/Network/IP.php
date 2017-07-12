<?php
namespace Yee\Libraries\Network;

class IP { 
	
	public static function server() {
		return isset( $_SERVER['SERVER_ADDR'] )	?	$_SERVER['SERVER_ADDR'] : '';
	}
	
	public function remote() {
		$ip = $this->checkIP();
		// usually when a reverse proxy is in place (such as when the X Forwarded Header is in place) multiple IPs are returned
		// in this occasion the first IP in the chain is the actual client IP
		if( strlen( trim( $ip ) ) >15 ) {
			$ex = explode(",", $ip);
			$ip = trim($ex[0]);
		}
		
		$ip = trim( str_replace( ",", "", $ip ) );
		
		return $ip;
	}
	
	public function checkIP() {
		if ( isset( $_SERVER['HTTP_CLIENT_IP'] ) )			return $_SERVER['HTTP_CLIENT_IP'];
		if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) )	return $_SERVER['HTTP_X_FORWARDED_FOR'];
		if ( isset( $_SERVER['HTTP_X_FORWARDED'] )	)		return $_SERVER['HTTP_X_FORWARDED'];
		if ( isset( $_SERVER['HTTP_FORWARDED_FOR'] ) )		return $_SERVER['HTTP_FORWARDED_FOR'];
		if ( isset( $_SERVER['HTTP_FORWARDED'] ) )			return $_SERVER['HTTP_FORWARDED'];
		if ( isset( $_SERVER['REMOTE_ADDR'] ) )				return $_SERVER['REMOTE_ADDR'];
		return '';
	}
	
	
}