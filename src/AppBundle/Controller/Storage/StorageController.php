<?php

namespace AppBundle\Controller\Storage;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

class StorageController extends Controller
{
    public function indexAction()
    {
        echo(GLOBAL_GROCERY_LIST_PATH);

        $url = 'https://s3-us-west-1.amazonaws.com/rdn-web-app/DSC_0009.JPG';



        preg_match('/https?:\/\/([^.]*)s3-us-west-1.amazonaws.com\/(.*)/', $url, $parts);
        echo($parts[1]); 
        //echo ($parts[2]);





        $number = mt_rand(0, 100);
        
        return new Response(
            '<html><body>Lucky number: '.$number.'</body></html>'
        );
    }

}
