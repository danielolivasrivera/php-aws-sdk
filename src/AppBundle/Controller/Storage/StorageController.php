<?php

namespace AppBundle\Controller\Storage;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

use AppBundle\Services\AWS\S3Photo;
use AppBundle\Services\AWS\S3File;
use AppBundle\Services\AWS\S3Document; 


class StorageController extends Controller
{
    public function indexAction()
    {
        try
        {
            //phpinfo();
            //$stack = debug_backtrace();
            
            //$this->TestS3Photo();
            //$this->TestS3Documents();
            //$this->TestS3Copy();
            //$this->TestS3Delete();
            $this->TestS3StaticClasses();

            return new Response
            (
                '<html><body>Test Completed</body></html>'
            );
        }
        catch(\Exception $ex)
        {
            throw $ex;
        }
        
    }
    private function TestS3Photo()
    {
        //Old URL - https://document-uploads.s3.amazonaws.com/case/documents/RDNDOCUMENT59516d3d428d26.72192294.pdf 
        //New URL - https://s3-us-west-1.amazonaws.com/rdn-web-app/hello-world.txt 

        //$url = "https://rdn-web-app.s3-us-west-1.amazonaws.com/hello-world.txt"; 
        //$url = "https://s3-us-west-1.amazonaws.com/hello-world.txt";
        //https://dev-rdn-web-app.s3-us-west-1.amazonaws.com/maintenance.png
        //https://dev-rdn-web-app.s3-us-west-1.amazonaws.com/case/photos/maintenance.png 

        //S3FILE::getURL($url);

        $file = __DIR__ . '/../../../../web/build/images/maintenance.png';
        $s3Photo = new S3Photo($file, false);        
        $photos = $s3Photo->save(1, "daniel-image.png");
    }
    
    private function TestS3Documents()
    {
        //$file = __DIR__ . '/../../../../web/build/documents/Test-Doc.zip';
        $file = __DIR__ . '/../../../../web/build/documents/Test-Sheet.zip';
        $s3Document =  new S3Document($file);
        $document = $s3Document->save(1, "hello-world");
    }

    private function TestS3Copy()
    {
        //Old URL - https://document-uploads.s3.amazonaws.com/case/documents/RDNDOCUMENT59516d3d428d26.72192294.pdf 
        //New URL - https://s3-us-west-1.amazonaws.com/rdn-web-app/hello-world.txt 

        //$url = "https://rdn-web-app.s3-us-west-1.amazonaws.com/hello-world.txt"; 
        //$url = "https://s3-us-west-1.amazonaws.com/hello-world.txt"; 

        //S3FILE::getURL($url);

        //$s3URL = "https://document-uploads.s3.amazonaws.com/case/documents/RDNDOCUMENT59516d3d428d26.72192294.pdf";
        //$s3URL = "https://s3-us-west-1.amazonaws.com/dev-rdn-web-app/case/photos/maintenance.png";
        $s3URL = "https://s3-us-west-1.amazonaws.com/dev-rdn-web-app/case/photos/RDNPICTURE59b1bc07910389.59832342.png";
        

        $case_id = 1;
        $original_case_photo_id = 2;

        $photo = new S3Photo();
        $photo->copy($case_id, $s3URL, $original_case_photo_id);
    }

    private function TestS3Delete()
    {
        $s3Url = "https://s3-us-west-1.amazonaws.com/dev-rdn-web-app/case/photos/RDNPICTURE59b1ceb16eb334.90559879.png";

        $photo = new S3Photo();
        $s3Result = $photo->delete(2024505182, $s3Url);
        var_dump($s3Result);
    }

    private function TestS3StaticClasses()
    {
        //$s3Url = "https://s3-us-west-1.amazonaws.com/dev-rdn-web-app/case/photos/RDNPICTURE59b1ceb16eb334.90559879.png";
        //$s3Url = "https://s3-us-west-1.amazonaws.com/dev-rdn-web-app/case/photos/RDNPICTURE59b1bc07910389.59832342.png";
        $s3Url = "hola.png";
        $file = S3FILE::getFile($s3Url);
        echo $file['Body'];

    }
}
