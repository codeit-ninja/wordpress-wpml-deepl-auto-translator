<?php
namespace codeit\WPML_Translator;
/**
 * A better WP Settings API implementation OOP based
 * 
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
class Code_IT_Translator_Options {
    /**
     * Plugin slug
     * 
     * @var string
     */
    protected static $plugin_name = 'codeit-wpml-auto-translate';
    /**
     * Name of the options page
     * 
     * @var string
     */
    protected static $options_page = 'translator';
    /**
     * Array of option section fields
     * 
     * @var array
     */
    protected array $sections = array();
    /**
     * Array of option fields
     * 
     * @var array
     */
    protected array $fields = array();
    /**
     * Array of option values
     * 
     * @var array
     */
    protected array $options = array();

    public function __construct() 
    {
        $this->options = get_option(static::$plugin_name . '-settings', []);
    }

    /**
     * Call this function only once in your plugin
     * This script will hook into WordPress admin hooks
     * 
     * When calling it multiple times it will interfere with
     * already running instances
     */
    public function create()
    {
        add_action('admin_menu', array( $this, 'add_options_page' ));
        add_action('admin_init', array( $this, 'init' ));

        return $this;
    }

    public function init()
    {
        \register_setting( static::$plugin_name, static::$plugin_name . '-settings' );

        foreach( $this->sections as $id => $section ) {
            \add_settings_section( $id, $section['title'], function () use ( $section ) { echo $section['description']; }, static::$options_page );
        }

        foreach( $this->fields as $id => $field ) {
            \add_settings_field( $id, $field['title'], $field['callback'], static::$options_page, $field['section'], array_merge( $field['args'], array( 'id' => $id, 'section' => $field['section'] ) ) );
        }
    }

    public function add_options_page() 
    {
        \add_options_page(
            'Translator', 'Translator', 'manage_options', static::$options_page, fn() => include plugin_dir_path(__DIR__) . '/views/settings.php'
        );
    }

    public function add_section( string $id, string $title, string $description = '' ) 
    {
        $this->sections[$id] = [
            'title'     => $title,
            'description'  => $description
        ];

        return $this;
    }

    public function add_field( string $id, string $title, string $type = 'text', string $section = 'default', array $args = array() ) 
    {
        /**
         * Prevent overriding `id` field
         */
        unset($args['id']);

        $this->fields[$id] = [
            'type'      => $type,
            'title'     => $title,
            'section'   => $section,
            'args'      => $args
        ];

        if( 'text' === $type ) {
            $this->fields[$id]['callback'] = array( $this, 'render_text_field' );
        }

        if( 'checkbox' === $type ) {
            $this->fields[$id]['callback'] = array( $this, 'render_checkbox' );
        }

        return $this;
    }

    public function render_text_field( array $args )
    {
        echo "<input 
            id='". static::$plugin_name ."_". $args['id'] ."'
            name='". static::$plugin_name . '-settings['. $args['section'] .']['. $args['id'] ."]'
            type='text'
            class='regular-text ltr'
            value='{$this->options[$args['section']][$args['id']]}'
        />";
    }

    public function render_checkbox( array $args )
    {
        $label_text = apply_filters('codeit_checkbox_label', $args);
        $checkbox_state = apply_filters('codeit_checkbox_state', $args);

        echo "<input 
            type='checkbox' 
            id='". static::$plugin_name ."_". $args['id'] ."'
            name='". static::$plugin_name . '-settings['. $args['section'] .']['. $args['id'] ."]'";

            echo $checkbox_state;

            if ( isset( $this->options[$args['section']][$args['id']] ) ) {
                echo ' checked';
            }

        echo '/>';
        echo '<label for="' . static::$plugin_name ."_". $args['id'] . '">'. $label_text .'</label>';
    }

    public function get_options( string $section ) 
    {
        return $this->options[$section];
    }

    public static function get_option( string $key, string $section = null ) : string|null
    {
        if( $section ) {
            return get_option(static::$plugin_name . '-settings')[$section][$key] ?? null;
        }

        return get_option(static::$plugin_name . '-settings')[$key] ?? null;
    }
}