<?php
namespace codeit\WPML_Translator;
/**
 * Implements plugin update functionality
 *         _       _       
 *        (_)     (_)      
 *   _ __  _ _ __  _  __ _     
 *  | '_ \| | '_ \| |/ _` |    
 *  | | | | | | | | | (_| |    
 *  |_| |_|_|_| |_| |\__,_|    
 *               _/ |    
 *              |__/    
 *
 * @package CodeIT\WPML_Translator
 */
class Translator_Updater 
{
    /**
     * Plugin slug
     * 
     * @var string
     */
    public $plugin_slug;
    /**
     * Transient cache key
     * 
     * @var string
     */
    public $cache_key;
    /**
     * Whether we should cache
     * 
     * @var bool
     */
    public $cache_allowed;

    public function __construct() 
    {
        $this->plugin_slug = plugin_basename( __DIR__ );
        $this->cache_key = 'codeit-wpml-auto-translator-updater';
        $this->cache_allowed = false;
        /**
         * Hooks into the plugins api the change
         * the default behaviour of the Plugin API
         * 
         * We implemented a custom update server here
         * 
         * @since 1.0.4
         */
        add_filter( 'plugins_api', array( $this, 'plugin_info' ), 20, 3);
        /**
         * Implementation of the custom update server
         * 
         * @since 1.0.4
         */
        add_filter( 'site_transient_update_plugins', array( $this, 'update' ) );
        /**
         * Clean cache after update is installed
         * 
         * @since 1.1.5
         */
        add_action( 'upgrader_process_complete', array( $this, 'purge' ), 10, 2 );
    }

    function request() 
    {
        $remote = get_transient( $this->cache_key );

        if( false === $remote || ! $this->cache_allowed ) {

            $remote = wp_remote_get( CODE_IT_TRANSLATOR_UPDATE_URI,
                array(
                    'timeout' => 10,
                    'headers' => array(
                        'Accept' => 'application/json'
                    )
                )
            );
            if(
                is_wp_error( $remote )
                || 200 !== wp_remote_retrieve_response_code( $remote )
                || empty( wp_remote_retrieve_body( $remote ) )
            ) {
                return false;
            }

            set_transient( $this->cache_key, $remote, DAY_IN_SECONDS );

        }

        $remote = json_decode( wp_remote_retrieve_body( $remote ) );

        return $remote;
    }

    function plugin_info( $res, $action, $args )
    {
        // do nothing if you're not getting plugin information right now
        if( 'plugin_information' !== $action ) {
            return $res;
        }

        // do nothing if it is not our plugin
        if( $this->plugin_slug !== $args->slug ) {
            return $res;
        }

        // get updates
        $remote = $this->request();

        if( ! $remote ) {
            return $res;
        }

        $res = new \stdClass();

        $res->name = $remote->name;
        $res->slug = $remote->slug;
        $res->version = $remote->version;
        $res->tested = $remote->tested;
        $res->requires = $remote->requires;
        $res->author = $remote->author;
        $res->author_profile = $remote->author_profile;
        $res->download_link = $remote->download_url;
        $res->trunk = $remote->download_url;
        $res->requires_php = $remote->requires_php;
        $res->last_updated = $remote->last_updated;

        $res->sections = array(
            'changelog' => $remote->sections->changelog
        );

        if( ! empty( $remote->banners ) ) {
            $res->banners = array(
                'low' => $remote->banners->low,
                'high' => $remote->banners->high
            );
        }

        return $res;
    }

    public function update( $transient ) 
    {
        if ( empty($transient->checked ) ) {
            return $transient;
        }

        $remote = $this->request();

        if(
            $remote
            && version_compare( CODE_IT_TRANSLATOR_VERSION, $remote->version, '<' )
            && version_compare( $remote->requires, get_bloginfo( 'version' ), '<=' )
            && version_compare( $remote->requires_php, PHP_VERSION, '<' )
        ) {
            $res = new \stdClass();
            $res->slug = $this->plugin_slug;
            $res->plugin = CODE_IT_TRANSLATOR_BASENAME;
            $res->new_version = $remote->version;
            $res->tested = $remote->tested;
            $res->package = $remote->download_url;

            $transient->response[ $res->plugin ] = $res;

        }

        return $transient;
    }

    public function purge( $upgrader, $options ){

        if (
            $this->cache_allowed
            && 'update' === $options['action']
            && 'plugin' === $options[ 'type' ]
        ) {
            // just clean the cache when new plugin version is installed
            delete_transient( $this->cache_key );
        }

    }
}