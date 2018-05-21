<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-05-18 16:03
 */

// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', false);
defined('YII_ENV') or define('YII_ENV', 'dev');

require(__DIR__ . '/vendor/autoload.php');
require(__DIR__ . '/vendor/yiisoft/yii2/Yii.php');

//判断加载的项目（web、wap、api、web）
if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false)
    header('/admin',true,301);
elseif(strpos($_SERVER['PHP_SELF'], '/backend') !== false){
    header('/backend/web/',true,301);
}

