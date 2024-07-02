<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . '/../models/Comanda.php'; 
require_once __DIR__ . '/../models/Mesa.php'; 
require_once __DIR__ . '/../db/ConexionPDO.php'; 

class Encuesta
{
    public static function altaEncuesta($idMesa, $idPedido, $puntajeMesa, $puntajeRestaurante, $puntajeMozo, $puntajeCocinero, $experiencia)
    {
        try {
            self::validarPedido($idPedido);

            $objetoAccesoDato = ConexionPDO::obtenerInstancia();
            $consulta = $objetoAccesoDato->prepararConsulta("INSERT INTO Encuesta (idPedido, idMesa, puntajeMesa, puntajeRestaurante, puntajeMozo, puntajeCocinero, experiencia) VALUES (:idPedido, :idMesa, :puntajeMesa, :puntajeRestaurante, :puntajeMozo, :puntajeCocinero, :experiencia)");

            $consulta->bindValue(':idMesa', $idMesa, PDO::PARAM_INT);
            $consulta->bindValue(':idPedido', $idPedido, PDO::PARAM_INT);
            $consulta->bindValue(':puntajeMesa', $puntajeMesa, PDO::PARAM_INT);
            $consulta->bindValue(':puntajeRestaurante', $puntajeRestaurante, PDO::PARAM_INT);
            $consulta->bindValue(':puntajeMozo', $puntajeMozo, PDO::PARAM_INT);
            $consulta->bindValue(':puntajeCocinero', $puntajeCocinero, PDO::PARAM_INT);
            $consulta->bindValue(':experiencia', $experiencia, PDO::PARAM_STR);

            $consulta->execute();

        } catch (Exception $e) {
            throw new Exception("Error al crear la encuesta: " . $e->getMessage());
        }
    }

    public static function validarPedido($idPedido)
    {
        try {
            $pedidoConProductos = Comanda::obtenerUnPedidoConProductos($idPedido);
            if (!$pedidoConProductos) {
                throw new Exception("El idPedido proporcionado no es válido.");
            }

            $encuestaExistente = self::buscarEncuestaPorPedido($idPedido);
            if ($encuestaExistente) {
                throw new Exception("Ya existe una encuesta asociada al idPedido proporcionado.");
            }

        } catch (Exception $e) {
            throw $e;
        }
    }

    public static function buscarEncuestaPorPedido($idPedido)
    {
        try {
            $objetoAccesoDato = ConexionPDO::obtenerInstancia();
            $consulta = $objetoAccesoDato->prepararConsulta("SELECT * FROM Encuesta WHERE idPedido = :idPedido");
            $consulta->bindValue(':idPedido', $idPedido, PDO::PARAM_INT);
            $consulta->execute();

            $encuesta = $consulta->fetch(PDO::FETCH_ASSOC);

            return $encuesta ? true : false;

        } catch (Exception $e) {
            throw new Exception("Error al buscar la encuesta por pedido: " . $e->getMessage());
        }
    }

    public static function obtenerTodasLasEncuestas()
    {
        try {
            $objetoAccesoDato = ConexionPDO::obtenerInstancia();
            $consulta = $objetoAccesoDato->prepararConsulta("SELECT * FROM Encuesta");
            $consulta->execute();
            $encuestas = $consulta->fetchAll(PDO::FETCH_ASSOC);
            return $encuestas;
        } catch (Exception $e) {
            throw new Exception("Error al obtener las encuestas: " . $e->getMessage());
        }
    }

    public static function obtenerComentariosEncuestas()
    {
        try {
            $objetoAccesoDato = ConexionPDO::obtenerInstancia();
            $consulta = $objetoAccesoDato->prepararConsulta("SELECT experiencia FROM Encuesta");
            $consulta->execute();
            $comentarios = $consulta->fetchAll(PDO::FETCH_COLUMN);
            return $comentarios;
        } catch (Exception $e) {
            throw new Exception("Error al obtener los comentarios de las encuestas: " . $e->getMessage());
        }
    }

}

?>
