<?php

use DI\Container;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\StreamFactory;

require __DIR__ . '/../vendor/autoload.php';

$app = AppFactory::create(container: new Container());

$app->getContainer()
    ->set('db', function () {
        ['db' => [
            'host' => $host,
            'user' => $user,
            'pass' => $pass,
            'schema' => $schema,
        ]] = parse_ini_file(__DIR__ . '/../configs.ini', true);

        return new PDO("mysql:host=$host;dbname=$schema;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    });

$app->get('/', function (Request $request, Response $response) {
    return $response
        ->withHeader('Content-Type', 'text/html')
        ->withBody(
            new StreamFactory()
                ->createStreamFromFile(__DIR__ . '/index.html')
        );
});

$app->post('/sensor-data', function (Request $request, Response $response) {
    $db = $this->get('db');
    $data = json_decode($request->getBody(), true);

    if (!isset($data['id'], $data['face'], $data['timestamp'], $data['temperature'])) {
        $response->getBody()->write(json_encode(['error' => 'Invalid request data' ,'requested_data' => $data]));
        return $response->withStatus(400);
    }


    $stmt = $db->prepare(<<<SQL
INSERT INTO sensors (sensor_id, face) 
VALUES (:id, :face) 
ON DUPLICATE KEY UPDATE face = :face
SQL
    );
    $stmt->execute([
        ':id' => $data['id'],
        ':face' => $data['face']
    ]);

    $stmt = $db->prepare(<<<SQL
INSERT INTO temperatures (sensor_id, timestamp, temperature) 
VALUES (:id, FROM_UNIXTIME(:timestamp), :temperature)
SQL
    );
    $stmt->execute([
        ':id' => $data['id'],
        ':timestamp' => $data['timestamp'],
        ':temperature' => $data['temperature']
    ]);

    $response->getBody()->write(json_encode(['status' => 'success']));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/report/hourly', function (Request $request, Response $response) {
    /** @var PDO $db */
    $db = $this->get('db');
    $stmt = $db->query(<<<SQL
SELECT 
    s.face AS face, 
    DATE(t.timestamp) AS day, 
    HOUR(t.timestamp) AS hour, 
    AVG(t.temperature) AS avg_temp
FROM temperatures AS t
JOIN sensors AS s
USING(sensor_id)
WHERE t.timestamp >= NOW() - INTERVAL 7 DAY
GROUP BY s.face, day, hour
ORDER BY day DESC, hour DESC, face DESC
SQL
    );
    $data = $stmt->fetchAll();

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});


$app->get('/report/malfunctions', function (Request $request, Response $response) {
    $interval = 1;
    /** @var PDO $db */
    $db = $this->get('db');
    $stmt = $db->query(<<<SQL
WITH
FaceAvg AS (
    SELECT s.face, AVG(t.temperature) AS avg_temp
    FROM temperatures AS t
    JOIN sensors AS s
        USING(sensor_id)
    WHERE t.timestamp >= NOW() - INTERVAL {$interval} HOUR
    GROUP BY s.face
),
SensorAvg AS (
    SELECT t.sensor_id, AVG(t.temperature) AS sensor_avg
    FROM temperatures AS t
    WHERE t.timestamp >= NOW() - INTERVAL {$interval} HOUR
    GROUP BY t.sensor_id
)
SELECT s.sensor_id, s.face, 
       sa.sensor_avg AS sensor_avg, 
       fa.avg_temp AS avg_temp
FROM sensors AS s
LEFT JOIN SensorAvg AS sa
USING(sensor_id)

JOIN FaceAvg AS fa
USING(face)

WHERE
    sa.sensor_id IS NULL OR
    (ABS(sa.sensor_avg - fa.avg_temp) / fa.avg_temp) * 100 > 20
SQL
    );

    $data = $stmt->fetchAll();

    $response->getBody()->write(json_encode($data));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->run();