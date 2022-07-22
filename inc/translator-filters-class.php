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
 * @package CodeIT\WPML_Translator
 * @since v3.4.10
 */
class Code_IT_Translator_Filters
{
    public function __construct()
    {
        /**
         * Set text of checkbox label
         *
         * Show a 'not supported' label on settings page
         * when language is not supported by DeepL
         *
         * @since v1.0.1
         */
        add_filter( 'codeit_checkbox_label', array( $this, 'set_checkbox_label' ));
        /**
         * Set text of checkbox state
         *
         * Disable checkbox on settings page when
         * language is not supported by DeepL
         *
         * @since v1.0.1
         */
        add_filter( 'codeit_checkbox_state', array( $this, 'set_checkbox_state' ));
        /**
         * Filter post term IDs
         *
         * Returns an array of IDs
         *
         * @since v3.4.10
         */
        add_filter('codeit_get_term_ids', array( $this, 'get_term_ids' ), 10, 3 );
        /**
         * Generate a unique term slug
         *
         * @since v3.4.10
         */
        add_filter('codeit_generate_unique_term_slug', array( $this, 'generate_unique_term_slug' ), 10, 3 );
    }

    /**
     * @throws ErrorException|DeepLException
     */
    public function set_checkbox_label(array $args ): string
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
    public function set_checkbox_state(array $args ): string
    {
        if( ! Code_IT_Translator_Deepl::deepl() ) {
            return 'disabled';
        }

        if( isset( $args['code'] ) && $args['code'] ) {
            return Code_IT_Translator_Deepl::is_language_supported( $args['code'] ) ? '' : 'disabled';
        }

        return '';
    }

    /**
     * Get term translation ID's by post
     *
     * @param int       $post_ID   - WP_Post ID
     * @param string    $tax       - Name of taxonomy
     * @param string    $language  - WPML Language code
     *
     * @return array
     */
    public function get_term_ids( int $post_ID, string $tax, string $language ): array
    {
        $term_ids = wp_get_post_terms( $post_ID, $tax, array( 'fields' => 'ids' ) );
        $translated_term_ids = array_map( fn( $cat_ID ) => apply_filters( 'wpml_object_id', $cat_ID, 'category', false, $language ), $term_ids );

        return array_filter( $translated_term_ids, fn( $term_id) => term_exists( $term_id, $tax ) );
    }

    /**
     * Sometimes WordPress fails to create a unique slug
     * when invoking the terms hooks
     *
     * This function will generate a unique slug based on given slug
     *
     * @param string    $slug       - String so slugify
     * @param string    $taxonomy   - Type of taxonomy
     * @param int       $offset     - (Optional) What offset number to start appending? (default 1)
     *
     * @return string
     */
    public function generate_unique_term_slug( string $slug, string $taxonomy, int $offset = 1 ): string
    {
        $slug = sanitize_title( $slug );
        $exists = get_term_by( 'slug', $slug, $taxonomy );

        if( ! $exists ) {
            return $slug;
        }

        return apply_filters( 'codeit_generate_unique_term_slug', $slug . '-' . $offset, $taxonomy, $offset + 1 );
    }
}