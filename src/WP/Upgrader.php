<?php
namespace Piggly\WooERedeGateway\WP;

use Exception;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\Interfaces\Runnable;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\Scaffold\Internationalizable;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\WP;

/**
 * Upgrade plugin.
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
class Upgrader extends Internationalizable implements Runnable
{
	/**
	 * Method to run all business logic.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function run ()
	{ 
		if ( !WP::is_admin() )
		{ return; }

		// Current version
		$version = \get_option('pgly_erede_gateway', '0' );

		// If version greater than plugin version, ignore
		if ( \version_compare($version, $this->_plugin->getVersion(), '>=') )
		{ return; }

		// New version
		\update_option('pgly_erede_gateway', $this->_plugin->getVersion());
	}
}