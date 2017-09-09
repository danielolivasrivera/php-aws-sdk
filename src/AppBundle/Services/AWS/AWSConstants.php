<?php

namespace AppBundle\Services\AWS;

/**
 * AWSConstants class contains the constants used by the AWS SDK clients.
 * @author Daniel A. Olivas Rivera <daniel.olivasrivera>
 */

 class AWSConstants
 {
     
    // AWS S3 Constants

    const S3_AWS_DOMAIN = ".amazonaws.com";
    const S3_NAME_URL = ".s3"; 
    const S3_ERROR_FOUND = "AWS S3 - Error found on ";
    const S3_MIME_TYPE_STRING_PARAMETER = ';';
    const S3_CACHE_CONTROL_MAX_AGE = 'max-age=31536000';
    const S3_URL_LIFETIME = '+5 minutes';
    
    const S3_ERROR_CODE_NOT_FOUND = 'NotFound';
 }