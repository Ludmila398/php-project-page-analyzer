<?php

require __DIR__ . '/../vendor/autoload.php';

use Carbon\Carbon;
use DI\Container;
use DiDom\Document;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use App\Connection;
use App\PgsqlActions;
use Slim\Factory\AppFactory;
use Slim\Flash\Messages;
use Slim\Middleware\MethodOverrideMiddleware;
use Valitron\Validator;

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new Messages();
});

$container->set('connection', function () {
    $pdo = Connection::get()->connect();
    return $pdo;
});

$app = AppFactory::createFromContainer($container);
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'main.phtml');
})->setName('/');

$app->get('/urls/{id}', function ($request, $response, $args) {
    $id = $args['id'];
    $messages = $messages = $this->get('flash')->getMessages();

    $dataBase = new PgsqlActions($this->get('connection'));
    $dataFromDB = $dataBase->query('SELECT * FROM urls WHERE id = :id', $args);
    $dataCheckUrl = $dataBase->query('SELECT * FROM urls_checks WHERE url_id = :id ORDER BY id DESC', $args);

    $params = [
                'id' => $dataFromDB[0]['id'],
                'name' => $dataFromDB[0]['name'],
                'created_at' => $dataFromDB[0]['created_at'],
                'flash' => $messages,
                'urls' => $dataCheckUrl
                ];
    return $this->get('renderer')->render($response, 'show.phtml', $params);
})->setName('urlsId');

$app->post('/urls', function ($request, $response) use ($router) {
    $urls = $request->getParsedBodyParam('url');
    $dataBase = new PgsqlActions($this->get('connection'));
    $error = [];

    if (strlen($urls['name']) < 1) {
        $error['name'] = 'URL не должен быть пустым';
    }

    $v = new Validator(array('name' => $urls['name'], 'count' => strlen((string) $urls['name'])));
    $v->rule('required', 'name')->rule('lengthMax', 'count.*', 255)->rule('url', 'name');

    if (!$v->validate() && isset($urls) && !isset($error['name'])) {
        $error['name'] = 'Некорректный URL';
    }

    if (count($error) === 0) {
        $parseUrl = parse_url($urls['name']);
        $urls['name'] = "{$parseUrl['scheme']}://{$parseUrl['host']}";

        $searchName = $dataBase->query('SELECT id FROM urls WHERE name = :name', $urls);

        if (count($searchName) !== 0) {
            $url = $router->urlFor('urlsId', ['id' => $searchName[0]['id']]);
            $this->get('flash')->addMessage('success', 'Страница уже существует');
            return $response->withRedirect($url);
        }
        $urls['time'] = Carbon::now();
        $insertedId = $dataBase->query('INSERT INTO urls(name, created_at) VALUES(:name, :time) RETURNING id', $urls);
        $id = $dataBase->query('SELECT MAX(id) FROM urls');
        $url = $router->urlFor('urlsId', ['id' => $id[0]['max']]);
        $this->get('flash')->addMessage('success', 'Страница успешно добавлена');
        return $response->withRedirect($url);
    } else {
        $params = ['errors' => $error];
        return $this->get('renderer')->render($response->withStatus(422), 'main.phtml', $params);
    }
});

$app->get('/urls', function ($request, $response) {
    $dataBase = new PgsqlActions($this->get('connection'));
    $dataFromUrls = $dataBase->query(
        'SELECT urls.id, urls.name FROM urls ORDER BY urls.id DESC'
    );
    $dataFromUrlsChecks = $dataBase->query(
        'SELECT url_id, MAX(created_at) AS created_at, status_code 
         FROM urls_checks 
         GROUP BY url_id, status_code'
    );
    $combinedData = array_map(function ($url) use ($dataFromUrlsChecks) {
        foreach ($dataFromUrlsChecks as $check) {
            if ($url['id'] === $check['url_id']) {
                $url['created_at'] = $check['created_at'];
                $url['status_code'] = $check['status_code'];
            }
        }
        return $url;
    }, $dataFromUrls);

    $params = ['data' => $combinedData];
    return $this->get('renderer')->render($response, 'list.phtml', $params);
})->setName('urls');

$app->post('/urls/{url_id}/checks', function ($request, $response, $args) use ($router) {
    $url_id = $args['url_id'];
    $pdo = Connection::get()->connect();
    $dataBase = new PgsqlActions($pdo);

    $checkUrl['url_id'] = $args['url_id'];
    $name = $dataBase->query('SELECT name FROM urls WHERE id = :url_id', $checkUrl);

    $client = new Client();
    try {
        $res = $client->request('GET', $name[0]['name']);
        $checkUrl['status'] = $res->getStatusCode();
    } catch (ConnectException $e) {
        $this->get('flash')->addMessage('failure', 'Произошла ошибка при проверке, не удалось подключиться');
        $url = $router->urlFor('urlsId', ['id' => $url_id]);
        return $response->withRedirect($url);
    } catch (ClientException $e) {
        if ($e->getResponse()->getStatusCode() != 200) {
            $checkUrl['status'] = $e->getResponse()->getStatusCode();
            $checkUrl['title'] = 'Доступ ограничен: проблема с IP';
            $checkUrl['h1'] = 'Доступ ограничен: проблема с IP';
            $checkUrl['meta'] = 'Доступ ограничен: проблема с IP';
            $checkUrl['time'] = Carbon::now();
            $dataBase->query('INSERT INTO urls_checks(url_id, status_code, title, h1, description, created_at) 
            VALUES(:url_id, :status, :title, :h1, :meta, :time)', $checkUrl);
            $this->get('flash')->addMessage('warning', 'Проверка была выполнена успешно, но сервер ответил с ошибкой');
            $url = $router->urlFor('urlsId', ['id' => $url_id]);
            return $response->withRedirect($url);
        }
    }

    $resultFromBody = $client->request('GET', $name[0]['name']);
    $htmlFromUrl = (string) $resultFromBody->getBody();
    $document = new Document($htmlFromUrl);

    $title = optional($document->first('title'));
    $h1 = optional($document->first('h1'));
    $meta = optional($document->first('meta[name="description"]'));

    $elements = [
        'title' => $title,
        'h1' => $h1
    ];

    foreach ($elements as $key => $element) {
        if ($element?->text()) {
            $checkUrl[$key] = mb_substr($element->text(), 0, 255);
        } else {
            $checkUrl[$key] = '';
        }
    }

    if ($meta?->getAttribute('content')) {
        $meta = mb_substr($meta->getAttribute('content'), 0, 255);
        $checkUrl['meta'] = $meta;
    } else {
        $checkUrl['meta'] = '';
    }

    $checkUrl['time'] = Carbon::now();

    if (isset($checkUrl['status'])) {
        try {
            $dataBase->query('INSERT INTO urls_checks(url_id, status_code, title, h1, description, created_at) 
            VALUES(:url_id, :status, :title, :h1, :meta, :time)', $checkUrl);
        } catch (\PDOException $e) {
            echo $e->getMessage();
        }
        $this->get('flash')->addMessage('success', 'Страница успешно проверена');
    }

    $url = $router->urlFor('urlsId', ['id' => $url_id]);
    return $response->withRedirect($url, 302);
});

$app->run();
