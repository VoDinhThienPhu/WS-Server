<?php
namespace WorkSpace\Service;
use Exception;

use WorkSpace\Model\User;

class UserService
{
   public function createUser($data)
   {
      $requiredFields = [
         'username' => 'Password is required',
         'email' => 'Email is required',
         'password' => 'Password is required'
      ];

      foreach ($requiredFields as $field => $message) {
         if (empty($data[$field])) {
            throw new Exception($message);
         }
      }

      $exists = User::where('Username', $data['username'])
         ->orWhere('Email', $data['email'])
         ->exists();

      if ($exists) {
         $field = User::where('Username', $data['username'])->exists()
            ? 'Username' : 'Email';
         throw new Exception($field . ' already exists');
      }

      return User::create([
         "Username" => $data["username"],
         "Email" => $data["email"],
         "DisplayName" => $data["displayName"],
         "Password" => $data["password"],
      ]);
   }

   public function findUser($field, $value)
   {
      $user = User::where($field, $value)
         ->where('IsDeleted', false)
         ->first();

      if (!$user) {
          return ["message" => "User not found"];
      }

      return $user;
   }
}