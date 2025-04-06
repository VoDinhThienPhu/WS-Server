<?php
namespace WorkSpace\Controller;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Aws\S3\S3Client;
use Aws\Exception\AwsException;
use Utils\EnvironmentVariable;

class SystemController
{
   private $s3Client;
   private $allowedMimeTypes = [
      // Images
      'image/jpeg' => ['jpg', 'jpeg'],
      'image/png' => ['png'],
      'image/gif' => ['gif'],
      'image/webp' => ['webp'],
      // Documents
      'application/pdf' => ['pdf'],
      'application/msword' => ['doc'],
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => ['docx'],
      'application/vnd.ms-excel' => ['xls'],
      'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
      // Archives
      'application/zip' => ['zip'],
      'application/x-rar-compressed' => ['rar'],
      // Audio
      'audio/mpeg' => ['mp3'],
      'audio/wav' => ['wav'],
      // Video
      'video/mp4' => ['mp4'],
      'video/quicktime' => ['mov'],
      // Text
      'text/plain' => ['txt'],
      'text/csv' => ['csv']
   ];

   private $maxFileSize = 100 * 1024 * 1024; // 100MB

   public function __construct()
   {
      $this->s3Client = new S3Client([
         'version' => 'latest',
         'region' => EnvironmentVariable::get('S3.REGION'),
         'credentials' => [
            'key' => EnvironmentVariable::get('S3.ACCESS_KEY'),
            'secret' => EnvironmentVariable::get('S3.SECRET_KEY'),
         ],
         'endpoint' => EnvironmentVariable::get('S3.ENDPOINT'),
         'use_path_style_endpoint' => true
      ]);
   }

   /**
    * Helper function để tạo CloudFront URL từ tên file
    */
   private function getCloudFrontUrl(string $fileName): string
   {
      return sprintf(
         '%s/%s',
         rtrim(EnvironmentVariable::get('CLOUDFRONT.DOMAIN'), '/'),
         $fileName
      );
   }

   /**
    * Kiểm tra file có hợp lệ không
    */
   private function validateFile($file): array
   {
      // Kiểm tra dung lượng
      if ($file->getSize() > $this->maxFileSize) {
         return [
            'valid' => false,
            'message' => 'File quá lớn. Giới hạn là ' . ($this->maxFileSize / 1024 / 1024) . 'MB'
         ];
      }

      // Kiểm tra loại file
      $mimeType = $file->getMimeType();
      if (!isset($this->allowedMimeTypes[$mimeType])) {
         return [
            'valid' => false,
            'message' => 'Loại file không được hỗ trợ'
         ];
      }

      return ['valid' => true];
   }

   /**
    * Tạo tên file an toàn và duy nhất
    */
   private function generateSafeFileName($file): string
   {
      $originalName = $file->getClientOriginalName();
      $extension = pathinfo($originalName, PATHINFO_EXTENSION);
      $timestamp = time();
      $randomString = substr(md5(uniqid()), 0, 8);

      // Chuyển tên file thành slug an toàn
      $baseName = pathinfo($originalName, PATHINFO_FILENAME);
      $baseName = preg_replace('/[^a-z0-9]+/', '-', strtolower($baseName));
      $baseName = trim($baseName, '-');

      // Tạo đường dẫn theo loại file và ngày
      $mimeType = $file->getMimeType();
      $fileType = explode('/', $mimeType)[0]; // image, video, application, etc.
      $datePath = date('Y/m/d');

      return sprintf(
         '%s/%s/%s-%s-%s.%s',
         $fileType,
         $datePath,
         $baseName,
         $timestamp,
         $randomString,
         $extension
      );
   }

   public function root()
   {
      return new JsonResponse([
         'data' => '🚀 WS-Server is running 🚀',
         'message' => 'Hello World'
      ], 200);
   }

   public function getUserInfoFromRequest(Request $request): JsonResponse
   {
      $user = $request->get('USER');
      $IDUser = $request->get('USER')['IDUser'];
      return new JsonResponse([
         'message' => 'User Inf',
         'user' => $user,
         'IDUser' => $IDUser
      ], 200);
   }

   public function uploadFileToS3(Request $request): JsonResponse
   {
      try {
         if (!$request->hasFile('file')) {
            return new JsonResponse([
               'message' => 'Không tìm thấy file để upload',
               'success' => false
            ], 400);
         }

         $file = $request->file('file');

         // Validate file
         $validation = $this->validateFile($file);
         if (!$validation['valid']) {
            return new JsonResponse([
               'message' => $validation['message'],
               'success' => false
            ], 400);
         }

         // Tạo tên file an toàn
         $fileName = $this->generateSafeFileName($file);

         // Upload file lên S3
         $result = $this->s3Client->putObject([
            'Bucket' => EnvironmentVariable::get('S3.BUCKET'),
            'Key' => $fileName,
            'Body' => fopen($file->getPathname(), 'rb'),
            'ContentType' => $file->getMimeType()
         ]);

         // Tạo response với thông tin file
         $fileInfo = [
            'fileName' => $fileName,
            'originalName' => $file->getClientOriginalName(),
            'contentType' => $file->getMimeType(),
            'size' => $file->getSize(),
            'uploadDate' => date('Y-m-d H:i:s'),
            'fileType' => explode('/', $file->getMimeType())[0]
         ];

         return new JsonResponse([
            'message' => 'Upload file success',
            'success' => true,
            'data' => [
               'url' => $this->getCloudFrontUrl($fileName),
               'file' => $fileInfo
            ]
         ], 200);

      } catch (AwsException $e) {
         return new JsonResponse([
            'message' => 'Lỗi khi upload file: ' . $e->getMessage(),
            'success' => false
         ], 500);
      } catch (\Exception $e) {
         return new JsonResponse([
            'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
            'success' => false
         ], 500);
      }
   }

   public function getFileFromS3(Request $request): JsonResponse
   {
      try {
         $fileName = $request->get('fileName');

         if (!$fileName) {
            return new JsonResponse([
               'message' => 'Không tìm thấy tên file',
               'success' => false
            ], 400);
         }

         // Lấy thông tin file từ S3
         $result = $this->s3Client->getObject([
            'Bucket' => EnvironmentVariable::get('S3.BUCKET'),
            'Key' => $fileName
         ]);

         return new JsonResponse([
            'message' => 'Lấy thông tin file thành công',
            'success' => true,
            'data' => [
               'url' => $this->getCloudFrontUrl($fileName),
               'fileName' => $fileName,
               'contentType' => $result['ContentType'],
               'size' => $result['ContentLength'],
               'lastModified' => $result['LastModified']->format('Y-m-d H:i:s')
            ]
         ], 200);

      } catch (AwsException $e) {
         return new JsonResponse([
            'message' => 'Lỗi khi lấy file: ' . $e->getMessage(),
            'success' => false
         ], 500);
      } catch (\Exception $e) {
         return new JsonResponse([
            'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
            'success' => false
         ], 500);
      }
   }

   public function getPresignedUrl(Request $request): JsonResponse
   {
      try {
         $fileName = $request->get('fileName');

         if (!$fileName) {
            return new JsonResponse([
               'message' => 'Không tìm thấy tên file',
               'success' => false
            ], 400);
         }

         // Tạo Presigned URL với thời hạn 1 giờ
         $presignedUrl = $this->s3Client->createPresignedRequest(
            $this->s3Client->getCommand('GetObject', [
               'Bucket' => EnvironmentVariable::get('S3.BUCKET'),
               'Key' => $fileName
            ]),
            '+1 hour'
         )->getUri();

         return new JsonResponse([
            'message' => 'Tạo URL thành công',
            'success' => true,
            'data' => [
               'url' => (string) $presignedUrl
            ]
         ], 200);

      } catch (AwsException $e) {
         return new JsonResponse([
            'message' => 'Lỗi khi tạo URL: ' . $e->getMessage(),
            'success' => false
         ], 500);
      }
   }
}