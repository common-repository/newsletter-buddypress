<?php

/*
  Plugin Name: Newsletter - BuddyPress Integration
  Plugin URI: https://www.thenewsletterplugin.com/documentation/addons/integrations/buddypress-extension/
  Description: Integrates the subscription option in the BuddyPress registration page
  Version: 1.0.5
  Author: The Newsletter Team
  Author URI: https://www.thenewsletterplugin.com
  Disclaimer: Use at your own risk. No warranty expressed or implied is provided.
  Text Domain: newsletter-buddypress
  License: GPLv2 or later
  Requires PHP: 5.6
  Require at least: 5.0.0
 
  Copyright 2009-2021 The Newsletter Team (email: info@thenewsletterplugin.com, web: https://www.thenewsletterplugin.com)

  Newsletter is free software: you can redistribute it and/or modify
  it under the terms of the GNU General Public License as published by
  the Free Software Foundation, either version 2 of the License, or
  any later version.

  Newsletter is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with Newsletter. If not, see https://www.gnu.org/licenses/gpl-2.0.html.
 */

defined('ABSPATH') || exit;

register_activation_hook(__FILE__, function () {
    $options = get_option('newsletter_buddypress', []);
    $defaults = array('confirmation' => 1, 'welcome' => 1, 'status' => '', 'subscribe' => 0, 'subscribe_label' => 'Subscribe our newsletter');
    update_option('newsletter_buddypress', array_merge($defaults, $options), false);
});

class NewsletterBuddyPress {

    /**
     * @var NewsletterBuddyPress
     */
    static $instance;
    var $prefix = 'newsletter_buddypress';
    var $options;
    /* @var NewsletterLogger */
    var $logger;

    function __construct() {
        self::$instance = $this;
        $this->options = get_option($this->prefix, array());

        add_action('init', array($this, 'hook_init'), 1);
    }


    /**
     * 
     * @return NewsletterLogger
     */
    function get_logger() {
        if ($this->logger) {
            return $this->logger;
        }
        $this->logger = new NewsletterLogger('buddypress');
        return $this->logger;
    }

    function hook_init() {
        if (!class_exists('Newsletter') || NEWSLETTER_VERSION < '4.8.7') {
            return;
        }

        if (is_admin()) {
            add_action('admin_menu', array($this, 'hook_admin_menu'), 100);
//            add_filter('newsletter_menu_subscription', array($this, 'hook_newsletter_menu_subscription'));
            add_filter('newsletter_lists_notes', array($this, 'hook_newsletter_lists_notes'), 10, 2);
        }

        add_action('bp_core_signup_user', array($this, 'hook_bp_core_signup_user'));

        if (!empty($this->options['subscribe']) && $this->options['subscribe'] != 1) {
            add_action('bp_account_details_fields', array($this, 'hook_bp_account_details_fields'));
        }

        add_action('bp_after_profile_field_content', function () {
            $newsletter = Newsletter::instance();
            //echo get_current_user_id();
            $user = $newsletter->get_user_by_wp_user_id(get_current_user_id());
            if ($user) {
                echo '<a href="', NewsletterProfile::instance()->get_profile_url($user), '">';
                echo $this->options['profile_label'];
                echo '</a>';
            } else {
                //echo 'Not connected';
            }
        });
    }

    function hook_newsletter_lists_notes($notes, $list_id) {
        if (!isset($this->options['lists'])) {
            return $notes;
        }
        foreach ($this->options['lists'] as $list) {
            if ($list == $list_id) {
                $notes[] = 'Added on BuddyPress user registration';
                return $notes;
            }
        }
        return $notes;
    }

//    function hook_newsletter_menu_subscription($entries) {
//        $entries[] = array('label' => '<i class="fa fa-wordpress"></i> BuddyPress Integration', 'url' => '?page=newsletter_buddypress_index', 'description' => 'Subscribe on WP registration');
//        return $entries;
//    }

    function hook_admin_menu() {
        add_submenu_page('newsletter_main_index', 'BuddyPress', '<span class="tnp-side-menu">BuddyPress</span>', 'manage_options', 'newsletter_buddypress_index', function () {
            global $wpdb;
            require dirname(__FILE__) . '/index.php';
        });
    }

    function hook_bp_account_details_fields() {
        echo '<p>';
        echo '<label><input type="checkbox" value="1" name="newsletter"';
        if ($this->options['subscribe'] == 3) {
            echo ' checked';
        }
        echo '>&nbsp;';
        echo $this->options['subscribe_label'];
        echo '</label></p>';
    }

    function hook_bp_core_signup_user($wp_user_id) {
        global $wpdb;
        static $last_wp_user_id = 0;


        $logger = $this->get_logger();

        // If the integration is disabled...
        if ($this->options['subscribe'] == 0) {
            return;
        }

        if ($last_wp_user_id == $wp_user_id) {
            $logger->fatal('Called twice with the same user id!');
            return;
        }

        $last_wp_user_id = $wp_user_id;

        // If not forced and the user didn't choose the newsletter...
        if ($this->options['subscribe'] != 1) {
            if (!isset($_REQUEST['newsletter'])) {
                return;
            }
        }

        $logger->info('Adding a registered WordPress user (' . $wp_user_id . ')');
        $wp_user = $wpdb->get_row($wpdb->prepare("select * from $wpdb->users where id=%d limit 1", $wp_user_id));
        if (empty($wp_user)) {
            $logger->fatal('User not found?!');
            return;
        }

        // Yes, some registration procedures allow empty email
        if (!NewsletterModule::is_email($wp_user->user_email)) {
            $logger->fatal('User without a valid email?!');
            return;
        }

        $_REQUEST['ne'] = $wp_user->user_email;
        $_REQUEST['nr'] = 'registration';
        $_REQUEST['nn'] = get_user_meta($wp_user_id, 'first_name', true);
        $_REQUEST['ns'] = get_user_meta($wp_user_id, 'last_name', true);

        $status = $this->options['status'];
        if (empty($status)) {
            $opt_in = (int) NewsletterSubscription::instance()->options['noconfirmation'];
            $status = $opt_in ? 'S' : 'C';
        }

        if ($status == 'S') {
            // Single
            $emails = $this->options['confirmation'] == 1;
        } else {
            // Double
            $emails = $this->options['welcome'] == 1;
        }

        $user = NewsletterSubscription::instance()->subscribe($status, $emails);

        if (!$user) {
            $logger->fatal('Unable to create the subscription ');
            return;
        }

        // Now we associate it with wp
        Newsletter::instance()->set_user_wp_user_id($user->id, $wp_user_id);

        // Force the lists
        $user = array('id' => $user->id);

        if (isset($this->options['lists'])) {
            foreach ($this->options['lists'] as $list) {
                $user['list_' . $list] = 1;
            }
            NewsletterSubscription::instance()->save_user($user);
        }
    }

}

new NewsletterBuddyPress();

