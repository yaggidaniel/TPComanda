<?php

use Slim\Psr7\UploadedFile;

class ImageManager {
    private $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
    private $maxSizeInBytes = 5 * 1024 * 1024; // 5 MB

    public function validateAndMoveImage(UploadedFile $uploadedFile, $rutaImagen, $nombreImagen) {
        $error = $uploadedFile->getError();
        $fileName = $uploadedFile->getClientFilename();
        $fileSize = $uploadedFile->getSize();
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $newFileName = $rutaImagen . DIRECTORY_SEPARATOR . $nombreImagen . '.' . $fileExtension;

        if ($error !== UPLOAD_ERR_OK) {
            switch ($error) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $errorMessage = 'El archivo supera el tamaño máximo permitido.';
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $errorMessage = 'La subida del archivo se interrumpió.';
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $errorMessage = 'No se seleccionó ningún archivo.';
                    break;
                case UPLOAD_ERR_NO_TMP_DIR:
                    $errorMessage = 'Falta una carpeta temporal para la subida.';
                    break;
                case UPLOAD_ERR_CANT_WRITE:
                    $errorMessage = 'No se pudo escribir el archivo en el disco.';
                    break;
                default:
                    $errorMessage = 'Error desconocido durante la subida.';
            }
            return ['error' => $errorMessage];
        }

        if (!in_array($fileExtension, $this->allowedExtensions)) {
            $errorMessage = 'Solo se permiten extensiones: ' . implode(', ', $this->allowedExtensions);
            return ['error' => $errorMessage];
        }

        if ($fileSize > $this->maxSizeInBytes) {
            $errorMessage = 'El archivo supera el tamaño máximo permitido (' . $this->maxSizeInBytes . ' bytes).';
            return ['error' => $errorMessage];
        }

        try {
            $uploadedFile->moveTo($newFileName);
            return ['success' => true, 'fileName' => $newFileName];
        } catch (\Exception $e) {
            $errorMessage = 'Error al mover el archivo temporal: ' . $e->getMessage();
            return ['error' => $errorMessage];
        }
    }
}
?>
