<?php
namespace codeit\WPML_Translator;

use \DeepL\Translator;
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
 */
class Code_IT_Translator_Deepl {
    /**
     * Array of `Code_IT_Translator_Deepl` instances
     * 
     * @var Code_IT_Translator_Deepl[]
     */
    private static $instances = [];
    /**
     * DeepL instance
     * 
     * @var Translator
     */
    protected $deepl;

    protected function __construct() 
    {
        $this->deepl = new Translator( Code_IT_Translator_Options::get_option('api-key', 'global-settings') );
    }

    /**
     * Singletons should not be cloneable.
     */
    protected function __clone() { }

    /**
     * Singletons should not be restorable from strings.
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize a singleton.");
    }

    /**
     * Get Code_IT_Translator_Deepl instance
     * 
     * @return Code_IT_Translator_Deepl|null
     */
    public static function get_instance(): Code_IT_Translator_Deepl|null
    {
        $cls = static::class;

        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }

        return self::$instances[$cls];
    }

    /**
     * Get DeepL instance
     * 
     * Returns `null` when no API key is provided
     * 
     * @return Translator|null
     */
    public static function deepl() 
    {
        if( static::no_api_key() ) {
            return null;
        }

        return static::get_instance()->deepl;
    }

    /**
     * Check if we have a API key available
     * 
     * @return bool
     */
    public static function no_api_key() 
    {
        return ! Code_IT_Translator_Options::get_option('api-key', 'global-settings');
    }

    /**
     * Check if DeepL supports given target language
     * 
     * @return bool
     */
    public static function is_language_supported( string $language_code )
    {
        if( static::no_api_key() ) {
            throw new ErrorException('No API key available', 500);
        }

        if( ! static::deepl() ) {
            throw new ErrorException('Failed to call DeepL', 500);
        }

        $supported_languages = static::deepl()->getTargetLanguages();

        switch( $supported_languages ) {
            case !!array_filter( $supported_languages, fn ( \DeepL\Language $lang ) => $language_code === strtolower( $lang->code ) || str_contains( strtolower( $lang->code ), $language_code ) ) :
                return true;
            case $language_code === 'zh-hans' :
                return true;
            case $language_code === 'zh-hant' : 
                return false;
            default : 
                return false;
        }
    }
}