<?php
/**
 * Author:  Andrei Baicus <andrei@themeisle.com>
 * On:      21/06/2018
 */

namespace ThemeIsle;

/**
 * Class Quiet_Skin
 */
class Quiet_Skin extends \WP_Upgrader_Skin {
	/**
	 * Done Header.
	 *
	 * @var bool
	 */
	public $done_header = true;

	/**
	 * Done Footer.
	 *
	 * @var bool
	 */
	public $done_footer = true;

	/**
	 * Feedback function overwrite.
	 *
	 * @param string $string
	 */
	public function feedback( $string ) {
		// keep it quiet
	}
}