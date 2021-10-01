<?php
namespace Piggly\WooERedeGateway\Core;

use Piggly\WooERedeGateway\Core\Gateway\CreditGateway;
use Piggly\WooERedeGateway\Core\Gateway\DebitGateway;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\Scaffold\Initiable;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\WP;

/**
 * Manages all woocommerce actions and filters.
 * 
 * @package \Piggly\WooERedeGateway
 * @subpackage \Piggly\WooERedeGateway\Core
 * @version 1.0.0
 * @since 1.0.0
 * @category Core
 * @author Caique Araujo <caique@piggly.com.br>
 * @author Piggly Lab <dev@piggly.com.br>
 * @license GPLv3 or later
 * @copyright 2021 Piggly Lab <dev@piggly.com.br>
 */
class Woocommerce extends Initiable
{
	/**
	 * Startup method with all actions and
	 * filter to run.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function startup ()
	{
		WP::add_filter(
			'woocommerce_payment_gateways', 
			$this, 
			'add_gateway'
		);
	}

	/**
	 * Add gateway to Woocommerce.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_gateway ( array $gateways )
	{
		array_push( $gateways, CreditGateway::class );
		array_push( $gateways, DebitGateway::class );
		return $gateways;
	}
	
	/**
	 * Add notice to Woocommerce.
	 *
	 * @param string $message
	 * @since 1.0.0
	 * @return void
	 */
	public static function add_notice (
		string $message
	)
	{
		global $woocommerce;

		$title = '<strong>Ocorreu um erro:</strong> ';

		if ( function_exists( 'wc_add_notice' ) ) 
		{ \wc_add_notice( $title . $message, 'error' ); } 
		else 
		{ $woocommerce->add_error( $title . $message ); }
	}

	/**
	 * Get all available woocommerce status.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public static function getAvailableStatuses () : array
	{ return \wc_get_order_statuses(); }
}