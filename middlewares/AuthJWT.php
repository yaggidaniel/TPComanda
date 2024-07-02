<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

if (!class_exists('Firebase\JWT\JWT')) {
    die('La librería Firebase JWT no se cargó correctamente.'); // es la librería
}

class AuthJWT {

    private static string $secretKey = 'clave';
    private static string $encryptionAlgorithm = 'HS256';
    private const EXPIRATION_TIME = 30000; // 5 minutos en segundos

    public static function createToken($data) {
        $now = time();
        $payload = array(
            'iat' => $now,                                          // Current timestamp
            'exp' => $now + self::EXPIRATION_TIME,                  // Expiración: actual + constante
            'data' => $data,                                        // Datos a almacenar en el token
        );
        return JWT::encode($payload, self::$secretKey, self::$encryptionAlgorithm);  // Codifica el payload usando la clave secreta
    }

    public static function verifyToken($token) {
        if (empty($token) || $token == "") {
            throw new Exception("El token está vacío");
        } else {
            try {
                // Decodifica el token usando la clave secreta y el mismo algoritmo
                $decoded = JWT::decode($token, new Key(self::$secretKey, self::$encryptionAlgorithm));
    
            } catch (Exception $e) {
                throw $e;  // Si la decodificación falla, arroja una excepción
            }
        }
        return $decoded;  // Devuelve el token decodificado
    }

    // Obtiene el payload de un token JWT
    public static function getPayload($token) {
        if (empty($token) || $token == "") {
            throw new Exception("El token está vacío");
        }
        return JWT::decode($token, new Key(self::$secretKey, self::$encryptionAlgorithm));  // Decodifica el token y devuelve el payload
    }

    // Obtiene los datos almacenados en un token JWT
    public static function getData($token) {
        return JWT::decode($token, new Key(self::$secretKey, self::$encryptionAlgorithm))->data;  // Decodifica el token y devuelve los datos
    }
}
?>
