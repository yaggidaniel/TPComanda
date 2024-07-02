<?php 



class FileManager {
    private $ruta_archivo;

    public function __construct($ruta_archivo) {
        $this->ruta_archivo = $ruta_archivo;
    }

    public function crearArchivoJSON($datos) {
        $contenido_json = json_encode($datos, JSON_PRETTY_PRINT);
        return file_put_contents($this->ruta_archivo, $contenido_json);
    }

    public function leerContenidoJSON($ruta_archivo) {
        if (file_exists($ruta_archivo)) {
            $contenido_json = file_get_contents($ruta_archivo);
            return json_decode($contenido_json, true);
        }
        return []; 
    }

    public function contarElementosYGenerarID($ruta_archivo) {
        $datos = $this->leerContenidoJSON($ruta_archivo);
        $numElementos = count($datos);
        return $numElementos + 1;
    }

    public function actualizarArchivoJSON($datos, $ruta_archivo) {
        $contenido_json = json_encode($datos, JSON_PRETTY_PRINT);
        
        if (!file_exists($this->ruta_archivo)) {
            $this->crearArchivoJSON([]);
        }
    
        $resultado = file_put_contents($this->ruta_archivo, $contenido_json);
        
        if ($resultado === false) {
            echo "Error al actualizar el archivo JSON.";
            return false;
        }
        
        return true;
    }

    public function obtenerProximoID() {
        $contenido_json = file_get_contents($this->ruta_archivo);
        $datos = json_decode($contenido_json, true);
        $proximo_id = count($datos) + 1;
        return $proximo_id;
    }


    public static function createCSV($data)
    {
        $output = fopen('php://temp', 'r+');

        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]));
        }

        foreach ($data as $row) {
            fputcsv($output, $row);
        }

        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);

        return $csv;
    }



}

?>