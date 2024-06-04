<?php

require_once __DIR__ . '/../db/ConexionPDO.php';
require_once __DIR__ . '/../controllers/ComandaController.php';


class Comanda
{
    public $id;
    public $id_mesa;
    public $idUsuario; 
    public $codigo;   
    public $id_estado;   
    public $tiempoEspera;   
    public $totalAPagar;   

    public function InsertarPedido()
    {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("INSERT INTO pedidos (id_mesa, idUsuario, codigo, id_estado, tiempoEspera, totalAPagar) VALUES (:id_mesa, :idUsuario, :codigo, :id_estado, :tiempoEspera, :totalAPagar)");
        $consulta->bindValue(':id_mesa', $this->id_mesa, PDO::PARAM_INT);
        $consulta->bindValue(':idUsuario', $this->idUsuario, PDO::PARAM_INT);
        $consulta->bindValue(':codigo', $this->codigo, PDO::PARAM_STR);
        $consulta->bindValue(':id_estado', $this->id_estado, PDO::PARAM_INT);
        $consulta->bindValue(':tiempoEspera', $this->tiempoEspera, PDO::PARAM_STR);
        $consulta->bindValue(':totalAPagar', $this->totalAPagar, PDO::PARAM_STR);

        $consulta->execute();
        return $objetoAccesoDato->obtenerUltimoId();
    }

    public static function TraerTodosLosPedidos()
    {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("SELECT * FROM pedidos");
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_CLASS, "Pedido");
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

    public static function TraerUnPedidoPorCodigo($codigo_pedido)
    {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("SELECT * FROM pedidos where codigo = :codigo_pedido");
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);
        $consulta->execute();
        $pedidoBuscado = $consulta->fetchObject('Pedido');
       
        return $pedidoBuscado;
    }

    public function ModificarPedidoParametros()
    {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("UPDATE comandas SET id_mesa = :id_mesa, idUsuario = :idUsuario, codigo = :codigo, id_estado = :id_estado, tiempoEspera = :tiempoEspera, totalAPagar = :totalAPagar WHERE id = :id");
        $consulta->bindValue(':id', $this->id, PDO::PARAM_INT);
        $consulta->bindValue(':id_mesa', $this->id_mesa, PDO::PARAM_INT);
        $consulta->bindValue(':idUsuario', $this->idUsuario, PDO::PARAM_INT);
        $consulta->bindValue(':codigo', $this->codigo, PDO::PARAM_STR);
        $consulta->bindValue(':id_estado', $this->id_estado, PDO::PARAM_INT);
        $consulta->bindValue(':tiempoEspera', $this->tiempoEspera, PDO::PARAM_STR);
        $consulta->bindValue(':totalAPagar', $this->totalAPagar, PDO::PARAM_STR);

        $resultado = $consulta->execute();

        $filasAfectadas = $consulta->rowCount();
        if ($filasAfectadas === 0 || !$resultado) {
            return false;
        }
        return $this->id;
    }

    public static function CambiarEstadoPedido($codigo_pedido, $id_estado, $tiempoRetraso){       

        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("UPDATE pedidos SET id_estado = :id_estado, fecha_entrega = :fecha_entrega, tiempo_retraso = :tiempo_retraso WHERE codigo = :codigo_pedido");  
        $consulta->bindValue(':codigo_pedido', $codigo_pedido, PDO::PARAM_STR);     
        $consulta->bindValue(':id_estado', $id_estado, PDO::PARAM_INT);
        if($tiempoRetraso === null){
            $dateFormatted = null;
        }else{
            $ahora = time();
            $dateFormatted = date('Y-m-d H:i:s', $ahora);
        }
        $consulta->bindValue(':fecha_entrega', $dateFormatted, PDO::PARAM_STR);
        $consulta->bindValue(':tiempo_retraso', $tiempoRetraso, PDO::PARAM_STR);
        $resultado = $consulta->execute();

        $filasAfectadas = $consulta->rowCount();
        
        if ($filasAfectadas === 0) {
            return false;
        }
        return $codigo_pedido;
    }

    public function BorrarPedido()
    {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("DELETE FROM pedidos WHERE id = :id");
        $consulta->bindValue(':id', $this->id, PDO::PARAM_INT);
        $consulta->execute();
        return $consulta->rowCount();
    }

    public static function codigoRepetido($codigo) {

        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("SELECT COUNT(*) FROM pedidos WHERE codigo = :codigo");
        $consulta->bindValue(':codigo', $codigo, PDO::PARAM_STR);
        $consulta->execute();
        $cantidad = $consulta->fetchColumn();
        return $cantidad > 0;
    }

    public static function TraerPedidosPorEstado($estado_pedido) {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("SELECT * FROM pedidos WHERE id_estado = :estado_pedido");
        $consulta->bindValue(':estado_pedido', $estado_pedido, PDO::PARAM_INT);
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_CLASS, "Pedido");
    }

    public function AsignarEmpleado($idEmpleado, $tiempoEstimado, $codigoPedido)
    {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("UPDATE pedidos SET idUsuario = :idEmpleado, tiempoEspera = :tiempoEstimado, id_estado = 2, fecha_inicio = :fecha_inicio WHERE codigo = :codigoPedido AND id_estado = 2");
        $consulta->bindValue(':codigoPedido', $codigoPedido, PDO::PARAM_STR);
        $consulta->bindValue(':idEmpleado', $idEmpleado, PDO::PARAM_INT);
        $consulta->bindValue(':tiempoEstimado', $tiempoEstimado, PDO::PARAM_INT);
        $ahora = time();
        $dateFormatted = date('Y-m-d H:i:s', $ahora);
        $consulta->bindValue(':fecha_inicio', $dateFormatted, PDO::PARAM_STR);
        $resultado = $consulta->execute();

        $filasAfectadas = $consulta->rowCount();
        if ($filasAfectadas === 0 || !$resultado) {
            return false;
        }
        return true;
    }





}