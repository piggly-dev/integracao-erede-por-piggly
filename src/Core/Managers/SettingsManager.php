<?php
namespace Piggly\WooERedeGateway\Core\Managers;

use Exception;

use Piggly\WooERedeGateway\Core\Woocommerce;
use Piggly\WooERedeGateway\CoreConnector;
use Piggly\WooERedeGateway\WP\Cron;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Settings\KeyingBucket;

/**
 * Manages all plugin settings.
 * 
 * @package \Piggly\WooERedeGateway
 * @subpackage \Piggly\WooERedeGateway\Core\Managers
 * @version 1.0.0
 * @since 1.0.0
 * @category Managers
 * @author Caique Araujo <caique@piggly.com.br>
 * @author Piggly Lab <dev@piggly.com.br>
 * @license GPLv3 or later <key>
 * @copyright 2021 Piggly Lab <dev@piggly.com.br>
 */
class SettingsManager
{
	/**
	 * All sections available to settings.
	 * 
	 * @var array
	 * @since 1.0.0
	 */
	const SETTINGS_SECTIONS = [
		'global',
		'credit',
		'debit'
	];

	/**
	 * Export settings as array.
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function getSettings () : array
	{ 
		/** @var KeyingBucket $settings */
		$settings = CoreConnector::settings();
		
		$statusesOp = [];
		$statuses   = Woocommerce::getAvailableStatuses();

		foreach ( $statuses as $key => $label )
		{
			$key = \str_replace('wc-','',$key);

			$statusesOp[] = [
				'value' => $key,
				'label' => $label.' ('.$key.')'
			];
		}

		$settings->getAndCreate('runtime', [
			'statuses' => $statusesOp
		]);
		
		return CoreConnector::settingsManager()->bucket()->export() ?? []; 
	}

	/**
	 * Save and parse settings data by $section.
	 * $section must be a value of static::SETTINGS_SECTION.
	 *
	 * @param string $section
	 * @param array $data
	 * @since 1.0.0
	 * @return void
	 * @throws Exception
	 */
	public function saveSettings ( string $section, array $data )
	{
		if ( !\in_array($section, static::SETTINGS_SECTIONS, true) )
		{ throw new Exception(CoreConnector::__translate('Não foi possível salvar: Configurações inválidas.')); }

		switch ( $section )
		{
			case 'global':
				$this->saveGlobal($data);
				break;
			case 'credit':
				$this->saveCredit($data);
				break;
			case 'debit':
				$this->saveDebit($data);
				break;
		}

		// Always save
		CoreConnector::settingsManager()->save();
	}

	/**
	 * Save global section.
	 *
	 * @param array $data
	 * @since 1.0.0
	 * @return void
	 * @throws Exception
	 */
	protected function saveGlobal ( array $data )
	{
		/** @var KeyingBucket $settings */
		$settings = CoreConnector::settings()->getAndCreate('global', new KeyingBucket());

		$this->prepare(
			$settings,
			$data,
			[
				'debug' => [ 'default' => false, 'sanitize' => \FILTER_VALIDATE_BOOLEAN ],
				'env' => [ 'default' => 'test', 'sanitize' => \FILTER_SANITIZE_STRING, 'allowed' => ['test', 'prod'] ],
				'pv' => [ 'default' => '', 'sanitize' => \FILTER_SANITIZE_STRING ],
				'token' => [ 'default' => '', 'sanitize' => \FILTER_SANITIZE_STRING ],
				'soft_descriptor' => [ 'default' => '', 'sanitize' => \FILTER_SANITIZE_STRING ],
				'auto_refund' => [ 'default' => false, 'sanitize' => \FILTER_VALIDATE_BOOLEAN ],
				'cron_frequency' => [ 'default' => 'everyfifteen', 'sanitize' => \FILTER_SANITIZE_STRING, 'allowed' => Cron::AVAILABLE_FREQUENCIES ],
			]
		);

		Cron::create(CoreConnector::plugin());
	}

	/**
	 * Save credit section.
	 *
	 * @param array $data
	 * @since 1.0.0
	 * @return void
	 * @throws Exception
	 */
	protected function saveCredit ( array $data )
	{
		/** @var KeyingBucket $settings */
		$settings = CoreConnector::settings()->getAndCreate('credit', new KeyingBucket());

		$this->prepare(
			$settings,
			$data,
			[
				'enabled' => [ 'default' => false, 'sanitize' => \FILTER_VALIDATE_BOOLEAN ],
				'title' => [ 'default' => CoreConnector::__translate('Cartão de Crédito'), 'sanitize' => \FILTER_SANITIZE_STRING ],
				'waiting_status' => [ 'default' => 'on-hold', 'sanitize' => \FILTER_SANITIZE_STRING ],
				'paid_status' => [ 'default' => 'processing', 'sanitize' => \FILTER_SANITIZE_STRING ],
				'auto_capture' => [ 'default' => false, 'sanitize' => \FILTER_VALIDATE_BOOLEAN ],
				'min_parcels_value' => [ 'default' => 0, 'sanitize' => \FILTER_VALIDATE_INT ],				
				'max_parcels_number' => [ 'default' => 1, 'sanitize' => \FILTER_VALIDATE_INT ],				
				'partner_module' => [ 'default' => '', 'sanitize' => \FILTER_SANITIZE_STRING ],
				'partner_gateway' => [ 'default' => '', 'sanitize' => \FILTER_SANITIZE_STRING ],
			]
		);	
		
		if ( $settings->get('enabled', false) && !static::canEnable() )
		{ throw new Exception(CoreConnector::__translate('Configure o PV e o Token antes de habilitar')); }
	}

	/**
	 * Save debit section.
	 *
	 * @param array $data
	 * @since 1.0.0
	 * @return void
	 * @throws Exception
	 */
	protected function saveDebit ( array $data )
	{
		/** @var KeyingBucket $settings */
		$settings = CoreConnector::settings()->getAndCreate('debit', new KeyingBucket());

		$this->prepare(
			$settings,
			$data,
			[
				'enabled' => [ 'default' => false, 'sanitize' => \FILTER_VALIDATE_BOOLEAN ],
				'title' => [ 'default' => CoreConnector::__translate('Cartão de Débito'), 'sanitize' => \FILTER_SANITIZE_STRING ],
				'waiting_status' => [ 'default' => 'on-hold', 'sanitize' => \FILTER_SANITIZE_STRING ],
				'paid_status' => [ 'default' => 'processing', 'sanitize' => \FILTER_SANITIZE_STRING ],
			]
		);	
		
		if ( $settings->get('enabled', false) && !static::canEnable() )
		{ throw new Exception(CoreConnector::__translate('Configure o PV e o Token antes de habilitar')); }
	}

	/**
	 * Apply required and optional parses to
	 * $data array.
	 *
	 * @param array $data
	 * @param KeyingBucket $settings
	 * @param array $optional
	 * @since 1.0.0
	 * @return array
	 * @throws Exception
	 */
	public function prepare ( KeyingBucket $settings, array $data, array $fields = [] )
	{
		foreach ( $fields as $key => $meta )
		{
			$value = \filter_var( $data[$key] ?? '', $meta['sanitize'], FILTER_NULL_ON_FAILURE );

			if ( !$this->isFilled($value) || !\in_array($value, ($meta['allowed'] ?? [$value]), true) )
			{ $settings->set($key, ''); continue; }

			if ( !$this->isFilled($value) && ($meta['required'] ?? false) )
			{ throw new Exception(CoreConnector::__translate('Campo obrigatório não preenchido')); }

			$settings->set($key, $value);
		}
	} 

	/**
	 * Get default settings.
	 *
	 * @since 1.0.0
	 * @return KeyingBucket
	 */
	public static function defaults () : KeyingBucket
	{
		$settings = [
			'global' => [
				'debug' => false,
				'env' => 'test',
				'pv' => '',
				'token' => '',
				'soft_descriptor' => '',
				'auto_refund' => false,
				'cron_frequency' => 'everyfifteen'
			],
			'credit' => [
				'enabled' => false,
				'title' => __('Cartão de Crédito', 'pgly_erede_gateway'),
				'waiting_status' => 'on-hold',
				'paid_status' => 'processing',
				'auto_capture' => false,
				'min_parcels_value' => 0,
				'max_parcels_number' => 1,
				'partner_module' => '',
				'partner_gateway' => ''
			],
			'debit' => [
				'enabled' => false,
				'title' => __('Cartão de Débito', 'pgly_erede_gateway'),
				'waiting_status' => 'on-hold',
				'paid_status' => 'processing'
			],
		];

		return (new KeyingBucket())->import($settings);
	}

	/**
	 * Return if $var is filled.
	 *
	 * @param mixed $var
	 * @since 1.0.0
	 * @return boolean
	 */
	protected function isFilled ( $var )
	{ return !\is_null($var) && $var !== ''; }

	/**
	 * Return if can be enabled.
	 *
	 * @since 1.0.0
	 * @return boolean
	 */
	public static function canEnable ()
	{
		return 
			!empty(CoreConnector::settings()->get('global')->get('pv'))
			&& !empty(CoreConnector::settings()->get('global')->get('token'));
	}
}