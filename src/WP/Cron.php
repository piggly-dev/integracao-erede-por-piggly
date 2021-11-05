<?php
namespace Piggly\WooERedeGateway\WP;

use Piggly\WooERedeGateway\Core\Api\Payload;
use Piggly\WooERedeGateway\Core\Gateway\AbstractGateway;
use Piggly\WooERedeGateway\CoreConnector;

use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\Scaffold\Initiable;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\WP;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Plugin;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Settings\KeyingBucket;

use WC_Order;

/**
 * Cronjob tasks.
 * 
 * @package \Piggly\WooERedeGateway
 * @subpackage \Piggly\WooERedeGateway\WP
 * @version 1.0.0
 * @since 1.0.0
 * @category WP
 * @author Caique Araujo <caique@piggly.com.br>
 * @author Piggly Lab <dev@piggly.com.br>
 * @license GPLv3 or later
 * @copyright 2021 Piggly Lab <dev@piggly.com.br>
 */
class Cron extends Initiable
{
	/**
	 * Available frequencies.
	 * 
	 * @var string
	 * @since 1.0.0
	 */
	const AVAILABLE_FREQUENCIES = [
		'everyminute',
		'everyfifteen', 
		'twicehourly', 
		'hourly', 
		'daily', 
		'weekly', 
		'monthly'
	];

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
			'cron_schedules',
			$this,
			'schedules',
			99
		);

		WP::add_action(
			'pgly_cron_erede_gateway_update_orders',
			$this,
			'update_orders'
		);
	}

	/**
	 * Update all orders which are still waiting...
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function update_orders ()
	{
		WC()->mailer();
		
		$settings = $this->_plugin->settings()->bucket();
		CoreConnector::debugger()->debug('Iniciando a tarefa cron para leitura dos pagamentos pendentes via e-Rede');
		
		$orders = wc_get_orders(array(
			'limit'          => -1,
			'payment_method' => array( 'pgly_erede_gateway_debit', 'pgly_erede_gateway_credit' ),
			'status'         => [
				$settings->get('credit', new KeyingBucket())->get('waiting_status', 'on-hold'),
				$settings->get('debit', new KeyingBucket())->get('waiting_status', 'on-hold')
			]
		));

		foreach ( $orders as $order )
		{
			$order = $order instanceof WC_Order ? $order : new WC_Order($order);
			
			$payment_gateway = wc_get_payment_gateway_by_order($order);
			$payload         = Payload::fill($order);
			$tid             = $payload->get('tid', null);

			if ( $payment_gateway instanceof AbstractGateway ) 
			{
				if ( !is_null($tid) ) 
				{ $payment_gateway->process_pending( $order, $tid, $payload ); }
			}
		}
	}

	/**
	 * All schedules available to current cron jobs.
	 *
	 * @param array $schedules
	 * @since 1.0.0
	 * @return array
	 */
	public function schedules ( array $schedules ) : array
	{
		$schedules['everyminute'] = [
			'interval' => 60,
			'display' => 'Uma vez a cada minuto'
		];

		$schedules['everyfifteen'] = [
			'interval' => 900,
			'display' => 'Uma vez a cada quinze minutos'
		];

		$schedules['twicehourly'] = [
			'interval' => 1800,
			'display' => 'Duas vezes a cada hora'
		];

		$schedules['monthly'] = [
			'interval' => 2635200,
			'display' => 'Uma vez por mês'
		];

		return $schedules;
	}

	/**
	 * Create cron jobs.
	 *
	 * @param Plugin $plugin
	 * @since 1.0.0
	 * @return void
	 */
	public static function create ( Plugin $plugin ) 
	{
		/** @var KeyingBucket $settings */
		$settings = $plugin->settings()->bucket()->get('global', new KeyingBucket());

		// --- Cronjob to do transactions
		if ( wp_next_scheduled('pgly_cron_erede_gateway_update_orders') )
		{ wp_clear_scheduled_hook( 'pgly_cron_erede_gateway_update_orders' ); }

		wp_schedule_event(
			current_time('timestamp'), 
			$settings->get('cron_frequency', 'everyfifteen'), 
			'pgly_cron_erede_gateway_update_orders' 
		);
	}

	/**
	 * Destroy cron jobs.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public static function destroy ()
	{ wp_clear_scheduled_hook( 'pgly_cron_erede_gateway_update_orders' ); }
}