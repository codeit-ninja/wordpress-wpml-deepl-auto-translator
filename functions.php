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