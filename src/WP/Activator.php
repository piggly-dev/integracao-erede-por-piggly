<?php
namespace Piggly\WooERedeGateway\WP;

use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\Interfaces\Runnable;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\WP;
use Piggly\WooERedeGateway\Vendor\Piggly\Wordpress\Core\Scaffold\Internationalizable;

/**
 * Activate plugin.
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
class Activator extends Internationalizable implements Runnable
{
	/**
	 * Method to run all business logic.
	 *
	 * @since 1.0.0
	 * @return void
	 */
	public function run ()
	{
		if ( !WP::is_pure_admin() )
		{ return; }

		// Create cronjobs
		Cron::create($this->_plugin);
	}
}