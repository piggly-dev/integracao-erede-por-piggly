<?php
namespace Piggly\WooERedeGateway\Core;

use Exception;
use Piggly\WooERedeGateway\Core\Api\Payload;
use Piggly\WooERedeGateway\Core\Gateway\CreditGateway;
use Piggly\WooERedeGateway\Core\Gateway\DebitGateway;
use Piggly\WooERedeGateway\Core\Managers\SettingsManager;
use Piggly\WooERedeGateway\CoreConnector;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\Scaffold\Ajaxable;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\WP;
use Piggly\WooERedeGateway\WP\Cron;
use WC_Order;

/**
 * Manages all AJAX endpoints.
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
class Ajax extends Ajaxable
{
	/**
	 * Handle all admin endpoints to ajax.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function handlers ()
	{
		$priv = [
			'pgly_erede_gateway_get_plugin_settings',
			'pgly_erede_gateway_set_plugin_settings',
			'pgly_erede_gateway_admin_cron_run',
			'pgly_erede_gateway_admin_capture',
			'pgly_erede_gateway_admin_refund',
			'pgly_erede_gateway_admin_clean_logs'
		];

		foreach ( $priv as $action )
		{
			WP::add_action(
				'wp_ajax_'.$action,
				$this,
				$action
			); 
		}
	}

	/**
	 * Get all plugin settings.
	 * 
	 *	@since 1.0.0
	 * @return void
	 */
	public function pgly_erede_gateway_get_plugin_settings () 
	{
		$this
			->prepare('pgly_erede_gateway_admin', 'xSecurity')
			->need_capability('manage_woocommerce');

		$this->success((new SettingsManager())->getSettings());
		exit;
	}

	/**
	 * Set plugin settings by section.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function pgly_erede_gateway_set_plugin_settings ()
	{
		$this
			->prepare('pgly_erede_gateway_admin', 'xSecurity')
			->need_capability('manage_woocommerce');

		// Plugin setting section
		$section = \filter_input(\INPUT_POST, 'section', \FILTER_SANITIZE_STRING);
		// Plugin setting data
		$data = \filter_input(\INPUT_POST, 'data', \FILTER_SANITIZE_ENCODED);

		$sManager = new SettingsManager();

		try
		{ $sManager->saveSettings($section, json_decode(urldecode($data), true)); }
		catch ( Exception $e )
		{
			$this->status(422)->error([
				'code' => 3,
				'message' => $e->getMessage()
			]);
		}

		$this->success([
			'message' => CoreConnector::__translate('Configurações salvas')
		]);
	}

	/**
	 * Run cron tasks.
	 * 
	 *	@since 1.0.0
	 * @return void
	 */
	public function pgly_erede_gateway_admin_capture () 
	{
		$this
			->prepare('pgly_erede_gateway_admin', 'xSecurity')
			->need_capability('manage_woocommerce');

		try
		{ 
			$order_id = filter_input ( INPUT_POST, 'oid', FILTER_SANITIZE_STRING);

			if ( empty($order_id) )
			{ throw new Exception(CoreConnector::__translate('Pedido não enviado')); }

			$order = new WC_Order( $order_id );

			if ( empty($order) )
			{ throw new Exception(CoreConnector::__translate('Pedido não localizado')); }

			if ( \strpos($order->get_payment_method(), CoreConnector::plugin()->getName()) === false )
			{ throw new Exception(CoreConnector::__translate('Método de pagamento incompatível')); }

			$payload = Payload::fill($order);

			if ( empty($payload) )
			{ throw new Exception(CoreConnector::__translate('Payload de pagamento não localizado')); }
			
			$kind = $payload->get('kind');

			if ( $kind !== 'credit' )
			{ throw new Exception(CoreConnector::__translate('Captura disponível apenas para operação de crédito')); }

			if ( !$payload->isAuthorized() )
			{ throw new Exception(CoreConnector::__translate('Operação de captura não permitida')); }

			$response = (new CreditGateway())->process_capture($order, $payload);

			if ( $response !== true )
			{ throw new Exception(CoreConnector::__translate('Não foi possível capturar o pagamento no momento')); }
			
			$this->success([
				'message'=>CoreConnector::__translate('Pagamento capturado, atualize a página para continuar')
			]);
		}
		catch ( Exception $e )
		{ $this->exceptionError($e); }
	}

	/**
	 * Run cron tasks.
	 * 
	 *	@since 1.0.0
	 * @return void
	 */
	public function pgly_erede_gateway_admin_refund () 
	{
		$this
			->prepare('pgly_erede_gateway_admin', 'xSecurity')
			->need_capability('manage_woocommerce');

		try
		{ 
			$order_id = filter_input ( INPUT_POST, 'oid', FILTER_SANITIZE_STRING);

			if ( empty($order_id) )
			{ throw new Exception(CoreConnector::__translate('Pedido não enviado')); }

			$order = new WC_Order( $order_id );

			if ( empty($order) )
			{ throw new Exception(CoreConnector::__translate('Pedido não localizado')); }

			if ( \strpos($order->get_payment_method(), CoreConnector::plugin()->getName()) === false )
			{ throw new Exception(CoreConnector::__translate('Método de pagamento incompatível')); }

			$payload = Payload::fill($order);

			if ( empty($payload) )
			{ throw new Exception(CoreConnector::__translate('Payload de pagamento não localizado')); }
			
			$kind = $payload->get('kind') === 'credit' ? CreditGateway::class : DebitGateway::class;

			if ( $payload->isAuthorized() )
			{
				$order->update_status('cancelled');
				(new $kind())->process_refund($order->get_id());
			}
			else if ( $payload->isCaptured() )
			{
				$order->update_status('refunded');
				(new $kind())->process_refund($order->get_id());
			}
			else
			{ throw new Exception(CoreConnector::__translate('Operação de reembolso não permitida')); }

			$this->success([
				'message'=>CoreConnector::__translate('Pagamento reembolsado, atualize a página para continuar')
			]);
		}
		catch ( Exception $e )
		{ $this->exceptionError($e); }
	}

	/**
	 * Run cron tasks.
	 * 
	 *	@since 1.0.0
	 * @return void
	 */
	public function pgly_erede_gateway_admin_cron_run () 
	{
		$this
			->prepare('pgly_erede_gateway_admin', 'xSecurity')
			->need_capability('manage_woocommerce');

		try
		{ 
			(new Cron($this->_plugin))->update_orders(); 
			$this->success([
				'message'=>CoreConnector::__translate('Pedidos atualizados')
			]);
		}
		catch ( Exception $e )
		{ $this->exceptionError($e); }
	}

	/**
	 * Clean pix.
	 * 
	 *	@since 1.0.0
	 * @return void
	 */
	public function pgly_erede_gateway_admin_clean_logs ()
	{
		$this
			->prepare('pgly_erede_gateway_admin', 'xSecurity')
			->need_capability('manage_woocommerce');

		$path  = \WP_CONTENT_DIR.'integracao-erede-por-piggly/';
		$files = [];
		$files = glob($path.'*.log');

		foreach ( $files as $file )
		{ \unlink($file); }

		$this->success([
			'message'=>CoreConnector::__translate('Logs limpos com sucesso, atualize para continuar')
		]);
	}
}