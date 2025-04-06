<?php
use Illuminate\Http\Request;
use Illuminate\Routing\Router;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Http\JsonResponse;
use Illuminate\Container\Container;

function RouterConfig()
{
   $container = new Container();

   $router = new Router(new \Illuminate\Events\Dispatcher(), $container);

   $routes = require __DIR__ . '/../routes/API.php';
   $routes($router);

   // Xử lý request
   $request = Request::createFromGlobals();
   $container->instance('Illuminate\Http\Request', $request);

   // Thêm logic xử lý CORS
   $allowedOrigins = $envConfig['APP']['ALLOWS_CORS'] ?? '*'; // Mặc định là '*' nếu không có cấu hình
   $origin = $request->headers->get('Origin');

   if ($origin && fnmatch($allowedOrigins, $origin, FNM_CASEFOLD)) {
      header("Access-Control-Allow-Origin: $origin");
      // Thêm đầy đủ các method RESTful
      header("Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS");
      header("Access-Control-Allow-Headers: Content-Type, Authorization");
      header("Access-Control-Allow-Credentials: true");
      header("Access-Control-Max-Age: 86400"); // Cache preflight request trong 24h
   }

   // Xử lý preflight request (OPTIONS)
   if ($request->isMethod('OPTIONS')) {
      return (new JsonResponse(null, 200))->send();
   }

   // Xử lý request chính
   try {
      $response = $router->dispatch($request);
   } catch (NotFoundHttpException $e) {
      $response = new JsonResponse([
         'message' => 'Route not found',
         'status' => 404
      ], 404);
   }

   // Gửi response
   $response->send();
}

RouterConfig();