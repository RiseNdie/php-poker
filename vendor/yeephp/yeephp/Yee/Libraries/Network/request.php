<?php
namespace Yee\Libraries\Network;

class Request {
	
	public static function uri() {
		return $_SERVER['REQUEST_URI'];
	}
	
	public static function scriptUri() {
		$uri	= self::isHttps()	? 'https://'	: 'http://';
		$uri	.= $_SERVER['HTTP_HOST'];
		$uri	.= $_SERVER['REQUEST_URI'];
		return $uri;
	}
	
	public static function method() {
		return $_SERVER['REQUEST_METHOD'];
	}
	
	public static function data() {
		return $_REQUEST;
	}
	
	public static function isPost() {
		return $_SERVER['REQUEST_METHOD'] == 'POST';
	}
	
	public static function isHttps() {
		if ( isset( $_SERVER['HTTPS'] ) && ! empty( $_SERVER['HTTPS'] ) ) return true; 
		return ( isset( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) && strtolower( $_SERVER['HTTP_X_FORWARDED_PROTO'] ) == 'https' );
	}
	
	public static function post() {
		return $_POST;
	}
	
	public static function get() {
		return $_REQUEST;
	}
}