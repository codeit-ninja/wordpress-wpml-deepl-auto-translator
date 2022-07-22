<?php
namespace codeit\WPML_Translator;

use DeepL\DeepLException;
use \DeepL\Translator;
use ErrorException;
use Exception;

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
    private static array $instances = [];
    /**
     * DeepL instance
     * 
     * @var Translator
     */
    protected Translator $deepl;

    /**
     * @throws DeepLException
     */
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
     * @throws Exception
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize a singleton.");
    }

    /**
     * Get Code_IT_Translator_Deepl instance
     * 
     * @return Code_IT_Translator_Deepl|null
     */
    public static function get_instance(): Code_IT_Translator_Deepl
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
    public static function deepl(): ?Translator
    {
        if( static::no_api_key() ) {
            return null;
        }

        return static::get_instance()->deepl;
    }

    /**
     * Check if we have an API key available
     * 
     * @return bool
     */
    public static function no_api_key(): bool
    {
        return ! Code_IT_Translator_Options::get_option('api-key', 'global-settings');
    }

    /**
     * Check if DeepL supports given target language
     *
     * @param string $language_code
     * @return bool
     * @throws DeepLException
     * @throws ErrorException
     */
    public static function is_language_supported( string $language_code ): bool
    {
        if( static::no_api_key() ) {
            throw new ErrorException('No API key available', 500);
        }

        if( ! static::deepl() ) {
            throw new ErrorException('Failed to call DeepL', 500);
        }

        $supported_languages = static::deepl()->getTargetLanguages();

        switch( $supported_languages ) {
            case $language_code === 'zh-hans':
            case !!array_filter( $supported_languages, fn ( \DeepL\Language $lang ) => $language_code === strtolower( $lang->code ) || str_contains( strtolower( $lang->code ), $language_code ) ) :
                return true;
            default :
                return false;
        }
    }
}