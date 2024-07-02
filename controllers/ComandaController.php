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

        if (empty($data['id_mesa']) || empty($data['idUsuario']) || empty($data['id_estado'])) {
            $response->getBody()->write(json_encode(array('error' => 'Todos los campos son obligatorios')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $nuevaComanda = new Comanda();
        $nuevaComanda->id_mesa = $data['id_mesa'];
        $nuevaComanda->idUsuario = $data['idUsuario'];
        $nuevaComanda->id_estado = $data['id_estado'];
        $nuevaComanda->tiempoEspera = $data['tiempoEspera'];
        $nuevaComanda->totalAPagar = $data['totalAPagar'];

        try {
            $idNuevaComanda = $nuevaComanda->AltaComanda();

            $result = array('mensaje' => 'Comanda insertada correctamente', 'idComanda' => $idNuevaComanda);

            $response->getBody()->write(json_encode($result));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array('error' => 'No se pudo insertar la comanda en CARGARUNO')));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function AgregarProductoAComanda(Request $request, Response $response, array $args)
    {
        $data = $request->getParsedBody();

        if (empty($data['idComanda']) || empty($data['idProducto'])) {
            $response->getBody()->write(json_encode(array('error' => 'Todos los campos son obligatorios')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $resultado = Comanda::insertarProductoEnComanda($data['idComanda'], $data['idProducto']);

            $response->getBody()->write(json_encode($resultado));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(array('error' => $e->getMessage())));
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


    public function TraerPedidosListosParaServir(Request $request, Response $response, $args)
    {
        try {
            $pedidosListos = Comanda::TraerPedidosListosParaServir();
            
            $payload = json_encode($pedidosListos, JSON_PRETTY_PRINT);

            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
            
        } catch (Exception $e) {
            $payload = json_encode(['error' => $e->getMessage()]);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }


    public function cobrarComanda($request, $response, $args)
    {
        try {
            $data = $request->getParsedBody();

            $idPedido = $data['idComanda'];
            $resultado = Comanda::cobrarComanda($idPedido);

            $payload = json_encode($resultado);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(200);
    
        } catch (Exception $e) {
            $payload = json_encode(['error' => $e->getMessage()]);
            $response->getBody()->write($payload);
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(500);
        }
    }

    public static function descargarComandasCSVController(Request $request, Response $response, $args)
    {
        try {
            $csvData = Comanda::descargarComandasCSV();

            $response = $response->withHeader('Content-Type', 'application/csv')
                                 ->withHeader('Content-Disposition', 'attachment; filename="comandas.csv"');
            $response->getBody()->write($csvData);
            return $response;
        } catch (Exception $e) {
            $payload = json_encode(array("error" => $e->getMessage()));
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
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

    public function ObtenerUnPedidoConProductos(Request $request, Response $response, array $args)
    {
        $queryParams = $request->getQueryParams();
        $idComanda = isset($queryParams['idComanda']) ? $queryParams['idComanda'] : null;
        if ($idComanda === null) {
            $result = array('error' => 'Se debe proporcionar el parámetro "idComanda"');
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            $pedidoConProductos = Comanda::ObtenerUnPedidoConProductos($idComanda);
            $response->getBody()->write(json_encode($pedidoConProductos));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (Exception $e) {
            $result = array('error' => $e->getMessage());
            $response->getBody()->write(json_encode($result));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }



    public function relacionarFotoEnComanda(Request $request, Response $response, array $args) {
        $data = $request->getParsedBody();
        $uploadedFiles = $request->getUploadedFiles();
        
        // Verifica si el campo idDeComanda está presente en el cuerpo de la solicitud
        if (empty($data['idDeComanda'])) {
            $response->getBody()->write(json_encode(array('error' => 'El campo idDeComanda es obligatorio')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    
        // Verifica si el archivo de imagen está presente en los archivos subidos
        if (!isset($uploadedFiles['imagen'])) {
            $response->getBody()->write(json_encode(array('error' => 'El campo imagen es obligatorio')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    
        // Obtén el archivo de imagen
        $uploadedFile = $request->getUploadedFiles()['imagen'];
    
        $idDeComanda = $data['idDeComanda'];
    
        try {
            $resultado = Comanda::relacionarFotoEnComanda($idDeComanda, $uploadedFile);
    
            if (!empty($resultado['mensaje'])) {
                $response->getBody()->write(json_encode([
                    'mensaje' => $resultado['mensaje'],
                    'imagen' => $resultado['imagen']
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            } else {
                $response->getBody()->write(json_encode(['error' => $resultado['error']]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
    
    public function listarPedidosPorSector(Request $request, Response $response, array $args) {
        $data = $request->getParsedBody();
        $sector = $data['sector'] ?? ''; // Obtener el sector desde los datos recibidos
        
        try {
            $productos = Comanda::obtenerProductosPorSector($sector);
            
            $response->getBody()->write(json_encode([
                'Pedidos' => $productos
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
            
        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => $e->getMessage()]));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
    
    
    public function obtenerProductosPorEstadoPorSector(Request $request, Response $response, array $args) {
        try {
            $data = $request->getParsedBody();

            $sector = $data['sector'];
            $estado = $data['estado'];

            // Obtener productos según el estado y sector
            $productos = Comanda::obtenerProductosPorEstadoPorSector($sector, $estado);

            // Preparar la respuesta JSON
            $payload = json_encode($productos);

            // Establecer cabeceras de respuesta
            $response->getBody()->write($payload);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (Exception $e) {
            $error = ['error' => $e->getMessage()];
            $response->getBody()->write(json_encode($error));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    }


    public static function setEstadoYTiempoPreparacion(Request $request, Response $response, $args)
    {
        try {
            $body = $request->getParsedBody();
            
            $idPedidoProducto = $body['idPedidoProducto'];
            $tiempoPreparacion = isset($body['tiempoPreparacion']) ? $body['tiempoPreparacion'] : null;
            $estado = $body['estado'];
    
            $producto = Comanda::obtenerProductoPorIdPedidoProducto($idPedidoProducto);
    
            $objetoAccesoDato = ConexionPDO::obtenerInstancia();
    
            if ($tiempoPreparacion !== null) {
                $consulta = $objetoAccesoDato->prepararConsulta("UPDATE pedidos_productos SET estado = :estado, tiempoPreparacion = :tiempoPreparacion WHERE idPedidoProducto = :idPedidoProducto");
                $consulta->bindValue(':tiempoPreparacion', $tiempoPreparacion, PDO::PARAM_INT);
            } else {
                $consulta = $objetoAccesoDato->prepararConsulta("UPDATE pedidos_productos SET estado = :estado WHERE idPedidoProducto = :idPedidoProducto");
            }
    
            $consulta->bindValue(':estado', $estado, PDO::PARAM_STR);
            $consulta->bindValue(':idPedidoProducto', $idPedidoProducto, PDO::PARAM_INT);
    
            $consulta->execute();
    
            if ($consulta->rowCount() == 0) {
                throw new Exception("No se ha modificado ningún valor");
            }
    
            $producto['estado'] = $estado;
            if ($tiempoPreparacion !== null) {
                $producto['tiempoPreparacion'] = $tiempoPreparacion;
            }
    
            $responseBody = json_encode([
                'success' => true,
                'producto' => $producto
            ]);
    
            $response->getBody()->write($responseBody);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    
        } catch (Exception $e) {
            $errorResponse = json_encode(['error' => $e->getMessage()]);
            $response->getBody()->write($errorResponse);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    

    public function obtenerTiempoEspera(Request $request, Response $response, $args) {
        try {
            $body = $request->getParsedBody();

            $idPedido = $body['idPedido'];

            $tiempoEspera = Comanda::calcularTiempoEspera($idPedido);

            $responseBody = json_encode([
                'success' => true,
                'idPedido' => $idPedido,
                'tiempoEspera' => $tiempoEspera . ' minutos'
            ]);

            $response->getBody()->write($responseBody);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (Exception $e) {
            $errorResponse = json_encode(['error' => $e->getMessage()]);
            $response->getBody()->write($errorResponse);
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function obtenerPedidosPorTiempoController(Request $request, Response $response, $args)
    {
        $params = $request->getQueryParams();
        $criterio = isset($params['criterio']) ? $params['criterio'] : '';

        try {
            @$pedidosFiltrados = Comanda::obtenerPedidosPorTiempo($criterio);

            $payload = json_encode($pedidosFiltrados);
            $response->getBody()->write($payload);

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (Exception $e) {
            $payload = json_encode(array("error" => $e->getMessage()));
            $response->getBody()->write($payload);

            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
    
}

?>