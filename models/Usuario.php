<?php 

require_once __DIR__ . '/../db/ConexionPDO.php';
require_once __DIR__ . '/../controllers/UsuarioController.php';
require_once __DIR__ . '/../middlewares/AuthJWT.php';



class Usuario {
    
    public $idUsuario;
    public $nombre;
    public $mail;
    public $clave;
    public $sector;
    public $estado;
    public $idSector;
    public $idEstado;
    public $fecha_ingreso;
    public $fecha_salida;

    public function GetIdPuesto() {
        return $this->idSector;
    }

    public function GetPuesto() {
        return $this->sector;
    }

    public function GetIdUsuario() {
        return $this->idUsuario;
    }

    public function crearUsuario()
    {
        $usuarioExistente = self::obtenerUsuarioMail($this->mail);
        if ($usuarioExistente) {
            throw new Exception("El usuario con correo {$this->mail} ya existe.");
        }

        $objConexionPDO = ConexionPDO::obtenerInstancia();
        $consulta = $objConexionPDO->prepararConsulta("INSERT INTO 
            usuarios (idUsuario, nombre, mail, clave, puesto, estado, idEstado, idPuesto, fecha_ingreso, fecha_salida) 
            VALUES 
            (:idUsuario, :nombre, :mail, :clave, :puesto, :estado, :idEstado, :idPuesto, :fecha_ingreso, :fecha_salida)");

        $consulta->bindValue(':idUsuario', $this->idUsuario, PDO::PARAM_INT);
        $consulta->bindValue(':nombre', $this->nombre, PDO::PARAM_STR);
        $consulta->bindValue(':mail', $this->mail, PDO::PARAM_STR);
        $consulta->bindValue(':clave', $this->clave, PDO::PARAM_INT);
        $consulta->bindValue(':puesto', $this->sector, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $this->estado, PDO::PARAM_STR);
        $consulta->bindValue(':idPuesto', $this->idSector, PDO::PARAM_INT);
        $consulta->bindValue(':idEstado', $this->idEstado, PDO::PARAM_INT);
        $consulta->bindValue(':fecha_ingreso', $this->fecha_ingreso, PDO::PARAM_STR);
        $consulta->bindValue(':fecha_salida', $this->fecha_salida, PDO::PARAM_STR);
        $consulta->execute();

        return $objConexionPDO->obtenerUltimoId();
    }

    public static function obtenerTodos() {
        $objConexionPDO = ConexionPDO::obtenerInstancia();
        $consulta = $objConexionPDO->prepararConsulta("SELECT 
        idUsuario, nombre, mail, clave, puesto, estado, idEstado, idPuesto, fecha_ingreso, fecha_salida 
        FROM 
        usuarios");
        $consulta->execute();

        return $consulta->fetchAll(PDO::FETCH_CLASS, 'Usuario');
    }

    public static function obtenerUsuarioMail($mail) {
        $objConexionPDO = ConexionPDO::obtenerInstancia();
        $consulta = $objConexionPDO->prepararConsulta("SELECT 
        idUsuario, nombre, mail, clave, puesto, estado, idEstado, idPuesto, fecha_ingreso, fecha_salida 
        FROM usuarios 
        WHERE mail = :mail");
        $consulta->bindValue(':mail', $mail, PDO::PARAM_STR);
        $consulta->execute();

        return $consulta->fetchObject('Usuario');
    }

    public static function obtenerUsuarioId($idUsuario) {
        $objConexionPDO = ConexionPDO::obtenerInstancia();
        $consulta = $objConexionPDO->prepararConsulta("SELECT idUsuario, nombre, mail, clave, puesto, estado, idPuesto, idEstado, fecha_ingreso, fecha_salida FROM usuarios WHERE idUsuario = :idUsuario");
        $consulta->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $consulta->execute();

        return $consulta->fetchObject('Usuario');
    }

    public static function modificarUsuario($estado, $numEstado, $idUsuario, $fecha_salida, $puesto, $idPuesto) {
        $objConexionPDO = ConexionPDO::obtenerInstancia();

        $consultaBase = "UPDATE usuarios SET estado = :estado, idEstado = :numEstado";

        if ($fecha_salida !== null) {
            $consultaBase .= ", fecha_salida = :fecha_salida";
        }

        if ($puesto !== null && $idPuesto !== null) {
            $consultaBase .= ", puesto = :puesto, idPuesto = :idPuesto";
        }

        $consultaBase .= " WHERE idUsuario = :idUsuario";

        $consulta = $objConexionPDO->prepararConsulta($consultaBase);

        $consulta->bindValue(':estado', $estado, PDO::PARAM_STR);
        $consulta->bindValue(':numEstado', $numEstado, PDO::PARAM_INT);
        $consulta->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);

        if ($fecha_salida !== null) {
            $consulta->bindValue(':fecha_salida', $fecha_salida, PDO::PARAM_STR);
        }

        if ($puesto !== null && $idPuesto !== null) {
            $consulta->bindValue(':puesto', $puesto, PDO::PARAM_STR);
            $consulta->bindValue(':idPuesto', $idPuesto, PDO::PARAM_INT);
        }

        $consulta->execute();
    }


    public static function borrarUsuario($idUsuario) {
        $fechaSalida = date('Y-m-d H:i:s');
        $estado = 'Baja';
        $idEstado = 3;

        $objConexionPDO = ConexionPDO::obtenerInstancia();
        $consulta = $objConexionPDO->prepararConsulta("UPDATE 
        usuarios 
        SET 
        fecha_salida = :fechaSalida, estado = :estado, idEstado = :idEstado 
        WHERE idUsuario = :idUsuario");
        $consulta->bindValue(':idUsuario', $idUsuario, PDO::PARAM_INT);
        $consulta->bindValue(':fechaSalida', $fechaSalida, PDO::PARAM_STR);
        $consulta->bindValue(':estado', $estado, PDO::PARAM_STR);
        $consulta->bindValue(':idEstado', $idEstado, PDO::PARAM_INT);
        $consulta->execute();
    }

    public static function ObtenerUnUsuarioPorPuesto($puesto) {
        $lista = self::obtenerTodos();
        $array = array();
        foreach ($lista as $aux) {
            if ($aux->GetIdPuesto() == $puesto) {
                array_push($array, $aux);
            }
        }
        $random = random_int(1, count($array));
        if ($random == count($array))
            return $array[$random - 1]->GetIdUsuario();
        else
            return $array[$random]->GetIdUsuario();
    }

    public function loginUsuario($mail, $contrasena) {
        $conexion = ConexionPDO::obtenerInstancia();

        $consultaExistencia = $conexion->prepararConsulta("SELECT * FROM usuarios WHERE mail = :usuario LIMIT 1");
        $consultaExistencia->bindValue(':usuario', $mail, PDO::PARAM_STR);
        $consultaExistencia->execute();
        $usuario = $consultaExistencia->fetch(PDO::FETCH_ASSOC);

        if (!$usuario || !password_verify($contrasena, $usuario['clave'])) {
            throw new Exception("Credenciales inválidas");
        }

        $datosToken = array(
            'id' => $usuario['idUsuario'],
            'mail' => $usuario['mail'],
            'perfil' => $usuario['puesto'],
            'peso' => $usuario['idPuesto'],

        );

        $token = AuthJWT::createToken($datosToken);

        return $token;
    }
    



}



?>