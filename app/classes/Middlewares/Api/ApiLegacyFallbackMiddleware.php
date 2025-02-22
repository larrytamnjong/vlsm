<?php

namespace App\Middlewares\Api;

use Exception;
use Throwable;
use Laminas\Diactoros\Response;
use App\Exceptions\SystemException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Exception\HttpNotFoundException;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;


class ApiLegacyFallbackMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {

        try {
            // Try to handle the request with Slim routing
            $response = $handler->handle($request);
        } catch (HttpNotFoundException $e) {


            // If the route is not found in Slim routing, fallback to legacy routes

            $uri = $request->getUri()->getPath();
            $uri = preg_replace('/([\/.])\1+/', '$1', $uri);
            $uri = trim(parse_url($uri, PHP_URL_PATH), "/");

            $GLOBALS['request'] = $request;

            try {
                ob_start();
                require_once APPLICATION_PATH . DIRECTORY_SEPARATOR . $uri;
                $output = ob_get_clean();

                // Create a new response object
                $response = new Response();

                // Set the output of the legacy PHP code as the response body
                $response->getBody()->write($output);
            } catch (SystemException | Exception $e) {
                ob_end_clean(); // Clean the buffer in case of an error
                throw new SystemException("An error occurred while processing the request: " . $e->getMessage(), 500, $e);
            } catch (Throwable $e) {
                ob_end_clean(); // Clean the buffer in case of an error
                throw new SystemException("An error occurred while processing the request: " . $e->getMessage(), 500);
            }
        }

        return $response;
    }
}
