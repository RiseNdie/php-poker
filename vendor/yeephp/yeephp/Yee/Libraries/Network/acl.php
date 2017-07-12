<?php
namespace Yee\Libraries\Network;

class Acl { 
	
	/**
	 * 
	 * An array with ip addresses that 
	 * form the access control list
	 * @var array
	 */
	protected $list;
	
	/**
	 * 
	 * List record
	 * @see Acl::$list
	 * @var array
	 */
	protected $record;
	
	/**
	 * 
	 * @param array $list
	 * @example array('65.52.104.48', '66.249.0.0/16', '66.249.72.0/24')
	 */
	public function __construct( array $list ) {
		$this->setList( $list );
	}
	
	/**
	 * 
	 * Sets a list of ip addresses
	 * @param array $list
	 */
	public function setList( array $list ) {
		$this->list	= array();
		foreach ( $list as $ip ) {
			$this->list[]	= array(
				'raw'	=> $ip
			);
		}
	}
	
	/**
	 * 
	 * Enter description here ...
	 * @param string $targetIp
	 * @see http://www.pgregg.com/projects/php/ip_in_range/
	 */
	public function match( $targetIp ) {
		if ( empty( $this->list ) ) {
			return false;
		}
		
		$targetIp = (double) sprintf( '%u', ip2long( $targetIp ) );
		
		foreach ( $this->list as $this->record ) {
			if ( ! $this->isProcessed( $this->record )) {
				$this->process( $this->record );
			}
			
			if( $this->record['mask'] === 0 ) {
				if ( $this->record['range'] == $targetIp ) {
					return true;
				} else {
					continue;
				}
			}
			
			if ( ( $targetIp & $this->record['mask'] ) == ( $this->record['range'] & $this->record['mask'] ) ) {
				return true;
			}
		}
		return false;
	}
	
	protected function isProcessed( &$listRecord ) {
		return isset( $listRecord['mask'] );
	}
	
	protected function process( &$listRecord ) {
		if ( strpos( $listRecord['raw'], '/' ) !== false) { 
			// format: xxx.xxx.xxx.0/24
		 	list( $range, $netmask )	= explode( '/', $listRecord['raw'], 2 );
		 	$wildcard					= pow( 2, ( 32 - (int) $netmask ) ) - 1;
		 	$listRecord['range']		= (double) sprintf( '%u', ip2long( $range ) );
		 	$listRecord['mask']			= (double) sprintf( '%u', ~ $wildcard );
		} else {
			// format: xxx.xxx.xxx.xxx
			$listRecord['range']	= (double) sprintf('%u', ip2long( $listRecord['raw'] ) );
			$listRecord['mask']		= 0;
		}
	}
}
?>