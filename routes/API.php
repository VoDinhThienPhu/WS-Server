<?php

use Illuminate\Routing\Router;
use WorkSpace\Controller\AuthController;
use WorkSpace\Controller\SystemController;
use WorkSpace\Controller\WorkSpaceController;
use WorkSpace\Controller\TeamController;
use WorkSpace\Controller\StatusController;
use WorkSpace\Controller\ProjectController;
use WorkSpace\Controller\NoteController;
use WorkSpace\Controller\WidgetController;
use WorkSpace\Controller\TaskController;
use Middleware\Authenticate;

return function (Router $router) {
   $router->aliasMiddleware('auth', Authenticate::class);

   //--------------------------------------------------HOME--------------------------------------------------//

   $router->group(['prefix' => '/'], function (Router $router) {
      $router->get('/', [SystemController::class, 'root']);
      $router->post('/upload-file-s3', [SystemController::class, 'uploadFileToS3']);
      $router->get('/user-inf', [SystemController::class, 'getUserInfoFromRequest'])->middleware('auth');
   });

   //--------------------------------------------------AUTH--------------------------------------------------//

   $router->group(['prefix' => 'auth'], function (Router $router) {
      $router->post('/register', [AuthController::class, 'Register']);
      $router->post('/login', [AuthController::class, 'Login']);
      $router->post('/refresh-token', [AuthController::class, 'RefreshToken']);
   });

   //--------------------------------------------------WORKSPACE--------------------------------------------------//

   $router->group(['prefix' => 'workspace', 'middleware' => 'auth'], function (Router $router) {
      $router->get('/', [WorkSpaceController::class, 'getWorkSpacesByIDUser']);
      $router->post('/', [WorkSpaceController::class, 'createWorkSpace']);
      $router->get('/{IDWorkSpace}', [WorkSpaceController::class, 'getWorkSpacesByIDWorkSpace']);
      $router->delete('/{id}', [WorkSpaceController::class, 'deleteWorkSpace']);
   });

   //--------------------------------------------------NOTE--------------------------------------------------//
   
   $router->group(['prefix' => 'note', 'middleware' => 'auth'], function (Router $router) {
      $router->post('/',[NoteController::class,'createNotewithWidget']);
      $router->put('/modify/{IDNote}', [NoteController::class, 'ModifyNote']);
   });

   //--------------------------------------------------PROJECT--------------------------------------------------//

   $router->group(['prefix' => 'project', 'middleware' => 'auth'], function (Router $router) {
      $router->post('/add', [ProjectController::class, 'createNewProject']);
   });

   //--------------------------------------------------WIDGET--------------------------------------------------//

   $router->group(['prefix' => 'widget', 'middleware' => 'auth'], function (Router $router) {
      $router->get('/{IDWorkSpace}', [WidgetController::class, 'GetAllWidgets']);
      $router->put('/{IDWorkSpace}/{IDWidget}', [WidgetController::class, 'ModifyWidget']);
   });

   //--------------------------------------------------TEAM--------------------------------------------------//

   $router->group(['prefix'=> 'team','middleware' => 'auth'], function (Router $router) {
        $router->get('/', [TeamController::class, 'getTeamsByIDUser']);
        $router->post('/', [TeamController::class, 'createTeam']);
        $router->delete('/{IDTeam}', [TeamController::class,'leaveTeam']);
        $router->delete('/delete/{IDTeam}', [TeamController::class,'deleteTeam']);
      });  

//--------------------------------------------------STASTUS--------------------------------------------------//   

   $router->group(['prefix'=> 'status','middleware' => 'auth'], function (Router $router) {
        $router->get('/', [StatusController::class, 'getAllStatuses']);
        $router->post('/create', [StatusController::class, 'createStatus']);
        $router->delete('/{IDStatus}', [StatusController::class, 'deleteStatus']);
      });
 //--------------------------------------------------TASK--------------------------------------------------//   

      $router->group(['prefix'=> 'task','middleware' => 'auth'], function (Router $router) {
         $router->get('/', [TaskController::class, 'getAllTasks']);
         $router->post('/create', [TaskController::class, 'createTask']);
         $router->delete('/{IDTask}', [TaskController::class, 'deleteTask']);
       });     

  //TODO
   //--------------------------------------------------STASTUS--------------------------------------------------//   

   $router->group(['prefix' => 'status', 'middleware' => 'auth'], function (Router $router) {
      $router->get('/', [StatusController::class, 'getAllStatuses']);
      $router->post('/create', [StatusController::class, 'createStatus']);
   });
};
