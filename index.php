<?php
defined('ABSPATH') || exit;

/* @var $this NewsletterBuddyPress */
include_once NEWSLETTER_INCLUDES_DIR . '/controls.php';
$controls = new NewsletterControls();

if (!$controls->is_action()) {
    $controls->data = $this->options;
} else {
    if ($controls->is_action('save')) {
        update_option('newsletter_buddypress', $controls->data);
        $controls->add_message_saved();
    }
}
?>
<div class="wrap" id="tnp-wrap">

    <?php include NEWSLETTER_DIR . '/tnp-header.php'; ?>

    <div id="tnp-heading">

        <h2>BuddyPress Addon for Newsletter</h2>
        
        <?php $controls->page_help('https://www.thenewsletterplugin.com/documentation/addons/integrations/buddypress-extension/', 'Read our guide') ?>

    </div>

    <div id="tnp-body">

        <form method="post" action="">

            <?php $controls->init(); ?>

            <table class="form-table">
                <tr valign="top">
                    <th><?php _e('Subscribe on registration?', 'newsletter-buddypress')?></th>
                    <td>
                        <?php $controls->select('subscribe', array(0 => __('No', 'newsletter-buddypress'), 
                            1 => __('Yes', 'newsletter-buddypress'), 
                            2 => __('Yes and show the unchecked checkbox', 'newsletter-buddypress'), 
                            3 => __('Yes and show the checked checkbox', 'newsletter-buddypress'))); ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th><?php _e('Checkbox label', 'newsletter-buddypress') ?></th>
                    <td>
                        <?php $controls->text('subscribe_label', 30); ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th><?php _e('Subscribe as', 'newsletter-buddypress') ?></th>
                    <td>
                        <?php $controls->select('status', array(''=>__('Default', 'newsletter-buddypress'), 'S' => __('To be confirmed', 'newsletter-buddypress'), 'C' => __('Confirmed', 'newsletter-buddypress'))); ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th><?php _e('Send activation email?', 'newsletter-buddypress')?></th>
                    <td>
                        <?php $controls->yesno('confirmation'); ?>
                        <p class="description">Only if the subscription requires confirmation</p>
                    </td>
                </tr>  
                <tr valign="top">
                    <th><?php _e('Send welcome email?', 'newsletter-buddypress') ?></th>
                    <td>
                        <?php $controls->yesno('welcome'); ?>
                    </td>
                </tr>


                <tr valign="top">
                    <th><?php _e('Add subscribers to', 'newsletter-buddypress')?></th>
                    <td>
                        <?php $controls->preferences_group('lists'); ?>
                    </td>
                </tr>  

            </table>

            <p>
                <?php $controls->button_save(); ?>
            </p>


        </form>
    </div>

    <?php include NEWSLETTER_DIR . '/tnp-footer.php'; ?>

</div>
