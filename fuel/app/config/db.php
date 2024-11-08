
<?php
/**
 * Fuel is a fast, lightweight, community driven PHP 5.4+ framework.
 *
 * @package    Fuel
 * @version    1.9-dev
 * @author     Fuel Development Team
 * @license    MIT License
 * @copyright  2010 - 2019 Fuel Development Team
 * @link       https://fuelphp.com
 */

/**
 * -----------------------------------------------------------------------------
 *  Global database settings
 * -----------------------------------------------------------------------------
 *
 *  Set database configurations here to override environment specific
 *  configurations
 *
 */
?>

<?php

return array(
    'default' => array(
        'type'        => 'mysql',
        'connection'  => array(
            'hostname'   => 'localhost',
            'port'       => '3306', 
            'database'   => '', //taskappDBを参照
            'username'   => 'root',  // デフォルトのMySQLユーザー
            'password'   => 'root',  // デフォルトのMySQLパスワード（MAMPの場合）
            'persistent' => false,
        ),
        'table_prefix' => '',
        'charset'      => 'utf8',
        'caching'      => false,
        'profiling'    => true,
    ),
);
