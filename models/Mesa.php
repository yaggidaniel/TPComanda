<?php

require_once __DIR__ . '/../db/ConexionPDO.php';
require_once __DIR__ . '/../controllers/ComandaController.php';

class Mesa {

    public $idMesa;
    public $nombre;
    public $estado;

    public function InsertarMesa() {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("INSERT INTO Mesas (nombre, estado) VALUES (:nombre, :estado)");
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $consulta->execute();

        return $objetoAccesoDato->obtenerUltimoId();
    }

    public static function TraerTodasLasMesas() {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("SELECT * FROM Mesas");
        $consulta->execute();
        return $consulta->fetchAll(PDO::FETCH_CLASS, "Mesa");
    }


    public static function TraerUnaMesa($id) {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("SELECT idMesa, nombre, estado FROM Mesas WHERE idMesa = :id");
        $consulta->bindValue(':id', $id, PDO::PARAM_INT);
        $consulta->execute();
        $MesaBuscada = $consulta->fetchObject('Mesa');
    
        return $MesaBuscada;
    }

    public static function obtenerMesaMasUsada()
    {
        try {
            $objetoAccesoDato = ConexionPDO::obtenerInstancia();
            $consulta = $objetoAccesoDato->prepararConsulta("
                SELECT id_Mesa, COUNT(id_Mesa) as cantidad
                FROM pedidos
                GROUP BY id_Mesa
                ORDER BY cantidad DESC
                LIMIT 1
            ");
            $consulta->execute();
            $resultado = $consulta->fetch(PDO::FETCH_ASSOC);
            
            if ($resultado) {
                $idMesaMasUsada = $resultado['id_Mesa'];
                $mesa = self::TraerUnaMesa($idMesaMasUsada);
                return $mesa;
            } else {
                throw new Exception("No se encontraron pedidos en la base de datos.");
            }

        } catch (Exception $e) {
            throw new Exception("Error al obtener la mesa más usada: " . $e->getMessage());
        }
    }

    public function ModificarMesaParametros() {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("UPDATE Mesas SET nombre = :nombre, estado = :estado WHERE idMesa = :id");
        $consulta->bindValue(':id', $this->idMesa, PDO::PARAM_INT);
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $resultado = $consulta->execute();

        $filasAfectadas = $consulta->rowCount();
        if ($filasAfectadas === 0 || !$resultado) {
            return false;
        }
        return $this->idMesa;
    }

    public function EliminarMesa() {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("DELETE FROM Mesas WHERE idMesa = :id");
        $consulta->bindValue(':id', $this->idMesa, PDO::PARAM_INT);
        $consulta->execute();
        return $consulta->rowCount();
    }
    
}
?>
