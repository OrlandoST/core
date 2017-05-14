<?php
/**
 * Db Model
 *
 */

namespace Core\Db;

use Zend\Db\RowGateway\RowGateway;
use Core\Api\Api;
use Core\Image\Image;
use Application\Model\File;
use Zend\Form\Element\File as FileElement;

class Model extends RowGateway
{
    
    protected $primaryKeyColumn = null;
    protected $table = null;
    protected $_type = null;
    protected $_mainImageSizes = array('x' => 700, 'y' => 700);
    protected $_thumbImageSizes = array('x' => 160, 'y' => 160);
    protected $_lang = 'ru';

    public function __construct() {
        
        $primaryKeyColumn = $this->getPrimaryKeyColumn();
        $table = $this->getTableName();
        $adapter = Api::_()->getServiceManager()->get('Zend\Db\Adapter\Adapter');
//        $this->_lang = $currentLocale = Api::_()->sm()->get('translator')->getLocale();
        parent::__construct($primaryKeyColumn, $table, $adapter);
    }
    
    public function getPrimaryKeyColumn(){
        if(is_null($this->primaryKeyColumn)){
            $tmp = explode('\\', strtolower(get_class($this)));
            $class_name = $tmp[count($tmp) - 1];
            
            if($class_name == 'model'){
                $this->primaryKeyColumn = 'id';
            }else{
                $this->primaryKeyColumn = $class_name.'_id';
            }
        }elseif(is_array($this->primaryKeyColumn)){
            return $this->primaryKeyColumn[0];
        }
        
        return $this->primaryKeyColumn;
    }
    
    public function getTableName(){
        if(is_null($this->table)){
            $class_name = strtolower(get_class($this));
            
            
            $this->table =str_replace('\\model\\', '_', $class_name) . 's'; //Add s to class name
        }
        
        return $this->table;
    }


    public function getTitle()
    {
        if (isset($this->title)) {
            return $this->title;
        }

        return 'Unknown';
    }

    public function getDescription()
    {
        if (isset($this->description)) {
            return $this->description;
        }
    }

    public function getIdentity() {
        $primaryKeyColumn = $this->getPrimaryKeyColumn();
        return (int) (isset($this->{$primaryKeyColumn}) ? $this->{$primaryKeyColumn} : 0);
    }
    
    public function getType(){
        if(is_null($this->_type)){
            $class_name = strtolower(get_class($this));
            $this->_type = str_replace('\\model\\', '_', $class_name);
        }
        
        return $this->_type;
    }


    public function setFromArray(array $newData = array()) {
        $oldData = $this->toArray();
        $data = array_merge($oldData, array_intersect_key($newData, $oldData));
        $this->populate($data, true);
    }
    
    public function setPhoto($photo) {
        if ($photo instanceof FileElement) {
            $file = Api::_()->getFileName($photo);
//            $file = $photo->getFileName();
            $fileName = $file;
        } else if ($photo instanceof File) {
            $file = $photo->temporary();
            $fileName = $photo->name;
        } else if ($photo instanceof Model && !empty($photo->file_id)) {
//            $tmpRow = Engine_Api::_()->getItem('storage_file', $photo->file_id);
            $tmpRow = Api::_()->getDbtable('files')->getFile($photo->file_id);
            $file = $tmpRow->temporary();
            $fileName = $tmpRow->name;
        } else if (is_array($photo) && !empty($photo['tmp_name'])) {
            $file = $photo['tmp_name'];
            $fileName = $photo['name'];
        } else if (is_string($photo) && file_exists($photo)) {
            $file = $photo;
            $fileName = $photo;
        } else {
            throw new Exception('invalid argument passed to setPhoto');
        }

        if (!$fileName) {
            $fileName = $file;
        }
        
        $viewer = Api::_()->getViewer();

        $name = basename($file);
        $extension = ltrim(strrchr(basename($fileName), '.'), '.');
        $base = rtrim(substr(basename($fileName), 0, strrpos(basename($fileName), '.')), '.');
        $path = ROOT_PATH . '/temporary';
        $params = array(
            'parent_type' => $this->getType(),
            'parent_id' => $this->getIdentity(),
            'user_id' => $viewer->getIdentity(),
            'name' => basename($fileName),
        );

        // Save
        $filesTable = Api::_()->getDbtable('files');

        // Resize image (main)
        $mainPath = $path . '/' . $base . '_m.' . $extension;
        $image = Image::factory();
        $image->open($file)
                ->resize($this->_mainImageSizes['x'], $this->_mainImageSizes['y'])
                ->write($mainPath)
                ->destroy();

        // Resize image (normal)
        $normalPath = $path . '/' . $base . '_in.' . $extension;
        $image = Image::factory();
        $image->open($file)
                ->resize($this->_thumbImageSizes['x'], $this->_thumbImageSizes['y'])
                ->write($normalPath)
                ->destroy();

        // Store
        $iMain = $filesTable->createFile($mainPath, $params);
        $iIconNormal = $filesTable->createFile($normalPath, $params);


        $iMain->bridge($iIconNormal, 'thumb.normal');

        // Remove temp files
        @unlink($mainPath);
        @unlink($normalPath);

        // Update row
        
        if(isset($this->photo_id)){
            $this->photo_id = $iMain->file_id;
        }
        $this->save();
//die;
        return $this;
    }
    
    public function getPhotoUrl($type = null)
    {
        if (empty($this->photo_id)) {
            if($type == 'thumb.normal'){
                return Api::_()->sm()->get('request')->getBaseUrl().'/externals/images/nophoto_thumb_profile.png';
            }
            else{
                return Api::_()->sm()->get('request')->getBaseUrl().'/externals/images/nophoto_thumb_profile.png';
            }
        }

        $file = Api::_()->getDbtable('files')->getFile($this->photo_id, $type);
        if (!$file) {
            return Api::_()->sm()->get('request')->getBaseUrl().'/externals/images/nophoto_thumb_profile.png';
        }

        return $file->map();
    }

    public function setName($name = '')
    {
        if($name == '')
        {
            return false;
        }

        $name = $this->_ruToEn($name);

        $name = strtolower(trim($name));
        $name = preg_replace('/[^a-z0-9-]/', '-', $name);
        $name = preg_replace('/-+/', "-", $name);

        $this->name = $name;

        $this->save();
    }

    public function getName()
    {
        return $this->name;
    }

    protected  function _ruToEn($string){
        $table = array(
            'А' => 'A',
            'Б' => 'B',
            'В' => 'V',
            'Г' => 'G',
            'Д' => 'D',
            'Е' => 'E',
            'Ё' => 'YO',
            'Ж' => 'ZH',
            'З' => 'Z',
            'И' => 'I',
            'Й' => 'J',
            'К' => 'K',
            'Л' => 'L',
            'М' => 'M',
            'Н' => 'N',
            'О' => 'O',
            'П' => 'P',
            'Р' => 'R',
            'С' => 'S',
            'Т' => 'T',
            'У' => 'U',
            'Ф' => 'F',
            'Х' => 'H',
            'Ц' => 'C',
            'Ч' => 'CH',
            'Ш' => 'SH',
            'Щ' => 'CSH',
            'Ь' => '',
            'Ы' => 'Y',
            'Ъ' => '',
            'Э' => 'E',
            'Ю' => 'YU',
            'Я' => 'YA',

            'а' => 'a',
            'б' => 'b',
            'в' => 'v',
            'г' => 'g',
            'д' => 'd',
            'е' => 'e',
            'ё' => 'yo',
            'ж' => 'zh',
            'з' => 'z',
            'и' => 'i',
            'й' => 'j',
            'к' => 'k',
            'л' => 'l',
            'м' => 'm',
            'н' => 'n',
            'о' => 'o',
            'п' => 'p',
            'р' => 'r',
            'с' => 's',
            'т' => 't',
            'у' => 'u',
            'ф' => 'f',
            'х' => 'h',
            'ц' => 'c',
            'ч' => 'ch',
            'ш' => 'sh',
            'щ' => 'csh',
            'ь' => '',
            'ы' => 'y',
            'ъ' => '',
            'э' => 'e',
            'ю' => 'yu',
            'я' => 'ya',
        );

        $output = str_replace(
            array_keys($table),
            array_values($table),$string
        );

        return $output;
    }
}
