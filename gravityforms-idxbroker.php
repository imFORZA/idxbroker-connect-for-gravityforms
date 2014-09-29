<?php
/*
Plugin Name: Gravity Forms IDX Broker Add-On
Plugin URI: http://www.brandonhubbard.com
Description: Integrates Gravity Forms with IDX Broker allowing form submissions to be automatically sent to your IDX Broker account.
Version: 1.0.1
Author: imFORZA
Author URI: http://www.imforza.com


IDX Broker API Documentation: http://middleware.idxbroker.com/docs/api/1.0.4/leads.php

*/

// Update Feature
require_once('wp-updates-plugin.php');
new WPUpdatesPluginUpdater_714( 'http://wp-updates.com/api/2/plugin', plugin_basename(__FILE__));

 
// Make sure Gravity Forms is active and already loaded.
if (class_exists("GFForms")) {
 
    // The Add-On Framework is not loaded by default.
    // Use the following function to load the appropriate files.
    GFForms::include_addon_framework();
 
    class GFIDXBroker extends GFAddOn {
 
        // The following class variables are used by the Framework.
        // They are defined in GFAddOn and should be overridden.
 
        // The version number is used for example during add-on upgrades.
        protected $_version = "1.0";
 
        // The Framework will display an appropriate message on the plugins page if necessary
        protected $_min_gravityforms_version = "1.8.7";
 
        // A short, lowercase, URL-safe unique identifier for the add-on.
        // This will be used for storing options, filters, actions, URLs and text-domain localization.
        protected $_slug = "gravityformsidxbroker";
 
        // Relative path to the plugin from the plugins folder.
        protected $_path = "gravityforms-idxbroker/gravityforms-idxbroker.php";
 
        // Full path the the plugin.
        protected $_full_path = __FILE__;
 
        // Title of the plugin to be used on the settings page, form settings and plugins page.
        protected $_title = "Gravity Forms IDX Broker Add-On";
 
        // Short version of the plugin title to be used on menus and other places where a less verbose string is useful.
        protected $_short_title = "IDX Broker Add-On";
    }
    
    
/* Sends all Gravity Form Submissions to IDX Broker */    
add_action('gform_after_submission', 'gravityforms_to_idxbroker_leads', 10, 2);
function gravityforms_to_idxbroker_leads($entry, $form) {
    
    // Check for IDX API Key
    if(!get_option('idx_broker_apikey'))
	return false;

	// Headers for API Call
	$headers = array(
		'Content-Type' => 'application/x-www-form-urlencoded',
		'accesskey' => get_option('idx_broker_apikey'),
		'outputtype' => 'json'
	);
    
    // IDX Broker Lead API URL
    $post_url = 'https://api.idxbroker.com/leads/lead';
    
    // Find Name and Email Fields to submit
	foreach($form['fields'] as &$field){
    	
		if($field['type'] == 'name') {
			// the first name portion of a name field is always x.3
			$firstnamepart = (string)$field['id'] . ".3";
			$firstName = $entry[$firstnamepart];
		}
		
		if($field['type'] == 'name') {
			// the last name portion of a name field is always x.6
			$lastnamepart = (string)$field['id'] . ".6";
			$lastName = $entry[$lastnamepart];
		}
		
		if($field['type'] == 'email') {
			$email = $entry[$field['id']];
		}
		
		if($field['type'] == 'phone') {
    		$phone = $entry[$field['id']];
        }
		
		if($field['type'] == 'address') {
    		$streetpart = (string)$field['id'] . ".1";
    		$street = $entry[$streetpart];
    		$citypart = (string)$field['id'] . ".3";
    		$city = $entry[$citypart];
    		$statepart = (string)$field['id'] . ".4";
    		$state = $entry[$statepart];
    		$zippart = (string)$field['id'] . ".5";
    		$zip = $entry[$zippart];
    		$countrypart = (string)$field['id'] . ".6";
    		$country = $entry[$countrypart];
        }
	}
	
	$idxpassword = wp_generate_password();

    // IDX Broker Fields
	$body = array(
		'firstName' => $firstName,
		'lastName' => $lastName,
		'email'=> $email,
		'address' => $street,
		'city' => $city,
		'stateProvince' => $state,
		'country' => $country,
		'zipCode' => $zip,
		'actualCategory' => 'Contact',
		'phone' => $phone,
		'password' => $idxpassword
	);


    // Send Leads to IDX Broker
    $response = wp_remote_post( $post_url, array(
	'method' => 'PUT',
	'timeout' => 45,
	//'redirection' => 5,
	//'httpversion' => '1.0',
	//'blocking' => true,
	'headers' => $headers,
	'body' => $body,
	//'cookies' => array()
    ) );
    
    
    // Display Response when in Debug Mode
   if (WP_DEBUG === true) {
        echo 'IDX Broker API Response:<pre>';
        print_r( $response );
        echo '</pre>';
    }    



// Send Email to Submitter with IDX Login Info
$message = "
Thank you for completing our form.

Our team will be with in touch with you as soon as possible.

In the meantime, we have gone ahead and created a FREE Member’s Only account through our website. With this account you can Search All Local Listings, Save Listings, Receive New Listing Notifications and even Book Showings….all from your computer.

To start using your FREE account please visit our website at [". get_bloginfo('url') ."] and click on the LOGIN link. Your login information is:

Username: ". $email ."
Password: ". $idxpassword ."

 "; // end message
 
 // Send
 wp_mail($email, '['.get_bloginfo('name') .'] Access Your Complimentary Account Now', $message);



} // end gravityforms_to_idxbroker_leads


} // end GFIDXBroker
 
new GFIDXBroker();


