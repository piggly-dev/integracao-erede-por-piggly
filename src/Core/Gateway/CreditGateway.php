<?php
namespace Piggly\WooERedeGateway\Core\Gateway;

use Exception;
use Piggly\WooERedeGateway\Core\Api\eRede;
use Piggly\WooERedeGateway\Core\Api\Payload;
use Piggly\WooERedeGateway\Core\Managers\SettingsManager;
use Piggly\WooERedeGateway\Core\Supports\Card;
use Piggly\WooERedeGateway\Core\Woocommerce;
use Piggly\WooERedeGateway\CoreConnector;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\WP;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Settings\KeyingBucket;
use Piggly\WooERedeGateway\Vendor\Rede\Transaction;
use WP_Error;
use WC_Order;

class CreditGateway extends AbstractGateway
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
		$this->id                 = CoreConnector::plugin()->getName().'_credit';
		$this->method_title       = CoreConnector::__translate('Cartão de Crédito');
		$this->method_description = CoreConnector::__translate('Habilita e configura pagamentos com cartão de crédito via e-Rede.');

		// Abstract Payment constructor
		parent::__construct();

		/** @var KeyingBucket $gatewaySettings */
		$credit = CoreConnector::settings()->get('credit', new KeyingBucket());

		// Register actions when auto capture is not enabled
		if ( !$credit->get('auto_capture', false) )
		{ 
			$processing_actions = [
				'woocommerce_order_status_on-hold_to_'.$credit->get('paid_status', 'processing'),
				'woocommerce_order_status_'.$credit->get('paid_status', 'processing'),
				'woocommerce_order_status_on-hold_to_completed',
				'woocommerce_order_status_completed'
			];

			foreach ( $processing_actions as $actions )
			{
				WP::add_action(
					$actions,
					$this,
					'process_capture'
				);
			}
		}
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
		$credit = CoreConnector::settings()->get('credit', new KeyingBucket());

		$this->enabled = !SettingsManager::canEnable() ? 'no' : ($credit->get('enabled', false) ? 'yes' : 'no');
		$this->title = $credit->get('title', CoreConnector::__translate('Cartão de Crédito'));
	}

	/**
	 * Initialise settings form fields.
	 * An array of fields to be displayed on the gateway's 
	 * settings screen.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init_form_fields () 
	{ return; }

	/**
	 * Process Payment.
	 *
	 * @param int $order_id Order ID.
	 * @since 1.0.0
	 * @return array
	 */
	public function process_payment ($order_id)
	{
		// Load order
		$order = new WC_Order($order_id);

		CoreConnector::debugger()->debug(\sprintf('Iniciando o pagamento via Crédito para o pedido %s.', $order_id));

		try
		{ 
			// Get card fields from $_POST
			$fields = $this->get_card_data(Transaction::CREDIT);
			// Parse fields to valid card data
			$cardData = Card::parse_fields($fields); 
			// Get installments
			$installments = $fields['installments'] ?? 1;

			// Validate installments
			Card::validate_installments(
				$installments, 
			   (float)$order->get_total(),
				CoreConnector::settings()->get('credit')->get('min_parcels_value', 1),
				CoreConnector::settings()->get('credit')->get('max_parcels_number', 1)
			);
		}
		catch ( Exception $e )
		{
			CoreConnector::debugger()->error(\sprintf('O pagamento via Crédito para o pedido %s falhou.', $order_id));
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

			// Try to do a credit transaction
			$transaction = 
				$this
					->api
					->updateDescriptor($order)
					->credit( 
						$order_id + time(),
						$order_total, 
						$installments, 
						$cardData
					);
			
			// Create a transaction id to order
			update_post_meta( $order_id, '_pgly_rede_order_id', $order_uid );			
			$order->set_transaction_id($transaction->getTid()); $order->save();

			$this->process_order_status($order, $transaction);

			CoreConnector::debugger()->debug(\sprintf('Pagamento via Débito %s processado.', $order_id));
			return [
				'result'   => 'success',
				'redirect' => $this->get_return_url( $order )
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
	 * Capture Payment.
	 *
	 * @param WC_Order|int $order Order ID.
	 * @param Payload $payload Payload.
	 * @since 1.0.0
	 * @return bool|WP_Error
	 */
	public function process_capture ( $order, Payload $payload = null )
	{
		// Load order
		$order = $order instanceof WC_Order ? $order : new WC_Order( $order );
		$order_id = $order->get_id();

		if ( $order->get_payment_method() !== $this->id )
		{ return false; }

		// Order cannot be captured
		if ( !$order || !$order->get_transaction_id() )
		{ 
			CoreConnector::debugger()->error(\sprintf('Erro na captura do pedido %s: O pedido ou o ID da transação não existem.', $order_id));
			
			return new WP_Error(
				'erede_capture_error', 
				sanitize_text_field(CoreConnector::__translate('Não foi possível capturar o pedido. O ID da transação está ausente.')) 
			);
		}
		
		$payload = empty($payload) ? Payload::fill($order) : $payload;

		if ( $payload->isCaptured() )
		{ return true; }

		// Payload has authorized status
		if ( $payload->isAuthorized() )
		{
			// Get order data
			$tid    = $order->get_transaction_id();
			$amount = $order->get_total();

			try
			{
				CoreConnector::debugger()->debug(\sprintf('Iniciando o processo de captura de crédito para o pedido %s.', $order_id));
				
				// Try to do a credit transaction
				$transaction = 
					$this
						->api
						->capture( $tid, $amount );

				// Get capture data and update payload
				$payload->capture($transaction)->save();

				// Add note to order
				$order->add_order_note( CoreConnector::__translate('e.Rede[Capturado]: O pagamento foi aprovado e capturado com sucesso.') );
				$order->payment_complete($tid);
				CoreConnector::debugger()->debug(\sprintf('Pagamento para o pedido %s capturado via crédito.', $order_id));
			}
			catch ( Exception $e )
			{
				CoreConnector::debugger()->error(sprintf('Não foi possível capturar o pedido %s: %s.', $order_id, sanitize_text_field($e->getMessage())));
				
				// Add note to order
				$order->add_order_note(
					\sprintf(
						CoreConnector::__translate('e.Rede[Erro]: Não foi possível capturar o pedido -> %s'), 
						sanitize_text_field($e->getMessage())
					)
				);
				
				return false;
			}

			return true;
		}
		
		CoreConnector::debugger()->error(\sprintf('Erro na captura do pedido %s: A transação exige o status de autorização.', $order_id));
		
		return new WP_Error(
			'erede_capture_error', 
			sanitize_text_field(CoreConnector::__translate('Não foi possível capturar o pedido. A transação não está acessível.')) 
		);
	}

	/**
	 * Get all installment labels based in $total.
	 * 
	 * @param float $total
	 * @since 1.0.0
	 * @return array
	 */
	public function get_installments (
		float $total = 0
	) : array
	{
		$installments = [];
		$minValue     = CoreConnector::settings()->get('credit')->get('min_parcels_value', 1);
		$maxParcels   = CoreConnector::settings()->get('credit')->get('max_parcels_number', 1);

		for ( $i = 1; $i <= $maxParcels; ++$i )
		{
			// Cannot get installments
			if ($total / $i < $minValue) 
			{ break; }

			// Set installment label
			if ( $i === 1 ) 
			{ $label = \sprintf( CoreConnector::__translate('R$ %.02f à vista'), $total ); }
			else
			{ $label = \sprintf( CoreConnector::__translate('%dx de R$ %.02f'), $i, $total / $i ); }

			// Add to installments
			$installments[] = [
				'num'   => $i,
				'label' => $label
			];
		}

		// There is no installments
		if ( count( $installments ) == 0 ) 
		{
			$installments[] = [
				'num'   => 1,
				'label' => \sprintf( CoreConnector::__translate('R$ %.02f à vista'), $total )
			];
		}

		return $installments;
	}

	/**
	 * Get label to current installments $quantity.
	 * 
	 * @param int $quantity Number of parcels
	 * @param float $total Order total
	 * @since 1.0.0
	 * @return string|int
	 */
	public function get_installments_label (
		int $quantity,
		float $total
	)
	{
		$installments = $this->get_installments($total);

		if ( isset($installments[$quantity-1]) )
		{ return $installments[$quantity-1]['label']; }

		if ( isset($installments[$quantity]) )
		{ return $installments[$quantity]['label']; }

		return $quantity;
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
		/** @var KeyingBucket $credit */
		$settings = CoreConnector::settings()->get('credit', new KeyingBucket());
		
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
		/** @var KeyingBucket $credit */
		$settings = CoreConnector::settings()->get('credit', new KeyingBucket());

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
	{ return CoreConnector::settings()->get('credit', new KeyingBucket())->get('waiting_status', 'on-hold'); }

	/**
	 * Get paid status.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_paid_status () : string
	{ return CoreConnector::settings()->get('credit', new KeyingBucket())->get('paid_status', 'processing'); }

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
			'pgly-erede-credit-card-form.php',
			[ 'installments' => $this->get_installments( $total ) ],
			WC()->template_path().\dirname(CoreConnector::plugin()->getBasename()).'/',
			CoreConnector::plugin()->getTemplatePath().'woocommerce/'
		);
	}
}