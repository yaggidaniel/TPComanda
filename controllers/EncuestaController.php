<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . '/../models/Encuesta.php';

class EncuestaController
{
    public static function altaEncuestaController(Request $request, Response $response, $args)
    {
        $params = $request->getParsedBody();

        if (!isset($params['idMesa']) ||
            !isset($params['idPedido']) ||
            !isset($params['puntajeMesa']) ||
            !isset($params['puntajeRestaurante']) ||
            !isset($params['puntajeMozo']) ||
            !isset($params['puntajeCocinero']) ||
            !isset($params['experiencia'])) {
            
            $payload = json_encode(array("error" => "Todos los campos son obligatorios"));
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400); 
        }

        $idMesa = $params['idMesa'];
        $idPedido = $params['idPedido'];
        $puntajeMesa = $params['puntajeMesa'];
        $puntajeRestaurante = $params['puntajeRestaurante'];
        $puntajeMozo = $params['puntajeMozo'];
        $puntajeCocinero = $params['puntajeCocinero'];
        $experiencia = $params['experiencia'];

        try {
            Encuesta::altaEncuesta($idMesa, $idPedido, $puntajeMesa, $puntajeRestaurante, $puntajeMozo, $puntajeCocinero, $experiencia);

            $payload = json_encode(array("mensaje" => "Encuesta creada con éxito"));
            $response->getBody()->write($payload);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(201); 
        } catch (Exception $e) {
            $payload = json_encode(array("error" => $e->getMessage()));
            $response->getBody()->write($payload);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500); 
        }
    }


    public static function obtenerTodasLasEncuestas(Request $request, Response $response, $args)
    {
        try {
            $encuestas = Encuesta::obtenerTodasLasEncuestas();
            
            $payload = json_encode($encuestas);
            $response->getBody()->write($payload);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (Exception $e) {
            $payload = json_encode(array("error" => $e->getMessage()));
            $response->getBody()->write($payload);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    public static function obtenerComentariosEncuestas(Request $request, Response $response, $args)
    {
        try {
            $comentarios = Encuesta::obtenerComentariosEncuestas();
            
            $payload = json_encode($comentarios);
            $response->getBody()->write($payload);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
        } catch (Exception $e) {
            $payload = json_encode(array("error" => $e->getMessage()));
            $response->getBody()->write($payload);

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }
    
}
?>