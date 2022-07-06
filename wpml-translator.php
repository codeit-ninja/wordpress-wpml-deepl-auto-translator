<?php
namespace codeit\WPML_Translator;
use DeepL\DeepLException;
use ErrorException;

/**
 *         _       _       
 *        (_)     (_)      
 *   _ __  _ _ __  _  __ _ 
 *  | '_ \| | '_ \| |/ _` |
 *  | | | | | | | | | (_| |
 *  |_| |_|_|_| |_| |\__,_|
 *               _/ |      
 *              |__/   
 *
 * Version: 2.2.10
 * Plugin Name: Code IT - WPML Auto translate posts
 * Plugin URI: https://codeit.ninja
 * Description: Auto translate post when publishing a new posts using DEEPL 🥷
 * Author: Code IT Ninja
 * Author URI: https://codeit.ninja
 * Update URI: https://github.com/codeit-ninja/wordpress-wpml-deepl-auto-translator
 * 
 * You are not allowed to sell or distribute this plugin without
 * the permission of its author
 * 
 * You can contact the author of this plugin at richard@codeit.ninja
 *
 * @package CodeIT\WPML_Translator
 */
if( defined('WP_INSTALLING') && WP_INSTALLING ) {
	return;
}

if ( ! defined('ABSPATH') ) {
	exit;
}

require __DIR__ . '/vendor/autoload.php';

class Code_IT_Translator {
    protected function __construct() {
        $plugin_data = get_file_data( __FILE__, array( 'Version' => 'Version' ), false );

        /**
         * Define plugin constants
         */
        define( 'CODE_IT_TRANSLATOR_VERSION', $plugin_data['Version'] );
        define( 'CODE_IT_TRANSLATOR_BASENAME', plugin_basename( __FILE__ ) );
        define( 'CODE_IT_TRANSLATOR_UPDATE_URI', 'https://raw.githubusercontent.com/codeit-ninja/wordpress-wpml-deepl-auto-translator/master/composer.json' );

        /**
         * Start translating term to enabled languages
         * after the creation of a new term
         * 
         * @since v1.0.1
         */
        add_action( 'create_term', array( Code_IT_WPML_Translator::class, 'create_term_translation_job' ), 10, 1 );
        /**
         * Start translating post to enabled languages
         * after the creation of a new post
         * 
         * @since v1.0.1
         */
        add_action( 'wp_insert_post', array( Code_IT_WPML_Translator::class, 'create_post_translation_job' ), 10, 2 );
    }

    /**
     * This function starts the plugins functionality
     *
     * Call it with the appropriate WordPress hook!
     *
     * @throws DeepLException
     */
    public static function boot(): void
    {
        /**
         * Most likely WordPress didn't fully load yet,
         * so we have to include the pluggable.php file
         * to use authentication scripts and so on
         */
        if( ! function_exists( 'is_user_logged_in' ) ) {
            require ABSPATH . WPINC . '/pluggable.php';
        }
        /**
         * No need to initialize plugin when not logged in
         */
        if( ! is_user_logged_in() ) {
            return;
        }
        /**
         * Set text of checkbox label 
         * 
         * Show a 'not supported' label on settings page 
         * when language is not supported by DeepL
         * 
         * @since v1.0.1
         */
        add_filter( 'codeit_checkbox_label', array( static::class, 'set_checkbox_label' ));
        /**
         * Set text of checkbox state
         * 
         * Disable checkbox on settings page when 
         * language is not supported by DeepL
         * 
         * @since v1.0.1
         */
        add_filter( 'codeit_checkbox_state', array( static::class, 'set_checkbox_state' ));
        /**
         * Check if WPML is installed, if not show an error message
         */
        if ( ! in_array( 'sitepress-multilingual-cms/sitepress.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
            add_action( 'admin_notices', array( static::class, 'wpml_not_installed_notice' ) );

            return;
        }
        /**
         * Create a plugin options page
         */
        $options = codeit_register_options_page();
        /**
         * Verify if we have a DeepL API key
         */
        if( ! $options->get_option('api-key', 'global-settings') ) {
            add_action( 'admin_notices', array( static::class, 'deepl_no_api_key' ) );

            return;
        }
        /**
         * Notify user that plugin won't work, because we 
         * reached the limit, probably running free version? (limit is 500.000 characters)
         */
        if( Code_IT_Translator_Deepl::deepl()->getUsage()->anyLimitReached() ) {
            add_action( 'admin_notices', array( static::class, 'deepl_limit_reached' ) );

            return;
        }

        new Code_IT_Translator();
        new Translator_Updater();
    }

    /**
     * Runs on plugin activation
     */
    public static function install(): void
    {
        
        // Add some code which needs to run on plugin activation ...
    }

    /**
     * Runs on plugin deactivation
     */
    public static function uninstall(): void
    {
        
        // Add some code which needs to run on plugin deactivation ...
    }

    public static function wpml_not_installed_notice(): void
    {
        $class = 'notice notice-error';
        $message = __( 'WPML not enabled, Auto Translator requires <a href="https://wpml.org/">WPML</a> in order to work.' );
        
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
    }

    public static function deepl_no_api_key(): void
    {
        $class = 'notice notice-warning';
        $message = __( 'You must provide a DeepL API key before Auto Translator starts working, set it <a href="/wp-admin/options-general.php?page=translator">here</a>' );
    
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
    }

    public static function deepl_limit_reached(): void
    {
        $class = 'notice notice-error';
        $message = __( 'You have reached the API limits of DeepL, we are not able anymore to automatically translate your content. Find out more about the limits by clicking <a target="_blank" href="https://www.deepl.com/pro?cta=header-prices">here</a>.' );
    
        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
    }

    /**
     * @throws ErrorException|DeepLException
     */
    public static function set_checkbox_label(array $args ): string
    {
        if( Code_IT_Translator_Deepl::no_api_key() && $args['id'] === 'enabled' ) {
            return '<span style="color: red;"> Setup your DeepL API key first</span>';
        }

        if( ! Code_IT_Translator_Deepl::deepl() ) {
            return '';
        }

        if( isset( $args['code'] ) && $args['code'] ) {
            return Code_IT_Translator_Deepl::is_language_supported( $args['code'] ) ? '' : '<span style="color: red;"> Not supported by DeepL</span>';
        }

        return '';
    }

    /**
     * @throws ErrorException|DeepLException
     */
    public static function set_checkbox_state(array $args ): string
    {
        if( ! Code_IT_Translator_Deepl::deepl() ) {
            return 'disabled';
        }

        if( isset( $args['code'] ) && $args['code'] ) {
            return Code_IT_Translator_Deepl::is_language_supported( $args['code'] ) ? '' : 'disabled';
        }

        return '';
    }
}

// Load plugin code after init
// This to make sure we can access all WordPress functionality
try {
    Code_IT_Translator::boot();
} catch (DeepLException $e) {
    add_action( 'admin_notices', function () use ($e) {
        $class = 'notice notice-error';
        $message = 'An error exception occurred, ' . $e->getMessage();

        printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), $message );
    } );
}

register_activation_hook( __FILE__, array(Code_IT_Translator::class, 'install') );
register_deactivation_hook( __FILE__, array(Code_IT_Translator::class, 'uninstall') );