<?php
/**
 * Plugin Name: Tofino MailChimp Addon
 * Plugin URI: https://github.com/lambdacreatives/mailchimp-tofino-addon
 * Description: A WordPress plugin to add MailChimp API v3 subscribe to list functionality to the Tofino theme.
 * Author: Daniel Hewes
 * Version: 0.1
 * Author URI: http://lambdacreatives.com/
 */


/**
 * MailChimp settings
 *
 * @since 0.0.1
 * @param object $wp_customize Instance of WP_Customize_Manager class.
 * @return void
 */
add_action('customize_register', function($wp_customize) {
  $wp_customize->add_section('tofino_mailchimp_settings', [
    'title' => __('MailChimp', 'tofino-mc'),
    'panel' => 'tofino_options'
  ]);

  $wp_customize->add_setting('mailchimp_api_key', ['default' => '']);

  $wp_customize->add_control('mailchimp_api_key', [
    'label'       => __('MailChimp API Key', 'tofino-mc'),
    'description' => __('API key required to authenticate with MailChimp.', 'tofino'),
    'section'     => 'tofino_mailchimp_settings',
    'type'        => 'text'
  ]);

  $wp_customize->add_setting('mailchimp_list_id', ['default' => '']);

  $wp_customize->add_control('mailchimp_list_id', [
    'label'       => __('MailChimp List ID', 'tofino-mc'),
    'description' => __('The MailChimp list ID for new subscribers', 'tofino-mc'),
    'section'     => 'tofino_mailchimp_settings',
    'type'        => 'text'
  ]);

  $wp_customize->add_setting('mailchimp_success_msg', ['default' => __('Subscribed!', 'tofino-mc')]);

  $wp_customize->add_control('mailchimp_success_msg', [
    'label'       => __('Success Message', 'tofino-mc'),
    'description' => __('Message displayed on successful subscribe', 'tofino-mc'),
    'section'     => 'tofino_mailchimp_settings',
    'type'        => 'text'
  ]);

  $wp_customize->add_setting('mailchimp_failed_msg', ['default' => __('An error occured. Try again later.', 'tofino-mc')]);

  $wp_customize->add_control('mailchimp_failed_msg', [
    'label'       => __('Success Message', 'tofino-mc'),
    'description' => __('Message displayed on failure', 'tofino-mc'),
    'section'     => 'tofino_mailchimp_settings',
    'type'        => 'text'
  ]);
});


/**
 * Subscribe to MC List
 *
 * @param  array  $data    Array with email address, first and last names
 * @link   http://developer.mailchimp.com/documentation/mailchimp/reference/lists/members/
 * @return boolen True or False if the subscribe request was successful or failed
 */
function subscribe($data) {
  $api_key = get_theme_mod('mailchimp_api_key');
  $list_id = get_theme_mod('mailchimp_list_id');

  if (empty($api_key) || empty($list_id)) {
    error_log('[' . __('MailChimp API Error', 'tofino') . '] Missing API key or List ID.'); // Log error
    return false;
  }

  $data_center = substr($api_key, strpos($api_key, '-')+1);
  $url         = 'https://' . $data_center . '.api.mailchimp.com/3.0/lists/' . $list_id . '/members/';

  $data = [
    'email_address' => $data['email'],
    'merge_fields' => [
      'FNAME' => '',
      'LNAME' => ''
    ],
    'status' => 'subscribed',
    'language' => 'en' // en or fr etc
  ];

  apply_filters('tofino_mc_data', $data);

  // Send the API key as a http header
  $headers = array(
    'Content-Type'  => 'application/json',
    'Authorization' => 'Basic ' . $api_key
  );

  // Use wp_remote_post to make the http request
  $response = wp_remote_post($url, [
    'headers' => $headers,
    'timeout' => 10,
    'body'    => json_encode($data)
  ]);

  // var_dump($response);
  // die;

  if (is_wp_error($response)) { // Request error occured.
    $error_message = $response->get_error_message();
    error_log('[' . __('MailChimp API Error', 'tofino') . '] ' . $error_message); // Log error
    return false;
  }

  if (json_decode($response['body'])) { // Response body is valid JSON
    $json_response = json_decode(wp_remote_retrieve_body($response));

    // var_dump($json_response);
    // die;

    if ($json_response->status == 'subscribed') { // Only response status 200 is good
      $result = true;
    } else { // Valid JSON, with error.
      error_log('[' . __('MailChimp API Error', 'tofino-mc') . '] ' . $json_response->title . ' - ' . $json_response->detail); // Log error
      $result = false;
    }
  } else { // Invlid response received
    error_log('[' . __('MailChimp API Error', 'tofino-mc') . '] ' . __('Invalid resposne (not JSON) received from the API endpoint.', 'tofino-mc')); // Log error
    $result = false;
  }

  return $result;
}


/**
 * Ajax MailChimp Form
 *
 * Process the ajax request.
 * Called via JavaScript.
 *
 * @since 0.0.1
 * @return void
 */
 add_action('after_setup_theme', function() {
   add_actions([
     'wp_ajax_tofino-mc-form',
     'wp_ajax_nopriv_tofino-mc-form'
   ], function() {
     $form = new \Tofino\AjaxForm(); // Required

     $fields = [
       'email' => ['required' => true]
     ];

     apply_filters('tofino_mc_fields', $fields);

     $form->validate($fields); // Required  Call validate

     $data = $form->getData();

     $result = subscribe($data);

     if (!$result) {
       $form->respond(false, get_theme_mod('mailchimp_failed_msg', __('An error occured. Try again later.', 'tofino-mc')));
     }

     $form->respond(true, get_theme_mod('mailchimp_success_msg', __('Subscribed!', 'tofino-mc'))); // Required
   });
 });


/**
 * Hooks a single callback to multiple tags
 */
if (!function_exists('add_filters')) {
  function add_filters($tags, $function, $priority = 10, $accepted_args = 1) {
    foreach ((array) $tags as $tag) {
      add_filter($tag, $function, $priority, $accepted_args);
    }
  }
}


/**
 * Add multiple actions to a closure
 *
 * @param $tags
 * @param $function_to_add
 * @param int $priority
 * @param int $accepted_args
 *
 * @return bool true
 */
if (!function_exists('add_actions')) {
  function add_actions($tags, $function_to_add, $priority = 10, $accepted_args = 1) {
    //add_action() is just a wrapper around add_filter(), so we do the same
    return add_filters($tags, $function_to_add, $priority, $accepted_args);
  }
}
