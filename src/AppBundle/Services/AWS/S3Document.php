<?php

namespace AppBundle\Services\AWS;

class S3Document extends S3File 
{

	protected $mime_whitelist = array(
		'text/csv'                     => 'csv',

		'application/msword'           => 'doc',

		'application/x-zip'            => 'docx', // and xlsx
    'application/zip'              => 'docx', // and xlsx
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'       => 'xlsx',

		'image/gif'                    => 'gif',

		'image/jpg'                    => 'jpg',
		'image/jpeg'                   => 'jpg',

		'application/vnd.ms-outlook'   => 'msg',

		'application/pdf'              => 'pdf',

		'image/png'                    => 'png',

		'text/rtf'                     => 'rtf',

		'image/tiff'                   => 'tif',

		'text/plain'                   => 'txt',

		'application/excel'            => 'xls',
		'\012- application/msword'     => 'xls',
    'application/vnd.ms-excel'     => 'xls',
    'application/vnd.ms-office'    => 'doc',
	);
	protected $max_file_size = 7340032;

	public $base_path = 'case/documents/';
	public $bucket = 'document-uploads';
	public $file_prefix = 'RDNDOCUMENT';
	//public $acl = S3::ACL_PRIVATE;
  public $meta_headers = array(
    'x-amz-server-side-encryption' => 'AES256'
  );

	/**
	 * Save the file and insert a row into case_document
	 *
	 * @param int $case_id PK on regowner
	 * @param string $original_name File name
	 * @param int $document_type \Rdn\Model\DocumentType
	 * @return S3 file information
	 */
    public function save($case_id = null, $original_name = '', $document_type = null) 
    {
			$this->original_name = $original_name;
			$info = parent::save();
    
        /*
		if(empty($original_name)){
			$original_name = "untitled.". $this->mime_whitelist[$info['type']];
		}
		
		if (is_numeric($case_id)) {
			$qry = 'INSERT INTO `case_document` (`url`, `case_id`, `original_name`, `file_size`) VALUES (?, ?, ?, ?)';
			$this->getDb(true)->execute($qry, $info['url'], $case_id, $original_name, $info['filesize']);
			
			if($document_type !== null && in_array($document_type, array_keys(\Rdn\Model\DocumentType::getDocumentTypes()))){
			    $document_id = $this->getDB(true)->getLastInsertId();
			    $document_extra = new \Rdn\Model\DocumentExtra($document_id);
			    $document_extra->setDocumentType($document_type);
			   $info['newly_added_document_id'] = $document_id;
			}
			
			
        }
        */
        
			return $info;
    }  
}
