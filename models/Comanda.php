<?php

require_once __DIR__ . '/../db/ConexionPDO.php';
require_once __DIR__ . '/../controllers/ComandaController.php';
require_once __DIR__ . '/../controllers/MesaController.php';
require_once __DIR__ . '/../controllers/ProductosController.php';
require_once __DIR__ . '/../models/Producto.php';
require_once __DIR__ . '/../capaDatos/ImageManager.php';
require_once __DIR__ . '/../capaDatos/FileManager.php';


class Comanda
{
    public $id;
    public $id_mesa;
    public $idUsuario; 
    public $id_estado;   
    public $tiempoEspera;   
    public $totalAPagar;   


    public function AltaComanda() {
        // Validar la existencia de la mesa y obtener sus datos
        $Mesa = Mesa::TraerUnaMesa($this->id_mesa);
        if (!$Mesa) {
            throw new Exception("La mesa con id {$this->id_mesa} no existe.");
        }
    
        // Asignar el valor de idMesa para la inserción en la tabla pedidos
        $this->id_mesa = $Mesa->idMesa;
    
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        
        // Insertar en la tabla pedidos
        $consultaPedido = $objetoAccesoDato->prepararConsulta("INSERT INTO pedidos (id_mesa, idUsuario, id_estado, tiempoEspera, totalAPagar, horaLlegada) VALUES (:id_mesa, :idUsuario, :id_estado, :tiempoEspera, :totalAPagar, NOW())");
        $consultaPedido->bindValue(':id_mesa', $this->id_mesa, PDO::PARAM_INT);
        $consultaPedido->bindValue(':idUsuario', $this->idUsuario, PDO::PARAM_INT);
        $consultaPedido->bindValue(':id_estado', $this->id_estado, PDO::PARAM_INT); // Asegúrate de tener el id_estado definido en tu clase
        $consultaPedido->bindValue(':tiempoEspera', $this->tiempoEspera, PDO::PARAM_STR);
        $consultaPedido->bindValue(':totalAPagar', $this->totalAPagar, PDO::PARAM_STR);
        $consultaPedido->execute();
        
        return $objetoAccesoDato->obtenerUltimoId();
    }
    

    public static function obtenerComandaPorId($idDeComanda) {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("SELECT * FROM pedidos WHERE idPedido = :idDeComanda");
        $consulta->bindValue(':idDeComanda', $idDeComanda, PDO::PARAM_INT);
        $consulta->execute();
        $comanda = $consulta->fetch(PDO::FETCH_ASSOC);

        if (!$comanda) {
            throw new Exception("No se encontró la comanda con ID: {$idDeComanda}");
        }

        return $comanda;
    }


    public static function insertarProductoEnComanda($idDeComanda, $idProducto) {
        // Verificar que la comanda exista
        $comanda = self::obtenerComandaPorId($idDeComanda);
        if (!$comanda) {
            throw new Exception("La comanda con id {$idDeComanda} no existe.");
        }

        // Verificar que el producto exista
        $producto = Producto::TraerUnProducto($idProducto);
        if (!$producto) {
            throw new Exception("El producto con id {$idProducto} no existe.");
        }

        // Insertar en la tabla pedidos_productos
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consultaProducto = $objetoAccesoDato->prepararConsulta("INSERT INTO 
        pedidos_productos (idPedido, idProducto, nombreProducto, precioUnitario, sector, estado) 
        VALUES (:idPedido, :idProducto, :nombreProducto, :precioUnitario, :sector, :estado)");

        $consultaProducto->bindValue(':idPedido', $idDeComanda, PDO::PARAM_INT);
        $consultaProducto->bindValue(':idProducto', $idProducto, PDO::PARAM_INT);
        $consultaProducto->bindValue(':nombreProducto', $producto->nombreProducto, PDO::PARAM_INT);
        $consultaProducto->bindValue(':precioUnitario', $producto->precioProducto, PDO::PARAM_STR);
        $consultaProducto->bindValue(':sector', $producto->sector, PDO::PARAM_STR);
        $consultaProducto->bindValue(':estado', 'Pendiente', PDO::PARAM_STR);
        $consultaProducto->execute();

        return array(
            'idPedido' => $idDeComanda,
            'idProducto' => $idProducto,
            'nombreProducto' => $producto->nombreProducto,
            'precioUnitario' => $producto->precioProducto,
            'sector' => $producto->sector,
            'estado' => 'Pendiente'
        );
    }



    public static function obtenerUnPedidoConProductos($idPedido) {
        try {
            // Obtener la comanda
            $comanda = self::obtenerComandaPorId($idPedido);

            // Obtener la mesa asociada
            $mesa = Mesa::TraerUnaMesa($comanda['id_mesa']);
            if (!$mesa) {
                throw new Exception("La mesa con id {$comanda['id_mesa']} no existe.");
            }


            // Obtener la hora de llegada del pedido
            $objetoAccesoDato = ConexionPDO::obtenerInstancia();
            $consulta = $objetoAccesoDato->prepararConsulta("SELECT pd.horaLlegada
                                                            FROM pedidos pd
                                                            WHERE pd.idPedido = :idPedido");
            $consulta->bindValue(':idPedido', $idPedido, PDO::PARAM_INT);
            $consulta->execute();
            $horaLlegada = $consulta->fetchColumn();


            // Obtener los productos asociados
            $objetoAccesoDato = ConexionPDO::obtenerInstancia();
            $consulta = $objetoAccesoDato->prepararConsulta("SELECT pp.idProducto, p.nombreProducto, pp.precioUnitario, pp.sector, pp.estado, pp.tiempoPreparacion
            FROM pedidos_productos pp
            JOIN productos p ON pp.idProducto = p.idProducto
            WHERE pp.idPedido = :idPedido");
            $consulta->bindValue(':idPedido', $idPedido, PDO::PARAM_INT);
            $consulta->execute();
            $productos = $consulta->fetchAll(PDO::FETCH_ASSOC);

            // Calcular el total a pagar
            $totalAPagar = array_reduce($productos, function($carry, $item) {
                return $carry + $item['precioUnitario'];
            }, 0);

            $tiempoEspera = 0;
            if (!empty($productos)) {
                $tiempoEspera = max(array_column($productos, 'tiempoPreparacion'));
            }


            // Construir el objeto de salida
            $resultado = array(
                "idPedido" => $comanda['idPedido'],
                "fotoMesa" => $comanda['urlFotoPedido'],
                "idMesa" => $mesa->idMesa,
                "nombre" => $mesa->nombre, 
                "estado" => $mesa->estado, 
                "horaLlegada" => $horaLlegada,
                "tiempoEspera" => $tiempoEspera .' '. 'minutos',
                "totalAPagar" => $totalAPagar,
                "productos" => $productos          
            );

            return $resultado;

        } catch (Exception $e) {
            throw new Exception("Error al obtener el pedido con productos: " . $e->getMessage());
        }
    }


    public static function relacionarFotoEnComanda($idDeComanda, $uploadedFile) {
        $comanda = self::obtenerComandaPorId($idDeComanda);
    
        if (!$comanda) {
            return ['error' => 'La comanda no existe.'];
        }
    
        $imageManager = new ImageManager();
        $rutaImagen = __DIR__ . '/../fotosMesas/';
        $nombreImagen = 'Comanda_' . $idDeComanda;
        $resultadoImagen = $imageManager->validateAndMoveImage($uploadedFile, $rutaImagen, $nombreImagen);
    
        if (!empty($resultadoImagen['success'])) {
            $urlFotoPedido = $resultadoImagen['fileName'];
    
            $objetoAccesoDato = ConexionPDO::obtenerInstancia();
            $consulta = $objetoAccesoDato->prepararConsulta("UPDATE pedidos SET urlFotoPedido = :urlFotoPedido WHERE idPedido = :idDeComanda");
            $consulta->bindValue(':urlFotoPedido', $urlFotoPedido, PDO::PARAM_STR);
            $consulta->bindValue(':idDeComanda', $idDeComanda, PDO::PARAM_INT);
            $consulta->execute();
    
            return array('mensaje' => 'Foto guardada con éxito', 'imagen' => $urlFotoPedido);
        } else {
            return array('error' => $resultadoImagen['error']);
        }
    }
    

    public static function obtenerProductosPorSector($sector) {
        try {
            $objetoAccesoDato = ConexionPDO::obtenerInstancia();
            $consulta = $objetoAccesoDato->prepararConsulta("SELECT pp.idPedidoProducto, pp.idProducto, p.nombreProducto, pp.precioUnitario, pp.sector, pp.estado 
                                                            FROM pedidos_productos pp
                                                            JOIN productos p ON pp.idProducto = p.idProducto
                                                            WHERE pp.sector = :sector");
            $consulta->bindValue(':sector', $sector, PDO::PARAM_STR);
            $consulta->execute();
            $productos = $consulta->fetchAll(PDO::FETCH_ASSOC);
    
            if (empty($productos)) {
                throw new Exception("No se encontraron productos para el sector $sector.");
            }
    
            $resultado = array_map(function($producto) {
                return [
                    "idProducto" => $producto['idProducto'],
                    "idPedidoProducto" => $producto['idPedidoProducto'],
                    "nombreProducto" => $producto['nombreProducto'],
                    "precioUnitario" => $producto['precioUnitario'],
                    "sector" => $producto['sector'],
                    "estado" => $producto['estado']
                ];
            }, $productos);
    
            return $resultado;
    
        } catch (Exception $e) {
            throw new Exception("Error al obtener los productos por sector $sector: " . $e->getMessage());
        }
    }
    

    public static function obtenerProductosPorEstadoPorSector($sector, $estado) {
        try {
            $productos = self::obtenerProductosPorSector($sector);
    
            $productosFiltrados = array_filter($productos, function($producto) use ($estado) {
                return $producto['estado'] === $estado;
            });
    
            if (empty($productosFiltrados)) {
                throw new Exception("No se encontraron productos con estado '$estado' para el sector $sector.");
            }
    
            return $productosFiltrados;
    
        } catch (Exception $e) {
            throw new Exception("Error al obtener los productos por estado '$estado' y sector $sector: " . $e->getMessage());
        }
    }
    
    
    public static function obtenerProductoPorIdPedidoProducto($idPedidoProducto) {
        try {
            $objetoAccesoDato = ConexionPDO::obtenerInstancia();
    
            $consulta = $objetoAccesoDato->prepararConsulta("SELECT pp.idPedidoProducto, pp.idProducto, p.nombreProducto, pp.precioUnitario, pp.sector, pp.estado, pp.tiempoPreparacion
                                                            FROM pedidos_productos pp
                                                            JOIN productos p ON pp.idProducto = p.idProducto
                                                            WHERE pp.idPedidoProducto = :idPedidoProducto");
            $consulta->bindValue(':idPedidoProducto', $idPedidoProducto, PDO::PARAM_INT);
            
            $consulta->execute();
    
            $producto = $consulta->fetch(PDO::FETCH_ASSOC);
    
            if (!$producto) {
                throw new Exception("No se encontró ningún producto con idPedidoProducto $idPedidoProducto.");
            }
    
            return $producto;
    
        } catch (Exception $e) {
            throw new Exception("Error al obtener el producto con idPedidoProducto $idPedidoProducto: " . $e->getMessage());
        }
    }

    public static function cambiarEstadoYTiempoPreparacion($idPedidoProducto, $tiempoPreparacion, $estado = null) {
        try {
            $producto = self::obtenerProductoPorIdPedidoProducto($idPedidoProducto);
    
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
    
            return ['success' => true, 'producto' => $producto]; 
    
        } catch (Exception $e) {
            throw new Exception("Error al cambiar estado y tiempo de preparación del pedido producto $idPedidoProducto: " . $e->getMessage());
        }
    }
    



    public static function calcularTiempoEspera($idPedido) {
        try {
            $pedidoConProductos = self::obtenerUnPedidoConProductos($idPedido);
    
            // Configurar la zona horaria a Argentina
            date_default_timezone_set('America/Argentina/Buenos_Aires');
            $horaActual = new DateTime();

            $horaLlegada = new DateTime($pedidoConProductos['horaLlegada']);
            $intervalo = $horaActual->diff($horaLlegada);
    
            $tiempoEsperaMinutos = ($intervalo->days * 24 * 60) + ($intervalo->h * 60) + $intervalo->i;
    
            return $tiempoEsperaMinutos;
    
        } catch (Exception $e) {
            throw new Exception("Error al calcular el tiempo de espera: " . $e->getMessage());
        }
    }


    public static function TraerTodosLosPedidos()
    {
        try {
            $objetoAccesoDato = ConexionPDO::obtenerInstancia();
            
            $consulta = $objetoAccesoDato->prepararConsulta("SELECT idPedido FROM pedidos");
            $consulta->execute();
            $idPedidos = $consulta->fetchAll(PDO::FETCH_COLUMN);

            $resultado = [];

            foreach ($idPedidos as $idPedido) {
                $pedidoConProductos = self::obtenerUnPedidoConProductos($idPedido);
                
                $resultado[] = $pedidoConProductos;
            }

            return $resultado;

        } catch (Exception $e) {
            throw new Exception("Error al obtener todos los pedidos con productos: " . $e->getMessage());
        }
    }


    public static function TraerPedidosListosParaServir()
    {
        try {
            $objetoAccesoDato = ConexionPDO::obtenerInstancia();

            $consulta = $objetoAccesoDato->prepararConsulta("
                SELECT DISTINCT pp.idPedido
                FROM pedidos_productos pp
                WHERE NOT EXISTS (
                    SELECT 1
                    FROM pedidos_productos
                    WHERE idPedido = pp.idPedido AND estado != 'listo para servir'
                )
            ");
            $consulta->execute();
            $idPedidos = $consulta->fetchAll(PDO::FETCH_COLUMN);

            $resultado = [];

            foreach ($idPedidos as $idPedido) {
                $pedidoConProductos = self::obtenerUnPedidoConProductos($idPedido);

                // Verificar si todos los productos del pedido están listos para servir
                $productosListos = array_filter($pedidoConProductos['productos'], function ($producto) {
                    return $producto['estado'] == 'listo para servir';
                });

                if (count($pedidoConProductos['productos']) === count($productosListos)) {
                    $pedidoConProductos['estado'] = 'Listo para servir';
                    $resultado[] = $pedidoConProductos;
                }
            }

            return $resultado;

        } catch (Exception $e) {
            throw new Exception("Error al obtener los pedidos listos para servir: " . $e->getMessage());
        }
    }


    public static function cobrarComanda($idPedido)
    {
        try {
            $pedidoConProductos = self::obtenerUnPedidoConProductos($idPedido);
    
            if ($pedidoConProductos['estado'] !== 'Entregado') {
                throw new Exception("El pedido no está en estado 'Entregado'.");
            }

            $objetoAccesoDato = ConexionPDO::obtenerInstancia();
            $consulta = $objetoAccesoDato->prepararConsulta("UPDATE pedidos SET id_estado = 'Por pagar' WHERE idPedido = :idPedido");
            $consulta->bindValue(':idPedido', $idPedido, PDO::PARAM_INT);
            $consulta->execute();

            $totalAPagar = array_reduce($pedidoConProductos['productos'], function($carry, $item) {
                return $carry + $item['precioUnitario'];
            }, 0);
    
            $consulta = $objetoAccesoDato->prepararConsulta("UPDATE pedidos SET totalAPagar = :totalAPagar WHERE idPedido = :idPedido");
            $consulta->bindValue(':totalAPagar', $totalAPagar, PDO::PARAM_STR);
            $consulta->bindValue(':idPedido', $idPedido, PDO::PARAM_INT);
            $consulta->execute();

            $tiempoEsperaMinutos = self::calcularTiempoEspera($idPedido);

            $consulta = $objetoAccesoDato->prepararConsulta("UPDATE pedidos SET tiempoEspera = :tiempoEspera WHERE idPedido = :idPedido");
            $consulta->bindValue(':tiempoEspera', $tiempoEsperaMinutos, PDO::PARAM_INT);
            $consulta->bindValue(':idPedido', $idPedido, PDO::PARAM_INT);
            $consulta->execute();
        
            $horaSalida = new DateTime("now", new DateTimeZone('America/Argentina/Buenos_Aires'));
            $horaSalida = $horaSalida->format('Y-m-d H:i:s');

            $consulta = $objetoAccesoDato->prepararConsulta("UPDATE pedidos SET horaSalida = :horaSalida WHERE idPedido = :idPedido");
            $consulta->bindValue(':horaSalida', $horaSalida, PDO::PARAM_STR);
            $consulta->bindValue(':idPedido', $idPedido, PDO::PARAM_INT);
            $consulta->execute();
    
            $respuesta = array(
                "mensaje" => "Gracias por elegirnos",
                "idPedido" => $pedidoConProductos['idPedido'],
                "nombre" => $pedidoConProductos['nombre'],
                "estado" => "Por pagar",
                "horaLlegada" => $pedidoConProductos['horaLlegada'],
                "horaSalida" => $horaSalida,
                "totalAPagar" => '$' . number_format($pedidoConProductos['totalAPagar'], 2),
                "productos" => array_map(function($producto) {
                    return array(
                        "nombreProducto" => $producto['nombreProducto'],
                        "precioUnitario" => $producto['precioUnitario']
                    );
                }, $pedidoConProductos['productos'])
            );
    
            return $respuesta;
    
        } catch (Exception $e) {
            throw new Exception("Error al cobrar la comanda: " . $e->getMessage());
        }
    }

    public static function descargarComandasCSV()
    {
        try {
            $pedidos = self::TraerTodosLosPedidos();
            @$csvData = FileManager::createCSV($pedidos);
            return $csvData;
        } catch (Exception $e) {
            throw new Exception("Error al generar el CSV de comandas: " . $e->getMessage());
        }
    }


    public static function obtenerPedidosPorTiempo($criterio)
    {
        try {
            $todosLosPedidos = self::TraerTodosLosPedidos();

            $pedidosFiltrados = [];

            foreach ($todosLosPedidos as $pedidoConProductos) {
                $idPedido = $pedidoConProductos['idPedido'];
                $pedidoConProductos = self::obtenerUnPedidoConProductos($idPedido);

                $tiempoTotalPreparacion = array_reduce($pedidoConProductos['productos'], function($carry, $producto) {
                    return $carry + $producto['tiempoPreparacion'];
                }, 0);

                $horaLlegada = new DateTime($pedidoConProductos['horaLlegada']);
                $horaSalida = new DateTime($pedidoConProductos['horaSalida']);
                $diferenciaMinutos = $horaLlegada->diff($horaSalida)->i;

                // Comparar según el criterio
                if ($criterio === 'ATiempo' && $tiempoTotalPreparacion <= $diferenciaMinutos) {
                    $pedidosFiltrados[] = $pedidoConProductos;
                }  elseif ($criterio === 'Demorados' && $tiempoTotalPreparacion > $diferenciaMinutos) {
                    $pedidoConProductos['tiempoRealDeEspera'] = $diferenciaMinutos;
                    $pedidosFiltrados[] = $pedidoConProductos;                }
            }

            return $pedidosFiltrados;

        } catch (Exception $e) {
            throw new Exception("Error al obtener pedidos por tiempo: " . $e->getMessage());
        }
    }


    public static function TraerUnPedido($id)
    {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("SELECT * FROM pedidos where id = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
        $pedidoBuscado = $consulta->fetchObject('Pedido');
       
        return $pedidoBuscado;
    }

    public function BorrarPedido()
    {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("UPDATE pedidos SET id_estado = :id_estado WHERE id = :id");
        $consulta->bindValue(':id_estado', 'cancelado', PDO::PARAM_STR);
        $consulta->execute();
        
        $filasAfectadas = $consulta->rowCount();
        if ($filasAfectadas === 0) {
            return false; 
        }
        return true;
    }

    public static function TraerPedidosPorEstado($estado_pedido) {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("SELECT * FROM pedidos WHERE id_estado = :estado_pedido");
        $consulta->bindValue(':estado_pedido', $estado_pedido, PDO::PARAM_INT);
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_CLASS, "Pedido");
    }


}