<?php

require_once __DIR__ . '/../db/ConexionPDO.php';

class Cliente {

    public $idCliente;
    public $id_comanda;
    public $id_encuesta;
    public $nombre;
    public $hora_llegada;
    public $estado;

    public function InsertarCliente() {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("INSERT INTO clientes (id_comanda, id_encuesta, nombre, hora_llegada, estado) VALUES (:id_comanda, :id_encuesta, :nombre, :hora_llegada, :estado)");
        $consulta->bindValue(':id_comanda', $this->id_comanda, PDO::PARAM_INT);
        $consulta->bindValue(':id_encuesta', $this->id_encuesta, PDO::PARAM_INT);
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':hora_llegada', $this->hora_llegada, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $consulta->execute();

        return $objetoAccesoDato->obtenerUltimoId();
    }

    public static function TraerTodosLosClientes() {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("SELECT * FROM clientes");
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_CLASS, "Cliente");
    }

    public static function TraerUnCliente($id) {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("SELECT * FROM clientes WHERE idCliente = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
        $clienteBuscado = $consulta->fetchObject('Cliente');

        return $clienteBuscado;
    }

    public function ModificarClienteParametros() {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("UPDATE clientes SET id_comanda = :id_comanda, id_encuesta = :id_encuesta, nombre = :nombre, hora_llegada = :hora_llegada, estado = :estado WHERE idCliente = :id");
        $consulta->bindValue(':id', $this->idCliente, PDO::PARAM_INT);
        $consulta->bindValue(':id_comanda', $this->id_comanda, PDO::PARAM_INT);
        $consulta->bindValue(':id_encuesta', $this->id_encuesta, PDO::PARAM_INT);
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':hora_llegada', $this->hora_llegada, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $resultado = $consulta->execute();

        $filasAfectadas = $consulta->rowCount();
        if ($filasAfectadas === 0 || !$resultado) {
            return false;
        }
        return $this->idCliente;
    }

    public function EliminarCliente() {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("DELETE FROM clientes WHERE idCliente = :id");
        $consulta->bindValue(':id', $this->idCliente, PDO::PARAM_INT);
        $consulta->execute();
        return $consulta->rowCount();
    }
}
?>
