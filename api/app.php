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
require_once __DIR__ . '/prebor.php';
require_once __DIR__ . '/results.php';
require_once __DIR__ . '/results_import.php';
require_once __DIR__ . '/banner.php';
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
    // A scheduled task on the hosting can only call a plain URL, so the update takes its own key in the query.
    if (in_array($routeName, ['bavlnka-update', 'prebor-update'], true) && hash_equals(bavlnkaCronKey($apikey), (string) ($request->getQueryParams()['key'] ?? ''))) {
        return $handler->handle($request);
    }

    $method =  $request->getMethod();

    // Define routes that user does not have to be logged in with. All other routes, the user needs to be logged in with.
    // Names for routes must be defined in routes.php with ->setName() for each one.
    $publicRoutesArray = array('tournament', 'tournaments', 'home', 'bavlnka-ranking', 'bavlnka-ranking-csv', 'prebor-competitions', 'prebor-ranking', 'prebor-ranking-csv', 'results-seasons', 'results-season', 'banner');

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

// Lets the admin check the password once instead of on the first save.
$app->get("$prefix/auth", function (Request $request, Response $response) {
    return $response->withStatus(204);
})->setName('auth');

$app->options("$prefix/auth", function (Request $request, Response $response): Response {
    return $response;
});

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

// The daily scheduled task: it also refreshes running competitions on the results page, which have
// nothing else to update them and only one free task slot left on the hosting.
$bavlnkaUpdate = function (Request $request, Response $response) use ($pdo, $config) {
    @set_time_limit(90);
    $results = resultsRefreshRunning($pdo);
    $response->getBody()->write(json_encode(bavlnkaUpdate($pdo, $config) + ['results' => $results], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    return $response;
};
$app->get("$prefix/bavlnka/update", $bavlnkaUpdate)->setName('bavlnka-update');
$app->post("$prefix/bavlnka/update", $bavlnkaUpdate)->setName('bavlnka-update');

$app->get("$prefix/bavlnka/admin", function (Request $request, Response $response) use ($pdo, $config) {
    $data = bavlnkaAdminData($pdo, $config) + ['cronKey' => bavlnkaCronKey($config['key'])];
    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
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


$preborJson = function (Response $response, $data, int $status = 200): Response {
    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    return $response->withStatus($status);
};
$preborError = fn(Response $response, Throwable $e, int $status = 400) => $preborJson($response, ['message' => $e->getMessage()], $status);
$slug = '{slug:[a-z0-9-]+}';

$app->get("$prefix/prebor", function (Request $request, Response $response) use ($pdo, $preborJson) {
    $fields = ['slug', 'season', 'title', 'unit', 'best', 'rounds', 'played'];
    return $preborJson($response, array_map(fn($c) => array_intersect_key($c, array_flip($fields)), preborCompetitions($pdo)));
})->setName('prebor-competitions');

$app->get("$prefix/prebor/$slug/ranking", function (Request $request, Response $response, array $args) use ($pdo, $preborJson, $preborError) {
    try {
        return $preborJson($response, preborRanking($pdo, $args['slug']));
    } catch (InvalidArgumentException $e) {
        return $preborError($response, $e, 404);
    }
})->setName('prebor-ranking');

$app->get("$prefix/prebor/$slug/ranking.csv", function (Request $request, Response $response, array $args) use ($pdo, $preborError) {
    try {
        $response->getBody()->write(preborRankingCsv(preborRanking($pdo, $args['slug'])));
    } catch (InvalidArgumentException $e) {
        return $preborError($response, $e, 404);
    }
    return $response->withHeader('Content-Type', 'text/csv; charset=utf-8');
})->setName('prebor-ranking-csv');

$preborUpdate = function (Request $request, Response $response) use ($pdo, $preborJson) {
    @set_time_limit(90);
    return $preborJson($response, preborUpdate($pdo));
};
$app->get("$prefix/prebor/update", $preborUpdate)->setName('prebor-update');
$app->post("$prefix/prebor/update", $preborUpdate)->setName('prebor-update');

$app->get("$prefix/prebor/admin", function (Request $request, Response $response) use ($pdo, $config, $preborJson) {
    return $preborJson($response, ['competitions' => preborAdminData($pdo), 'cronKey' => bavlnkaCronKey($config['key'])]);
});

$app->post("$prefix/prebor/competitions", function (Request $request, Response $response) use ($pdo, $preborJson, $preborError) {
    try {
        return $preborJson($response, preborSaveCompetition($pdo, (array) $request->getParsedBody()), 201);
    } catch (InvalidArgumentException $e) {
        return $preborError($response, $e);
    }
});

$app->put("$prefix/prebor/competitions/{id:[0-9]+}", function (Request $request, Response $response, array $args) use ($pdo, $preborJson, $preborError) {
    try {
        return $preborJson($response, preborSaveCompetition($pdo, (array) $request->getParsedBody(), (int) $args['id']));
    } catch (InvalidArgumentException $e) {
        return $preborError($response, $e);
    }
});

$app->post("$prefix/prebor/$slug/rounds", function (Request $request, Response $response, array $args) use ($pdo, $preborJson, $preborError) {
    try {
        return $preborJson($response, preborAddRound($pdo, $args['slug'], (array) $request->getParsedBody()), 201);
    } catch (InvalidArgumentException $e) {
        return $preborError($response, $e);
    }
});

$app->put("$prefix/prebor/rounds/{id:[0-9]+}", function (Request $request, Response $response, array $args) use ($pdo, $preborError) {
    $data = (array) $request->getParsedBody();
    try {
        preborRoundAction($pdo, (int) $args['id'], (string) ($data['action'] ?? ''));
    } catch (InvalidArgumentException $e) {
        return $preborError($response, $e);
    }
    return $response->withStatus(204);
});

$app->post("$prefix/prebor/$slug/entries/import", function (Request $request, Response $response, array $args) use ($pdo, $preborJson, $preborError) {
    try {
        return $preborJson($response, ['entries' => preborImportRoster($pdo, $args['slug'])]);
    } catch (InvalidArgumentException | RuntimeException $e) {
        return $preborError($response, $e);
    }
});

$app->put("$prefix/prebor/rounds/{id:[0-9]+}/assignments", function (Request $request, Response $response, array $args) use ($pdo) {
    $data = (array) $request->getParsedBody();
    $entry = $data['entry'] ?? null;
    preborSetAssignment($pdo, (int) $args['id'], (string) ($data['playersKey'] ?? ''), $entry === null || $entry === '' ? null : (int) $entry);
    return $response->withStatus(204);
});

$app->options("$prefix/prebor/{path:.*}", function (Request $request, Response $response): Response {
    return $response;
});


$resultsJson = function (Response $response, $data, int $status = 200): Response {
    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
    return $response->withStatus($status);
};
// Every write answers with the season's page as the admin sees it, so the admin never reloads separately.
$resultsWrite = function (Response $response, callable $action, ?string $season) use ($pdo, $resultsJson): Response {
    try {
        $result = $action();
        return $resultsJson($response, ['result' => $result] + ($season ? ['page' => resultsPage($pdo, $season, true)] : []));
    } catch (InvalidArgumentException $e) {
        return $resultsJson($response, ['message' => $e->getMessage()], 400);
    }
};
$season = '{season:[0-9]{4}-[0-9]{2}}';
$sectionSeason = fn(int $id) => resultsSection($pdo, $id)['season'];
$eventSeason = function (int $id) use ($pdo): string {
    $select = $pdo->prepare("SELECT s.season FROM results_events e JOIN results_sections s ON s.id = e.section_id WHERE e.id = ?");
    $select->execute([$id]);
    return (string) $select->fetchColumn();
};

$app->get("$prefix/results/seasons", function (Request $request, Response $response) use ($pdo, $resultsJson) {
    return $resultsJson($response, resultsSeasons($pdo));
})->setName('results-seasons');

$app->get("$prefix/results/$season", function (Request $request, Response $response, array $args) use ($pdo, $resultsJson) {
    return $resultsJson($response, resultsPage($pdo, $args['season']));
})->setName('results-season');

$app->get("$prefix/results/$season/admin", function (Request $request, Response $response, array $args) use ($pdo, $resultsJson) {
    return $resultsJson($response, resultsPage($pdo, $args['season'], true));
});

$app->post("$prefix/results/$season/copy", function (Request $request, Response $response, array $args) use ($pdo, $resultsWrite) {
    $from = (string) (((array) $request->getParsedBody())['from'] ?? '');
    return $resultsWrite($response, fn() => resultsCopySeason($pdo, $from, $args['season']), $args['season']);
});

$app->post("$prefix/results/$season/import", function (Request $request, Response $response, array $args) use ($pdo, $resultsWrite) {
    @set_time_limit(90);
    $data = (array) $request->getParsedBody();
    $url = (string) ($data['url'] ?? '');
    return $resultsWrite($response, fn() => resultsImport($pdo, $args['season'], $url, null, 45, !empty($data['replace'])), $args['season']);
});

$app->post("$prefix/results/sections", function (Request $request, Response $response) use ($pdo, $resultsWrite) {
    $data = (array) $request->getParsedBody();
    return $resultsWrite($response, fn() => resultsSaveSection($pdo, $data), (string) ($data['season'] ?? '') ?: null);
});

$app->put("$prefix/results/sections/{id:[0-9]+}", function (Request $request, Response $response, array $args) use ($pdo, $resultsWrite) {
    $data = (array) $request->getParsedBody();
    return $resultsWrite($response, fn() => resultsSaveSection($pdo, $data, (int) $args['id']), (string) ($data['season'] ?? '') ?: null);
});

$app->delete("$prefix/results/sections/{id:[0-9]+}", function (Request $request, Response $response, array $args) use ($pdo, $resultsWrite, $sectionSeason) {
    try {
        $season = $sectionSeason((int) $args['id']);
    } catch (InvalidArgumentException $e) {
        $season = null;
    }
    return $resultsWrite($response, fn() => resultsDeleteSection($pdo, (int) $args['id']), $season);
});

$app->post("$prefix/results/sections/{id:[0-9]+}/move", function (Request $request, Response $response, array $args) use ($pdo, $resultsWrite, $sectionSeason) {
    $direction = (int) (((array) $request->getParsedBody())['direction'] ?? 0);
    return $resultsWrite($response, function () use ($pdo, $args, $direction) {
        resultsMoveSection($pdo, (int) $args['id'], $direction);
    }, $sectionSeason((int) $args['id']));
});

$app->post("$prefix/results/sections/{id:[0-9]+}/events", function (Request $request, Response $response, array $args) use ($pdo, $resultsWrite, $sectionSeason) {
    $data = (array) $request->getParsedBody();
    return $resultsWrite($response, fn() => resultsAddEvent($pdo, (int) $args['id'], $data), $sectionSeason((int) $args['id']));
});

$app->put("$prefix/results/events/{id:[0-9]+}", function (Request $request, Response $response, array $args) use ($pdo, $resultsWrite, $eventSeason) {
    $data = (array) $request->getParsedBody();
    return $resultsWrite($response, fn() => resultsUpdateEvent($pdo, (int) $args['id'], $data), $eventSeason((int) $args['id']) ?: null);
});

$app->post("$prefix/results/events/{id:[0-9]+}/refetch", function (Request $request, Response $response, array $args) use ($pdo, $resultsWrite, $eventSeason) {
    return $resultsWrite($response, fn() => resultsRefetchEvent($pdo, (int) $args['id']), $eventSeason((int) $args['id']) ?: null);
});

$app->delete("$prefix/results/events/{id:[0-9]+}", function (Request $request, Response $response, array $args) use ($pdo, $resultsWrite, $eventSeason) {
    $season = $eventSeason((int) $args['id']) ?: null;
    return $resultsWrite($response, fn() => resultsDeleteEvent($pdo, (int) $args['id']), $season);
});

$app->options("$prefix/results/{path:.*}", function (Request $request, Response $response): Response {
    return $response;
});


$app->get("$prefix/banner", function (Request $request, Response $response) use ($pdo, $resultsJson) {
    return $resultsJson($response, ['banner' => bannerPublic($pdo)])->withHeader('Cache-Control', 'max-age=300');
})->setName('banner');

$app->get("$prefix/banner/admin", function (Request $request, Response $response) use ($pdo, $resultsJson) {
    return $resultsJson($response, bannerGet($pdo));
});

$app->put("$prefix/banner", function (Request $request, Response $response) use ($pdo, $resultsJson) {
    try {
        return $resultsJson($response, bannerSave($pdo, (array) $request->getParsedBody()));
    } catch (InvalidArgumentException $e) {
        return $resultsJson($response, ['message' => $e->getMessage()], 400);
    }
});

$app->options("$prefix/banner[/{path:.*}]", function (Request $request, Response $response): Response {
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
