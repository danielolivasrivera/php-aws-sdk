<?php

// require_once 
namespace AppBundle\Services\AWS;

/**
 * S3Service class allows the integration between RDN business layer and AWS S3 Client
 * @author Daniel A. Olivas Rivera <daniel.olivasrivera>
 */

 require GLOBAL_GROCERY_LIST_PATH.'../../vendor/autoload.php';

 class S3Service
{
    const S3_REGION = '';
    const S3_VERSION = '';
    
    public $bucket = 'https://s3-us-west-1.amazonaws.com/rdn-web-app';
    public $keyname = 'DSC_0009.JPG';
    public $filepath = '';

    private $s3Client;
    
    
    
    public function __construct()
    {
        Initialize();
    }

    private function Initialize()
    {
        InitializeS3Client();
    }

    private function InitializeS3Client()
    {
        //Options for S3 constructor
        $options = [
            'region'            => S3_REGION, //required
            'version'           => S3_VERSION, //required
            'signature_version' => 'v4'
        ];

    }

    private function putObject()
    {
        try
        {
            


        }
        catch( Aws\S3\Exception $ex)
        {
            echo $ex->getMessage() . "\n";
        }
    }
}