<?php
/*
Plugin Name: magazify-eklenti-m1
Description: boş eklenti deneme
Version: 1.0
Author: Magazify
* GitHub Plugin URI: https://github.com/adminmagazify/Magazify-Eklenti-M1
*/

require plugin_dir_path(__FILE__) . 'plugin-update-checker-master/plugin-update-checker.php';

$updateChecker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/adminmagazify/Magazify-Eklenti-M1',
    __FILE__,
    'magazify-eklenti-m1' // Eklenti klasör adı
);

$updateChecker->setBranch('main');