<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . '/../models/Cliente.php'; 
require_once __DIR__ . '/../db/ConexionPDO.php'; 

class ClienteController
{
    public function CargarUno(Request $request, Response $response, array $args)
    {
        $data = $request->getParsedBody();

        if (empty($data['id_comanda']) || empty($data['nombre']) || empty($data['hora_llegada']) || empty($data['estado'])) {
            $response->getBody()->write(json_encode(array('error' => 'Todos los campos son obligatorios')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $nuevoCliente = new Cliente();
        $nuevoCliente->id_comanda = $data['id_comanda'];
        $nuevoCliente->nombre = $data['nombre'];
        $nuevoCliente->hora_llegada = $data['hora_llegada'];
        $nuevoCliente->estado = $data['estado'];

        try {
            $idNuevoCliente = $nuevoCliente->InsertarCliente();

            $result = array('mensaje' => 'Cliente insertado correctamente', 'idCliente' => $idNuevoCliente);

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array('error' => 'No se pudo insertar el cliente')));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function TraerTodos(Request $request, Response $response, array $args)
    {
        $clientes = Cliente::TraerTodosLosClientes();

        $response->getBody()->write(json_encode($clientes));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function TraerUno(Request $request, Response $response, array $args)
    {
        $queryParams = $request->getQueryParams();
        $idCliente = isset($queryParams['idCliente']) ? $queryParams['idCliente'] : null;

        if ($idCliente === null) {
            $response->getBody()->write(json_encode(array('error' => 'Se debe proporcionar el parámetro "idCliente"')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $cliente = Cliente::TraerUnCliente($idCliente);

        if (!$cliente) {
            $response->getBody()->write(json_encode(array('error' => 'Cliente no encontrado')));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $response->getBody()->write(json_encode($cliente));
        return $response->withHeader('Content-Type', 'application/json');
    }

    public function ModificarUno(Request $request, Response $response, array $args)
    {
        $data = $request->getParsedBody();

        if (empty($data['idCliente']) || empty($data['id_comanda']) || empty($data['nombre']) || empty($data['hora_llegada']) || empty($data['estado'])) {
            $response->getBody()->write(json_encode(array('error' => 'Todos los campos son obligatorios')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $cliente = new Cliente();
        $cliente->idCliente = $data['idCliente'];
        $cliente->id_comanda = $data['id_comanda'];
        $cliente->nombre = $data['nombre'];
        $cliente->hora_llegada = $data['hora_llegada'];
        $cliente->estado = $data['estado'];

        try {
            $idModificado = $cliente->ModificarClienteParametros();

            if ($idModificado === false) {
                $response->getBody()->write(json_encode(array('error' => 'No se pudo modificar el cliente')));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $result = array('mensaje' => 'Cliente modificado correctamente', 'idCliente' => $idModificado);

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array('error' => 'No se pudo modificar el cliente')));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function BorrarUno(Request $request, Response $response, array $args)
    {
        $data = $request->getParsedBody();
        $idCliente = isset($data['idCliente']) ? $data['idCliente'] : null;

        if ($idCliente === null && isset($args['idCliente'])) {
            $idCliente = $args['idCliente'];
        }

        $cliente = Cliente::TraerUnCliente($idCliente);

        if (!$cliente) {
            $response->getBody()->write(json_encode(array('error' => 'Cliente no encontrado')));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        $filasEliminadas = $cliente->EliminarCliente();

        if ($filasEliminadas === 0) {
            $response->getBody()->write(json_encode(array('error' => 'No se pudo eliminar el cliente')));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }

        $result = array('mensaje' => 'Cliente eliminado correctamente');

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
}
?>