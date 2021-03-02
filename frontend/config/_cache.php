<?php
/**
 * @author Eugene Terentev <eugene@terentev.net>
 */

/*$cache = [
    'class' => 'yii\caching\FileCache',
    'cachePath' => '@frontend/runtime/cache'
];*/

if (YII_ENV_DEV) {
    /*$cache = [
        'class' => 'yii\caching\DummyCache'
    ];*/
}

if(isset(\Yii::$app->user->id)){
    $user_id = \Yii::$app->user->id;
} else {
    $user_id = 0;
}
$cache = [
            'class' => 'yii\redis\Cache',
            'keyPrefix' => $user_id.'_',
            'redis' => [
                'hostname' => 'localhost',
                'port' => 6379,
                'database' => 0,
            ]
        ];
return $cache;
