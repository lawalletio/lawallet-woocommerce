<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInit8a5418840e573889a7110ecef11a6121
{
    public static $files = array (
        '97be8d00d4e1b8596dda683609f3dce2' => __DIR__ . '/..' . '/tcdent/php-restclient/restclient.php',
        'd35d0bc736fb0a17832888e8bd923562' => __DIR__ . '/..' . '/elementsproject/lightning-charge-client-php/client.php',
    );

    public static $prefixesPsr0 = array (
        'B' => 
        array (
            'BaconQrCode' => 
            array (
                0 => __DIR__ . '/..' . '/bacon/bacon-qr-code/src',
            ),
        ),
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixesPsr0 = ComposerStaticInit8a5418840e573889a7110ecef11a6121::$prefixesPsr0;

        }, null, ClassLoader::class);
    }
}
