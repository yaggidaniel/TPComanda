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
        $consulta = $objetoAccesoDato->prepararConsulta("INSERT INTO productos (nombreProducto, precioProducto, sector, estado) VALUES (:nombreProducto, :precioProducto, :sector, :estado)");
        $consulta->bindValue(':nombreProducto', $this->nombreProducto, PDO::PARAM_STR);
        $consulta->bindValue(':precioProducto', $this->precioProducto, PDO::PARAM_STR);
        $consulta->bindValue(':sector', $this->sector, PDO::PARAM_STR);
        $consulta->bindValue(':estado', 'Disponible', PDO::PARAM_STR); // Valor por defecto 'Disponible'
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
        $consulta = $objetoAccesoDato->prepararConsulta("SELECT idProducto, nombreProducto, precioProducto, sector, estado FROM productos where idProducto = :idProducto");
        $consulta->bindValue(':idProducto', $idProducto, PDO::PARAM_INT);
        $consulta->execute();
        $productoBuscado = $consulta->fetchObject('Producto');

        return $productoBuscado;
    }  

    public static function TraerUnProductoPorIdONombre($idProducto = null, $nombreProducto = null)
    {
        $objetoAccesoDato = ConexionPDO::obtenerInstancia();
        if ($idProducto !== null) {
            $consulta = $objetoAccesoDato->prepararConsulta("SELECT idProducto, nombreProducto, precioProducto, sector FROM productos WHERE idProducto = :idProducto");
            $consulta->bindValue(':idProducto', $idProducto, PDO::PARAM_INT);
        } elseif ($nombreProducto !== null) {
            $consulta = $objetoAccesoDato->prepararConsulta("SELECT idProducto, nombreProducto, precioProducto, sector FROM productos WHERE nombreProducto = :nombreProducto");
            $consulta->bindValue(':nombreProducto', $nombreProducto, PDO::PARAM_STR);
        } else {
            return null; // Si no se proporciona idProducto ni nombreProducto, retornar null
        }
        $consulta->execute();
        $producto = $consulta->fetchObject('Producto');
        return $producto ?: null; // Si no se encuentra el producto, retornar null
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
            $consulta->bindValue(':estado', 'Disponible', PDO::PARAM_STR);
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
