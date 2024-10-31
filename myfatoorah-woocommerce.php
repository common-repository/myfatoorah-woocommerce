<?php

/**
 * 
 * @link              https://www.myfatoorah.com/
 * @package           myfatoorah-woocommerce/myfatoorah-woocommerce
 *
 * @wordpress-plugin
 * Plugin Name:       MyFatoorah - WooCommerce
 * Plugin URI:        https://myfatoorah.readme.io/docs/woocommerce/
 * Description:       MyFatoorah Payment Gateway for WooCommerce. Integrated with MyFatoorah DHL/Aramex Shipping Methods.
 * Version:           2.2.7
 * Author:            MyFatoorah
 * Author URI:        https://www.myfatoorah.com/
 * License:           GNU General Public License v3.0
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       myfatoorah-woocommerce
 * Domain Path:       /languages
 * 
 * Requires at least: 5.6
 * Tested up to: 6.4
 * 
 * Requires PHP: 7.4
 *
 * WC requires at least: 5.3
 * WC tested up to: 8.7
 */
if (!defined('ABSPATH')) {
    exit;
}
if (!defined('WPINC')) {
    die;
}

use Automattic\WooCommerce\Utilities\FeaturesUtil;

//MFWOO_PLUGIN
define('MYFATOORAH_WOO_PLUGIN_VERSION', '2.2.7');
define('MYFATOORAH_WOO_PLUGIN', plugin_basename(__FILE__));
define('MYFATOORAH_WOO_PLUGIN_NAME', dirname(MYFATOORAH_WOO_PLUGIN));
define('MYFATOORAH_WOO_PLUGIN_PATH', plugin_dir_path(__FILE__));

require_once MYFATOORAH_WOO_PLUGIN_PATH . 'includes/libraries/MyfatoorahLoader.php';
require_once MYFATOORAH_WOO_PLUGIN_PATH . 'includes/libraries/MyfatoorahLibrary.php';

/**
 * MyFatoorah WooCommerce Class
 */
class MyfatoorahWoocommerce {
//-----------------------------------------------------------------------------------------------------------------------------

    /**
     * Constructor
     */
    public function __construct() {

        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);

        //actions
        add_action('activate_plugin', [$this, 'activate_plugin'], 0);
        add_action('plugins_loaded', [$this, 'init'], 0);
        add_action('in_plugin_update_message-' . MYFATOORAH_WOO_PLUGIN, [$this, 'prefix_plugin_update_message'], 10, 2);
        add_action('upgrader_process_complete', [$this, 'upgrader_process_complete'], 10, 2);

        //to show that MyFatoorah is supported with the woo features
        //http://wordpress-6.2.2.com/wp-admin/plugins.php?plugin_status=incompatible_with_feature
        add_action('before_woocommerce_init', [$this, 'before_woocommerce_init']);
    }

//-----------------------------------------------------------------------------------------------------------------------------

    /**
     * Show row meta on the plugin screen.
     *
     * @param mixed $links Plugin Row Meta.
     * @param mixed $file  Plugin Base file.
     *
     * @return array
     */
    public static function plugin_row_meta($links, $file) {

        if (MYFATOORAH_WOO_PLUGIN === $file) {
            $row_meta = array(
                'docs'    => '<a href="' . esc_url('https://myfatoorah.readme.io/docs/woocommerce') . '" aria-label="' . esc_attr__('View MyFatoorah documentation', 'myfatoorah-woocommerce') . '">' . esc_html__('Docs', 'woocommerce') . '</a>',
                'apidocs' => '<a href="' . esc_url('https://myfatoorah.readme.io/docs') . '" aria-label="' . esc_attr__('View MyFatoorah API docs', 'myfatoorah-woocommerce') . '">' . esc_html__('API docs', 'woocommerce') . '</a>',
                'support' => '<a href="' . esc_url('https://myfatoorah.com/contact.html') . '" aria-label="' . esc_attr__('Visit premium customer support', 'myfatoorah-woocommerce') . '">' . esc_html__('Premium support', 'woocommerce') . '</a>',
            );

            //unset($links[2]);
            return array_merge($links, $row_meta);
        }

        return (array) $links;
    }

//-----------------------------------------------------------------------------------------------------------------------------
    function activate_plugin($plugin) {

        // Localisation
        $this->updateTransFile();

        //nice code but give graceful failure in
        //https://plugintests.com/plugins/wporg/myfatoorah-woocommerce/latest
        //it is very important to say that the plugin is MyFatoorah
        /*
          $pluginsArr  = apply_filters('active_plugins', get_option('active_plugins'));
          $siteWideArr = apply_filters('active_plugins', get_site_option('active_sitewide_plugins'));

          $isWooPlugActive  = is_array($pluginsArr) && in_array('woocommerce/woocommerce.php', $pluginsArr);
          $isSiteWideActive = is_array($siteWideArr) && array_key_exists('woocommerce/woocommerce.php', $siteWideArr);

          if ($plugin == MYFATOORAH_WOO_PLUGIN && !$isWooPlugActive && !$isSiteWideActive) {
          $msg = __('WooCommerce plugin needs to be activated first to activate MyFatoorah plugin.', 'myfatoorah-woocommerce');
          wp_die($msg, 403);
          }

         */
    }

//-----------------------------------------------------------------------------------------------------------------------------
    function upgrader_process_complete($upgraderObject, $options) {

        // If an update has taken place and the updated type is plugins and the plugins element exists
        if ($options['action'] == 'update' && $options['type'] == 'plugin' && isset($options['plugins'])) {
            foreach ($options['plugins'] as $plugin) {
                // Check to ensure it's my plugin
                if ($plugin == MYFATOORAH_WOO_PLUGIN) {
                    $this->updateTransFile();
                }
            }
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------
    function updateTransFile() {
        $arTrans = 'myfatoorah-woocommerce-ar';
        if (is_dir(WP_LANG_DIR . '/plugins/')) {
            $filePath = WP_LANG_DIR . '/plugins/' . $arTrans;
            $moFileAr = $filePath . '.mo';
            $poFileAr = $filePath . '.po';

            $newFilePath = __DIR__ . '/languages/' . $arTrans;
            $moNewFileAr = $newFilePath . '.mo';
            $poNewFileAr = $newFilePath . '.po';

            copy($moNewFileAr, $moFileAr);
            copy($poNewFileAr, $poFileAr);
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------
    function admin_notices() {
        $msg = __('MyFatoorah - WooCommerce plugin needs WooCommerce plugin to be installed and active.', 'myfatoorah-woocommerce');
        echo '<div class="error"><p><strong>' . $msg . '</strong></p></div>';
    }

//-----------------------------------------------------------------------------------------------------------------------------

    /**
     * Init localizations and files
     */
    public function init() {
        // Localisation
        load_plugin_textdomain('myfatoorah-woocommerce', false, MYFATOORAH_WOO_PLUGIN_NAME . '/languages');

        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', [$this, 'admin_notices']);
            return;
        }

        //load payment
        require_once 'includes/PluginPaymentMyfatoorahWoocommerce.php';
        new PluginPaymentMyfatoorahWoocommerce('v2');
        new PluginPaymentMyfatoorahWoocommerce('embedded');

        //load shipping
        require_once 'includes/PluginShippingMyfatoorahWoocommerce.php';
        new PluginShippingMyfatoorahWoocommerce();

        //load webhook
        require_once 'includes/PluginWebhookMyfatoorahWoocommerce.php';
        new PluginWebhookMyfatoorahWoocommerce();

        //load cron
        //https://www.codesmade.com/wordpress-add-cron-job-programmatically/
        add_action('myfatoorah_backup_log_files', [$this, 'myfatoorah_backup_log_files']);
        if (!wp_next_scheduled('myfatoorah_backup_log_files')) {
            wp_schedule_event(time(), 'weekly', 'myfatoorah_backup_log_files');
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------------------

    /**
     * Show important release note
     * @param type $data
     * @param type $response
     */
    function prefix_plugin_update_message($data, $response) {

        $notice = null;
        if (!empty($data['upgrade_notice'])) {
            $notice = trim(strip_tags($data['upgrade_notice']));
        } else if (!empty($response->upgrade_notice)) {
            $notice = trim(strip_tags($response->upgrade_notice));
        }

        if (!empty($notice)) {
            printf(
                    '<div class="update-message notice-error"><p style="background-color: #d54e21; padding: 10px; color: #f9f9f9; margin-top: 10px"><strong>Important Upgrade Notice: </strong>%s',
                    __($notice, 'myfatoorah-woocommerce')
            );
        }
        //https://andidittrich.com/2015/05/howto-upgrade-notice-for-wordpress-plugins.html
    }

    //-----------------------------------------------------------------------------------------------------------------------------
    function myfatoorah_backup_log_files() {

        $codes   = array_keys(apply_filters('myfatoorah_woocommerce_payment_gateways', []));
        $codes[] = 'shipping';
        $codes[] = 'webHook';

        foreach ($codes as $code) {
            $this->myfatoorah_backup_log_file($code);
        }
    }

    function myfatoorah_backup_log_file($code) {

        $myfatoorahLogFile = WC_LOG_DIR . 'myfatoorah_' . $code . '.log';
        if (file_exists($myfatoorahLogFile)) {
            $mfLogFolder = WC_LOG_DIR . 'mfOldLog';
            if (!file_exists($mfLogFolder)) {
                mkdir($mfLogFolder);
            }

            $mfLogFolder .= '/' . $code;
            if (!file_exists($mfLogFolder)) {
                mkdir($mfLogFolder);
            }

            rename($myfatoorahLogFile, $mfLogFolder . '/' . date('Y-m-d') . '_myfatoorah_' . $code . '.log');
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------
    function before_woocommerce_init() {

        if (class_exists(FeaturesUtil::class)) {
            //to remove mf from feature_id=custom_order_tables list
            //to disable waring message for High-Performance Order Storage features
            //http://wordpress-6.2.2.com/wp-admin/plugins.php?plugin_status=incompatible_with_feature&feature_id=custom_order_tables
            //https://github.com/woocommerce/woocommerce/wiki/High-Performance-Order-Storage-Upgrade-Recipe-Book
            FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);

            //to remove mf from feature_id=cart_checkout_blocks
            //http://wordpress-6.2.2.com/wp-admin/plugins.php?plugin_status=incompatible_with_feature&feature_id=cart_checkout_blocks
            //https://woocommerce.com/document/cart-checkout-blocks-support-status/
            //https://developer.woocommerce.com/2021/03/15/integrating-your-payment-method-with-cart-and-checkout-blocks/
            //follow instruction here b4 enable it
            //https://developer.woo.com/2023/11/06/faq-extending-cart-and-checkout-blocks/
            //https://github.com/woocommerce/woocommerce-blocks/blob/trunk/docs/third-party-developers/extensibility/checkout-payment-methods/payment-method-integration.md#registering-assets
            //FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
        }
    }

//-----------------------------------------------------------------------------------------------------------------------------
}

new MyfatoorahWoocommerce();
