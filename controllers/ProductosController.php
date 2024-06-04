<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;


require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../interfaces/IApiUsable.php';

class ProductoController implements IApiUsable
{
    public function TraerUno($request, $response, $args)
    {
        $conexionPDO = ConexionPDO::obtenerInstancia();
        $queryParams = $request->getQueryParams();

        $idProducto = isset($queryParams['idProducto']) ? $queryParams['idProducto'] : null;
        $nombreProducto = isset($queryParams['nombreProducto']) ? $queryParams['nombreProducto'] : null;

        if ($idProducto === null && $nombreProducto === null) {
            $response->getBody()->write(json_encode(array('error' => 'Se debe proporcionar al menos un parámetro: "idProducto" o "nombreProducto"')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            if ($idProducto !== null) {
                $consulta = $conexionPDO->prepararConsulta("SELECT * FROM productos WHERE idProducto = :idProducto");
                $consulta->bindValue(':idProducto', $idProducto, PDO::PARAM_INT);
            } elseif ($nombreProducto !== null) {
                $consulta = $conexionPDO->prepararConsulta("SELECT * FROM productos WHERE nombreProducto = :nombreProducto");
                $consulta->bindValue(':nombreProducto', $nombreProducto, PDO::PARAM_STR);
            } else {
                $response->getBody()->write(json_encode(array('error' => 'Se debe proporcionar al menos un parámetro: "idProducto" o "nombreProducto"')));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $consulta->execute();

            $resultado = $consulta->fetch(PDO::FETCH_ASSOC);

            if (!$resultado) {
                $response->getBody()->write(json_encode(array('error' => 'Producto no encontrado')));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($resultado));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(array('error' => 'Error de base de datos')));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }


    public function TraerTodos($request, $response, $args)
    {
        $productos = Producto::TraerTodosLosProductos();
        $response->getBody()->write(json_encode($productos));
        return $response->withHeader('Content-Type', 'application/json');
    }


    public function CargarUno($request, $response, $args)
    {
        $data = $request->getParsedBody();
        if (!isset($data['nombreProducto']) || !isset($data['precioProducto']) || !isset($data['categoriaProducto'])) {
            $response->getBody()->write(json_encode(array('error' => 'Datos incompletos')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $productoExistente = Producto::TraerUnProductoPorNombre($data['nombreProducto']);

        if ($productoExistente !== null) {
        $response->getBody()->write(json_encode(array('error' => 'El producto ya existe')));
        return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        $nuevoProducto = new Producto();
        $nuevoProducto->nombreProducto = $data['nombreProducto'];
        $nuevoProducto->precioProducto = $data['precioProducto'];
        $nuevoProducto->sector = $data['categoriaProducto'];

        $idInsertado = $nuevoProducto->InsertarProductoParametros();

        $result = array('idProducto' => $idInsertado, 'mensaje' => 'Producto insertado correctamente');
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    }

    public function BorrarUno($request, $response, $args)
    {
        $data = $request->getParsedBody();
    
        if (!isset($data['idProducto'])) {
            $response->getBody()->write(json_encode(array('error' => 'ID de producto no proporcionado')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    
        $idProducto = $data['idProducto'];
    
        if (!is_numeric($idProducto) || $idProducto <= 0) {
            $response->getBody()->write(json_encode(array('error' => 'ID de producto no válido')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    
        $producto = Producto::TraerUnProducto($idProducto);
    
        if (!$producto) {
            $response->getBody()->write(json_encode(array('error' => 'Producto no encontrado')));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    
        if ($producto->estado === 'eliminado') {
            $response->getBody()->write(json_encode(array('error' => 'Error, el producto fue dado de baja previamente')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    
        $filasAfectadas = $producto->BorrarProducto();
    
        if ($filasAfectadas > 0) {
            $response->getBody()->write(json_encode(array('mensaje' => 'Producto borrado correctamente')));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(array('error' => 'Error al borrar el producto')));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function ModificarUno($request, $response, $args)
    {
        $data = $request->getParsedBody();
        $idProducto = $data['idProducto'];
    
        $data = $request->getParsedBody();
    
        $producto = new Producto();
        $producto->idProducto = $idProducto;
    
        if (isset($data['nombreProducto'])) {
            $producto->nombreProducto = $data['nombreProducto'];
        }
    
        if (isset($data['precioProducto'])) {
            $producto->precioProducto = $data['precioProducto'];
        }
    
        if (isset($data['categoriaProducto'])) {
            $producto->sector = $data['categoriaProducto'];
        }
    
        if (isset($data['estado'])) {
            if ($data['estado'] === 'eliminado' || $data['estado'] === 'activo') {
                $producto->estado = $data['estado'];
            } else {
                $response->getBody()->write(json_encode(array('error' => 'Estado no válido')));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
        }
    
        $idModificado = $producto->ModificarProductoParametros();
    
        if ($idModificado !== false) {
            $response->getBody()->write(json_encode(array('idProducto' => $idModificado, 'mensaje' => 'Producto modificado correctamente')));
            return $response->withHeader('Content-Type', 'application/json');
        } else {
            $response->getBody()->write(json_encode(array('error' => 'Producto no encontrado')));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
    }

}
