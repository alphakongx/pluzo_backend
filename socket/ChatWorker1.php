<?php 
require_once __DIR__ . '/vendor/autoload.php';
use Workerman\Worker;
$users = [];
$ws_worker = new Worker("websocket://0.0.0.0:27800");

$ws_worker->onMessage = function($connection, $data) use (&$users) {

        $data = json_decode($data);
        $msg = json_encode( (array)$data );

        foreach ($users as $c) {
                $c->send($msg);
        }
};
$ws_worker->onConnect = function($connection) use (&$users)
{
    $connection->onWebSocketConnect = function($connection) use (&$users)
    {    
        $users[$_GET['user']] = $connection;
        $messageData = [
            'action' => 'Authorized',
            'userId' => $_GET['user']
        ];
        $connection->send(json_encode($messageData));
    };
};
$ws_worker->onClose = function($connection) use(&$users)
{
    $user = array_search($connection, $users);
    unset($users[$user]);
};

Worker::runAll();