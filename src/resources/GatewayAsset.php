<?php

namespace samuelreichor\gateway\resources;

use craft\web\AssetBundle;

/**
 * Gateway Bundle asset bundle
 */
class GatewayAsset extends AssetBundle
{
    public $sourcePath = __DIR__ . '/src';
    public $js = [
        'script.js',
    ];
    public $css = [
        'style.css',
    ];
}
