<?php
/*
   Plugin Name: TrulyWP CDN
   Text Domain: trulywp-cdn
   Description: TrulyWP CDN manager for your WordPress site.
   Author: TrulyWP, KeyCDN
   Author URI: https://trulywp.com
   License: GPLv2 or later
   Version: 1.0.5
 */

/*
   Copyright (C)  2018 TrulyWP
   Copyright (C)  2017 KeyCDN

   This program is free software; you can redistribute it and/or modify
   it under the terms of the GNU General Public License as published by
   the Free Software Foundation; either version 2 of the License, or
   (at your option) any later version.

   This program is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU General Public License for more details.

   You should have received a copy of the GNU General Public License along
   with this program; if not, write to the Free Software Foundation, Inc.,
   51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

/* Check & Quit */
defined('ABSPATH') OR exit;


/* constants */
define('TRULYWP_CDN_FILE', __FILE__);
define('TRULYWP_CDN_DIR', dirname(__FILE__));
define('TRULYWP_CDN_BASE', plugin_basename(__FILE__));
define('TRULYWP_CDN_MIN_WP', '3.8');


/* loader */
add_action(
    'plugins_loaded',
    [
        'TrulyWP_CDN',
        'instance',
    ]
);


/* uninstall */
register_uninstall_hook(
    __FILE__,
    [
        'TrulyWP_CDN',
        'handle_uninstall_hook',
    ]
);


/* activation */
register_activation_hook(
    __FILE__,
    [
        'TrulyWP_CDN',
        'handle_activation_hook',
    ]
);



/* autoload init */
spl_autoload_register('TRULYWP_CDN_autoload');

/* autoload funktion */
function TRULYWP_CDN_autoload($class) {
    if ( in_array($class, ['TrulyWP_CDN', 'TrulyWP_Rewriter', 'TrulyWP_CDN_Settings']) ) {
        require_once(
            sprintf(
                '%s/inc/%s.class.php',
                TRULYWP_CDN_DIR,
                strtolower($class)
            )
        );
    }
}
