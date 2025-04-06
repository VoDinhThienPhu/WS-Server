<?php
namespace WorkSpace\Controller;

use WorkSpace\Service\TeamService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;
use Illuminate\Http\Response;

class TeamController
{
    protected $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
    }

    public function getTeamsByIDUser(Request $request): JsonResponse
    {
        try {
            $IDUser = $request->attributes->get('USER')['IDUser'];
            $teams = $this->teamService->getAllTeams($IDUser);
            
            return new JsonResponse([
                'message' => 'Team List',
                'data' => $teams
            ], 200);
        } catch (Exception $e) {
            return new JsonResponse([
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    // Tạo một team mới
   public function createTeam(Request $request): JsonResponse
   {
      try {
         $IDLeader = $request->get('USER')['IDUser'];
        //  print_r($IDLeader);
         $team = $this->teamService->createTeam($request->json()->all(), $IDLeader);
         return new JsonResponse([
            'message' => 'Created Team Successfully',
            'data' => $team
         ], 201);
      } catch (Exception $e) {
         return new JsonResponse([
            'message' => $e->getMessage(),
         ], 400);
      }
   }
   public function leaveTeam(Request $request, $IDTeam): JsonResponse
   {
       try {
           $IDUser = $request->attributes->get('USER')['IDUser'];
           $this->teamService->leaveTeam($IDUser, $IDTeam);

           return new JsonResponse([
               'message' => 'You have successfully left the group.!!!!',
           ], 200);
       } catch (Exception $e) {
           return new JsonResponse([
               'message' => $e->getMessage(),
           ], 400);
       }
   }

    public function deleteTeam(Request $request, $IDTeam): JsonResponse
    {
        try {
            $IDUser = $request->attributes->get('USER')['IDUser'];
            // print_r($IDTeam);
            // print_r($IDUser);

            if (!$IDTeam || !$IDUser) {
                return new JsonResponse([
                    'success' => false,
                    'message' => 'Missing required parameters'
                ], 400);
            }

            $result = $this->teamService->deleteTeam($IDTeam, $IDUser);

            return new JsonResponse([
                'success' => true,
                'message' => 'Team deleted successfully'
            ]);

        } catch (Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }

}