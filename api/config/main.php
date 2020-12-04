<?php
$params = array_merge(
    require(__DIR__ . '/../../common/config/base.php')
);

$config = [
    'id' => 'Pluzo',
    'basePath' => dirname(__DIR__),
    'sourceLanguage' => 'en-US',
    'language' => 'en-US',
    'controllerNamespace' => 'api\controllers',
    'bootstrap' => ['log'],
    'modules' => [],
    'components' => [
        'request' => [
            'cookieValidationKey' => env('API_COOKIE_VALIDATION_KEY'),
            'parsers' => [
                'application/json' => 'yii\web\JsonParser',
                'application/xml' => 'yii\web\XmlParser',
            ],
        ],
        'response' => [
            'class' => 'yii\web\Response',
            'format' => 'json',
            'on beforeSend' => function ($event) {
                header("Access-Control-Allow-Origin: *");               
                header('Access-Control-Allow-Headers: authorization');
                header('Access-Control-Allow-Credentials: true');
                $response = $event->sender;
                if ($response->data !== null) {

                    $data = $response->data;
                    // Error handle
                    $error = '';
                    if( ! $response->isSuccessful) {
                        if(isset($data['message'])) {
                            $error = $data['message'];
                        } elseif(isset(current($data)['message'])) {
                            $error = current($data)['message'];
                        }
                    }
                    $response->data = [
                        'error' => !$response->isSuccessful,
                        //'code' => $response->statusCode,
                        'message' => $error,
                    ];
                    if($response->isSuccessful) {
                        $response->data['data'] = $data;
                    }
                    // $response->statusCode = 200;
                }
            },
            'formatters' => [
                'json' => [
                    'class' => 'yii\web\JsonResponseFormatter',
                    'prettyPrint' => YII_DEBUG,
                    'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ],
            ],
        ],
        'user' => [
            'identityClass' => 'common\models\Client',
            'enableAutoLogin' => false,
            'enableSession' => false,
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
        'urlManager' => [
            'enablePrettyUrl' => true,
            'enableStrictParsing' => true,
            'showScriptName' => false,
            'rules' => [
                'test' => 'site/test',
                '' => 'site/index',
                'user-online' => 'site/user-online',
                'user-offline' => 'site/user-offline',
                'login' => 'site/login',
                'signup' => 'site/signup',
                'update' => 'site/update',
                'update-pass' => 'site/update-pass',
                'update-phone-send' => 'site/update-phone-send',
                'update-phone-code' => 'site/update-phone-code',
                'update' => 'site/update',
                'profile' => 'site/profile',
                'user-block' => 'site/user-block',
                'user-unblock' => 'site/user-unblock',
                'get-blocked' => 'site/get-blocked',
                'add-badge' => 'site/add-badge',
                'get-badge' => 'site/get-badge',
                'delete-badge' => 'site/delete-badge',
                'get-user-info' => 'site/get-user-info',
                'delete-account' => 'site/delete-account',
                'new-pass-code' => 'site/new-pass-code',
                'sort-image' => 'site/sort-image',
                'check-username' => 'site/check-username',
                'forgot-sms' => 'site/forgot-sms',
                'forgot-sms-send' => 'site/forgot-sms-send',
                'forgot-sms-code' => 'site/forgot-sms-code',
                'new-pass-code' => 'site/new-pass-code',
                'verify-sms-send' => 'site/verify-sms-send',
                'verify-sms-code' => 'site/verify-sms-code',
                'login-sms' => 'site/login-sms',
                'login-sms-send' => 'site/login-sms-send',
                'login-sms-code' => 'site/login-sms-code',
                'search-user' => 'site/search-user',
                'search' => 'site/search-all',
                'delete-image' => 'site/delete-image',
                'get-chat' => 'chat/chat',
                'get-chat-user' => 'chat/get-chat-user',
                'get-chat-msg' => 'chat/chat-message',
                'recently-messaged-users' => 'chat/recently-messaged-users',
                'get-current-chat' => 'chat/get-current-chat',
                'close-chat' => 'chat/close-chat',
                'open-chat' => 'chat/open-chat',
                'delete-chat' => 'chat/delete-chat',
                'send-msg' => 'chat/send-message',
                'read-message' => 'chat/read-message',
                'update-msg' => 'chat/update-message',
                'delete-msg' => 'chat/delete-message',
                'send-like' => 'like/send-like',
                'send-like-all' => 'like/send-like-all',
                'get-like' => 'like/get-like',
                'get-liked-users' => 'like/get-liked-users',
                'get-match' => 'like/get-match',
                'swipe' => 'like/swipe',
                'swipe-search' => 'like/swipe-search',
                'get-swipe-setting' => 'like/get-swipe-setting',
                'set-swipe-setting' => 'like/set-swipe-setting',
                'sms' => 'site/sms',
                'check-number' => 'site/check-number',
                'add-friend' => 'friend/add-friend',
                'get-friends' => 'friend/get-friends',
                'friend-requests-reject' => 'friend/friend-requests-reject',
                'friend-requests-my' => 'friend/friend-requests-my',
                'friend-requests-to-me' => 'friend/friend-requests-to-me',
                'friend-requests-to-me-reject' => 'friend/friend-requests-to-me-reject',
                'add-friend-username' => 'friend/add-friend-username',
                'friend-remove' => 'friend/friend-remove',
                'is-friend' => 'friend/is-friend',
                'read-flag' => 'friend/read-flag',
                'stream-start' => 'stream/stream-start',
                'stream-update' => 'stream/stream-update',
                'stream-stop' => 'stream/stream-stop',
                'stream-users' => 'stream/stream-users',
                'stream-join' => 'stream/stream-join',
                'stream-disconnect' => 'stream/stream-disconnect',
                'stream-list' => 'stream/stream-list',
                'stream-list-api' => 'stream/stream-list-api',
                'stream-user-list-api' => 'stream/stream-user-list-api',
                'stream-invite' => 'stream/stream-invite',
                'stream-cancel-invite' => 'stream/stream-cancel-invite',
                'stream-ban-user' => 'stream/stream-ban-user',
                'stream-unban-user' => 'stream/stream-unban-user',
                'stream-ban-list' => 'stream/stream-ban-list',
                'stream-ask-join' => 'stream/stream-ask-join',
                'stream-accept-join' => 'stream/stream-accept-join',
                'stream-refused-join' => 'stream/stream-refused-join',
                'stream-disconnect-broad' => 'stream/stream-disconnect-broad',
                'stream-disconnect-broad-by-user' => 'stream/stream-disconnect-broad-by-user',
                //user send ask to join as broad
                'stream-user-ask-join' => 'stream/stream-user-ask-join',
                //host accept to join
                'stream-user-accept-join' => 'stream/stream-user-accept-join',
                //host refused to join
                'stream-user-refused-join' => 'stream/stream-user-refused-join',
                //user cancel ask to join
                'stream-user-cancel-ask' => 'stream/stream-user-cancel-ask',
                'stream-chat-add-msg' => 'stream/stream-chat-add-msg',
                'stream-chat-get-msg' => 'stream/stream-chat-get-msg',
                //check if already invite user
                'is-invite' => 'stream/is-invite',
                'stream-new-people' => 'stream/stream-new-people',
                //change type of user in stream
                'stream-user-type' => 'stream/stream-user-type',
                'push-test' => 'site/push-test',

                'services' => 'payment/services',
                'pay' => 'payment/pay',
                //run service
                'run-service' => 'payment/run-service',
                'run-boost' => 'payment/run-boost',
                'run-remind' => 'payment/run-remind',
                'stream-report' => 'stream/stream-report',
                'user-report' => 'site/user-report',
                'check-user-status' => 'site/check-user-status',
                'ask-question' => 'site/ask-question',
                
            ],
        ],
    ],
    'params' => $params,
];

return $config;
