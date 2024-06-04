<?php
class ConexionPDO
{
    private static $objConexionPDO;
    private $objetoPDO;

    private function __construct()
    {
        try {
            // Crear una instancia de PDO para conectarse a la base de datos MySQL
            $this->objetoPDO = new PDO(
                'mysql:host=localhost;dbname=tp_la_comanda;charset=utf8mb4',
                'root',
                '',
                array(
                    PDO::ATTR_EMULATE_PREPARES => false,  // Evitar la emulación de consultas preparadas
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION  // Habilitar el manejo de errores
                )
            );
            $this->objetoPDO->exec("SET CHARACTER SET utf8");  // Establecer el conjunto de caracteres
        } catch (PDOException $e) {
            print "Error: " . $e->getMessage();
            die();
        }
    }

    public static function obtenerInstancia()
    {
        if (!isset(self::$objConexionPDO)) {
            self::$objConexionPDO = new ConexionPDO();
        }
        return self::$objConexionPDO;
    }

    public function prepararConsulta($sql)
    {
        // Preparar una consulta SQL y devolverla
        return $this->objetoPDO->prepare($sql);
    }

    public function obtenerUltimoId()
    {
        // Obtener el último ID insertado en la base de datos
        return $this->objetoPDO->lastInsertId();
    }

    public function __clone()
    {
        // Evitar la clonación de este objeto
        trigger_error('ERROR: La clonación de este objeto no está permitida', E_USER_ERROR);
    }
}
?>