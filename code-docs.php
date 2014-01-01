<?php
/*
Plugin Name: Code Docs
Plugin URI: http://andrewnorcross.com/plugins/documentation-manager/
Description: Create a documentation setup for theme and plugin releases
Version: 0.1
Author: Andrew Norcross
Author URI: http://andrewnorcross.com

    Copyright 2013 Andrew Norcross

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

    http://www.gravityhelp.com/documentation/page/Gform_column_input
*/

if( !defined( 'CDM_BASE' ) )
    define( 'CDM_BASE', plugin_basename(__FILE__) );

if( !defined( 'CDM_VER' ) )
    define( 'CDM_VER', '0.1' );


// include my secondary files
require_once('lib/admin.php');
require_once('lib/meta.php');
require_once('lib/front.php');

class Code_Docs_Core
{
    /**
     * Static property to hold our singleton instance
     * @var Code_Docs_Core
     */
    static $instance = false;


    /**
     * This is our constructor, which is private to force the use of
     * getInstance() to make this a Singleton
     *
     * @return Code_Docs_Core
     */
    private function __construct() {
        add_action      ( 'plugins_loaded',                             array( $this, 'textdomain'              )           );
        add_filter      ( 'plugin_action_links',                        array( $this, 'quick_link'              ), 10,  2   );

        register_activation_hook    ( __FILE__,                         array( $this, 'activate'                )           );
        register_deactivation_hook  ( __FILE__,                         array( $this, 'deactivate'              )           );

    }

    /**
     * If an instance exists, this returns it.  If not, it creates one and
     * retuns it.
     *
     * @return Code_Docs_Core
     */

    public static function getInstance() {
        if ( !self::$instance )
            self::$instance = new self;
        return self::$instance;
    }


    /**
     * load textdomain
     *
     * @return Code_Docs_Core
     */

    public function textdomain() {

        load_plugin_textdomain( 'cdm', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
    }

    /**
     * show settings link on plugins page
     *
     * @return Code_Docs_Core
     */

    public function quick_link( $links, $file ) {

        static $this_plugin;

        if ( ! $this_plugin ) {
            $this_plugin = plugin_basename( __FILE__ );
        }

        // check to make sure we are on the correct plugin
        if ( $file == $this_plugin ) {

            $settings_link  = '<a href="'.menu_page_url( 'documentation', 0 ).'">'. __( 'Settings', 'cdm' ).'</a>';

            array_unshift( $links, $settings_link );
        }

        return $links;

    }

    /**
     * our activation and deactivation hooks
     *
     * @return Code_Docs_Core
     */

    public function activate() {

        flush_rewrite_rules();
    }

    public function deactivate() {

        flush_rewrite_rules();
    }


/// end class
}


// Instantiate our class
$Code_Docs_Core = Code_Docs_Core::getInstance();
