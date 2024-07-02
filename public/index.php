
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
require_once __DIR__ . '/../controllers/MesaController.php';
require_once __DIR__ . '/../controllers/EncuestaController.php';


require_once __DIR__ . '/../middlewares/UsuariosMiddleware.php';


// Instantiate App
$app = AppFactory::create();

$app->setBasePath('/public');

// Add error middleware
$app->addErrorMiddleware(true, true, true);

// Add parse body
$app->addBodyParsingMiddleware();


$usuarioController = new UsuarioController();

$productoController = new ProductoController();

$mesaController = new MesaController();

$comandaController = new ComandaController();

$encuestaController = new EncuestaController();


$app->get('[/]', function (Request $request, Response $response) {    
    $payload = json_encode(array("mensaje" => "Funciona"));
    $response->getBody()->write($payload);
    return $response->withHeader('Content-Type', 'application/json');
});

$app->group('/api', function (RouteCollectorProxy $group) use ($usuarioController, $productoController, $comandaController, $mesaController, $encuestaController) {

    $group->group('', function (RouteCollectorProxy $group) use ($usuarioController) {
        $group->get('/usuario/{idUsuario}', [$usuarioController, 'TraerUno']);
        $group->get('/usuarios', [$usuarioController, 'TraerTodos']);
        $group->post('/dar-de-alta-usuario', [$usuarioController, 'CargarUno']);
        $group->post('/modificar', [$usuarioController, 'ModificarUno']);
        $group->post('/dar-de-baja', [$usuarioController, 'BorrarUno']);
    })->add(new UsuariosMiddleware(5));

    $group->post('/login', [$usuarioController, 'loginUsuarioController']);

    $group->group('', function (RouteCollectorProxy $group) use ($productoController) {
        $group->get('/producto/{idProducto}', [$productoController, 'TraerUno']);
        $group->get('/productos', [$productoController, 'TraerTodos']);
        $group->post('/agregar-producto', [$productoController, 'CargarUno']);
    })->add(new UsuariosMiddleware(4));

    $group->post('/modificar-producto', [$productoController, 'ModificarUno'])->add(new UsuariosMiddleware(5));
    $group->post('/borrar-producto/{idProducto}', [$productoController, 'BorrarUno'])->add(new UsuariosMiddleware(5));

    $group->group('', function (RouteCollectorProxy $group) use ($mesaController) {
        $group->post('/mesa/cargar', [$mesaController, 'CargarUno']);
        $group->post('/mesa/borrar', [$mesaController, 'BorrarUno']);
        $group->get('/mesa/mesaMasUsada', [$mesaController, 'obtenerMesaMasUsadaController']);

        
    })->add(new UsuariosMiddleware(5));

    $group->group('', function (RouteCollectorProxy $group) use ($mesaController) {
    $group->get('/mesa', [$mesaController, 'TraerUno']);
    $group->get('/todasLasMesas', [$mesaController, 'TraerTodos']);
    $group->post('/mesa/modificar', [$mesaController, 'ModificarUno']);
    })->add(new UsuariosMiddleware(4));


    $group->group('', function (RouteCollectorProxy $group) use ($comandaController) {
        $group->post('/comanda/cargar', [$comandaController, 'CargarUno']);
        $group->post('/comanda/agregarProducto', [$comandaController, 'agregarProductoAComanda']);
        $group->get('/comanda/comandaConProductos', [$comandaController, 'obtenerUnPedidoConProductos']);
        $group->post('/comanda/relacionarFotoEnComanda', [$comandaController, 'relacionarFotoEnComanda']);
        $group->get('/comanda/TraerTodosLosPedidos', [$comandaController, 'TraerTodos']); 
        $group->get('/comanda/TraerPedidosListos', [$comandaController, 'TraerPedidosListosParaServir']); 
        $group->post('/comanda/cobrarComanda', [$comandaController, 'cobrarComanda']); 

    })->add(new UsuariosMiddleware(4));


    $group->group('', function (RouteCollectorProxy $group) use ($comandaController) {
        $group->post('/comanda/listarPedidosPorSector', [$comandaController, 'listarPedidosPorSector']);
        $group->post('/comanda/obtenerProductosPorEstadoPorSector', [$comandaController, 'obtenerProductosPorEstadoPorSector']);   
        $group->post('/comanda/cambiarEstadoYTiempoPreparacion', [$comandaController, 'setEstadoYTiempoPreparacion']);   

    })->add(new UsuariosMiddleware(1));

    $group->group('/comanda', function (RouteCollectorProxy $group) use ($comandaController, $encuestaController) {
        $group->post('/cliente/obtenerTiempoEspera', [$comandaController, 'obtenerTiempoEspera']);
        $group->post('/cliente/contestarEncuesta', [$encuestaController, 'altaEncuestaController']);
    });

    $group->group('/encuestas', function (RouteCollectorProxy $group) use ($encuestaController) {
        $group->get('/todasLasEncuestas', [$encuestaController, 'obtenerTodasLasEncuestas']);
        $group->get('/comentariosEncuestas', [$encuestaController, 'obtenerComentariosEncuestas']);
    })->add(new UsuariosMiddleware(5));


    $group->group('', function (RouteCollectorProxy $group) use ($comandaController) {
        $group->get('/comanda/descargarComandas', [$comandaController, 'descargarComandasCSVController']);

    });


    $group->group('', function (RouteCollectorProxy $group) use ($comandaController) {
        $group->get('/comanda/pedidosPorTiempo', [$comandaController, 'obtenerPedidosPorTiempoController']);

    })->add(new UsuariosMiddleware(5));
    
    
      

    
    

});




// el entrypoint de la aplicacion
$app->run();

?>

