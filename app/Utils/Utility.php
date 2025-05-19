<?php

namespace App\Utils;

use App\Events\SendNewMail;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;

class Utility
{
  public static function sendEmail(array $params)
  {
    //first attempt to render the template
    try{
      $data = [
        'subject' =>  $params['subject'],
        'to' => $params['to'],
        'body' => $params['body'],
        'view' => $params['view']
      ];

      //then Queue it, fire new Email event
      event(new SendNewMail($data));
    }catch(\Exception $e) {
      return ["message" => $e->getMessage(), "errorCode" => 500, "status"=> false];
    }

    return ["message" => "success", "status"=> true];
  }

  public static function uploadFileToCloudinary($params)
  {
    // Upload any File to Cloudinary with One line of Code
    // $uploadedFileUrl = Cloudinary::uploadFile($params->file('file')->getRealPath())->getSecurePath();
   
		$image_name = $params->getRealPath(); 

		//upload image file to cloudinary 
		try{
      $uploadedFileUrl = Cloudinary::uploadFile($image_name, [
          'folder' => 'AfriWrites - '.env('APP_ENV').' Files', 
      ])->getSecurePath();

      return $uploadedFileUrl;
		}catch(\Exception $e){
      return ["message" => $e->getMessage(), "http_status" => 500, "status"=> false]; 
		}
  }

  public static function generateReferenceId()
  {
    return uniqid();
  }

  public static function picture_path_implode_explode($current_path, $new_path) 
  {
    $evidence_path = $current_path;
    $files = explode(",", $evidence_path);
    // if at least a file has been previously uploaded
    if( isset($files[0]) and strlen($files[0]) > 0 ) {
      array_push($files, $new_path);
      $evidence_path = implode(",", $files);
    } else {
      $evidence_path = $new_path;
    }
    return $evidence_path;
  }
}