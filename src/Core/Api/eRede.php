<?php
namespace Piggly\WooERedeGateway\Core\Api;

use Piggly\WooERedeGateway\CoreConnector;
use Piggly\WooERedeGateway\Core\Gateway\AbstractGateway;

use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Settings\KeyingBucket;
use Piggly\WooERedeGateway\Vendor\Rede\Environment;
use Piggly\WooERedeGateway\Vendor\Rede\eRede as eRedeBase;
use Piggly\WooERedeGateway\Vendor\Rede\Store;
use Piggly\WooERedeGateway\Vendor\Rede\ThreeDSecure;
use Piggly\WooERedeGateway\Vendor\Rede\Transaction;
use Piggly\WooERedeGateway\Vendor\Rede\Url;

use WC_Order;

/**
 * Interface to connect into eRede API.
 * 
 * @package \Piggly\WooERedeGateway
 * @subpackage \Piggly\WooERedeGateway\Core\Api
 * @version 1.0.0
 * @since 1.0.0
 * @category Api
 * @author Caique Araujo <caique@piggly.com.br>
 * @author Piggly Lab <dev@piggly.com.br>
 * @license GPLv3 or later <key>
 * @copyright 2021 Piggly Lab <dev@piggly.com.br>
 */
class eRede
{
	/**
	 * Api Environment.
	 * @var string 
	 * @since 1.0.0
	 */
	private $environment;
	
	/**
	 * eRede Store.
	 * @var Store 
	 * @since 1.0.0
	 */
	private $store;
	
	/**
	 * Auto capture.
	 * @var bool 
	 * @since 1.0.0
	 */
	private $capture = true;
	
	/**
	 * Soft descriptor.
	 * @var string 
	 * @since 1.0.0
	 */
	private $soft_descriptor;
	
	/**
	 * Partner Module.
	 * @var string 
	 * @since 1.0.0
	 */
	private $partner_module;
	
	/**
	 * Partner Gateway.
	 * @var string 
	 * @since 1.0.0
	 */
	private $partner_gateway;

	/**
	 * Startup object.
	 *
	 * @param AbstractGateway $gateway
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct()
	{
		/** @var KeyingBucket Global settings */
		$global = CoreConnector::settings()->get('global', new KeyingBucket);

		/** @var KeyingBucket Credit settings */
		$credit = CoreConnector::settings()->get('credit', new KeyingBucket);

		$pv    = $global->get('pv');
		$token = $global->get('token');

		if ( $global->get('env', 'test') === 'test' )
		{ $this->environment = Environment::sandbox(); }
		else
		{ $this->environment = Environment::production(); }

		$this->soft_descriptor = $global->get('soft_descriptor');

		// Credit data
		$this->partner_gateway = $credit->get('partner_gateway');
		$this->partner_module  = $credit->get('partner_module');
		$this->capture         = $credit->get('auto_capture', false);

		// Store object
		$this->store = new Store( $pv, $token, $this->environment );
	}

	/**
	 * Update soft descriptor if it has merge tags.
	 * 
	 * @param WC_Order $order
	 * @since 1.0.0
	 * @return self
	 */
	public function updateDescriptor ( WC_Order $order )
	{
		if ( empty($this->soft_descriptor) )
		{ return $this; }

		if ( strpos($this->soft_descriptor, '{{id}}') !== false )
		{ 
			if ( empty($order) )
			{ return $this; }

			$order_id = \method_exists($order, 'get_order_number') ? $order->get_order_number() : $order->get_id();
			$this->soft_descriptor = str_replace('{{id}}', $order_id, $this->soft_descriptor);
		}

		return $this;
	}

	/**
	 * Do a debit card operation.
	 * 
	 * @param string $id
	 * @param float $amount
	 * @param array $cardData
	 * @param string $returnUrl
	 * @since 1.0.0
	 * @return Transaction
	 */
	public function debit (
		$id,
		$amount,
		$cardData,
		$returnUrl
	)
	{
		$transaction = 
			( new Transaction( $amount, $id ) )
				->debitCard(
					$cardData['card_number'],
					$cardData['card_cvv'],
					$cardData['card_expiration_month'],
					$cardData['card_expiration_year'],
					$cardData['card_holder']
				);

		$transaction->threeDSecure( ThreeDSecure::DECLINE_ON_FAILURE );
		$transaction->addUrl( $returnUrl, Url::THREE_D_SECURE_SUCCESS );
		$transaction->addUrl( $returnUrl, Url::THREE_D_SECURE_FAILURE );

		if ( !empty( $this->soft_descriptor ) ) 
		{ $transaction->setSoftDescriptor( $this->soft_descriptor ); }

		$transaction = 
			(new eRedeBase($this->store, CoreConnector::debugger()->getLogger()))
				->create( $transaction );

		return $transaction;
	}

	/**
	 * Do a credit card operation.
	 * 
	 * @param string $id
	 * @param float $amount
	 * @param int $installments
	 * @param array $cardData
	 * @since 1.0.0
	 * @return Transaction
	 */
	public function credit (
		$id,
		$amount,
		$installments = 1,
		$cardData = []
	)
	{
		$transaction = 
			( new Transaction( $amount, $id ) )
				->creditCard(
					$cardData['card_number'],
					$cardData['card_cvv'],
					$cardData['card_expiration_month'],
					$cardData['card_expiration_year'],
					$cardData['card_holder']
				)
				->capture($this->capture);

		if ( $installments > 1 ) 
		{ $transaction->setInstallments( $installments ); }

		if ( !empty( $this->soft_descriptor ) ) 
		{ $transaction->setSoftDescriptor( $this->soft_descriptor ); }

		if ( ! empty( $this->partner_module ) && ! empty( $this->partner_gateway ) ) 
		{ $transaction->additional( $this->partner_gateway, $this->partner_module ); }

		$transaction = 
			( new eRedeBase( $this->store, CoreConnector::debugger()->getLogger() ) )
				->create( $transaction );

		return $transaction;
	}

	/**
	 * Apply debug to api, make logger available.
	 * 
	 * @param bool $debug
	 * @since 1.0.0
	 * @return self
	 */
	public function applyDebug ( bool $debug = true )
	{ $this->debug = $debug; return $this; }
	

	/**
	 * Check a transaction by $tid.
	 * 
	 * @param string $tid
	 * @param float $amount
	 * @since 1.0.0
	 * @return Transaction
	 */
	public function get ( $tid )
	{ 
		return 
			(new eRedeBase( $this->store, CoreConnector::debugger()->getLogger()))
				->get($tid);
	}

	/**
	 * Cancel a transaction by $tid.
	 * 
	 * @param string $tid
	 * @param float $amount
	 * @since 1.0.0
	 * @return Transaction
	 */
	public function cancel ( $tid, $amount = 0 )
	{ 
		return 
			(new eRedeBase( $this->store, CoreConnector::debugger()->getLogger()))
				->cancel( (new Transaction($amount))->setTid($tid) );
	}

	/**
	 * Capture a transaction by $tid.
	 * 
	 * @param string $tid
	 * @param float $amount
	 * @since 1.0.0
	 * @return Transaction
	 */
	public function capture ( $tid, $amount )
	{ 
		return 
			(new eRedeBase( $this->store, CoreConnector::debugger()->getLogger()))
				->capture( (new Transaction($amount))->setTid($tid) );
	}
}