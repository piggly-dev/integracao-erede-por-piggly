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
			'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjAgMCAyNi45MiAyNi45MiI+PHBhdGggZD0iTTIzLjM1LDIzLjM5YTMuOTMsMy45MywwLDAsMS0yLjgtMS4xNmwtNC00YS43NS43NSwwLDAsMC0xLjA2LDBMMTEuNCwyMi4yNWEzLjk0LDMuOTQsMCwwLDEtMi43OSwxLjE2aC0uOGw1LjEyLDUuMTFhNC4wOCw0LjA4LDAsMCwwLDUuNzgsMGw1LjEzLTUuMTNaIiB0cmFuc2Zvcm09InRyYW5zbGF0ZSgtMi4zNiAtMi44KSIgc3R5bGU9ImZpbGw6bm9uZSIvPjxwYXRoIGQ9Ik04LjYxLDkuMTFhMy45LDMuOSwwLDAsMSwyLjc5LDEuMTZsNC4wNiw0LjA1YS43NS43NSwwLDAsMCwxLjA2LDBsNC00YTQsNCwwLDAsMSwyLjgtMS4xNWguNDlMMTguNzEsNGE0LjA4LDQuMDgsMCwwLDAtNS43OCwwTDcuODEsOS4xMVoiIHRyYW5zZm9ybT0idHJhbnNsYXRlKC0yLjM2IC0yLjgpIiBzdHlsZT0iZmlsbDpub25lIi8+PHBhdGggZD0iTTI4LjA4LDEzLjM3LDI1LDEwLjI3YS41NC41NCwwLDAsMS0uMjIsMEgyMy4zNWEyLjgyLDIuODIsMCwwLDAtMiwuODFsLTQsNGExLjk0LDEuOTQsMCwwLDEtMS4zNy41NywxLjkxLDEuOTEsMCwwLDEtMS4zNy0uNTdsLTQuMDYtNC4wNWEyLjc0LDIuNzQsMCwwLDAtMi0uODFINi44OGEuNjUuNjUsMCwwLDEtLjIxLDBMMy41NiwxMy4zN2E0LjA4LDQuMDgsMCwwLDAsMCw1Ljc4bDMuMTEsMy4xMWEuNjUuNjUsMCwwLDEsLjIxLDBIOC42MWEyLjc4LDIuNzgsMCwwLDAsMi0uODFsNC4wNi00LjA1YTIsMiwwLDAsMSwyLjc0LDBsNCw0YTIuNzgsMi43OCwwLDAsMCwyLC44MWgxLjQxYS41NC41NCwwLDAsMSwuMjIuMDVsMy4xLTMuMWE0LjEsNC4xLDAsMCwwLDAtNS43OCIgdHJhbnNmb3JtPSJ0cmFuc2xhdGUoLTIuMzYgLTIuOCkiIHN0eWxlPSJmaWxsOm5vbmUiLz48L3N2Zz4='
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
			CoreConnector::plugin()->getUrl().'assets/js/pgly-erede-gateway.settings.js',
			[],
			'2.0.16',
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