<?php
namespace codeit\WPML_Translator;

use DeepL\DeepLException;
use WP_Error;
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
class Code_IT_WPML_Taxonomy {
    protected array $translate = [
        'name' => '',
        'slug' => '',
        'description' => ''
    ];

    /**
     * @var WP_Term|int
     */
    protected $term;

    /**
     * @var string
     */
    protected string $source_lang;

    /**
     * @var string
     */
    protected string $target_lang;

    /**
     * @var string
     */
    protected string $wpml_lang_code;

    /**
     * @param WP_Term|int   $term               - WP_Term object or term ID
     * @param string        $source_lang        - DeepL source language code
     * @param string        $target_lang        - DeepL target language code
     * @param string        $wpml_lang_code     - Should be lowercase
     */
    public function __construct($term, string $source_lang, string $target_lang, string $wpml_lang_code)
    {
        $this->wpml_lang_code = $wpml_lang_code;
        $this->target_lang = $target_lang;
        $this->source_lang = $source_lang;
        $this->term = get_term( $term );

        array_walk($this->translate, function($v, $k) {
            /**
             * Somehow DeepL translates lowercase words better
             * than its uppercase variant, we can use this to get better
             * translations of the Dutch language and fix duplicate slugs.
             *
             * @since v3.4.10
             */
            $this->translate[$k] = strtolower( $this->term->{$k} );
        });

        /**
         * Remove empty array entries to prevent DeepL throwing
         *
         * Uncaught DeepL\DeepLException: texts parameter must be a non-empty string or array of non-empty strings
         */
        $this->translate = array_filter( $this->translate );
    }

    /**
     * Start translation job of the term
     *
     * Returns `WP_Error` when failed, false if term exists, newly created term data array otherwise
     *
     * @throws DeepLException
     * @return WP_Error|array
     */
    public function translate()
    {
        $translated = Code_IT_Translator_Deepl::deepl()->translateText( $this->translate, $this->source_lang, $this->target_lang);

        $this->translate = array_combine( array_keys($this->translate), array_map( fn($t) => $t->text, $translated) );

        return wp_insert_term(ucfirst( $this->translate['name'] ) ?? $this->term->name, $this->term->taxonomy, array(
            'parent'        => $this->term->parent,
            'description'   => $this->translate['description'],
            'slug'          => apply_filters( 'codeit_generate_unique_term_slug', $this->translate['name'], $this->term->taxonomy )
        ));
    }
}