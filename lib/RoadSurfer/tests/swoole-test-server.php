<?php

use Swoole\Http\Server;
use Swoole\Http\Request;
use Swoole\Http\Response;

$host = '127.0.0.1';
$port = 9501;

$server = new Server($host, $port);

echo "Server Swoole on http://{$host}:{$port}\n";

$server->on(
    'request',
    function (Request $request, Response $response) {
        global $stations, $station, $rallyStations, $timeFrames;
        require_once __DIR__.'/DummyEntitiesConfig.php';

        echo 'Request Log: '.$request->server['request_uri']."\n";

        switch ($request->server['request_uri']) {
            case '/translations/stations':
                $response->header('Content-Type', 'application/json');
                $response->end(json_encode($stations));
                break;

            case '/it/rally/stations/1':
                $response->header('Content-Type', 'application/json');
                $response->end(json_encode($station));
                break;

            case '/it/rally/stations':
                $response->header('Content-Type', 'application/json');
                $response->end(json_encode($rallyStations));
                break;

            case '/it/rally/timeframes/1-2':
                $response->header('Content-Type', 'application/json');
                $response->end(json_encode($timeFrames));
                break;

            default:
                $response->status(404);
                $response->end('Not Found');
                break;
        }
    }
);

$server->set([
    'log_file' => __DIR__.'/swoole.log',
    'log_level' => SWOOLE_LOG_DEBUG,
]);

$server->start();
