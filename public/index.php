
<?php

// Error Handling
error_reporting(-1);
ini_set('display_errors', 1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;
use Slim\Routing\RouteContext;

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../controllers/UsuarioController.php';
require_once __DIR__ . '/../controllers/ProductosController.php';
require_once __DIR__ . '/../controllers/ComandaController.php';
require_once __DIR__ . '/../controllers/ClienteController.php';


// Instantiate App
$app = AppFactory::create();

$app->setBasePath('/public');

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add parse body
$app->addBodyParsingMiddleware();


$usuarioController = new UsuarioController();

$productoController = new ProductoController();

$clienteController = new ClienteController();

$comandaController = new ComandaController();


$app->get('[/]', function (Request $request, Response $response) {    
    $payload = json_encode(array("mensaje" => "Funciona"));
    
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->group('/api', function (RouteCollectorProxy $group) 
use ($usuarioController, $productoController, $comandaController, $clienteController) {
  
    // Define las rutas de usuarioController
    $group->get('/usuario/{idUsuario}', [$usuarioController, 'TraerUno']);
    $group->get('/usuarios', [$usuarioController, 'TraerTodos']);
    $group->post('/dar-de-alta-usuario', [$usuarioController, 'CargarUno']);
    $group->post('/modificar', [$usuarioController, 'ModificarUno']);
    $group->post('/dar-de-baja', [$usuarioController, 'BorrarUno']);


    // Define las rutas de productoController
    $group->get('/producto/{idProducto}', [$productoController, 'TraerUno']);
    $group->get('/productos', [$productoController, 'TraerTodos']);
    $group->post('/agregar-producto', [$productoController, 'CargarUno']);
    $group->post('/modificar-producto', [$productoController, 'ModificarUno']);
    $group->post('/borrar-producto/{idProducto}', [$productoController, 'BorrarUno']); 

    // Rutas del ClienteController
    $group->post('/cliente/cargar', [$clienteController, 'CargarUno']);
    $group->get('/cliente/{idCliente}', [$clienteController, 'TraerUno']);
    $group->get('/clientes', [$clienteController, 'TraerTodos']);
    $group->post('/cliente/modificar', [$clienteController, 'ModificarUno']);
    $group->post('/cliente/borrar', [$clienteController, 'BorrarUno']);

});

// el entrypoint de la aplicacion
$app->run();

?>

