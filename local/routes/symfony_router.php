<?php use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Routing\Exception\MethodNotAllowedException;
use Symfony\Component\Routing\Loader\YamlFileLoader;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;

require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include.php';
/** пока что заинициализируем все ядро */

// look inside *this* directory
$locator = new FileLocator([__DIR__]);
$loader = new YamlFileLoader($locator);

// подгружаем роутинг
$routes = $loader->load('routes.yml');

$request = Request::createFromGlobals();
// Init RequestContext object
$context = new RequestContext();
$context->fromRequest($request);

// Init UrlMatcher object
$matcher = new UrlMatcher($routes, $context);

// Find the current route
try {
    $pathinfo = $request->getPathInfo();
    $attributes = $matcher->match($pathinfo);
    $controller = $attributes['_controller'];
    unset($attributes['_controller']);
    $response = call_user_func_array($controller, $attributes);
} catch (ResourceNotFoundException $e) {
    $response = new Response('Not found!', Response::HTTP_NOT_FOUND);
}
catch (MethodNotAllowedException $e) {
    $response = new Response('Not allowed!', Response::HTTP_METHOD_NOT_ALLOWED);
}
$response->send();