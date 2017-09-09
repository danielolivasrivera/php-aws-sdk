<?php

namespace AppBundle\Services\AWS;

/**
 * S3File class allows the integration between RDN business layer and AWS S3 Client
 * @author Daniel A. Olivas Rivera <daniel.olivasrivera>
 */

 // Include the AWS SDK using the Composer autoloader
require __DIR__ .'/../../../../vendor/autoload.php';
require_once 'AWSConstants.php';

use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;
use Aws\S3\S3UriParser;

class S3File
{
    //TODO - daniel.olivasrivera - Move to Config
    const S3_CONFIG_REGION = 'us-west-1';
    const S3_CONFIG_VERSION = '2006-03-01';
    const S3_CONFIG_CREDENTIALS_FLAG = false;
    const S3_CONFIG_CREDENTIALS_KEY = 'AKIAJ7RGOFQM4JFP5ELA';
    const S3_CONFIG_CREDENTIALS_SECRET = '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';

    public $bucket = '';
    public $acl = 'public-read';
	public $base_path = '';
    public $filepath = '';
    public $file_prefix = '';
	public $headers = array();
	public $meta_headers = array();

    protected $db = null;
    protected $file = null;
    protected $mime = null;
    protected $mime_whitelist = array();
    protected $original_name = '';
    protected $size = null;
    protected $max_file_size = 2000000000;
    protected $caseID = 0;
    
    //S3 Parameters
    protected $metadataDirective = 'COPY';

    private $s3Client = null;
    private $unlink = true;

    /**
     * Initializes an instance of S3File
     *
     * @param string  $p_file - Input File path - Optional Argument 
     * @param boolean $p_unlink - Whether to unlink the Input File - Optional Argument 
     * @param string  $p_originalName - Input File original name - Optional Argument 
     * @param int $p_caseID - Case ID - Optional Argument
     * 
     */

    public function __construct($p_file = null, $p_unlink = true, $p_originalName = null, $p_caseID = 0)
    {
        $this->Initialize($p_file, $p_unlink, $p_originalName, $p_caseID);
    }

    /*** Functions ***/

    /*Public Functions*/

    /**
     * save - Stores the object into the AWS S3 bucket
     *
     * @return array Returns array containing file size, type and url  
     */

    public function save()
    {
        try
        {
            if($this->isValid())
            {
                $s3Key = $this->generateS3Key();

                $cacheControl = isset($this->headers['Cache-Control']) ? $this->headers['Cache-Control'] : '';
                $contentType = isset($this->headers['Content-Type']) ? $this->headers['Content-Type'] : '';

                $s3ClientResponse   = $this->s3Client->putObject
                ([
                    'Bucket' => $this->bucket,
                    'Key' => $s3Key,
                    'ACL' => $this->acl, 
                    'Metadata' => $this->meta_headers,
                    'CacheControl' => $cacheControl,
                    'ContentType' => $contentType, 
                    'SourceFile'   => $this->file,
                ]);

                if ($this->unlink)
                {
                    unlink($this->file);
                }

                return array
                (
                    'filesize' => $this->size,
                    'type' => $this->mime,
                    'url' => isset($s3ClientResponse['ObjectURL']) ? $s3ClientResponse['ObjectURL'] : '',
				);
            }
        }
        catch(S3Exception $ex)
        {
            //TODO - Fix Exceptions
            echo 'Exception found on S3File->save():' . "<br />" . $ex->getMessage() . "<br />";
        }       
        catch(\Exception $ex )
        {
            //TODO - Fix Exceptions
            echo 'Exception found on S3File->save():' . "<br />" . $ex->getMessage() . "<br />";
        }
    }

    /**
     * Creates a copy of an object that is already stored in AWS S3.
     *
     * @param string $p_s3URL S3 URL of existing object
     *
     * @return string Returns the URL of the new s3 object
     *
    */
    
    public function copy($p_s3Url)
    {
        $output = false;

        try
        {
            if($this->isUrlValid($p_s3Url))
            {
                $this->original_name = pathinfo($p_s3Url,  PATHINFO_BASENAME);

                $parsedS3Url = self::parseS3Uri($p_s3Url);

                $source_bucket = isset($parsedS3Url['bucket']) ? $parsedS3Url['bucket'] : '';
                $source_key = isset($parsedS3Url['key']) ? $parsedS3Url['key'] : '';
                $copysource = "{$source_bucket}/{$source_key}";

                $target_bucket = $this->bucket;
                $target_key = $this->generateS3Key();

                if(!empty($source_bucket) && !empty($source_key))
                {
                    if($this->doesS3ObjectExist($source_bucket, $source_key))
                    {
                        $s3ClientResponse = $this->s3Client->copyObject
                        ([
                            'Bucket' => $target_bucket,
                            'Key' => $target_key,
                            'CopySource' => $copysource,  
                            'ACL' => $this->acl,
                            'MetadataDirective' => $this->metadataDirective, 
                        ]);
    
                        $output = isset($s3ClientResponse['ObjectURL']) ? $s3ClientResponse['ObjectURL'] : '';
                    }
                    else
                    {
                        throw new \Exception(AWSConstants::S3_ERROR_FOUND . "S3FILE->copy()" . " The file you are trying to copy does not exists " . $p_s3Url . "\n");
                    }
                }
                else
                {
                    throw new \Exception(AWSConstants::S3_ERROR_FOUND . "S3FILE->copy()" . " The following URL is an invalid AWS S3 Endpoint - " . $p_s3Url . "\n");
                }
            }
            else
            {
                throw new \InvalidArgumentException(AWSConstants::S3_ERROR_FOUND . "S3FILE->copy()" . "\n" . $p_s3Url . " is not a valid URL" . "\n");
            }                        
        }
        catch(S3Exception $ex)
        {
            //TODO - Work on exceptions
            throw $ex;
        }       
        catch(\Exception $ex )
        {
            //TODO - Work on exceptions
            throw $ex;
        }

        return $output;
    }

    /**
	 * delete - Deletes an S3 object
	 * 
     * @param string $p_s3Key S3 object key
     *
     * @return boolean True, if S3 object was deleted successfully. False otherwise.
     *
	 */
    public function delete($p_s3Key) 
    {
        $output = false;
        try
        {
            if($this->doesS3ObjectExist($this->bucket, $p_s3Key))
            {
                $s3ClientResponse = $this->s3Client->DeleteObject(
                    [
                        'Bucket' => $this->bucket, 
                        'Key' => $p_s3Key,
                    ]);
                
                $output = true;
            }
            else
            {
                throw new \Exception(AWSConstants::S3_ERROR_FOUND . "S3FILE->delete()" . "\n The file you are trying to delete does not exists " . "\n Bucket: " . $this->bucket . "\n Key: " . $p_s3Key . "\n");
            }
        }
        catch(S3Exception $ex)
        {
            //TODO - Work on exceptions
            throw $ex;
        }       
        catch(\Exception $ex )
        {
            //TODO - Work on exceptions
            throw $ex;
        }

        return $output; 
	}

    /**
     * getDb  - Creates an instance of the data base access layer. 
     *
     *
     * @param bool $p_useWrite
     *
     * @return AbstractedDB instance
     */
    public function getDb($p_useWrite = false)
    {
        $db = AbstractedDB::getInstance();

        if($p_userWrite === true)
        {
            $db->useWrite = true;
        }
        return $db;
    }

    //TODO: daniel.olivasrivera - Work on adding the case ID on the callers.

    /**
    * getUrl - Constructs a 'pre-signed' request and encode it as a URL which enables direct third-party browser access to S3 data.
    * It also limits the pre-signed request by specifying an expiration time.
    *
    * @param string $p_s3UnsignedURL S3 object's unsigned URL.
    *
    * @param string $p_lifeTime - The time at which the URL should expire. This can be a Unix timestamp, a PHP DateTime object, or a string that can be evaluated by strtotime().
    *
    * @return string Returns the actual presigned-url
    *
    */

    public static function getUrl($p_s3UnsignedURL, $p_lifeTime = AWSConstants::S3_URL_LIFETIME, $p_caseID = 0 )
    {
        $output = '';

        try
        {
            $parsedS3URL = self::parseS3Uri($p_s3UnsignedURL);

            $bucket = isset($parsedS3URL['bucket']) ? $parsedS3URL['bucket'] : '';
            $key = isset($parsedS3URL['key']) ? $parsedS3URL['key'] : '';

            if(!empty($bucket) && !empty($key))
            {
                $s3Client = self::buildS3Client();

                $command = $s3Client->getCommand('GetObject',[
                    'Bucket' =>$bucket,
                    'Key' => $key 
                ]);

                $s3ClientResponse = $s3Client->createPresignedRequest($command, $p_lifeTime);

                $output = (string) $s3ClientResponse->getUri();
            }
            else
            {
                throw new \Exception(AWSConstants::S3_ERROR_FOUND . "S3FILE->getUrl()" . "\n Case ID: " . $p_caseID . "\nThe following URL is an invalid AWS S3 Endpoint - " . $p_s3UnsignedURL . "\n");
            }
        }
        catch(S3Exception $ex)
        {
            //TODO - Work on exceptions
            throw $ex;
        }       
        catch(\Exception $ex )
        {
            //TODO - Work on exceptions
            throw $ex;
        }

        return $output;
    }
    //TODO - Daniel Work on the return values. I am not sure if the new object contains the expected values

    /**
    * getFile - Retrieves objects from AWS S3
    *
    * @param string $p_s3Url - Object's AWS S3 URL 
    *
    * @param string $p_caseID - Case ID associated to S3 object
    *
    * @return s3ClientResponse object.
    */
    public static function getFile($p_s3Url, $p_caseID = 0)
    {
        $output = '';
        
        try
        {
            $parsedS3URL = self::parseS3Uri($p_s3Url);

            $bucket = isset($parsedS3URL['bucket']) ? $parsedS3URL['bucket'] : '';
            $key = isset($parsedS3URL['key']) ? $parsedS3URL['key'] : '';

            if(!empty($bucket) && !empty($key))
            {
                $s3Client = self::buildS3Client();

                $s3ClientResponse = $s3Client->getObject(
                [
                    'Bucket' =>$bucket,
                    'Key' => $key 
                ]);

                $output = $s3ClientResponse;
            }
            else
            {
                throw new \Exception(AWSConstants::S3_ERROR_FOUND . "S3FILE->getFile()" . "\n Case ID: " . $p_caseID . "\nThe following URL is an invalid AWS S3 Endpoint - " . $p_s3Url . "\n");
            }
        }
        catch(S3Exception $ex)
        {
            //TODO - Work on exceptions
            throw $ex;
        }       
        catch(\Exception $ex )
        {
            //TODO - Work on exceptions
            throw $ex;
        }

        return $output;
    }
    
    /* End of Public Functions*/

    /* Protected Functions */

    /**
     * isValid function checks to see if the file size is under the limits of MAX_FILE_SIZE and 
     * also validates the file extension (mime_content_type)
     *
     * @return bool Returns whether or not a file is valid.
     */

     protected function isValid()
     {
         if ($this->size <= $this->max_file_size)
         {
             if (empty($this->mime_whitelist) || array_key_exists($this->mime, $this->mime_whitelist))
             {
                 return true;
             }
             else 
             {
                 //TODO: daniel.olivasrivera - What to do on exceptions.
                 throw new \Exception('AWS S3 - Error found on CaseID: ' . $this->caseID . ' We don\'t support files of this type: ' . $this->mime);
             }
         }
         else 
         {
             //TODO: daniel.olivasrivera - What to do on exceptions.
            throw new \Exception('AWS S3 - Error found on CaseID: ' . $this->caseID . 'File is too large: ' . $this->size);
         }
     }

     /**
	 * getMimeType - Gets the mimetype from file info.
	 * 
	 * @return string Returns the file's  MIME typemime.
	 */
    protected function getMimeType()
    {
        $output = '';

        if($this->file)
        {
            $mime = finfo_file(finfo_open(FILEINFO_MIME), $this->file);
            $output = (strstr($mime, AWSConstants::S3_MIME_TYPE_STRING_PARAMETER)) ? substr($mime, 0, strpos($mime, AWSConstants::MIME_TYPE_STRING_PARAMETER)) : $mime;
        }

        return $output;  
    }

    /**
	 * generateFilename - Builds the filename
	 * 
	 * @return string Filename
	 */
     protected function generateFilename()
     {
         return uniqid($this->file_prefix, true) . '.' . $this->getFileExtension();
     }

     /**
	 * Get the file extension from the mimetype list
	 * 
	 * @return string File extension
	 */
    protected function getFileExtension() 
    {
        if(!empty($this->original_name))
        {
            $fileExtension = pathinfo($this->original_name, PATHINFO_EXTENSION);

            if(!empty($fileExtension))
            {
                return $fileExtension;
            }
        }
                
        if (in_array($this->mime, array('application/x-zip', 'application/zip'))) 
        {
            //TODO - daorsys - Might need to change the \ on \ZipArchive. Also I am not sure how this is working. I will need
            //To investigate this funtion.
            $zip = new \ZipArchive();
            
            if ($zip->open($this->file) === true)
            {
                $content = $zip->getFromName('[Content_Types].xml');
                
                $zip->close();
                
                if (strpos($content, 'spreadsheetml')) 
                {
                    return 'xlsx';
                }

                return 'docx';
            }
        }

        return $this->mime_whitelist[$this->mime];
    }  

    /**
     * doesS3ObjectExist - Determines whether or not an object exists by name.
     *
     * @param string $p_bucket Bucket name to check.
     *
     * @param string $p_key Key name to check.
     *
     * @return bool Returns true if the S3 Object exists. False otherwise.
     */

     protected function doesS3ObjectExist($p_bucket, $p_key)
     {
         $output = false;
 
         try
         {
             $s3ClientResponse = $this->s3Client->doesObjectExist($p_bucket, $p_key);
 
             $output = $s3ClientResponse;
         }
         catch(S3Exception $ex)
         {
            $output = false;
            throw $ex;    
         }       
         catch(\Exception $ex )
         {
            $output = false;
            throw $ex;
         }
 
         return $output;
     }


     /* End of Protected Functions */

    /*Private Functions*/
    
    /**
     * Initializes an instance of S3File
     *
     * @param string  $p_file - Input File path - Optional Argument 
     * @param boolean $p_unlink - Whether to unlink the Input File - Optional Argument 
     * @param string  $p_originalName - Input File original name - Optional Argument 
     * @param int $p_caseID - Case ID - Optional Argument
     * 
     */
    
    private function Initialize($p_file = null, $p_unlink = true, $p_originalName = null, $p_caseID = 0)
    {
        $this->InitializeS3Client();
        $this->db = AbstractedDB::getInstance();

        $this->caseID = $p_caseID;
        $this->unlink = $p_unlink;

        if($p_file)
        {
            if(file_exists($p_file))
            {
                $this->file = $p_file;
                $this->mime = $this->getMimeType();
                $this->size = filesize($p_file);
            }
            else
            {
                throw new \Exception(AWSConstants::S3_ERROR_FOUND . "S3FILE->Initialize()" . "\n" . "File " . $p_file . " does not exist" . "\n");
            }
        }

        $this->original_name = $p_originalName;
        $this->headers = array(
            'Content-Type'   => $this->mime,
			'Cache-Control'  => AWSConstants::S3_CACHE_CONTROL_MAX_AGE,
        );
    }

    /**
     * Initializes an instance of AWS SDK S3
     *
     */

    private function InitializeS3Client()
    {
        try
        {
            //Options for S3 constructor
            $options = 
            [
                'region'  => self::S3_CONFIG_REGION,
                'version' => self::S3_CONFIG_VERSION,
                'credentials' => [
                    'key'    => self::S3_CONFIG_CREDENTIALS_KEY,
                    'secret' => self::S3_CONFIG_CREDENTIALS_SECRET,
                ],
            ];

            $this->s3Client = new S3Client($options);
        }        
        catch(\Exception $ex )
        {
            echo $ex->getMessage() . "\n";
        }
    }
    
    /**
    * buildS3Client - Creates an instance of AWS SDK S3 for static calls
    *
    * @return S3Client instance
    */
    private static function buildS3Client()
    {
        return S3Client::Factory(
        [
            'region'  => self::S3_CONFIG_REGION, //required
            'version' => self::S3_CONFIG_VERSION, //required
            'credentials' => [
                'key'    => self::S3_CONFIG_CREDENTIALS_KEY,
                'secret' => self::S3_CONFIG_CREDENTIALS_SECRET,
            ],
        ]); 
    }

    /**
	 * The parseS3Uri functions get the bucket and key(URI) from an S3 URL
     *
     * @param string $url Full identifier for the resource.
     *
	 * @return array S3 bucket and key (Uri)
     */
     
    private static function parseS3Uri($p_url)
    {
        $output = array();

        if(!empty($p_url))
        {
            try
            {
                $s3UriParser = new S3UriParser();
                $parsedS3Uri = $s3UriParser->parse($p_url);

                $bucket = isset($parsedS3Uri['bucket']) ? $parsedS3Uri['bucket'] : '';
                $key = isset($parsedS3Uri['key']) ? $parsedS3Uri['key'] : '';
                
                if(!empty($bucket) && !empty($key))
                {
                    $output['bucket'] = $bucket;   
                    $output['key'] = $key;
                }
                else
                {
                    throw new \Exception(AWSConstants::S3_ERROR_FOUND . "S3FILE->parseS3Uri()" . " The following URL is an invalid AWS S3 Endpoint - " . $p_url . "\n");
                }
            }
            catch(\InvalidArgumentException $ex)
            {
                throw new \InvalidArgumentException('Exception found on S3File->parseS3Uri():' . "\n" . $ex->getMessage() . "\n");
            }       
            catch(\Exception $ex )
            {
                throw $ex;
            }
        }

        return $output;
    }
    
    /**
	 * generateS3Key - Build the S3 key (Uri)
	 *
	 * @return string S3 key (Uri)
	 */
    private function generateS3Key()
    {
		return $this->base_path . $this->generateFilename();
    }

    /**
    * Checks if URL is valid
    *
    * @param string $p_url Url to validate.
    *
    * @return string Returns true if URL is valid
    *
    */
    private function isUrlValid($p_url)
    {
        $output = false;

        $p_url = filter_var($p_url, FILTER_SANITIZE_URL);

        if(filter_var($p_url, FILTER_VALIDATE_URL))
        {
            $output = true;
        }

        return $output;
    }    

    /*End of Private Functions*/
}