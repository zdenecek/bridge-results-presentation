<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteContext;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\HttpNotFoundException;

$pdo = include(__DIR__ . '/db.php');
$app = AppFactory::create();
$config = include(__DIR__ . '/config.php');

$apikey = $config['key'];


$prefix = "/api";

$loggedInMiddleware = function ($request, $handler) use ($apikey): ResponseInterface {
    $routeContext = RouteContext::fromRequest($request);
    $route = $routeContext->getRoute();

    if (empty($route)) {
        throw new HttpNotFoundException($request, $response);
    }

    $routeName = $route->getName();
    $key = $request->getHeader('Apikey');

    $method =  $request->getMethod();

    // Define routes that user does not have to be logged in with. All other routes, the user needs to be logged in with.
    // Names for routes must be defined in routes.php with ->setName() for each one.
    $publicRoutesArray = array('tournament', 'tournaments', 'home');

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
})->setName('tournament');



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


    return $response->withHeader('Content-Type', 'application/json');
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
