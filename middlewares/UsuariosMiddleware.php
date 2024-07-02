<?php

require_once __DIR__ . '/../middlewares/AuthJWT.php';

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

class UsuariosMiddleware
{
    private $minPeso;

    public function __construct($minPeso)
    {
        $this->minPeso = $minPeso;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        try {
            $token = $this->getTokenFromHeaders($request);
            $decodedToken = AuthJWT::verifyToken($token);
            $pesoUsuario = $decodedToken->data->peso ?? 0; // Acceder a las propiedades del objeto
    
            if ($pesoUsuario >= $this->minPeso) {
                // Si el peso del usuario es suficiente, continuar con el siguiente middleware o controlador
                return $handler->handle($request);
            } else {
                // Si el peso del usuario no es suficiente, devolver una respuesta de acceso denegado
                $response = new Response();
                $payload = json_encode(['error' => 'Acceso denegado. Nivel de acceso insuficiente']);
                $response->getBody()->write($payload);
                return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
            }
        } catch (Exception $e) {
            // Manejar cualquier excepción lanzada durante la verificación del token
            $response = new Response();
            $payload = json_encode(['error' => $e->getMessage()]);
            $response->getBody()->write($payload);
            return $response->withStatus(401)->withHeader('Content-Type', 'application/json');
        }
    }
    

    private function getTokenFromHeaders(Request $request): string
    {
        $headerValue = $request->getHeaderLine('Authorization');
        return trim(str_replace('Bearer', '', $headerValue));
    }
}
?>
