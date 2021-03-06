<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit9b7152be2bf3d1be0630126c3e5b9cf5
{
    public static $files = array (
        'cbfa370d0d967f22492b18da91735fba' => __DIR__ . '/../..' . '/functions.php',
        '457c57933d205479e279206b349227c8' => __DIR__ . '/../..' . '/inc/translator-class.php',
        '7abc0b82851cf1c105e921f9db94146e' => __DIR__ . '/../..' . '/inc/translator-filters-class.php',
        '306846784e630d3419cb377fc289a822' => __DIR__ . '/../..' . '/inc/translator-tax-class.php',
        '8a85b96b778cd155b5b0bbda47211228' => __DIR__ . '/../..' . '/inc/translator-post-class.php',
        '4d3c082942c44d389a7786a825652851' => __DIR__ . '/../..' . '/inc/translator-updater-class.php',
        'c52d9bff5a0ee79a82b2446d04c6d293' => __DIR__ . '/../..' . '/inc/translator-deepl-class.php',
        '064a292841a03803ee1668e98dc6edc8' => __DIR__ . '/../..' . '/inc/translator-options-class.php',
    );

    public static $prefixLengthsPsr4 = array (
        'D' => 
        array (
            'DeepL\\' => 6,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'DeepL\\' => 
        array (
            0 => __DIR__ . '/..' . '/deeplcom/deepl-php/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInit9b7152be2bf3d1be0630126c3e5b9cf5::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInit9b7152be2bf3d1be0630126c3e5b9cf5::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInit9b7152be2bf3d1be0630126c3e5b9cf5::$classMap;

        }, null, ClassLoader::class);
    }
}
