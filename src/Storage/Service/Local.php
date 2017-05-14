<?php
/**
 * Storage Service
 * 
 * @copyright (c) 2013, Core.net
 * @author Berdimurat Masaliev <muratmbt@gmail.com>
 */

namespace Core\Storage\Service;

use Core\Api\Api;
use Core\Storage\Service\AbstractService;


class Local extends AbstractService{
    
    protected $_path = 'files';
    protected $_baseUrl;


    public function getBaseUrl() {
        if (null === $this->_baseUrl) {
            $this->_baseUrl = $this->_removeScriptName(Api::_()->sm()->get('request')->getBaseUrl());
        }
        return $this->_baseUrl;
    }

    // Accessors

    public function map($model) {
        return rtrim($this->getBaseUrl(), '/') . '/' . $model->storage_path;
    }

    public function store($model, $file) {
        $path = $this->generatePath($model->toArray());
        //die($path);
        // Copy file
        try {
            $this->_mkdir(dirname(ROOT_PATH . '/' . $path));
            $this->_copy($file, ROOT_PATH . '/' . $path);
            @chmod(ROOT_PATH . '/' . $path, 0777);
        } catch (Exception $e) {
            @unlink(ROOT_PATH . '/' . $path);
            throw $e;
        }

        return $path;
    }
    
    public function generatePath($params){
        extract($params);
        
        $path = 'public' . '/';
        $path .= $parent_type . '/';
        $path .= $parent_id . '/';

        $path .= sprintf("%04x", $file_id)
                . '_' . substr($hash, 4, 4)
                . '.' . $extension;

        return $path;
    }

    public function read($model) {
        $file = ROOT_PATH . '/' . $model->storage_path;
        return @file_get_contents($file);
    }

    public function write($model, $data) {
        // Write data
        $path = $this->generatePath($model->toArray());

        try {
            $this->_mkdir(dirname(ROOT_PATH . '/' . $path));
            $this->_write(ROOT_PATH . '/' . $path, $data);
            @chmod($path, 0777);
        } catch (Exception $e) {
            @unlink(ROOT_PATH . '/' . $path);
            throw $e;
        }

        return $path;
    }

    public function remove($model) {
        if (!empty($model->storage_path)) {
            $this->_delete(ROOT_PATH . '/' . $model->storage_path);
        }
    }

    public function temporary($model) {
        $file = ROOT_PATH . '/' . $model->storage_path;
        $tmp_file = ROOT_PATH . '/files/temporary/' . basename($model['storage_path']);
        $this->_copy($file, $tmp_file);
        @chmod($tmp_file, 0777);
        return $tmp_file;
    }

    public function removeFile($path) {
        $this->_delete($path);
    }
    
}

?>
