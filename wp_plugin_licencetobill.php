<?php
/*
Plugin Name: LicenceToBill For Wordpress
Plugin URI:
Description: A simple wordpress plugin LicenceToBill
Version: 2.0.1
Author: Sebastien Rousset
Author URI: http://licencetobill.com
License: GPL2
*/
/*
Copyright 2013  Sebastien Rousset  (email : seb@licencetobill.com)

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
*/

require_once(sprintf("%s/licencetobill.php", dirname(__FILE__)));

/******************************************/
/* LTBaccess_shortcode			  */
/******************************************/
function LTBaccess_shortcode( $atts , $content = null ) {

	// Attributes
	extract( shortcode_atts(
		array(
			'keyfeature' => '',
			'display_text_if_noaccess' => '',
			'text_if_noaccess' => '',
		), $atts )
	);
	$current_user = '';
	$answer_content = '';
	// Code
	if ( is_user_logged_in() )
	{
		// Retrieve info of the current user
		$current_user = wp_get_current_user();
		$WP_LicenceToBill = new LicenceToBill($current_user->ID);

		// Retrieve feature info for the current user
		$result = $WP_LicenceToBill->features($current_user->ID, $keyfeature);
		if(isset($result->status))
			$answer_content = 'Please contact the administrator.<br />Status:'.$result->status.'<br />StatusCode:'.$result->status_code.'<br />Message:'.$result->message;
		else
		{
			if(isset($result->limitation))
			{
				switch ($result->limitation) {
					case 0:
						if($display_text_if_noaccess=='yes')
							if($text_if_noaccess == NULL || $text_if_noaccess =='')
								$answer_content = 'A content is protected. Please <a href="'.$result->url_choose_offer.'">upgrade</a>.';
							else
								$answer_content = preg_replace ('{{link_upgrade}}',$result->url_choose_offer, $text_if_noaccess);
						else
							$answer_content = '';
						break;
					default:
						$answer_content = $content;
						break;
					}
			}
			else // no limit
			{
				$answer_content = $content;
			}
		}
	}
	else
	{
		$answer_content = 'Ce contenu est prot&eacute;g&eacute;. Merci de vous <strong>connecter</strong> ou de <strong>cr&eacute;er un compte</strong>.';
	}
	return $answer_content;
}
add_shortcode( 'LTBaccess', 'LTBaccess_shortcode' );

/******************************************/
/* ltb_create_user						  */
/******************************************/

function ltb_create_user($user_id)
{
	$info = get_userdata( $user_id );

	$args = array(
			'ID' => $user_id,
			'show_admin_bar_front' => 'False'
	);
	wp_update_user( $args );

	$trialoffer = get_option('LTB_setting_trial');
	if(!isset($trialoffer) || trim($trialoffer) == '')
    	$WP_LicenceToBill = new LicenceToBill($user_id, $info->user_email, 12, False);
    else
    	$WP_LicenceToBill = new LicenceToBill($user_id, $info->user_email, 12, True);

}
add_action('user_register', 'ltb_create_user');

/******************************************/
/* class WP_Plugin_LicenceToBill		  */
/******************************************/

if(!class_exists('WP_Plugin_LicenceToBill'))
{
	class WP_Plugin_LicenceToBill
	{
		/**
		 * Construct the plugin object
		 */
		public function __construct()
		{
        	// Initialize Settings
            require_once(sprintf("%s/settings.php", dirname(__FILE__)));
            $WP_Plugin_Template_Settings = new WP_Plugin_Template_Settings();

		} // END public function __construct

		/**
		 * Activate the plugin
		 */
		public static function activate()
		{
			// Do nothing
		} // END public static function activate

		/**
		 * Deactivate the plugin
		 */
		public static function deactivate()
		{
			// Do nothing
		} // END public static function deactivate
	} // END class WP_Plugin_LicenceToBill
} // END if(!class_exists('WP_Plugin_LicenceToBill'))

if(class_exists('WP_Plugin_LicenceToBill'))
{
	// Installation and uninstallation hooks
	register_activation_hook(__FILE__, array('WP_Plugin_LicenceToBill', 'activate'));
	register_deactivation_hook(__FILE__, array('WP_Plugin_LicenceToBill', 'deactivate'));

	// instantiate the plugin class
	$wp_plugin_licencetobill = new WP_Plugin_LicenceToBill();

    // Add a link to the settings page onto the plugin page
    if(isset($wp_plugin_licencetobill))
    {
        // Add the settings link to the plugins page
        function plugin_settings_link($links)
        {
            $settings_link = '<a href="options-general.php?page=wp_plugin_licencetobill">Settings</a>';
            array_unshift($links, $settings_link);
            return $links;
        }

        $plugin = plugin_basename(__FILE__);
        add_filter("plugin_action_links_$plugin", 'plugin_settings_link');
    }
}
/******************************************/
/* LTBoffers_shortcode				      */
/******************************************/

function LTBoffers_shortcode( $atts ) {

	// Attributes
	extract( shortcode_atts(
		array(
			'url_if_anonymous' => '',
			'link_text' => '',
			'keyoffer' => '',
		), $atts )
	);
	$current_user = '';
	$answer_content = '';
	// Code
	if ( is_user_logged_in() )
	{
		// Retrieve info of the current user
		$current_user = wp_get_current_user();
		$WP_LicenceToBill = new LicenceToBill($current_user->ID);

		if(!isset($WP_LicenceToBill))
			$answer_content = 'Please contact the administrator.<br />Status:'.$result->status.'<br />StatusCode:'.$result->status_code.'<br />Message:'.$result->message;
		else
		{
			if(isset($keyoffer) && $keyoffer != '')
			{
				$urloffer ='';
				$offers = $WP_LicenceToBill->offers($current_user->ID);
				//var_dump($offers);
				//echo '<br /><br />';
				foreach($offers as $offer)
				{
					//var_dump($offer);
					//echo '<br /><br />';
					if (strcmp($offer->key_offer,$keyoffer) == 0)
					{
						$urloffer = $offer->url_choose_payment;
						break;
					}
				}
				if(isset($link_text) && $link_text != '')
					$answer_content = '<a href="'.$urloffer.'">'.$link_text.'</a>';
				else
					$answer_content = $urloffer;
			}
			else
			{
				if(isset($link_text) && $link_text != '')
					$answer_content = '<a href="'.$WP_LicenceToBill->url_choose_offer.'">'.$link_text.'</a>';
				else
					$answer_content = $WP_LicenceToBill->url_choose_offer;
			}
		}
	}
	else
	{
		if(isset($url_if_anonymous) && $url_if_anonymous != '')
			$answer_content = $url_if_anonymous;
			else
				$answer_content = '\\';

	}
	return $answer_content;
}
add_shortcode( 'LTBoffers', 'LTBoffers_shortcode' );

/******************************************/
/* LTBinvoices_shortcode				  */
/******************************************/

function LTBinvoices_shortcode( $atts ) {

	// Attributes
	extract( shortcode_atts(
		array(
			'default_link_invoices' => '',
			'link_text' => '',
		), $atts )
	);
	$current_user = '';
	$answer_content = '';
	// Code
	if ( is_user_logged_in() )
	{
		// Retrieve info of the current user
		$current_user = wp_get_current_user();
		$WP_LicenceToBill = new LicenceToBill($current_user->ID);

		if(!isset($WP_LicenceToBill))
			$answer_content = 'Please contact the administrator.<br />Status:'.$result->status.'<br />StatusCode:'.$result->status_code.'<br />Message:'.$result->message;
		else
		{
			if(isset($link_text) && $link_text != '')
				$answer_content = '<a href="'.$WP_LicenceToBill->url_invoices.'">'.$link_text.'</a>';
			else
				$answer_content = $WP_LicenceToBill->url_invoices;
		}
	}
	else
	{
		if(isset($default_link_invoices) && $default_link_invoices != '')
			$answer_content = $default_link_invoices;
			else
				$answer_content = '\\';

	}
	return $answer_content;
}
add_shortcode( 'LTBinvoices', 'LTBinvoices_shortcode' );

/******************************************/
/* LTBdeals_shortcode					  */
/******************************************/
function LTBdeals_shortcode( $atts ) {

	// Attributes
	extract( shortcode_atts(
		array(
			'default_link_deals' => '',
			'link_text' => '',
		), $atts )
	);
	$current_user = '';
	$answer_content = '';
	// Code
	if ( is_user_logged_in() )
	{
		// Retrieve info of the current user
		$current_user = wp_get_current_user();
		$WP_LicenceToBill = new LicenceToBill($current_user->ID);

		if(!isset($WP_LicenceToBill))
			$answer_content = 'Please contact the administrator.<br />Status:'.$result->status.'<br />StatusCode:'.$result->status_code.'<br />Message:'.$result->message;
		else
		{
			if(isset($link_text) && $link_text != '')
				$answer_content = '<a href="'.$WP_LicenceToBill->url_deals.'">'.$link_text.'</a>';
			else
				$answer_content = $WP_LicenceToBill->url_deals;
		}
	}
	else
	{
		if(isset($default_link_deals) && $default_link_deals != '')
			$answer_content = $default_link_deals;
			else
				$answer_content = '\\';

	}
	return $answer_content;
}
add_shortcode( 'LTBdeals', 'LTBdeals_shortcode' );


/************************************************/
/* content_restricted_alterntives_shortcode		*/
/************************************************/
function content_restricted_alterntives_shortcode( $atts , $content = null ) {

	// Attributes
	extract( shortcode_atts(
		array(
			'key_feature' => '',
		), $atts )
	);
	$current_user = '';
	$answer_content = '';
	// Code
	if ( is_user_logged_in() )
	{
		// Retrieve info of the current user
		$current_user = wp_get_current_user();
		$WP_LicenceToBill = new LicenceToBill($current_user->ID);

		// Retrieve feature info for the current user
		$result = $WP_LicenceToBill->features($current_user->ID, $key_feature);
		if(isset($result->status))
			$answer_content = 'Please contact the administrator.<br />Status:'.$result->status.'<br />StatusCode:'.$result->status_code.'<br />Message:'.$result->message;
		else
		{
			if(isset($result->limitation))
			{
				switch ($result->limitation) {
					case 0:
						$answer_content = $content;
						break;
					default:
						$answer_content = '';
						break;
					}
			}
			else // no limit
			{
				$answer_content = '';
			}
		}
	}
	else
	{
		$answer_content = $content;
	}
	return $answer_content;
}
add_shortcode( 'content_restricted_alterntives', 'content_restricted_alterntives_shortcode' );

/**
 * Hook to implement shortcode logic inside WordPress nav menu items
 * Shortcode code can be added using WordPress menu admin menu in description field
 */
function shortcode_menu( $item_output, $item ) {

    if ( !empty($item->description)) {
         $output = do_shortcode($item->description);

         if ( $output != $item->description )
               $item_output = $output;

        }

    return $item_output;

}

add_filter("walker_nav_menu_start_el", "shortcode_menu" , 10 , 2);