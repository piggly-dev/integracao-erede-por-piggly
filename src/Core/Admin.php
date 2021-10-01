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
			CoreConnector::__translate('e-Rede por Piggly'),
			'manage_woocommerce',
			CoreConnector::domain(),
			[$this, 'settings_page'],
			'data:image/svg+xml;base64,PHN2ZyB2aWV3Qm94PSIwIDAgMzIgMzIiIHN0eWxlPSJ3aWR0aDogMzJweDsgaGVpZ2h0OiAzMnB4IiB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciPjxwYXRoIGQ9Ik02LjU3LDIwLjU5SDEwYS41MS41MSwwLDAsMCwuNS0uNVYxNy42NGEuNS41LDAsMCwwLS41LS41SDYuNTdhLjUuNSwwLDAsMC0uNS41djIuNDVhLjUxLjUxLDAsMCwwLC41LjVaIiBzdHlsZT0iZmlsbDogbm9uZSIvPjxwYXRoIGQ9Ik02LjU3LDE1LjY4SDEwYS41LjUsMCwwLDAsMC0xSDYuNTdhLjUuNSwwLDAsMCwwLDFaIiBzdHlsZT0iZmlsbDogbm9uZSIvPjxwYXRoIGQ9Ik04LjI5LDIyLjA1SDYuNTdhLjUuNSwwLDAsMC0uNS41LjUxLjUxLDAsMCwwLC41LjVIOC4yOWEuNTEuNTEsMCwwLDAsLjUtLjVBLjUuNSwwLDAsMCw4LjI5LDIyLjA1WiIgc3R5bGU9ImZpbGw6IG5vbmUiLz48cGF0aCBkPSJNMTIsMjIuMDVIMTAuMjlhLjUuNSwwLDAsMC0uNS41LjUuNSwwLDAsMCwuNS41SDEyYS41LjUsMCwwLDAsLjUtLjVBLjUuNSwwLDAsMCwxMiwyMi4wNVoiIHN0eWxlPSJmaWxsOiBub25lIi8+PHBhdGggZD0iTTE1LjczLDIySDE0YS41LjUsMCwwLDAtLjUuNS41MS41MSwwLDAsMCwuNS41aDEuNzNhLjUuNSwwLDAsMCwuNS0uNUEuNTEuNTEsMCwwLDAsMTUuNzMsMjJaIiBzdHlsZT0iZmlsbDogbm9uZSIvPjxwYXRoIGQ9Ik0xOS40MywyMkgxNy43MWEuNDkuNDksMCwwLDAtLjQ5LjUuNS41LDAsMCwwLC41LjVoMS43MWEuNS41LDAsMCwwLC41LS41QS41LjUsMCwwLDAsMTkuNDMsMjJaIiBzdHlsZT0iZmlsbDogbm9uZSIvPjxwYXRoIGQ9Ik0yOCw2LjVIOS4xNGEuNS41LDAsMCwwLS41LjV2NS4yM0g0YS41LjUsMCwwLDAtLjUuNVYyNWEuNS41LDAsMCwwLC41LjVIMjMuNzFhLjQ5LjQ5LDAsMCwwLC4zNi0uMTUuNS41LDAsMCwwLC4xNC0uMzVWMjAuNTlIMjhhLjUuNSwwLDAsMCwuNS0uNVY3QS41LjUsMCwwLDAsMjgsNi41Wm0tNC43OSwxOEg0LjVWMTMuMjNIMjMuMjFWMjQuNVptNC4yOS00LjkxSDI0LjIxVjEyLjczYS41LjUsMCwwLDAtLjUtLjVIOS42NGwwLTFIMjcuNTFaTTkuNjYsOC41bDAtMUgyNy41VjguNjNaIiBzdHlsZT0iZmlsbDogbm9uZSIvPjwvc3ZnPg=='
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
	 * @since 1.0.0
	 * @return void
	 */
	public function debug_notice ()
	{
		?>
		<div class="notice notice-warning">
			<p>
				O <strong>Modo Debug</strong> do plugin <strong>e-Rede por Piggly</strong>
				está ativado, só mantenha este modo ativado para testes ou detecções de erros.
				<a href="<?php echo esc_url(admin_url('admin.php?page='.$this->_plugin->getDomain())); ?>">
				Clique aqui</a> para ir para as configurações do plugin.
			</p>
		</div>
		<?php
	}
}