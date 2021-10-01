<?php
namespace Piggly\WooERedeGateway\Core\Gateway;

use Exception;
use Piggly\WooERedeGateway\Core\Api\eRede;
use Piggly\WooERedeGateway\Core\Api\Payload;
use Piggly\WooERedeGateway\Core\Woocommerce;
use Piggly\WooERedeGateway\CoreConnector;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\WP;
use Piggly\WooERedeGateway\Vendor\Rede\Transaction;

use WC_Order;
use WC_Payment_Gateway;
use WP_Error;

abstract class AbstractGateway extends WC_Payment_Gateway
{
	/** @var string ENV_TEST Test environment. */
	const ENV_TEST = 'test';
	/** @var string ENV_PROD Production environment. */
	const ENV_PROD = 'prod';

	/**
	 * API.
	 * @var eRede
	 * @since 1.0.0
	 */
	public $api;

	/**
	 * Startup payment gateway method.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function __construct () 
	{
		// Gateway settings
		$this->supports   = ['products', 'refunds'];
		$this->has_fields = true;

		/** @var eRede $api */
		$this->api = new eRede();

		$this->init_settings();

		// If automatic refund, detect when status 
		// is cancelled or refunded to refund and update order
		if ( CoreConnector::settings()->get('global')->get('auto_refund', false) )
		{ 
			WP::add_action('woocommerce_order_status_cancelled', $this, 'process_refund');
			WP::add_action('woocommerce_order_status_refunded', $this, 'process_refund');
		}

		// Add payment method to order thank you page
		WP::add_action('woocommerce_thankyou_'.$this->id, $this, 'thankyou_page');
		// Filter order totals with additional payment data
		WP::add_filter('woocommerce_get_order_item_totals', $this, 'order_items_payment_details', 10, 2);
	}

	/**
	 * Load the checkout form to current payment gateway.
	 * 
	 * @param float $total
	 * @since 1.0.0
	 * @return void
	 */
	abstract protected function get_checkout_form ( $total );

	/**
	 * Get api return url to $order.
	 * 
	 * @param WC_Order $order
	 * @since 1.0.0
	 * @return array
	 */
	public function get_api_return_url ( $order ) 
	{
		global $woocommerce;

		$url = $woocommerce->api_request_url( get_class( $this ) );

		return urlencode( add_query_arg( [
			'key'   => $order->order_key,
			'order' => $order->get_id()
		], $url ) );
	}

	/**
	 * Add installments to order items table.
	 * 
	 * @param array $items
	 * @param WC_Order $order
	 * @since 1.0.0
	 * @return void
	 */
	public function order_items_payment_details( $items, $order ) 
	{
		if ( $this->id === $order->get_payment_method() ) 
		{
			$payload      = Payload::fill($order);
			$installments = $payload->get('installments', 1);
			$last         = array_pop( $items );

			if ( !isset($last['payment_return']) )
			{ return $items; }

			if ( $installments ) 
			{ $items['payment_return']['value'] .= \sprintf( CoreConnector::__translate('<strong>Parcelas</strong>: %s<br/>'), $installments ); }

			$items[] = $last;
		}

		return $items;
	}
	
	/**
	 * Add fields to Woocommerce Card Form.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function payment_fields() 
	{
		if ( $description = $this->get_description() ) 
		{ echo \wpautop( \wptexturize( \esc_html( $description ) ) ); }

		\wp_enqueue_script( 'wc-credit-card-form' );
		$this->get_checkout_form($this->get_order_total());
	}

	/**
	 * Get order total.
	 * 
	 * @since 1.0.0
	 * @return float
	 */
	public function get_order_total () : float
	{
		global $woocommerce;

		$order_total = 0;

		// Get current order id
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) 
		{ $order_id = absint( get_query_var( 'order-pay' ) ); } 
		else 
		{ $order_id = isset( $_GET['order_id'] ) ? absint( $_GET['order_id'] ) : 0; }

		// If $order_id is found
		if ( 0 < $order_id ) 
		{
			$order       = new WC_Order( $order_id );
			$order_total = (float) $order->get_total();
		} 
		// Get total from $woocommerce->cart
		elseif ( 0 < $woocommerce->cart->total ) 
		{ $order_total = (float) $woocommerce->cart->total; }

		return $order_total;
	}

	/**
	 * Add data to thank you page.
	 * 
	 * @param int $order_id
	 * @since 1.0.0
	 * @return void
	 */
	public function thankyou_page ( $order_id )
	{
		$order = new WC_Order( $order_id );

		// Prepara to read transaction if got return data
		$this->process_return( $order );

		// Get the order url
		if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '2.1', '>=' ) ) 
		{ $order_url = $order->get_view_order_url(); } 
		else 
		{ $order_url = add_query_arg( 'order', $order_id, get_permalink( woocommerce_get_page_id( 'view_order' ) ) ); }

		$status = $order->get_status();

		switch ( $status )
		{
			case $this->get_waiting_status():
				echo '<div class="woocommerce-message">Seu pagamento já está sendo processado. <a href="' . esc_url( $order_url ) . '" class="button" style="display: block !important; visibility: visible !important;">Ver detalhes do pedido</a><br /></div>';
				break;
			case $this->get_paid_status():
				echo '<div class="woocommerce-message">Seu pagamento foi aprovado. <a href="' . esc_url( $order_url ) . '" class="button" style="display: block !important; visibility: visible !important;">Ver detalhes do pedido</a><br /></div>';
				break;
			default:
				echo '<div class="woocommerce-info">Seu pagamento já está sendo processado. <a href="' . esc_url( $order_url ) . '" class="button" style="display: block !important; visibility: visible !important;">Ver detalhes do pedido</a><br /></div>';
				break;
		}
	}

	/**
	 * When use ThreeDSecure return to debit card, 
	 * process transaction returned.
	 * 
	 * @param WC_Order $order
	 * @since 1.0.0
	 * @return void
	 */
	protected function process_return ( $order )
	{
		$tid = filter_input( INPUT_POST, 'tid', \FILTER_SANITIZE_STRING);
		$rCode = filter_input( INPUT_POST, 'threeDSecure_returnCode', \FILTER_SANITIZE_STRING);

		if ( !empty($tid) && $rCode === '200' )
		{
			CoreConnector::debugger()->debug('Retorno 3D Secure identificado.');
			
			$order_id   = $order->get_id();
			$mpi_return = get_post_meta( $order_id, '_mpi_return', true );

			if ( !$mpi_return )
			{
				CoreConnector::debugger()->debug(\sprintf('Obtendo a transação com ID %s.', $tid));

				try
				{
					// Get transaction from API
					$transaction =
						$this
							->api
							->get($tid);

					if ( $transaction !== null )
					{
						// Get order payload data
						$payload = Payload::fill($order);

						// Get threeD data and update payload
						$payload->threeD($transaction);

						// Get the return code and message
						$returnCode    = $transaction->getReturnCode();
						$returnMessage = $transaction->getReturnMessage();

						// If there is no return code, get it from authorization
						if ( empty( $returnCode ) ) 
						{
							$returnCode    = $transaction->getAuthorization()->getReturnCode();
							$returnMessage = $transaction->getAuthorization()->getReturnMessage();
						}

						// Create a status note to add
						$status_note = \sprintf( CoreConnector::__translate('eRede[%s]: %s'), $returnCode, $returnMessage );

						// Debug
						CoreConnector::debugger()->debug(\sprintf('Operação 3DS realizada para o pedido %s: %s', $order->get_id(), $status_note));
						
						// Create a new note to order
						$order->add_order_note( $status_note );

						// Payment success
						if ( $returnCode == '00' ) 
						{
							$payload->changeStatus(Payload::STATUS_CAPTURED)->save();

							// Add note to order
							$order->add_order_note(CoreConnector::__translate('e.Rede[Capturado]: O pagamento foi aprovado e capturado com sucesso.') );
							$order->payment_complete($tid);
							CoreConnector::debugger()->debug(\sprintf('Pagamento para o pedido %s capturado via débito.', $order_id));
						} 
						// Another status code
						else 
						{ 
							$payload->changeStatus(Payload::STATUS_FAILED)->save();
							$order->update_status('failed', \sprintf(CoreConnector::__translate('Erro [%s] na operação 3DS: %s'), $returnCode, $returnMessage)); 
						}
					}
				}
				catch ( Exception $e )
				{
					$err = \sprintf('Erro ao processar o retorno 3DS para o pedido %s: [%s] -> %s', $order_id, $e->getCode(), $e->getMessage());
		
					CoreConnector::debugger()->error($err);
					Woocommerce::add_notice(
						CoreConnector::debugger()->isDebugging() 
							? $err
							: CoreConnector::__translate('Não foi possível processar o pagamento no momento. Tente novamente mais tarde.')
					);
				}
			}
		}
	}
	
	/**
	 * Process refund.
	 *
	 * If the gateway declares 'refunds' support, this will allow it to refund.
	 * a passed in amount.
	 *
	 * @param  int        $order_id Order ID.
	 * @param  float|null $amount Refund amount.
	 * @param  string     $reason Refund reason.
	 * @return boolean True or false based on success, or a \WP_Error object.
	 */
	public function process_refund (
		$order_id, 
		$amount = null, 
		$reason = ''
	)
	{
		// Load order
		$order = new WC_Order( $order_id );

		if ( \strpos($order->get_payment_method(), CoreConnector::plugin()->getName()) === false )
		{ return false; }

		// Order cannot be refunded
		if ( !$order || !$order->get_transaction_id() )
		{ 
			CoreConnector::debugger()->error(\sprintf('Erro ao reembolsar o pedido %s: O pedido ou o ID da transação não existem.', $order_id));
			
			return new WP_Error(
				'erede_capture_error', 
				sanitize_text_field(CoreConnector::__translate('Não foi possível reembolsar o pedido. O ID da transação está ausente.')) 
			);
		}

		// Fill order payload
		$payload = Payload::fill($order);

		// Only refund if order never refunded
		if ( !$payload->isCancelled() )
		{
			$tid    = $order->get_transaction_id();
			$amount = \wc_format_decimal($amount??$order->get_total()); 

			try
			{
				CoreConnector::debugger()->debug(\sprintf('Iniciando o processo de reembolso para o pedido %s.', $order_id));
				$transaction = $this->api->cancel( $tid, $amount );

				$payload->refund($transaction)->save();
				$order->add_order_note(\sprintf(CoreConnector::__translate('e.Rede[Reembolsado]: %s.'), \wc_price( $amount )));
				CoreConnector::debugger()->debug(\sprintf('Pedido %s reembolsado.', $order_id));
			}
			catch ( Exception $e )
			{
				CoreConnector::debugger()->error(\sprintf('Não foi possível reembolsar o pedido %s: %s.', $order_id, sanitize_text_field($e->getMessage())));
				
				return new WP_Error(
					'erede_refund_error', 
					sanitize_text_field($e->getMessage()) 
				);
			}

			return true;
		}

		CoreConnector::debugger()->error(sprintf('Erro ao reembolsar do pedido %s: A transação não está acessível.', $order_id));
		
		return new WP_Error(
			'erede_capture_error', 
			sanitize_text_field(__('Não foi possível reembolsar o pedido. A transação não está acessível.')) 
		);
	}
	
	/**
	 * Process an order with a pending status.
	 * 
	 * @param WC_Order $order
	 * @param string $tid
	 * @since 1.0.0
	 * @return void
	 */
	public function process_pending ( 
		WC_Order $order, 
		string $tid,
		Payload $payload
	) 
	{
		if ( !$payload->isPending() && !$payload->isSubmitted() ) 
		{ 
			// Debug
			CoreConnector::debugger()->debug(\sprintf('Ignorando o processamento, o pedido %s não está PENDENTE.', $order->get_id()));
			return; 
		}

		try
		{
			$transaction = $this->api->get($tid);

			if ( $transaction instanceof Transaction )
			{ $this->process_order_status( $order, $transaction, CoreConnector::__translate('Verificação automática') ); }
			else
			{ CoreConnector::debugger()->error(\sprintf('Não foi possível localizar a transação: %s', $tid)); }
		}
		catch ( Exception $e )
		{ CoreConnector::debugger()->error(sprintf('Erro ao consultar o pedido %s pendente: %s', $order->get_id(), $e->getMessage())); }
	}

	/**
	 * After create order, process new status based in
	 * payment operations.
	 * 
	 * @param WC_Order $order
	 * @param Transaction $transaction
	 * @param string $note
	 * @since 1.0.0
	 * @return void
	 */
	public function process_order_status ( 
		WC_Order $order, 
		Transaction $transaction, 
		string $note = '' 
	) 
	{
		// Create a new payload with $transaction data to $order
		$payload = Payload::create($order, $transaction)->setEnvironment(CoreConnector::settings()->get('global')->get('env', 'test'));

		// Get the return code and message
		$returnCode    = $transaction->getReturnCode();
		$returnMessage = $transaction->getReturnMessage();

		// If there is no return code, get it from authorization
		if ( empty( $returnCode ) ) 
		{
			$returnCode    = $transaction->getAuthorization()->getReturnCode();
			$returnMessage = $transaction->getAuthorization()->getReturnMessage();
		}

		// Create a status note to add
		$status_note = sprintf( CoreConnector::__translate('eRede[%s]: %s'), $returnCode, $returnMessage );

		CoreConnector::debugger()->debug(\sprintf('Operação "%s" para o pedido %s realizada.', $status_note, $order->get_id()));
		
		// Create a new note to order
		$order->add_order_note( trim( $status_note . ' ' . $note ) );

		// Payment success
		if ( $returnCode == '00' ) 
		{
			// Authorized status
			$payload->changeStatus(Payload::STATUS_AUTHORIZED)->save();

			\wc_maybe_reduce_stock_levels($order->get_id());

			// If captured... mark as completes
			if ( $transaction->getCapture() ) 
			{
				$payload->changeStatus(Payload::STATUS_CAPTURED)->save();
				$order->payment_complete(); 
			} 
			// If not may mark as some state...
			else 
			{ $order->update_status( $this->get_waiting_status() ); }
		} 
		// Transaction request with authentication received. Redirect URL sent
		elseif ( $returnCode == '220' ) 
		{ 
			$payload->changeStatus(Payload::STATUS_PENDING)->save();
			$order->update_status( 'pending', CoreConnector::__translate('Redirecionado para autenticação') ); 
		} 
		// Another status code
		else 
		{ 
			$payload->changeStatus(Payload::STATUS_FAILED)->save();
			$order->update_status( 'failed', $status_note ); 
		}
	}

	/**
	 * Get card data from $_POST.
	 * 
	 * @param string $type
	 * @since 1.0.0
	 * @return array
	 */
	protected function get_card_data ( string $type ) : array
	{	
		$expected = [ 'number', 'holder_name', 'expiry' , 'cvv', 'installments' ];
		$fields   = [];

		foreach ( $expected as $field )
		{
			$value = filter_input( INPUT_POST, 'pgly_erede_'.$type.'_'.$field, \FILTER_SANITIZE_STRING );
			if ( !empty($field) ) $fields[$field] = $value;
		}

		return $fields;
	}

	/**
	 * Check if current environment is same as $environment.
	 * 
	 * @param string $environment
	 * @since 1.0.0
	 * @return bool
	 */
	public function isEnvironment ( string $environment ) : bool
	{ return $this->environment === $environment; }

	/**
	 * Initialise settings form fields.
	 * It ignores the WC_Settings_API behavior.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function init_form_fields () 
	{ return; }

	/**
	 * Output the gateway settings screen.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_options ()
	{ require_once(CoreConnector::plugin()->getTemplatePath().'/admin/redirection.php'); }
	
	/**
	 * Processes and saves options.
	 * If there is an error thrown, will continue to save 
	 * and validate fields, but will leave the erroring field out.
	 * It ignores the WC_Settings_API behavior.
	 *
	 * @since 1.0.0
	 * @return bool was anything saved?
	 */
	public function process_admin_options ()
	{ return false; }

	/**
	 * Get waiting status.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public abstract function get_waiting_status () : string;

	/**
	 * Get paid status.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public abstract function get_paid_status () : string;

	/**
	 * Return the name of the option in the WP DB.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_option_key() 
	{ return 'pgly_erede_gateway'; }
}