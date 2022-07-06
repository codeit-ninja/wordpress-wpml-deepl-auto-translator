<?php
/**
 * Returns WPML element language details
 *
 * @param int       $element_id
 * @param string    $element_type
 *
 * @return stdClass
 */
function get_element_language_details( int $element_id, string $element_type ): stdClass
{
    return apply_filters( 'wpml_element_language_details', null, array('element_id' => $element_id, 'element_type' => $element_type ) );
}
/**
 * Boots plugin admin area
 */
function codeit_register_options_page(): codeit\WPML_Translator\Code_IT_Translator_Options
{
    $options = new codeit\WPML_Translator\Code_IT_Translator_Options();


    $options
        ->add_section( 'global-settings', 'Global settings' )
        ->add_field('enabled', 'Enable translator?', 'checkbox', 'global-settings')
        ->add_field('api-key', 'DeepL API Key', 'text', 'global-settings');
    $options
        ->add_section( 'language-settings', 'Language settings', 'Check the languages you want to auto translate.' );
    
    $active_languages = apply_filters( 'wpml_active_languages', null );

    /**
     * Add language settings fields
     */
    array_walk($active_languages, fn( $language ) => $options->add_field( $language['code'], $language['translated_name'] ?? $language['english_name'] ?? $language['native_name'], 'checkbox', 'language-settings', $language ));

    /**
     * This will load and boot the options for the plugin to use
     */
    return $options->create();
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
function codeit_unique_term_slug( string $slug, string $taxonomy, int $offset = 1 ): string
{
    $slug = sanitize_title( $slug );
    $exists = get_term_by( 'slug', $slug, $taxonomy );

    if( ! $exists ) {
        return $slug;
    }

    return codeit_unique_term_slug( $slug . '-' . $offset, $taxonomy, $offset + 1 );
}
/**
 * Get term translation ID's by post
 * 
 * @param int       $post_ID        - WP_Post ID
 * @param string    $taxonomy       - Name of taxonomy
 * @param string    $language_code  - WPML Language code
 * 
 * @return array
 */
function codeit_get_post_terms_translation_ids( int $post_ID, string $taxonomy, string $language_code ): array
{
    $term_ids = wp_get_post_terms( $post_ID, $taxonomy, array( 'fields' => 'ids' ) );
    $translated_term_ids = array_map( fn( $cat_ID ) => apply_filters( 'wpml_object_id', $cat_ID, 'category', false, $language_code ), $term_ids );
    
    return array_filter( $translated_term_ids, fn( $term_id) => term_exists( $term_id, $taxonomy ) );
}