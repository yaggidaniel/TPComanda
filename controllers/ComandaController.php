<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . '/../models/Comanda.php'; 
require_once __DIR__ . '/../db/ConexionPDO.php'; 

class ComandaController
{
    public function CargarUno(Request $request, Response $response, array $args)
    {
        $data = $request->getParsedBody();

        if (empty($data['id_mesa']) || empty($data['idUsuario']) || empty($data['codigo']) || empty($data['id_estado']) || empty($data['tiempoEspera']) || empty($data['totalAPagar'])) {
            $response->getBody()->write(json_encode(array('error' => 'Todos los campos son obligatorios')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $nuevaComanda = new Comanda();
        $nuevaComanda->id_mesa = $data['id_mesa'];
        $nuevaComanda->idUsuario = $data['idUsuario'];
        $nuevaComanda->codigo = $data['codigo'];
        $nuevaComanda->id_estado = $data['id_estado'];
        $nuevaComanda->tiempoEspera = $data['tiempoEspera'];
        $nuevaComanda->totalAPagar = $data['totalAPagar'];

        try {
            $idNuevaComanda = $nuevaComanda->InsertarPedido();

            $result = array('mensaje' => 'Comanda insertada correctamente', 'idComanda' => $idNuevaComanda);

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array('error' => 'No se pudo insertar la comanda')));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function TraerTodos(Request $request, Response $response, array $args)
    {
        $comandas = Comanda::TraerTodosLosPedidos();

        $response->getBody()->write(json_encode($comandas));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno(Request $request, Response $response, array $args)
    {
        $queryParams = $request->getQueryParams();
        $idComanda = isset($queryParams['idComanda']) ? $queryParams['idComanda'] : null;

        if ($idComanda === null) {
            $response->getBody()->write(json_encode(array('error' => 'Se debe proporcionar el parámetro "idComanda"')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $comanda = Comanda::TraerUnPedido($idComanda);

        if (!$comanda) {
            $response->getBody()->write(json_encode(array('error' => 'Comanda no encontrada')));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($comanda));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno(Request $request, Response $response, array $args)
    {
        $data = $request->getParsedBody();

        if (empty($data['id']) || empty($data['id_mesa']) || empty($data['idUsuario']) || empty($data['codigo']) || empty($data['id_estado']) || empty($data['tiempoEspera']) || empty($data['totalAPagar'])) {
            $response->getBody()->write(json_encode(array('error' => 'Todos los campos son obligatorios')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $comanda = new Comanda();
        $comanda->id = $data['id'];
        $comanda->id_mesa = $data['id_mesa'];
        $comanda->idUsuario = $data['idUsuario'];
        $comanda->codigo = $data['codigo'];
        $comanda->id_estado = $data['id_estado'];
        $comanda->tiempoEspera = $data['tiempoEspera'];
        $comanda->totalAPagar = $data['totalAPagar'];

        try {
            $idModificada = $comanda->ModificarPedidoParametros();

            if ($idModificada === false) {
                $response->getBody()->write(json_encode(array('error' => 'No se pudo modificar la comanda')));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $result = array('mensaje' => 'Comanda modificada correctamente', 'idComanda' => $idModificada);

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array('error' => 'No se pudo modificar la comanda')));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function BorrarUno(Request $request, Response $response, array $args)
    {
        $data = $request->getParsedBody();
        $idComanda = isset($data['idComanda']) ? $data['idComanda'] : null;

        if ($idComanda === null && isset($args['idComanda'])) {
            $idComanda = $args['idComanda'];
        }

        $comanda = Comanda::TraerUnPedido($idComanda);

        if (!$comanda) {
            $response->getBody()->write(json_encode(array('error' => 'Comanda no encontrada')));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $filasEliminadas = $comanda->BorrarPedido();

        if ($filasEliminadas === 0) {
            $response->getBody()->write(json_encode(array('error' => 'No se pudo eliminar la comanda')));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $result = array('mensaje' => 'Comanda eliminada correctamente');

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }

    
}

?>