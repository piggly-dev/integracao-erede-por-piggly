<?php
namespace Piggly\WooERedeGateway\Core\Gateway;

use Exception;
use Piggly\WooERedeGateway\Core\Api\eRede;
use Piggly\WooERedeGateway\Core\Managers\SettingsManager;
use Piggly\WooERedeGateway\Core\Supports\Card;
use Piggly\WooERedeGateway\Core\Woocommerce;
use Piggly\WooERedeGateway\CoreConnector;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Settings\KeyingBucket;
use Piggly\WooERedeGateway\Vendor\Rede\Transaction;
use WC_Order;

class DebitGateway extends AbstractGateway
{
	/**
	 * Startup payment gateway method.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct()
	{
		// Gateway settings
		$this->id                 = CoreConnector::plugin()->getName().'_debit';
		$this->method_title       = CoreConnector::__translate('Cartão de Débito');
		$this->method_description = CoreConnector::__translate('Habilita e configura pagamentos com cartão de débito via e-Rede.');

		// Abstract Payment constructor
		parent::__construct();
	}

	/**
	 * Initialise settings for gateways.
	 * It ignores the WC_Settings_API behavior.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function init_settings() 
	{
		/** @var KeyingBucket $gatewaySettings */
		$debit = CoreConnector::settings()->get('debit', new KeyingBucket());

		$this->enabled = !SettingsManager::canEnable() ? 'no' : ($debit->get('enabled', false) ? 'yes' : 'no');
		$this->title = $debit->get('title', CoreConnector::__translate('Cartão de Débito'));
	}

	/**
	 * Process Payment.
	 *
	 * @param int $order_id Order ID.
	 * @since 1.0.0
	 * @return array
	 */
	public function process_payment ( $order_id )
	{
		// Load order
		$order = new WC_Order($order_id);

		CoreConnector::debugger()->debug(\sprintf('Iniciando o pagamento via Débito para o pedido %s.', $order_id));

		try
		{ 
			// Parse fields to valid card data
			$cardData = Card::parse_fields($this->get_card_data(Transaction::DEBIT)); 
		}
		catch ( Exception $e )
		{
			CoreConnector::debugger()->error(\sprintf('O pagamento via Débito para o pedido %s falhou.', $order_id));
			Woocommerce::add_notice(
				CoreConnector::debugger()->isDebugging() 
					? 'Dados inválidos: '.$e->getMessage()
					: $e->getMessage()
			);

			return [
				'result'   => 'fail',
				'redirect' => ''
			];
		}

		try
		{
			// Get order data
			$order_id    = $order->get_id();
			$order_uid   = $order_id + time();
			$order_total = $order->get_total();

			// Try to do a debit transaction
			$transaction = 
				$this
					->api
					->updateDescriptor($order)
					->debit( 
						$order_id + time(),
						$order_total,
						$cardData,
						$this->get_return_url($order)
					);
			
			// Create a transaction id to order
			update_post_meta( $order_id, '_pgly_erede_order_id', $order_uid );
			$order->set_transaction_id($transaction->getTid()); $order->save();

			$this->process_order_status($order, $transaction);
			CoreConnector::debugger()->debug(\sprintf('Pagamento via Débito %s processado.', $order_id));

			return [
				'result'   => 'success',
				'redirect' => $transaction->getThreeDSecure()->getUrl()
			];
		}
		catch ( Exception $e )
		{
			$err = \sprintf('Erro durante o pagamento para o pedido %s: [%s] -> %s', $order_id, $e->getCode(), $e->getMessage());

			CoreConnector::debugger()->error($err);
			Woocommerce::add_notice(
				CoreConnector::debugger()->isDebugging() 
					? $err
					: CoreConnector::__translate('Não foi possível processar o pagamento no momento. Tente novamente mais tarde.')
			);

			return [
				'result'   => 'fail',
				'redirect' => ''
			];
		}

		return [
			'result'   => 'fail',
			'redirect' => ''
		];
	}

	/**
	 * Get return url to $order.
	 * 
	 * @param \WC_Order|null $order
	 * @since 1.0.0
	 * @return string
	 */
	public function get_return_url (
		$order = null 
	)
	{
		if ( $order && $order instanceof WC_Order )
		{ $returnUrl = $order->get_checkout_order_received_url(); }
		else
		{ $returnUrl = \wc_get_endpoint_url( 'order-received', '', \wc_get_checkout_url() ); }

		return apply_filters( 'woocommerce_get_return_url', $returnUrl, $order );
	}

	/**
	 * Update a single option.
	 *
	 * @param string $key Option key.
	 * @param mixed  $value Value to set.
	 * @since 1.0.0
	 * @return bool was anything saved?
	 */
	public function update_option( $key, $value = '' ) 
	{
		/** @var KeyingBucket $debit */
		$settings = CoreConnector::settings()->get('debit', new KeyingBucket());
		
		if ( $key === 'enabled' )
		{ 
			if ( !SettingsManager::canEnable() )
			{ return false; }

			$value = \filter_var($value, \FILTER_VALIDATE_BOOL);
		}

		$settings->set($key, $value);
		
		CoreConnector::settingsManager()->save();
		return true;
	}

	/**
	 * Get option from DB.
	 *
	 * Gets an option from the settings API, using defaults 
	 * if necessary to prevent undefined notices.
	 *
	 * @param string $key Option key.
	 * @param mixed $empty_value Value when empty.
	 * @since 1.0.0
	 * @return string The value specified for the option or a default value for the option.
	 */
	public function get_option( $key, $empty_value = null ) 
	{
		/** @var KeyingBucket $debit */
		$settings = CoreConnector::settings()->get('debit', new KeyingBucket());

		$value = $settings->get($key, $empty_value);

		if ( \is_bool($value) )
		{ $value = $value ? 'yes' : 'no'; }

		return $value;
	}

	/**
	 * Get waiting status.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_waiting_status () : string
	{ return CoreConnector::settings()->get('debit', new KeyingBucket())->get('waiting_status', 'on-hold'); }

	/**
	 * Get paid status.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_paid_status () : string
	{ return CoreConnector::settings()->get('debit', new KeyingBucket())->get('paid_status', 'processing'); }

	/**
	 * Load the checkout form to current payment gateway.
	 * 
	 * @param float $total
	 * @since 1.0.0
	 * @return void
	 */
	protected function get_checkout_form (
		$total = 0
	)
	{
		\wc_get_template(
			'pgly-erede-debit-card-form.php',
			[],
			WC()->template_path().\dirname(CoreConnector::plugin()->getBasename()).'/',
			CoreConnector::plugin()->getTemplatePath().'woocommerce/'
		);
	}
}