<?php
namespace codeit\WPML_Translator;

use DeepL\DeepLException;
use ErrorException;
use stdClass;
use WP_Post;
use WP_Term;
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
 * @package CodeIT\WPML_Translator
 * @since 2.2.10
 */
class Code_IT_WPML_Translator
{
    /**
     * The object to translate
     * 
     * @var WP_Term|WP_Post
     */
    protected $object;
    /**
     * Code IT Translator settings
     * 
     * @var Code_IT_Translator_Options
     */
    protected Code_IT_Translator_Options $options;
    /**
     * Array of languages to translate to
     * 
     * @var array[string]
     */
    protected array $languages;

    /**
     * Element ID
     * 
     * WPML Needs elements ID
     * 
     * @var int
     */
    protected int $element_id;

    /**
     * WPML Element type
     * 
     * @see https://wpml.org/wpml-hook/wpml_element_type/
     * @var string
     */
    protected string $element_type;

    /**
     * Language args
     * 
     * WPML needs this info
     * 
     * @var array
     */
    protected array $language_args;

    /**
     * WPML Language details
     * 
     * @see https://wpml.org/wpml-hook/wpml_element_language_details/
     */
    protected stdClass $language_info;

    /**
     * Target translate language
     * 
     * @var string
     */
    protected string $target_language;

    /**
     * Constructor
     * 
     * @param WP_Term|WP_Post $object
     */
    function __construct( $object )
    {
        global $sitepress;

        $this->object = $object;

        /**
         * When somehow we received no WP_Term or WP_Post object
         * return to prevent code errors being spitted out
         */
        if( ! $object instanceof WP_Term && ! $object instanceof WP_Post ) {
            return;
        }

        $this->options = new Code_IT_Translator_Options();
        /**
         * Check if translator is enabled by user
         */
        if( ! $this->options->get_option('enabled', 'global-settings') ) {
            return;
        }
        /**
         * Fill properties needed by translator
         */
        $this->fill();
        /**
         * Remove `create_term` action created by plugin
         * to prevent creating duplicate terms
         * 
         * @since v1.0.1
         */
        remove_action( 'create_term', array( Code_IT_WPML_Translator::class, 'create_term_translation_job' ), 10, 1 );
        /**
         * Remove `create_term` action created by plugin
         * to prevent creating duplicate posts
         * 
         * @since v1.0.1
         */
        remove_action( 'wp_insert_post', array( Code_IT_WPML_Translator::class, 'create_post_translation_job' ), 10, 2 );
        /**
         * Start translating the WordPress object
         * This can be either WP_Post or WP_Term object
         */
        $enabled_languages = array_keys( $this->options->get_options('language-settings') );
        /**
         * Start the translation of the current object
         * into all active languages (if supported by DeepL)
         */
        array_walk( $enabled_languages, array( $this, 'translate' ) );
        /**
         * Switch back to initial language after translating
         */
        $sitepress->switch_lang( $this->language_args['source_language_code'] );
    }

    /**
     * Start translation job of WordPress term
     *
     * @throws ErrorException|DeepLException
     * @property string $language_code
     */
    protected function translate( string $language_code ): void
    {
        global $sitepress;

        /**
         * Switch to language when translating
         * This prevents WPML creating duplicate terms
         */
        $sitepress->switch_lang( $language_code );

        $this->language_args['language_code'] = $language_code;
        $this->target_language = strtoupper( $language_code );

        // IDK how to handle non-supported languages as for now
        // Just skip and continue to next language
        if( ! Code_IT_Translator_Deepl::is_language_supported( $language_code ) ) return;

        // No need to translate to original language term was created in
        if( $this->language_args['source_language_code'] === $language_code ) return;

        // Specify specifically in case of Chinese and English languages
        if( $language_code === 'zh-hans' ) $this->target_language = 'ZH';
        if( $language_code === 'en' ) $this->target_language = 'EN-GB';
        
        if( $this->object instanceof WP_Term ) {
            $array_or_post_id = $this->translate_term()->translate();
        }

        if( $this->object instanceof WP_Post ) {
            $array_or_post_id = $this->translate_post()->translate();
        }

        if( ! isset( $array_or_post_id ) ) {
            return;
        }

        if( $array_or_post_id instanceof \WP_Error) {
            throw new ErrorException($array_or_post_id->get_error_message());
        }

        $this->language_args['element_id'] = $array_or_post_id['term_taxonomy_id'] ?? $array_or_post_id;
        
        //Create and save WPML translation object
        do_action( 'wpml_set_element_language_details', $this->language_args );
    }

    /**
     * Translate a WP_Term object
     *
     * Called when translatable object is WP_Term instance
     *
     * @internal Call this only from within the class itself
     */
    protected function translate_term(): Code_IT_WPML_Taxonomy
    {
        return new Code_IT_WPML_Taxonomy( $this->object->term_taxonomy_id, $this->language_info->language_code, $this->target_language, $this->language_args['language_code'] );
    }

    /**
     * Translate a WP_Post object
     * 
     * Called when translatable object is WP_Post instance
     * 
     * @internal Call this only from within the class itself
     */
    protected function translate_post(): Code_IT_WPML_Post
    {
        return new Code_IT_WPML_Post( $this->object->ID, $this->language_info->language_code, $this->target_language, $this->language_args['language_code'] );
    }

    protected function fill(): void
    {
        $this->element_id = $this->object->term_taxonomy_id ?? $this->object->ID;
        $this->element_type = apply_filters( 'wpml_element_type', $this->object->taxonomy ?? 'post' );
        $this->language_info = get_element_language_details( $this->object->term_taxonomy_id ?? $this->object->ID, $this->object->taxonomy ?? 'post' );
        
        $this->language_args = array(
            'element_id'            => null,                                // Fill this when translation job starts
            'element_type'          => $this->element_type,
            'trid'                  => $this->language_info->trid,
            'language_code'         => '',                                  // Fill this when translation job starts
            'source_language_code'  => $this->language_info->language_code
        );
    }

    public static function create_term_translation_job( int $term_id ): Code_IT_WPML_Translator
    {
        return new Code_IT_WPML_Translator( get_term( $term_id ) );
    }

    public static function create_post_translation_job( int $post_id, WP_Post $post ): ?Code_IT_WPML_Translator
    {
        global $post;

        if (is_null($post)) {
            return null;
        }

        // Don't save for auto save
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
            return null;
        }

        // Don't save for revisions
        if ( isset( $post->post_type ) && $post->post_type == 'revision' ) {
            return null;
        }

        if( isset( $post->post_status ) && $post->post_status !== 'publish' ) return null;

        return new Code_IT_WPML_Translator( $post );
    }
}