<?php
$params = array_merge(
    require __DIR__ . '/../../common/config/params.php',
    require __DIR__ . '/../../common/config/params-local.php',
    require __DIR__ . '/params.php',
    require __DIR__ . '/params-local.php'
);

return [
    'id' => 'app-backend',
    'name' => 'Meta-Oa',
    'basePath' => dirname(__DIR__),
    'controllerNamespace' => 'backend\controllers',
    'bootstrap' => ['log'],
    'timeZone' => 'Asia/Shanghai',
    'modules' => [
        'admin' => [
            'class' => 'mdm\admin\Module',
//            'layout' => 'left-menu'
        ],
        'v1' => [
            'class' => 'backend\modules\v1\Module',
        ],
        'gii' => [
            'class' => 'yii\gii\Module',
            'generators' => [
                'mongoDbModel' => [
                    'class' => 'yii\mongodb\gii\model\Generator'
                ]
            ],
        ],
    ],
    'language' => 'zh-CN',
    'components' => [
        'formatter' => [
            'class' => 'yii\i18n\Formatter',
            'dateFormat' => 'php:Y-m-d',
            'datetimeFormat' => 'php:Y-m-d H:i:s',
            'timeFormat' => 'php:H:i:s',
        ],
        'request' => [
            'csrfParam' => '_csrf-backend',
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
            ]
        ],
        'user' => [
            'identityClass' => 'common\models\User',
            'enableAutoLogin' => false,
//            'enableSession' => false
//            'identityCookie' => ['name' => '_identity-backend', 'httpOnly' => true],
//            'authTimeout' => 24*3600
        ],
        'session' => [
            // this is the name of the session cookie used for login on the backend
            'name' => 'advanced-backend',
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'rules' => [
                [
                    'class' => 'yii\rest\UrlRule',
                    'controller' => ['v1/goods','v1/requirements']
                ],
            ]
        ],
        'authManager'  => [
        'class' => 'yii\rbac\DbManager',
                ]
    ],
    'aliases' => [
        '@mdm/admin' => '@vendor/mdmsoft/yii2-admin',
    ],
    'as cors' => [
        'class' => \yii\filters\Cors::className(),
        'cors' => [
            'Origin' => ['*'],
            'Access-Control-Allow-Credentials' => true
        ]
    ],
    'as access' => [
        'class' => 'mdm\admin\components\AccessControl',
        'allowActions' => [
            'site/*',
            'v1/*',
            'gii/*',
//            'admin/*',
            'backend/*',
        ]
    ],
    'params' => $params,
];
