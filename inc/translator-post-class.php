<?php
namespace codeit\WPML_Translator;

use DeepL\DeepLException;
use WP_Error;
use WP_Post;
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
 */
class Code_IT_WPML_Post {
    protected mixed $post_meta;

    protected array $translate = [
        'post_content' => '',
        'post_title' => '',
        'post_excerpt' => ''
    ];

    /**
     * @param WP_Post|int   $post               - WP_Post object or post ID
     * @param string        $source_lang        - DeepL source language code
     * @param string        $target_lang        - DeepL target language code
     * @param string        $wpml_lang_code     - Should be lowercase
     */
    public function __construct(protected WP_Post|int $post, protected string $source_lang, protected string $target_lang, protected string $wpml_lang_code)
    {
        $this->post = get_post( $post );
        $this->post_meta = get_post_custom( $this->post->ID );

        array_walk($this->translate, fn($v, $k) => $this->translate[$k] = $this->post->{$k});
    }

    /**
     * Start translation job of the post
     *
     * Returns `WP_Error` when failed, otherwise `Post_ID`
     *
     * @throws DeepLException
     * @return WP_Error|int
     */
    public function translate(): WP_Error|int
    {
        $translated = Code_IT_Translator_Deepl::deepl()->translateText( $this->translate, $this->source_lang, $this->target_lang,
            array(
                'tag_handling' => 'html'
            )
        );

        $post = array(
            'post_status'   => 'publish',
            'post_type'     => 'post',
            'tax_input'     => array(
                'category'  => codeit_get_post_terms_translation_ids( $this->post->ID, 'category', $this->wpml_lang_code ),
                'post_tag'  => codeit_get_post_terms_translation_ids( $this->post->ID, 'post_tag', $this->wpml_lang_code )
            )
        );

        $this->translate = array_combine( array_keys($this->translate), array_map( fn($t) => $t->text, $translated) );

        $post_ID =  wp_insert_post( array_merge($this->translate, $post), false, false );

        /**
         * Copy post meta over to translated post
         */
        foreach ( $this->post_meta as $key => $values) {
            foreach ($values as $value) {
                add_post_meta( $post_ID, $key, $value );
            }
        }

        return $post_ID;
    }
}