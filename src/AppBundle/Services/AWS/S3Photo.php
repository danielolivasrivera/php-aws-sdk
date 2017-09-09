<?php

namespace AppBundle\Services\AWS;

class S3Photo extends S3File 
{
    
    const MAX_HEIGHT  = 480;
    const MAX_WIDTH   = 640;

    public $base_path = 'case/photos/';
    public $bucket = 'dev-rdn-web-app';
    public $file_prefix = 'RDNPICTURE';
    public $meta_headers = array(
    'x-amz-server-side-encryption' => 'AES256'
    );

    protected $mime_whitelist = array(
        'image/gif'  => 'gif',
        'image/jpg'  => 'jpg',
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
    );
    
    protected $max_file_size = 7340032;

    /**
        * Save the photo (resize if necessary) and insert a row into case_photo
        *
        * @param int $case_id PK on regowner
        * @param string $original_name Original file name
        * @return array S3 file information with width and height
        */
    public function save($case_id = null, $original_name = '') 
    {
        if ($this->isValid()) 
        {
            list($width, $height) = getimagesize($this->file);
            if ($height > self::MAX_HEIGHT || $width > self::MAX_WIDTH) 
            {
               // list($width, $height) = $this->resize();
                $this->size = filesize($this->file);
            }
            
            $this->original_name = $original_name;
            if ($info = parent::save($this->file)) 
            {
                
                if(empty($original_name))
                {
                    $original_name = "untitled.". $this->mime_whitelist[$info['type']];
                }

                /*
                if (is_numeric($case_id)) 
                {
                    $qry = 'INSERT INTO `case_photo` (`url`, `case_id`, `original_name`, `file_size`, `width`, `height`) VALUES (?, ?, ?, ?, ?, ?)';
                    $this->getDb(true)->execute($qry, $info['url'], $case_id, $original_name, $info['filesize'], $width, $height);
                    $photo_id = $this->getDB(true)->getLastInsertId();
                }
                */

                $photo_id = 2;

                return array_merge($info, array('width'=>$width, 'height'=>$height, 'newly_added_photo_id' => $photo_id));
            }
        }
    }

    /**
	 * Copy the photo and insert a row into case_photo
	 *
	 * @param int $case_id PK on regowner
	 * @param string $original_case_photo_id Original photo PK on case_photo
	 * @return boolean True, if copied successfully
	 */
    public function copy($case_id, $url = null, $original_case_photo_id = null) 
    {
        if (is_numeric($original_case_photo_id)) 
        {
			//$qry = 'SELECT * FROM `case_photo` WHERE `case_photo_id` = ?';
            //$info = $this->db->execute($qry, $original_case_photo_id);
            
            if ($url = parent::copy($url))
            {
                var_dump($url);
					//$qry = 'INSERT INTO `case_photo` (`url`, `case_id`, `original_name`, `file_size`, `width`, `height`) VALUES (?, ?, ?, ?, ?, ?)';
					//$this->getDb(true)->execute($qry, $url, $case_id, $info[0]['original_name'], $info[0]['file_size'], $info[0]['width'], $info[0]['height']);
					//return true;
			}
		}
		//return false;
    }
    
    /**
	 * Delete the photo from S3 and the record from case_photo
	 *
	 * @param int $case_photo_id PK on case_photo table
	 * @return boolean True, if S3 object was deleted successfully
	 */
    public function delete($case_photo_id = null, $s3Url = '') 
    {
        if (is_numeric($case_photo_id)) 
        {
			//$qry = 'SELECT `url` FROM `case_photo` WHERE `case_photo_id` = ?';
			//$case_photos = $this->db->execute($qry, $case_photo_id);
            $url = explode('/', $s3Url);
            
            $file = array_pop($url);
            
			$uri = $this->base_path . $file;

			//$qry = 'DELETE FROM `case_photo` WHERE `case_photo_id` = ?';
			//$this->db->execute($qry, $case_photo_id);

			return parent::delete($uri);
		}
		return false;
	}


}