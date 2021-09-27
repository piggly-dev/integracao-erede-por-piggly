<?php
namespace Piggly\WooERedeGateway\Core\Api;

use InvalidArgumentException;
use Piggly\WooERedeGateway\CoreConnector;
use Piggly\WooERedeGateway\Core\Gateway\AbstractGateway;
use Piggly\WooERedeGateway\Vendor\Rede\Transaction;
use WC_Order;

/**
 * Payload including API response data.
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
class Payload 
{
	const ORDER_META_PAYLOAD = '_pgly_erede_gateway_payload';

	/** 
	 * @var string Submitted status.
	 * @since 1.0.0
	 */
	const STATUS_SUBMITTED = 5;

	/** 
	 * @var string Authorized status.
	 * @since 1.0.0
	 */
	const STATUS_AUTHORIZED = 0;

	/** 
	 * @var string Cancelled status.
	 * @since 1.0.0
	 */
	const STATUS_CANCELLED = 1;

	/** 
	 * @var string Captured status.
	 * @since 1.0.0
	 */
	const STATUS_CAPTURED = 2;

	/** 
	 * @var string Pending status.
	 * @since 1.0.0
	 */
	const STATUS_PENDING = 3;

	/** 
	 * @var string Failed status.
	 * @since 1.0.0
	 */
	const STATUS_FAILED = 4;

	/**
	 * Output data payload.
	 * @var array
	 * @since 1.0.0
	 */
	protected $data;

	/**
	 * Order ID.
	 * @var int
	 * @since 1.0.0
	 */
	protected $order_id;

	/**
	 * Fill payload with $order.
	 * 
	 * @param WC_Order $order
	 * @since 1.0.0
	 * @return self
	 */
	public static function fill ( WC_Order $order )
	{ return (new self())->load($order); }

	/**
	 * Create payload with $order and transaction data.
	 * 
	 * @param WC_Order $order
	 * @since 1.0.0
	 * @return self
	 */
	public static function create ( WC_Order $order, Transaction $new )
	{ return (new self())->load($order)->new($new); }

	/**
	 * Load payload from $order by getting self::ORDER_META_PAYLOAD.
	 * If payload is found, set $this->data to payload data.
	 * 
	 * @param WC_Order $order
	 * @since 1.0.0
	 * @return self
	 */
	public function load ( WC_Order $order )
	{
		$this->data = [];
		$payload    = $order->get_meta(self::ORDER_META_PAYLOAD);

		if ( !empty($payload) )
		{ $this->data = $payload; }		

		$this->order_id = $order->get_id();
		return $this;
	}

	/**
	 * Save the current $this->data to order meta as
	 * self::ORDER_META_PAYLOAD.
	 * 
	 * @since 1.0.0
	 * @return bool
	 */
	public function save () : bool
	{ 
		if ( empty($this->order_id) || empty($this->data) )
		{ return false; }

		update_post_meta( $this->order_id, self::ORDER_META_PAYLOAD, $this->data); 
		return true;
	}

	/**
	 * Export data.
	 * 
	 * @since 1.0.0
	 * @return array
	 */
	public function export () : array
	{ return $this->data ?? []; }

	/**
	 * Create a new payload data based in $transaction.
	 * 
	 * @param Transaction $order
	 * @since 1.0.0
	 * @return self
	 */
	public function new ( Transaction $transaction )
	{
		if ( empty($this->data) )
		{ $this->data = []; }

		$this
			->set('kind', $transaction->getKind())
			->set('bin', $transaction->getCardBin())
			->set('last4', $transaction->getLast4())
			->set('holderName', $transaction->getCardHolderName())
			->set('expiration', sprintf( '%02d/%04d', $transaction->getExpirationMonth(), $transaction->getExpirationYear() ))
			->set('brand_tid', $transaction->getBrandTid());

		$authorization = $transaction->getAuthorization();
		$brand         = $transaction->getBrand();

		if ( !is_null($authorization) )
		{ $this->set('auth_status', $authorization->getStatus()); }

		if ( !is_null($brand) )
		{ 
			$this
				->set('brand_name', $brand->getName())
				->set('brand_return_code', $brand->getReturnCode())
				->set('brand_return_message', $brand->getReturnMessage());
		}

		if ( $this->get('kind', Transaction::CREDIT) === Transaction::CREDIT )
		{
			$this
				->set('tid', $transaction->getTid())
				->set('return_code', $transaction->getReturnCode())
				->set('return_message', $transaction->getReturnMessage())
				->set('installments', $transaction->getInstallments())
				->set('rid', $transaction->getRefundId())
				->set('cid', $transaction->getCancelId())
				->set('nsu', $transaction->getNsu())
				->set('auth_code', $transaction->getAuthorizationCode());

			if ( !empty($transaction->getNsu()) && $transaction->getCapture() )
			{ $this->set('status', self::STATUS_CAPTURED); }
		}

		$this->save();
		return $this;
	}

	/**
	 * Add to current payload data, threeD authorization data.
	 * 
	 * @param Transaction $transaction
	 * @since 1.0.0
	 * @return self
	 */
	public function threeD ( Transaction $transaction )
	{
		$authorization = $transaction->getAuthorization();

		if ( !is_null($authorization) )
		{
			$this
				->set('tid', $transaction->getTid())
				->set('return_code', $authorization->getReturnCode())
				->set('return_message', $authorization->getReturnMessage())
				->set('nsu', $authorization->getNsu())
				->set('auth_code', $authorization->getAuthorizationCode())
				->set('auth_status', $authorization->getStatus());
		}

		$this
			->set('status', self::STATUS_CAPTURED)
			->set('holderName', $transaction->getCardHolderName())
			->set('expiration', sprintf( '%02d/%04d', $transaction->getExpirationMonth(), $transaction->getExpirationYear() ));
		
	}

	/**
	 * Add to current payload data, refund data.
	 * 
	 * @param Transaction $transaction
	 * @since 1.0.0
	 * @return self
	 */
	public function refund ( Transaction $transaction )
	{
		$this
			->set('rid', $transaction->getRefundId())
			->set('cid', $transaction->getCancelId())
			->set('status', self::STATUS_CANCELLED);
	
		return $this;
	}

	/**
	 * Add to current payload data, capture data.
	 * 
	 * @param Transaction $transaction
	 * @since 1.0.0
	 * @return self
	 */
	public function capture ( Transaction $transaction )
	{
		$this
			->set('nsu', $transaction->getNsu())
			->set('status', self::STATUS_CAPTURED);
		
		return $this;
	}

	/**
	 * Check if current payload is authorized.
	 * 
	 * @since 1.0.0
	 * @return bool
	 */
	public function isAuthorized () : bool
	{ return $this->get('status', null) === self::STATUS_AUTHORIZED; }

	/**
	 * Check if current payload is cancelled.
	 * 
	 * @since 1.0.0
	 * @return bool
	 */
	public function isCancelled () : bool
	{ return $this->get('status', null) === self::STATUS_CANCELLED; }

	/**
	 * Check if current payload is captured.
	 * 
	 * @since 1.0.0
	 * @return bool
	 */
	public function isCaptured () : bool
	{ return $this->get('status', null) === self::STATUS_CAPTURED; }

	/**
	 * Check if current payload is pending.
	 * 
	 * @since 1.0.0
	 * @return bool
	 */
	public function isPending () : bool
	{ return $this->get('status', null) === self::STATUS_PENDING; }

	/**
	 * Check if current payload is SUBMITED.
	 * 
	 * @since 1.0.0
	 * @return bool
	 */
	public function isSubmitted () : bool
	{ return $this->get('status', self::STATUS_SUBMITTED) === self::STATUS_SUBMITTED; }

	/**
	 * Check if current payload is FAILED.
	 * 
	 * @since 1.0.0
	 * @return bool
	 */
	public function isFailed () : bool
	{ return $this->get('status', null) === self::STATUS_FAILED; }
	

	/**
	 * Get status.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function getStatus () : string
	{ return $this->get('status', self::STATUS_SUBMITTED); }

	/**
	 * Get status label.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function getStatusLabel () : string
	{
		switch ( $this->getStatus() )
		{
			case static::STATUS_PENDING:
				return CoreConnector::__translate('Pendente');
			case static::STATUS_AUTHORIZED:
				return CoreConnector::__translate('Autorizado');
			case static::STATUS_SUBMITTED:
				return CoreConnector::__translate('Enviado');
			case static::STATUS_CANCELLED:
				return CoreConnector::__translate('Cancelado');
			case static::STATUS_FAILED:
				return CoreConnector::__translate('Falhou');
			case static::STATUS_CAPTURED:
				return CoreConnector::__translate('Concluído');
		}

		return CoreConnector::__translate('Pendente');
	}

	/**
	 * Get pix color.
	 *
	 * @since 2.0.0
	 * @return string
	 */
	public function getStatusColor () : string
	{
		switch ( $this->getStatus() )
		{
			case static::STATUS_PENDING:
			case static::STATUS_SUBMITTED:
				return 'warning';
				break;
			case static::STATUS_AUTHORIZED:
				return 'primary';
				break;
			case static::STATUS_CANCELLED:
			case static::STATUS_FAILED:
				return 'danger';
				break;
			case static::STATUS_CAPTURED:
				return 'success';
				break;
		}

		return 'neutral';
	}

	/**
	 * Set a new data $key to payload with $value.
	 * 
	 * @param string $key
	 * @param mixed $value
	 * @since 1.0.0
	 * @return self
	 */
	protected function set ( string $key, $value )
	{ $this->data[$key] = $value; return $this; }

	/**
	 * Get a $key data in payload or return $default
	 * if $key not found.
	 * 
	 * @param string $key
	 * @param mixed $default
	 * @since 1.0.0
	 * @return mixed
	 */
	public function get ( string $key, $default = null )
	{ return $this->data[$key] ?? $default; }

	/**
	 * Get all $data.
	 * 
	 * @since 1.0.0
	 * @return mixed
	 */
	public function getAll ( )
	{ return $this->data ?? []; }

	/**
	 * Force to change the current payload status.
	 * 
	 * @param string $status
	 * @since 1.0.0
	 * @return self
	 * @throws InvalidArgumentException
	 */
	public function changeStatus ( int $status )
	{ 
		if ( $status < self::STATUS_AUTHORIZED || $status > self::STATUS_FAILED )
		{ throw new InvalidArgumentException('Status inválido informado para atualiza o payload.'); }

		$this->set('status', $status); return $this; 
	}

	/**
	 * Define the current payload environment.
	 * 
	 * @param string $env
	 * @since 1.0.0
	 * @return self
	 * @throws InvalidArgumentException
	 */
	public function setEnvironment ( string $env )
	{ 
		if ( !in_array($env, [AbstractGateway::ENV_PROD, AbstractGateway::ENV_TEST]) )
		{ throw new InvalidArgumentException('Ambiente inválido para o payload.'); }

		$this->data['environment'] = $env; return $this; 
	}
}