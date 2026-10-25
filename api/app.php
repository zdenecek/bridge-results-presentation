<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;

$pdo = include(__DIR__ . '/db.php');
require_once __DIR__ . '/gemini.php';
require_once __DIR__ . '/bavlnka.php';
$app = AppFactory::create();
$config = include(__DIR__ . '/config.php');

$apikey = $config['key'];

// Written by the deploy workflow from the GEMINI_API_KEY secret.
$geminiConfigFile = __DIR__ . '/gemini-config.php';
$gemini = file_exists($geminiConfigFile) ? include($geminiConfigFile) : ($config['gemini'] ?? []);


$prefix = "/api";

$loggedInMiddleware = function ($request, $handler) use ($apikey): ResponseInterface {
    $routeContext = RouteContext::fromRequest($request);
    $route = $routeContext->getRoute();

    if (empty($route)) {
        throw new HttpNotFoundException($request, $response);
    }

    $routeName = $route->getName();
    $key = $request->getHeader('Apikey');
    // A scheduled task on the hosting can only call a plain URL, so the update also takes the key from the query.
    if ($routeName === 'bavlnka-update' && !array_key_exists(0, $key) && isset($request->getQueryParams()['key'])) {
        $key = [$request->getQueryParams()['key']];
    }

    $method =  $request->getMethod();

    // Define routes that user does not have to be logged in with. All other routes, the user needs to be logged in with.
    // Names for routes must be defined in routes.php with ->setName() for each one.
    $publicRoutesArray = array('tournament', 'tournaments', 'home', 'bavlnka-ranking', 'bavlnka-ranking-csv');

    if ((!array_key_exists(0, $key) || $key[0] !== $apikey) && (!in_array($routeName, $publicRoutesArray)) && $method !== 'OPTIONS') {
        $response = new \Slim\Psr7\Response();
        return $response->withStatus(401);
    } else {
        $response = $handler->handle($request);
        return $response;
    }
};


$app->get("$prefix/", function (Request $request, Response $response, $args) use ($pdo) {

    $response->getBody()->write(json_encode([
            'message' => "welcome to the api"
        ]));
    return $response;

})->setName('home');

$app->get("$prefix/tournaments", function (Request $request, Response $response, $args) use ($pdo) {

    try {
        // Prepare and execute the SQL query
        $query = "SELECT id, name, slug FROM tournaments";
        $statement = $pdo->prepare($query);
        $statement->execute();

        // Fetch all rows as associative arrays
        $tournaments = $statement->fetchAll(PDO::FETCH_ASSOC);

        $response->getBody()->write(json_encode($tournaments));


    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            'message' =>  $e->getMessage()
        ]));
        return $response->withStatus(500); // Internal Server Error status code
    }
    return $response;
})->setName('tournaments');


$app->options("$prefix/tournaments", function (Request $request, Response $response): Response {
    return $response;
});


$app->post("$prefix/tournament", function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();


    $name = $data['name'];
    $slug = $data['slug'];
    $jsonData = $data['data'] ? json_encode($data['data']) : '{}';

    try {

        // Insert data into the "tournaments" table
        $query = "INSERT INTO tournaments (name, slug, data) VALUES (:name, :slug, :data)";
        $statement = $pdo->prepare($query);
        $statement->bindParam(':name', $name);
        $statement->bindParam(':slug', $slug);
        $statement->bindParam(':data', $jsonData);
        $statement->execute();

        $lastId = $pdo->lastInsertId();
        $response->getBody()->write(json_encode([
            'id' => $lastId
        ]));
        return $response->withStatus(201); // Created status code
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            'message' =>  $e->getMessage()
        ]));
        return $response->withStatus(500); // Internal Server Error status code
    }
});


$app->options("$prefix/tournament", function (Request $request, Response $response): Response {
    return $response;
});


$app->put("$prefix/tournament/{id}", function (Request $request, Response $response, array $args) use ($pdo) {
    $data = $request->getParsedBody();

    $id = $args['id'];

    $name = $data['name'];
    $slug = $data['slug'];
    $jsonData = json_encode($data['data']);

    try {

        // Insert data into the "tournaments" table
        $query = "UPDATE tournaments SET name = :name, slug = :slug, data = :data WHERE id = :id";
        $statement = $pdo->prepare($query);
        $statement->bindParam(':name', $name);
        $statement->bindParam(':slug', $slug);
        $statement->bindParam(':data', $jsonData);
        $statement->bindParam(':id', $id);
        $statement->execute();

        $response->getBody()->write('Tournament created successfully');
        return $response->withStatus(201); // Created status code
    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            'message' =>  $e->getMessage()
        ]));
        return $response->withStatus(500); // Internal Server Error status code
    }
});

$app->options("$prefix/tournament/{id}", function (Request $request, Response $response): Response {
    return $response;
});


$app->get("$prefix/tournament/{slug}", function (Request $request, Response $response, array $args) use ($pdo) {

    $slug = $args['slug'];

    $id = intval($slug);

    try {

        // Insert data into the "tournaments" table
        $query = "SELECT * FROM tournaments WHERE (slug = :slug OR id = :id)";
        $statement = $pdo->prepare($query);
        $statement->bindParam(':slug', $slug);
        $statement->bindParam(':id', $id);
        $statement->execute();

        $data = $statement->fetch(PDO::FETCH_ASSOC);
        $data['data'] = json_decode($data['data']);

        $response->getBody()->write(json_encode($data));
        return $response->withStatus(200); // Created status code
    } catch (PDOException $e) {
        $response->getBody()->write('Error: ' . $e->getMessage());
        return $response->withStatus(500); // Internal Server Error status code
    }
})->setName('tournament');



$app->delete("$prefix/tournament/{id}", function (Request $request, Response $response, array $args) use ($pdo) {

    $id = $args['id'];

    try {

        $query = "DELETE FROM tournaments WHERE id = :id";
        $statement = $pdo->prepare($query);
        $statement->bindParam(':id', $id);
        $statement->execute();

        return $response->withStatus(204); 
    } catch (PDOException $e) {
        $response->getBody()->write('Error: ' . $e->getMessage());
        return $response->withStatus(500); // Internal Server Error status code
    }
});



$app->post("$prefix/transcribe-slip", function (Request $request, Response $response) use ($gemini) {
    $data = $request->getParsedBody();

    if (empty($gemini['key'])) {
        $response->getBody()->write(json_encode(['message' => 'Na serveru chybí klíč ke Gemini']));
        return $response->withStatus(500);
    }
    if (empty($data['image'])) {
        $response->getBody()->write(json_encode(['message' => 'Chybí fotka']));
        return $response->withStatus(400);
    }

    set_time_limit(180);
    try {
        $response->getBody()->write(json_encode(transcribeSlip($data['image'], $data['mimeType'] ?? 'image/jpeg', $gemini)));
        return $response;
    } catch (RuntimeException $e) {
        $response->getBody()->write(json_encode(['message' => $e->getMessage()]));
        return $response->withStatus(502);
    }
});

$app->options("$prefix/transcribe-slip", function (Request $request, Response $response): Response {
    return $response;
});


$app->get("$prefix/bavlnka/ranking", function (Request $request, Response $response) use ($pdo, $config) {
    $response->getBody()->write(json_encode(bavlnkaRanking($pdo, $config), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    return $response;
})->setName('bavlnka-ranking');

$app->get("$prefix/bavlnka/ranking.csv", function (Request $request, Response $response) use ($pdo, $config) {
    $response->getBody()->write(bavlnkaRankingCsv(bavlnkaRanking($pdo, $config)));
    return $response->withHeader('Content-Type', 'text/csv; charset=utf-8');
})->setName('bavlnka-ranking-csv');

$bavlnkaUpdate = function (Request $request, Response $response) use ($pdo, $config) {
    @set_time_limit(90);
    $response->getBody()->write(json_encode(bavlnkaUpdate($pdo, $config), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    return $response;
};
$app->get("$prefix/bavlnka/update", $bavlnkaUpdate)->setName('bavlnka-update');
$app->post("$prefix/bavlnka/update", $bavlnkaUpdate)->setName('bavlnka-update');

$app->get("$prefix/bavlnka/admin", function (Request $request, Response $response) use ($pdo, $config) {
    $response->getBody()->write(json_encode(bavlnkaAdminData($pdo, $config), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    return $response;
});

$app->post("$prefix/bavlnka/tournaments", function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();
    try {
        bavlnkaAddTournament($pdo, (string) ($data['url'] ?? ''), $data['date'] ?? null);
    } catch (InvalidArgumentException $e) {
        $response->getBody()->write(json_encode(['message' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
        return $response->withStatus(400);
    }
    return $response->withStatus(201);
});

$app->put("$prefix/bavlnka/tournaments/{id}", function (Request $request, Response $response, array $args) use ($pdo) {
    $data = $request->getParsedBody();
    try {
        bavlnkaSetTournamentStatus($pdo, (int) $args['id'], (string) ($data['status'] ?? ''));
    } catch (InvalidArgumentException $e) {
        $response->getBody()->write(json_encode(['message' => $e->getMessage()], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
        return $response->withStatus(400);
    }
    return $response->withStatus(204);
});

$app->put("$prefix/bavlnka/aliases", function (Request $request, Response $response) use ($pdo) {
    $data = $request->getParsedBody();
    bavlnkaSetAliases($pdo, (array) ($data['aliases'] ?? []));
    return $response->withStatus(204);
});

$app->options("$prefix/bavlnka/{path:.*}", function (Request $request, Response $response): Response {
    return $response;
});


$app->get("$prefix/migrate", function (Request $request, Response $response, $args) use ($pdo) {

    try {
        // Prepare and execute the SQL query
        $query = "CREATE TABLE tournaments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) UNIQUE NOT NULL,
            data JSON
        );";
        $statement = $pdo->prepare($query);
        $statement->execute();


        $response->getBody()->write(json_encode([
            'message' =>  "success"
        ]));


    } catch (PDOException $e) {
        $response->getBody()->write(json_encode([
            'message' =>  $e->getMessage()
        ]));
        return $response->withStatus(500); // Internal Server Error status code
    }
    return $response;
});
$app->add($loggedInMiddleware);

$app->add(function ($request, $handler) {
    $requestHeaders = $request->getHeaderLine('Access-Control-Request-Headers');

    $response = $handler->handle($request);

    $response = $response->withHeader('Access-Control-Allow-Methods', "GET, POST, PUT, DELETE, PATCH, OPTIONS");
    $response = $response->withHeader('Access-Control-Allow-Headers', $requestHeaders);


    return $response->hasHeader('Content-Type') ? $response : $response->withHeader('Content-Type', 'application/json');
});

$app->add(
    function ($request, $handler) {

        $contentType = $request->getHeaderLine('Content-Type');

        if (strstr($contentType, 'application/json')) {
            $contents = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $request = $request->withParsedBody($contents);
            }
        }

        return $handler->handle($request);
    }
);


$app->add(function ($request, $handler) {
    $routeContext = RouteContext::fromRequest($request);
    $route = $routeContext->getRoute();

    if (empty($route)) {
        throw new HttpNotFoundException($request, $response);
    }

    $response = $handler->handle($request);

    $routeName = $route->getName();
    $method = $request->getMethod();

    $value = $method === 'GET' && $routeName === 'tournament' ? 540 : 0;

    return $response->withHeader('Cache-Control', "max-age=".$value);

});

$app->addRoutingMiddleware();



return $app;
