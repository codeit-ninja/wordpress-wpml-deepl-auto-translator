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
     * @param WP_Term|int   $term               - WP_Term object or term ID
     * @param string        $source_lang        - DeepL source language code
     * @param string        $target_lang        - DeepL target language code
     * @param string        $wpml_lang_code     - Should be lowercase
     */
    public function __construct(protected WP_Term|int $term, protected string $source_lang, protected string $target_lang, protected string $wpml_lang_code)
    {
        $this->term = get_term( $term );

        array_walk($this->translate, fn($v, $k) => $this->translate[$k] = $this->term->{$k});

        $this->translate = array_filter( $this->translate );
    }

    /**
     * Start translation job of the term
     *
     * Returns `WP_Error` when failed, otherwise newly created term data array
     *
     * @throws DeepLException
     * @return WP_Error|array
     */
    public function translate(): WP_Error|array
    {
        $translated = Code_IT_Translator_Deepl::deepl()->translateText( $this->translate, $this->source_lang, $this->target_lang);

        $this->translate = array_combine( array_keys($this->translate), array_map( fn($t) => $t->text, $translated) );

        return wp_insert_term($this->translate['name'] ?? $this->term->name, $this->term->taxonomy, array(
            'parent'        => $this->term->parent,
            'description'   => $this->translate['description'],
            'slug' => codeit_unique_term_slug( $this->translate['name'], $this->term->taxonomy )
        ));
    }
}