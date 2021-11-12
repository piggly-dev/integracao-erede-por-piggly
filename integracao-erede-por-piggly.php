<?php
/**
 * @link https://studio.piggly.com.br/
 * @since 1.0.0
 * @version 1.0.0
 * @package \Piggly\WooERedeGateway
 * @author Caique Araujo <caique@piggly.com.br>
 * @author Piggly Lab <dev@piggly.com.br>
 * @copyright 2021 Piggly Lab <dev@piggly.com.br>
 * 
 * This code is released under the GPL licence version 3
 * or later, available here http://www.gnu.org/licenses/gpl-3.0.txt
 *
 * @wordpress-plugin
 * Plugin Name:       Integração e-Rede por Piggly
 * Plugin URI:        https://studio.piggly.com.br/
 * Description:       O melhor pagamento via e-Rede para o Woocomerce. Disponibilize pagamentos via crédito e débito com e-Rede.
 * Requires at least: 4.0
 * Requires PHP:      7.2
 * Version:           1.0.2
 * Author:            Piggly Lab
 * Author URI:        https://github.com/piggly-dev
 * License:           GPLv3 or later
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       pgly_erede_gateway
 * Domain Path:       /languages 
 * Network:           false
 */

use Piggly\WooERedeGateway\Core;
use Piggly\WooERedeGateway\Core\Managers\SettingsManager;
use Piggly\WooERedeGateway\CoreConnector;
use Piggly\WooERedeGateway\WP\Activator;
use Piggly\WooERedeGateway\WP\Deactivator;
use Piggly\WooERedeGateway\WP\Upgrader;

use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Plugin;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) exit;

/** @var string Currently plugin version. Start at version 1.0.0 and use SemVer - https://semver.org */
if (!defined('PGLY_EREDE_GATEWAY_VERSION')) define( 'PGLY_EREDE_GATEWAY_VERSION', '1.0.2' );

/** @var string Minimum php version required. */
if (!defined('PGLY_EREDE_GATEWAY_PHPVERSION')) define( 'PGLY_EREDE_GATEWAY_PHPVERSION', '7.2' );

/**
 * Check if plugin has requirements.
 *
 * @since 1.0.0
 * @return void
 */
function pgly_erede_gateway_requirements () : bool
{
	try
	{
		if ( version_compare( phpversion(), \PGLY_EREDE_GATEWAY_PHPVERSION, '<' ) )
		{ 
			throw new Exception(
				sprintf(
					__('A versão mínima requirida para o <strong>PHP</strong> é %s', 'pgly_erede_gateway'), 
					\PGLY_EREDE_GATEWAY_PHPVERSION
				)
			); 
		}

		require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

		if ( !is_plugin_active ('woocommerce/woocommerce.php') ) 
		{
			throw new Exception(
				__('Verifique se o <strong>Woocommerce</strong> está ativado', 'pgly_erede_gateway')
			); 
		}

		return true;
	}
	catch ( Exception $e )
	{
		add_action(
			'admin_notices',
			function () use ($e) {
				?>
				<div class="notice notice-error">
					<p>Não é possível habilitar o plugin <strong>e-Rede por Piggly</strong> no momento, certifique-se de atender os seguintes requisitos:</p>
					<p><?php echo esc_html($e->getMessage()); ?>.</p>
				</div>
				<?php

				// In case this is on plugin activation.
				if ( isset( $_GET['activate'] ) ) unset( $_GET['activate'] );
				// Desactivate plugin
				deactivate_plugins( plugin_basename( __FILE__ ) );
			}
		);

		return false;
	}
}

if ( pgly_erede_gateway_requirements() )
{
	// Include composer autoloader
	require __DIR__ . '/vendor/autoload.php';

	/**
	 * Global function holder. 
	 * Works similar to a singleton's instance().
	 * 
	 * @since 1.0.0
	 * @return Core
	 */
	function pgly_erede_gateway ()
	{
		// Prepare plugin runtime settings
		$plugin =
			(new Plugin('pgly_erede_gateway', 'pgly_erede_gateway_settings', SettingsManager::defaults()))
			->abspath(__FILE__)
			->url(__FILE__)
			->basename(__FILE__)
			->name('pgly_erede_gateway')
			->version(PGLY_EREDE_GATEWAY_VERSION)
			->minPhpVersion(PGLY_EREDE_GATEWAY_PHPVERSION);

		$core = new Core(
			$plugin,
			new Activator($plugin),
			new Deactivator($plugin),
			new Upgrader($plugin)
		);

		// Set global instance.
		CoreConnector::setInstance($core);

		// Return core
		return $core;
	}

	// Startup plugin core
	pgly_erede_gateway()->startup();
}