<?php
namespace WorkSpace\Controller;
use WorkSpace\Service\AuthService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Exception;

class AuthController
{
   private AuthService $AuthService;

   public function __construct(AuthService $AuthService)
   {
      $this->AuthService = $AuthService;
   }

   public function Register(Request $request): JsonResponse
   {
      try {
         $user = $this->AuthService->registerAccount($request->json()->all());
         return new JsonResponse([
            'message' => 'Register Successfully',
            'data' => $user
         ], 201);
      } catch (Exception $e) {
         return new JsonResponse([
            'message' => $e->getMessage(),
         ], 400);
      }
   }

   public function Login(Request $request): JsonResponse
   {
      try {
         $credentials = $request->json()->all();
         $result = $this->AuthService->login($credentials);

         return new JsonResponse([
            'message' => 'Login Successfully',
            'data' => $result
         ], 200);
      } catch (Exception $e) {
         return new JsonResponse([
            'message' => $e->getMessage(),
         ], 400);
      }
   }

   public function RefreshToken(Request $request): JsonResponse
   {
      try {
         $refreshToken = $request->json('refresh_token');
         $result = $this->AuthService->refreshAccessToken($refreshToken);

         return new JsonResponse([
            'message' => 'Get new access token successfully',
            'data' => $result
         ], 200);
      } catch (Exception $e) {
         return new JsonResponse([
            'message' => $e->getMessage(),
         ], 401);
      }
   }

}