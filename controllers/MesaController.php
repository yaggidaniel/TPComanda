<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
require_once __DIR__ . '/../models/Mesa.php';
require_once __DIR__ . '/../db/ConexionPDO.php'; 

class MesaController
{
    public function CargarUno(Request $request, Response $response, array $args)
    {
        $data = $request->getParsedBody();

        if (empty($data['nombre']) || empty($data['estado'])) {
            $response->getBody()->write(json_encode(array('error' => 'Todos los campos son obligatorios')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $nuevoMesa = new Mesa();
        $nuevoMesa->nombre = $data['nombre'];
        $nuevoMesa->estado = $data['estado'];
        try {
            $idNuevoMesa = $nuevoMesa->InsertarMesa();

            $result = array('mensaje' => 'Mesa insertada correctamente', 'idMesa' => $idNuevoMesa);

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array('error' => 'No se pudo insertar la Mesa')));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function TraerTodos(Request $request, Response $response, array $args)
    {
        $Mesas = Mesa::TraerTodasLasMesas();

        $response->getBody()->write(json_encode($Mesas));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno(Request $request, Response $response, array $args)
    {
        $queryParams = $request->getQueryParams();
        $idMesa = isset($queryParams['idMesa']) ? $queryParams['idMesa'] : null;

        if ($idMesa === null) {
            $response->getBody()->write(json_encode(array('error' => 'Se debe proporcionar el parámetro "idMesa"')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $Mesa = Mesa::TraerUnaMesa($idMesa);

        if (!$Mesa) {
            $response->getBody()->write(json_encode(array('error' => 'Mesa no encontrada')));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($Mesa));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno(Request $request, Response $response, array $args)
    {
        $data = $request->getParsedBody();

        if (empty($data['idMesa']) || empty($data['nombre']) || empty($data['estado'])) {
            $response->getBody()->write(json_encode(array('error' => 'Todos los campos son obligatorios')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $Mesa = new Mesa();
        $Mesa->idMesa = $data['idMesa'];
        $Mesa->nombre = $data['nombre'];
        $Mesa->estado = $data['estado'];

        try {
            $idModificado = $Mesa->ModificarMesaParametros();

            if ($idModificado === false) {
                $response->getBody()->write(json_encode(array('error' => 'No se pudo modificar el Mesa')));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $result = array('mensaje' => 'Mesa modificada correctamente', 'idMesa' => $idModificado);

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array('error' => 'No se pudo modificar la Mesa')));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function BorrarUno(Request $request, Response $response, array $args)
    {
        $data = $request->getParsedBody();
        $idMesa = isset($data['idMesa']) ? $data['idMesa'] : null;

        if ($idMesa === null && isset($args['idMesa'])) {
            $idMesa = $args['idMesa'];
        }

        $Mesa = Mesa::TraerUnaMesa($idMesa);

        if (!$Mesa) {
            $response->getBody()->write(json_encode(array('error' => 'Mesa no encontrado')));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $filasEliminadas = $Mesa->EliminarMesa();

        if ($filasEliminadas === 0) {
            $response->getBody()->write(json_encode(array('error' => 'No se pudo eliminar el Mesa')));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $result = array('mensaje' => 'Mesa eliminado correctamente');

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public static function obtenerMesaMasUsadaController(Request $request, Response $response, $args)
    {
        try {
            $mesa = Mesa::obtenerMesaMasUsada();
            $payload = json_encode($mesa);
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