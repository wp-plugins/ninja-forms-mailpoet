<?php
/*
Plugin Name: Ninja Forms - MailPoet
Plugin URL: http://www.jeangalea.com
Description: Include a Wysija signup option with your Easy Digital Downloads checkout
Version: 1.0.0
Author: Jean Galea
Author URI: http://www.jeangalea.com
Text Domain: ninja_mailpoet
Domain Path: languages
*/


// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;


/**
 * Retrieve an array of MailPoet lists
 *
 * @since       1.0.0
 * @return      void
 */
function ninja_mailpoet_getlists() {

	if( ! class_exists( 'WYSIJA' ) )
		return array();

	$modelList   = &WYSIJA::get( 'list','model' );
	$wysijaLists = $modelList->get( array( 'name', 'list_id' ), array( 'is_enabled' => 1 ) );

	$lists = array();

	if( ! empty( $wysijaLists ) ) {
		foreach( $wysijaLists as $key => $list ) {
			$lists[] = array(
				'value' => $list['list_id'],
				'name'  => $list['name']
			);	
		}
	}
	
	return $lists;
}


/**
 * Register the form-specific settings
 *
 * @since       1.0.0
 * @return      void
 */
function ninja_mailpoet_add_form_settings() {

	if ( ! function_exists( 'ninja_forms_register_tab_metabox_options' ) )
		return;

	$args = array();
	$args['page'] = 'ninja-forms';
	$args['tab']  = 'form_settings';
	$args['slug'] = 'basic_settings';
	$args['settings'] = array(
		array(
			'name'      => 'mailpoet_signup_form',
			'type'      => 'checkbox',
			'label'     => __( 'MailPoet', 'ninja_mailpoet' ),
			'desc'      => __( 'Enable MailPoet signup for this form?', 'ninja_mailpoet' ),
			'help_text' => __( 'This will cause all email fields in this form to be sent to MailPoet', 'ninja_mailpoet' ),
		),
		array(
			'name'    => 'ninja_mailpoet_list',
			'label'   => __( 'Choose a list', 'ninja_mailpoet' ),
			'desc'    => __( 'Select the list you wish to subscribe users to', 'ninja_mailpoet' ),
			'type'    => 'select',
			'options' => ninja_mailpoet_getlists()
		)
	);
	ninja_forms_register_tab_metabox_options( $args );

}
add_action( 'admin_init', 'ninja_mailpoet_add_form_settings', 100 );


/**
 * Adds a new subscriber to the MailPoet list from the Ninja Forms submission
 *
 * @since       1.0.0
 * @return      void
 */
function ninja_mailpoet_subscribe_email() {

	if( ! class_exists( 'WYSIJA' ) )
		return false;
	
	global $ninja_forms_processing;

	$form = $ninja_forms_processing->get_all_form_settings();

	// Check if MailPoet is enabled for this form
	if ( empty( $form['mailpoet_signup_form'] ) )
		return;	

	// Get all the user submitted values
	$all_fields = $ninja_forms_processing->get_all_fields();	


	$list_id = $form['ninja_mailpoet_list'];

	if( $list_id ) {

		if ( is_array( $all_fields ) ) { //Make sure $all_fields is an array.
			//Loop through each of our submitted values.
			$subscriber = array();
			foreach ( $all_fields as $field_id => $value ) {

				$field = $ninja_forms_processing->get_field_settings( $field_id );
				//echo '<pre>'; print_R( $field ); echo '</pre>'; exit;
				if ( ! empty( $field['data']['email'] ) && is_email( $value ) ) {
					$subscriber['email'] = $value;
				}

				if ( ! empty( $field['data']['first_name'] ) ) {
					$subscriber['first_name'] = $value;
				}

				if ( ! empty( $field['data']['last_name'] ) ) {
					$subscriber['last_name'] = $value;
				}

			}
		}

		$user_data = array(
			'email'     => $subscriber['email'],
			'firstname' => $subscriber['first_name'],
			'lastname'  => $subscriber['last_name'],
		);


		$data = array(
	      	'user'=> $user_data,
	      	'user_list' => array( 'list_ids' => array( $list_id ) )
	    );

		$userHelper = &WYSIJA::get( 'user','helper' );
		$userHelper->addSubscriber( $data );
		//this function will add the subscriber to mailpoet
	 
	    //if double optin is on it will send a confirmation email
	    //to the subscriber
	 
	    //if double optin is off and you have an active automatic
	    //newsletter then it will send the automatic newsletter to the subscriber		

	} else {
		return false;
	}
}


/**
 * Connect our signup check to form processing
 *
 * @since       1.0.0
 * @return      void
 */
function ninja_forms_to_mailpoet_hook(){
	add_action( 'ninja_forms_post_process', 'ninja_mailpoet_subscribe_email' );
}
add_action( 'init', 'ninja_forms_to_mailpoet_hook' );