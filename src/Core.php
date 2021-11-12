<?php
namespace Piggly\WooERedeGateway;

use DateTime;
use Piggly\WooERedeGateway\Core\Admin;
use Piggly\WooERedeGateway\Core\Ajax;
use Piggly\WooERedeGateway\Core\Metabox;
use Piggly\WooERedeGateway\Core\Woocommerce;
use Piggly\WooERedeGateway\WP\Cron;

use Piggly\WooERedeGateway\Vendor\Monolog\Handler\StreamHandler;
use Piggly\WooERedeGateway\Vendor\Monolog\Logger;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core as WordpressCore;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\WP;

/**
 * Plugin main core.
 * 
 * @package \Piggly\WooERedeGateway
 * @subpackage \Piggly\WooERedeGateway
 * @version 1.0.0
 * @since 1.0.0
 * @category Core
 * @author Caique Araujo <caique@piggly.com.br>
 * @author Piggly Lab <dev@piggly.com.br>
 * @license GPLv3 or later
 * @copyright 2021 Piggly Lab <dev@piggly.com.br>
 */
class Core extends WordpressCore
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
		// Debug state
		$this->debug()->changeState($this->settings()->bucket()->get('global')->get('debug', false));

		// Create logger
		$this->logger();

		// Admin global menu settings
		$this->initiable(Admin::class);
		$this->initiable(Ajax::class);
		$this->initiable(Cron::class);

		// After plugins loaded
		WP::add_action('plugins_loaded', $this, 'after_load' );
	}

	/**
	 * Run after plugin loaded...
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function after_load ()
	{		
		// Display an admin notice when WooCommerce is not enabled.
		if ( !class_exists('WC_Order') )
		{
			WP::add_action('admin_notices', $this, 'missing_woocommerce');
			return;
		}

		$this->initiable(Metabox::class);
		$this->initiable(Woocommerce::class);
	}
	
	/**
	 * Display a notice warning Woocommerce is not activated or installed,
	 * and it's required.
	 * 
	 * @since 1.0.0
	 * @return void
	 */
	public function missing_woocommerce () 
	{
		$is_installed = false; 
		if ( !class_exists ( 'woocommerce' ) ) { $is_installed = true; }
		
		?>
		<div class="error">
			<p><?php CoreConnector::_etranslate('O plugin <strong>e-Rede por Piggly</strong> necessita da última versão do Woocommerce para funcionar.'); ?></p>
		
			<?php if ( $is_installed && current_user_can( 'install_plugins' ) ) : ?>
				<p><a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'plugins.php?action=activate&plugin=woocommerce/woocommerce.php&plugin_status=active' ), 'activate-plugin_woocommerce/woocommerce.php' ) ); ?>" class="button button-primary"><?php CoreConnector::_etranslate( 'Ativar WooCommerce' ); ?></a></p>
			<?php else : ?>
				<?php if ( current_user_can( 'install_plugins' ) ) : ?>
					<p><a href="<?php echo esc_url( wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' ) ); ?>" class="button button-primary"><?php CoreConnector::_etranslate( 'Instalar WooCommerce' ); ?></a></p>
				<?php else : ?>
					<p><a href="http://wordpress.org/plugins/woocommerce/" class="button button-primary"><?php CoreConnector::_etranslate( 'Instalar WooCommerce' ); ?></a></p>
				<?php endif; ?>
			<?php endif; ?>
		</div> 
		<?php
	}

	/**
	 * Prepare and create logger.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	protected function logger ()
	{
		$path = \WP_CONTENT_DIR.'/integracao-erede-por-piggly/';

		if ( !\is_dir($path) )
		{ wp_mkdir_p($path); }

		if ( !\file_exists($path.'.htaccess') )
		{ \file_put_contents($path.'.htaccess', 'Options -Indexes'); }

		$now = (new DateTime('now', wp_timezone()))->format('Y-m-d');

		$hash = \sprintf(
			'integracao-erede-por-piggly-%s-%s.log', 
			$now,
			\md5($now.\get_option('pgly_erede_gateway_key', 'null'))
		);

		if ( !\file_exists($path.$hash) ) 
		{ touch($path.$hash); }

		// create a log channel
		$log = new Logger('pgly-erede-gateway');
		$log->pushHandler(new StreamHandler($path.$hash, Logger::DEBUG, true, 0666));

		$this->debug()->setLogger($log);
	}
}