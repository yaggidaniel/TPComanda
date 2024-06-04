<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

require_once __DIR__ . '/../models/Usuario.php';
require_once __DIR__ . '/../Interfaces/IApiUsable.php';

class UsuarioController implements IApiUsable
{

     public function CargarUno($request, $response, $args)
     {

         $data = $request->getParsedBody();
 
         if (empty($data['nombre']) || empty($data['mail']) || empty($data['clave']) || empty($data['sector'])) {
             $response->getBody()->write(json_encode(array('error' => 'Todos los campos son obligatorios')));
             return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
         }
 
         if (!filter_var($data['mail'], FILTER_VALIDATE_EMAIL)) {
             $response->getBody()->write(json_encode(array('error' => 'Formato de correo electrónico no válido')));
             return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
         }
 
         $usuarioExistente = Usuario::obtenerUsuarioMail($data['mail']);
         if ($usuarioExistente) {
             $response->getBody()->write(json_encode(array('error' => 'El correo electrónico ya está en uso')));
             return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
         }
 
         $mapaSector = [
             'Bartender' => 1,
             'Cervecero' => 2,
             'Cocinero' => 3,
             'Mozo' => 4,
             'Socio' => 5,
         ];
         
         if (!isset($mapaSector[$data['sector']])) {
             $response->getBody()->write(json_encode(array('error' => 'Sector no válido')));
             return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
         }
 
         $nuevoUsuario = new Usuario();
         $nuevoUsuario->nombre = $data['nombre'];
         $nuevoUsuario->mail = $data['mail'];
         $nuevoUsuario->clave = password_hash($data['clave'], PASSWORD_DEFAULT);
         $nuevoUsuario->sector = $data['sector'];
         $nuevoUsuario->estado = 'Activo'; 
         $nuevoUsuario->idSector = $mapaSector[$data['sector']];
         $nuevoUsuario->idEstado = 1; // estado predeterminado 1 Activo
         $nuevoUsuario->fecha_ingreso = date('Y-m-d'); 
 
         try {
             $idNuevoUsuario = $nuevoUsuario->crearUsuario();
              $result = array('mensaje' => 'Usuario creado correctamente', 'idUsuario' => $idNuevoUsuario);
 
             $response->getBody()->write(json_encode($result));
             return $response->withHeader('Content-Type', 'application/json');
         } catch (Exception $e) {
             $response->getBody()->write(json_encode(array('error' => 'No se pudo crear el usuario')));
             return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
         }
     }


    public function BorrarUno($request, $response, $args)
    {

        $data = $request->getParsedBody();
        $idUsuario = isset($data['idUsuario']) ? $data['idUsuario'] : null;

        if ($idUsuario === null && isset($args['idUsuario'])) {
            $idUsuario = $args['idUsuario'];
        }

        $usuario = Usuario::obtenerUsuarioId($idUsuario);

        if (!$usuario) {
            $response->getBody()->write(json_encode(array('error' => 'Usuario no encontrado')));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }

        // Verifica si el usuario ya está en estado "Baja"
        if ($usuario->estado === 'Baja') {
            $response->getBody()->write(json_encode(array('error' => 'Error, el usuario fue dado de baja previamente')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        Usuario::borrarUsuario($idUsuario);

        $result = array('mensaje' => 'Usuario dado de baja correctamente');

        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }


    public function ModificarUno($request, $response, $args)
    {
    
        $data = $request->getParsedBody();
    
        if (!isset($data['idUsuario'])) {
            $response->getBody()->write(json_encode(array('error' => 'Falta el parámetro idUsuario')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }
    
        $idUsuario = $data['idUsuario'];
    
        $usuario = Usuario::obtenerUsuarioId($idUsuario);
    
        if (!$usuario) {
            $response->getBody()->write(json_encode(array('error' => 'Usuario no encontrado')));
            return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
        }
            $mapaSector = [
            'Bartender' => 1,
            'Cervecero' => 2,
            'Cocinero' => 3,
            'Mozo' => 4,
            'Socio' => 5,
        ];
    
        $mapaEstados = [
            'Activo' => 1,
            'Suspendido' => 2,
            'Baja' => 3,
        ];
    
        if (isset($data['sector'])) {
            if (!isset($mapaSector[$data['sector']])) {
                $response->getBody()->write(json_encode(array('error' => 'sector no válido')));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
            $usuario->sector = $data['sector'];
            $usuario->idSector = $mapaSector[$data['sector']];
        }
    
        if (isset($data['estado'])) {
            if (!isset($mapaEstados[$data['estado']])) {
                $response->getBody()->write(json_encode(array('error' => 'Estado no válido')));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }
    
            $usuario->estado = $data['estado'];
    
            if ($data['estado'] === 'Suspendido') {
                $fechaSalida = new DateTime();
                $usuario->fecha_salida = $fechaSalida->format('Y-m-d H:i:s');
            }
        }
    
        Usuario::modificarUsuario($usuario->estado, $usuario->idEstado, $idUsuario, $usuario->fecha_salida, $usuario->sector, $usuario->idSector);
    
        $result = array('mensaje' => 'Usuario modificado correctamente');
    
        $response->getBody()->write(json_encode($result));
        return $response->withHeader('Content-Type', 'application/json');
    }
    

    public function TraerTodos($request, $response, $args)
    {
        $conexionPDO = ConexionPDO::obtenerInstancia();
    
        try {
            $consulta = $conexionPDO->prepararConsulta("SELECT * FROM usuarios");
            $consulta->execute();

            $resultados = $consulta->fetchAll(PDO::FETCH_ASSOC);
    
            $response->getBody()->write(json_encode($resultados));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(array('error' => 'Error al obtener los elementos')));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }

    public function TraerUno($request, $response, $args)
    {
        $conexionPDO = ConexionPDO::obtenerInstancia();

        $queryParams = $request->getQueryParams();

        $idUsuario = isset($queryParams['idUsuario']) ? $queryParams['idUsuario'] : null;
        $mail = isset($queryParams['mail']) ? $queryParams['mail'] : null;

        if ($idUsuario === null && $mail === null) {
            $response->getBody()->write(json_encode(array('error' => 'Se debe proporcionar al menos un parámetro: "idUsuario" o "mail"')));
            return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
        }

        try {
            if ($idUsuario !== null) {
                $consulta = $conexionPDO->prepararConsulta("SELECT * FROM usuarios WHERE idUsuario = :idUsuario");
                $consulta->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
            } elseif ($mail !== null) {
                $consulta = $conexionPDO->prepararConsulta("SELECT * FROM usuarios WHERE mail = :mail");
                $consulta->bindValue(':mail', $mail, PDO::PARAM_STR);
            } else {
                $response->getBody()->write(json_encode(array('error' => 'Se debe proporcionar al menos un parámetro: "idUsuario" o "mail"')));
                return $response->withStatus(400)->withHeader('Content-Type', 'application/json');
            }

            $consulta->execute();

            $resultado = $consulta->fetch(PDO::FETCH_ASSOC);

            if (!$resultado) {
                $response->getBody()->write(json_encode(array('error' => 'Usuario no encontrado')));
                return $response->withStatus(404)->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode($resultado));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (PDOException $e) {
            $response->getBody()->write(json_encode(array('error' => 'Error al obtener el elemento')));
            return $response->withStatus(500)->withHeader('Content-Type', 'application/json');
        }
    }
 
}

?>
