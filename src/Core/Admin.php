<?php
namespace Piggly\WooERedeGateway\Core;

use Piggly\WooERedeGateway\CoreConnector;

use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\Scaffold\Initiable;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\WP;

/**
 * Manages all admin menus and pages.
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
class Admin extends Initiable
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
		if ( !WP::is_pure_admin() )
		{ return; }

		WP::add_action(
			'admin_menu', 
			$this, 
			'add_menu', 
			99
		);

		WP::add_filter(
			'plugin_action_links_' . CoreConnector::plugin()->getBasename(),
			$this,
			'plugin_action_links'
		);

		if ( CoreConnector::debugger()->isDebugging() )
		{
			// Debug notice
			WP::add_action(
				'admin_notices', 
				$this,
				'debug_notice' 
			);
		}
	}

	/**
	 * Create a new menu at Wordpress admin menu bar.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function add_menu ()
	{
		add_menu_page(
			CoreConnector::__translate('Configurações'),
			CoreConnector::__translate('e-Rede'),
			'manage_woocommerce',
			CoreConnector::domain(),
			[$this, 'settings_page'],
			'data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PSIwIDAgMzIgMzIiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHBhdGggZD0iTTYuNTcsMjAuNTlIMTBhLjUxLjUxLDAsMCwwLC41LS41VjE3LjY0YS41LjUsMCwwLDAtLjUtLjVINi41N2EuNS41LDAsMCwwLS41LjV2Mi40NWEuNTEuNTEsMCwwLDAsLjUuNVoiLz48cGF0aCBkPSJNNi41NywxNS42OEgxMGEuNS41LDAsMCwwLDAtMUg2LjU3YS41LjUsMCwwLDAsMCwxWiIvPjxwYXRoIGQ9Ik04LjI5LDIyLjA1SDYuNTdhLjUuNSwwLDAsMC0uNS41LjUxLjUxLDAsMCwwLC41LjVIOC4yOWEuNTEuNTEsMCwwLDAsLjUtLjVBLjUuNSwwLDAsMCw4LjI5LDIyLjA1WiIvPjxwYXRoIGQ9Ik0xMiwyMi4wNUgxMC4yOWEuNS41LDAsMCwwLS41LjUuNS41LDAsMCwwLC41LjVIMTJhLjUuNSwwLDAsMCwuNS0uNUEuNS41LDAsMCwwLDEyLDIyLjA1WiIvPjxwYXRoIGQ9Ik0xNS43MywyMkgxNGEuNS41LDAsMCwwLS41LjUuNTEuNTEsMCwwLDAsLjUuNWgxLjczYS41LjUsMCwwLDAsLjUtLjVBLjUxLjUxLDAsMCwwLDE1LjczLDIyWiIvPjxwYXRoIGQ9Ik0xOS40MywyMkgxNy43MWEuNDkuNDksMCwwLDAtLjQ5LjUuNS41LDAsMCwwLC41LjVoMS43MWEuNS41LDAsMCwwLC41LS41QS41LjUsMCwwLDAsMTkuNDMsMjJaIi8+PHBhdGggZD0iTTI4LDYuNUg5LjE0YS41LjUsMCwwLDAtLjUuNXY1LjIzSDRhLjUuNSwwLDAsMC0uNS41VjI1YS41LjUsMCwwLDAsLjUuNUgyMy43MWEuNDkuNDksMCwwLDAsLjM2LS4xNS41LjUsMCwwLDAsLjE0LS4zNVYyMC41OUgyOGEuNS41LDAsMCwwLC41LS41VjdBLjUuNSwwLDAsMCwyOCw2LjVabS00Ljc5LDE4SDQuNVYxMy4yM0gyMy4yMVYyNC41Wm00LjI5LTQuOTFIMjQuMjFWMTIuNzNhLjUuNSwwLDAsMC0uNS0uNUg5LjY0bDAtMUgyNy41MVpNOS42Niw4LjVsMC0xSDI3LjVWOC42M1oiLz48L3N2Zz4='
		);
		
		add_submenu_page(
			CoreConnector::domain(),
			CoreConnector::__translate('Configurações'),
			CoreConnector::__translate('Configurações'),
			'manage_woocommerce',
			CoreConnector::domain(),
			'',
			1
		);
						
		add_submenu_page(
			CoreConnector::domain(),
			CoreConnector::__translate('Playground'),
			CoreConnector::__translate('Testar'),
			'manage_woocommerce',
			CoreConnector::domain().'_test',
			[$this, 'test_page'],
			21
		);
				
		add_submenu_page(
			CoreConnector::domain(),
			CoreConnector::__translate('Logs'),
			CoreConnector::__translate('Logs'),
			'manage_woocommerce',
			CoreConnector::domain().'_logs',
			[$this, 'logs_page'],
			41
		);
	}

	/**
	 * Add links to plugin settings page.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function plugin_action_links ( $links )
	{
		$plugin_links = array();
		$baseUrl = esc_url( admin_url( 'admin.php?page='.CoreConnector::domain() ) );

		$plugin_links[] = sprintf('<a href="%s">%s</a>', $baseUrl, CoreConnector::__translate('Configurações'));
		
		return array_merge( $plugin_links, $links );
	}

	/**
	 * Load plugin page settings.
	 * 
	 * @internal When update the CSS/JS, update version.
	 * @since 1.0.0
	 * @return void
	 */
	public function settings_page ()
	{
		// CSS and JS
		CoreConnector::enqueuePglyWpsAdmin(false);

		wp_enqueue_script(
			'pgly-erede-gateway-settings',
			CoreConnector::plugin()->getUrl().'assets/js/pgly-erede-por-piggly.settings.js',
			[],
			'1.0.0',
			true
		);

		wp_localize_script(
			'pgly-erede-gateway-settings',
			'eRedeSettings',
			[
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'x_security' => wp_create_nonce('pgly_erede_gateway_admin'),
				'plugin_url' => admin_url('admin.php?page='.CoreConnector::domain()),
				'assets_url' => CoreConnector::plugin()->getUrl()
			]
		);

		echo '<div id="pgly-wps-plugin" class="pgly-wps--settings">';
		require_once(CoreConnector::plugin()->getTemplatePath().'admin/settings.php');
		echo '</div>';
	}

	/**
	 * Load plugin page to see logs.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function logs_page () 
	{
		// CSS and JS
		CoreConnector::enqueuePglyWpsAdmin(true);
		
		echo '<div id="pgly-wps-plugin" class="pgly-wps--settings">';
		require_once(CoreConnector::plugin()->getTemplatePath().'admin/pages/logs.php');
		echo '</div>';
	}

	/**
	 * Load plugin page to see tests.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function test_page () 
	{
		// CSS and JS
		CoreConnector::enqueuePglyWpsAdmin(true);
		
		echo '<div id="pgly-wps-plugin" class="pgly-wps--settings">';
		require_once(CoreConnector::plugin()->getTemplatePath().'admin/pages/test.php');
		echo '</div>';
	}

	/**
	 * Show debug notice.
	 * 
	 * @since 2.0.22
	 * @return void
	 */
	public function debug_notice ()
	{
		?>
		<div class="notice notice-warning">
			<p>
				O <strong>Modo Debug</strong> do plugin <strong>e-Rede por Piggly</strong>
				está ativado, só mantenha este modo ativado para testes ou detecções de erros.
				<a href="<?=admin_url('admin.php?page='.$this->_plugin->getDomain())?>">
				Clique aqui</a> para ir para as configurações do plugin.
			</p>
		</div>
		<?php
	}
}