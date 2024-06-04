<?php

require_once __DIR__ . '/../db/ConexionPDO.php';
require_once __DIR__ . '/../controllers/ProductosController.php';


class Producto
{
    public $idProducto;
    public $nombreProducto;
    public $precioProducto;
    public $sector;
    public $estado;


    public function InsertarProductoParametros()
    {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("INSERT INTO productos (nombreProducto, precioProducto, sector) VALUES (:nombreProducto, :precioProducto, :sector)");
        $consulta->bindValue(':nombreProducto', $this->nombreProducto, PDO::PARAM_STR);
        $consulta->bindValue(':precioProducto', $this->precioProducto, PDO::PARAM_STR);
        $consulta->bindValue(':sector', $this->sector, PDO::PARAM_STR);
        $consulta->execute();

        return $objetoAccesoDato->obtenerUltimoId();
    }

    public static function TraerTodosLosProductos()
    {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("SELECT idProducto, nombreProducto, precioProducto, sector, estado FROM productos");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, "Producto");
    }

    public static function TraerUnProducto($idProducto)
    {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("SELECT idProducto, nombreProducto, precioProducto, sector FROM productos where idProducto = :idProducto");
        $consulta->bindValue(':idProducto', $idProducto, PDO::PARAM_INT);
        $consulta->execute();
        $productoBuscado = $consulta->fetchObject('Producto');

        return $productoBuscado;
    }  

    public static function TraerUnProductoPorNombre($nombreProducto)
    {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        $consulta = $objetoAccesoDato->prepararConsulta("SELECT idProducto, nombreProducto, precioProducto, sector FROM productos WHERE nombreProducto = :nombreProducto");
        $consulta->bindValue(':nombreProducto', $nombreProducto, PDO::PARAM_STR);
        $consulta->execute();

        $productoBuscado = $consulta->fetchObject('Producto');
        if (!$productoBuscado) {
            return null; // Producto no encontrado
        }

        return $productoBuscado;
    }


    public function ModificarProductoParametros()
    {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        
        $consulta = $objetoAccesoDato->prepararConsulta("UPDATE productos SET nombreProducto = :nombreProducto, precioProducto = :precioProducto, sector = :sector, estado = :estado WHERE idProducto = :idProducto");
        
        $consulta->bindValue(':idProducto', $this->idProducto, PDO::PARAM_INT);
        $consulta->bindValue(':nombreProducto', $this->nombreProducto, PDO::PARAM_STR);
        $consulta->bindValue(':precioProducto', $this->precioProducto, PDO::PARAM_STR);
        $consulta->bindValue(':sector', $this->sector, PDO::PARAM_STR);
        
        if (isset($this->estado)) {
            $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        } else {
            $consulta->bindValue(':estado', 'activo', PDO::PARAM_STR);
        }

        $resultado = $consulta->execute();

        $filasAfectadas = $consulta->rowCount();
        if ($filasAfectadas === 0 || !$resultado) {
            return false;
        }

        return $this->idProducto;
    }

    
    public function BorrarProducto()
    {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();

        $consulta = $objetoAccesoDato->prepararConsulta("UPDATE productos SET estado = 'eliminado' WHERE idProducto = :idProducto");
        $consulta->bindValue(':idProducto', $this->idProducto, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->rowCount();
    }
}
