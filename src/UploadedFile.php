<?php

namespace haohetao\file;

use yii\base\ErrorException;
use yii\helpers\BaseFileHelper;
use Yii;
use yii\helpers\Html;

/**
 * Class UploadedBase64File
 *
 * @package aminkt\uploadManager\classes
 *
 * @author  Amin Keshavarz <amin@keshavarz.pro>
 */
class UploadedFile extends \yii\web\UploadedFile
{
    private static $_files;

    /**
     * @param $inputName
     * @return UploadedFile|null
     * @throws ErrorException
     */
    public static function uploadBase64File($file, $name)
    {
        if (!$file) {
            return null;
        }
        if (strncmp($file, 'data:', 5) == 0) {
            $fileParse = explode(',', $file);
            $file = $fileParse[1];
        } else {
            return null;
        }

        $fileDecoded = base64_decode($file);
        $f = finfo_open();

        $mimeType = finfo_buffer($f, $fileDecoded, FILEINFO_MIME_TYPE);
        $sizes = strlen($fileDecoded);
        $ext = BaseFileHelper::getExtensionsByMimeType($mimeType);
        $tempName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('upload_') . '.' . $ext[0];
        if (!file_put_contents($tempName, $fileDecoded)) {
            throw new ErrorException($tempName);
        }
        if (!self::$_files) {
            self::$_files = [];
        }

        self::$_files[$name] = [
            'name' => "{$name}.{$ext[0]}",
            'tempName' => $tempName,
            'type' => $mimeType,
            'size' => $sizes,
            'error' => UPLOAD_ERR_OK,
        ];
        return isset(self::$_files[$name]) ? new static(self::$_files[$name]) : null;
    }

    /**
     * @param string[] $inputName
     * @return UploadedFile[]|null
     */
    public static function uploadBase64Files($files, $name)
    {
        if (!is_array($files)) {
            return null;
        }
        $fileInstances = [];
        foreach ($files as $file) {
            if (strncmp($file, 'data:', 5) == 0) {
                $fileParse = explode(',', $file);
                $file = $fileParse[1];
            } else {
                $file = null;
            }
            if ($file) {
                $fileDecoded = base64_decode($file);
                $f = finfo_open();

                $mimeType = finfo_buffer($f, $fileDecoded, FILEINFO_MIME_TYPE);
                $sizes = strlen($fileDecoded);
                $ext = BaseFileHelper::getExtensionsByMimeType($mimeType);
                $tempName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('upload_') . '.' . $ext[0];
                if (!file_put_contents($tempName, $fileDecoded)) {
                    throw new ErrorException('file write failed:' . $tempName);
                }
                if (!self::$_files) {
                    self::$_files = [];
                }
                $fileObj = [
                    'name' => "{$name}.{$ext[0]}",
                    'tempName' => $tempName,
                    'type' => $mimeType,
                    'size' => $sizes,
                    'error' => UPLOAD_ERR_OK,
                ];
                self::$_files[$name][] = $fileObj;
                $fileInstances[] = new Static($fileObj);
            }
        }
        return isset(self::$_files[$name]) ? $fileInstances : null;
    }

    /**
     * Returns an uploaded file according to the given file input name.
     * The name can be a plain string or a string like an array element (e.g. 'Post[imageFile]', or 'Post[0][imageFile]').
     * @param string $name the name of the file input field.
     * @return null|\yii\web\UploadedFile the instance of the uploaded file.
     * Null is returned if no file is uploaded for the specified name.
     */
    public static function getInstanceByName($name)
    {
        if (static::isBase64()) {
            return null;
        }
        return parent::getInstanceByName($name);
    }

    /**
     * Returns an array of uploaded files corresponding to the specified file input name.
     * This is mainly used when multiple files were uploaded and saved as 'files[0]', 'files[1]',
     * 'files[n]'..., and you can retrieve them all by passing 'files' as the name.
     * @param string $name the name of the array of files
     * @return \yii\web\UploadedFile[] the array of UploadedFile objects. Empty array is returned
     * if no adequate upload was found. Please note that this array will contain
     * all files from all sub-arrays regardless how deeply nested they are.
     */
    public static function getInstancesByName($name)
    {
        if (static::isBase64()) {
            return null;
        }
        return parent::getInstancesByName($name);
    }

    /**
     * Returns an uploaded file for the given model attribute.
     * The file should be uploaded using [[\yii\widgets\ActiveField::fileInput()]].
     * @param \yii\base\Model $model the data model
     * @param string $attribute the attribute name. The attribute name may contain array indexes.
     * For example, '[1]file' for tabular file uploading; and 'file[1]' for an element in a file array.
     * @return null|\yii\web\UploadedFile the instance of the uploaded file.
     * Null is returned if no file is uploaded for the specified model attribute.
     * @see getInstanceByName()
     */
    public static function getInstance($model, $attribute)
    {
        if (static::isBase64()) {
            return static::uploadBase64File($model->$attribute, $attribute);
        }
        $name = Html::getInputName($model, $attribute);
        return static::getInstanceByName($name);
    }

    /**
     * Returns all uploaded files for the given model attribute.
     * @param \yii\base\Model $model the data model
     * @param string $attribute the attribute name. The attribute name may contain array indexes
     * for tabular file uploading, e.g. '[1]file'.
     * @return \yii\web\UploadedFile[] array of UploadedFile objects.
     * Empty array is returned if no available file was found for the given attribute.
     */
    public static function getInstances($model, $attribute)
    {
        if (static::isBase64()) {
            $file = $model->$attribute;
            if (is_array($file)) {
                $files = $file;
            } else {
                $files = [$file];
            }
            return static::uploadBase64Files($files, $attribute);
        }
        $name = Html::getInputName($model, $attribute);
        return static::getInstancesByName($name);
    }

    /**
     * Saves the uploaded file.
     * Note that this method uses php's move_uploaded_file() method. If the target file `$file`
     * already exists, it will be overwritten.
     *
     * @param string $file the file path used to save the uploaded file
     * @param bool $deleteTempFile whether to delete the temporary file after saving.
     *                               If true, you will not be able to save the uploaded file again in the current
     *                               request.
     *
     * @return bool true whether the file is saved successfully
     * @see error
     */
    public function saveAs($file, $deleteTempFile = true)
    {
        if (!$this->isBase64()) {
            return parent::saveAs($file, $deleteTempFile);
        }
        if ($this->error == UPLOAD_ERR_OK) {
            if ($deleteTempFile) {
                return rename($this->tempName, $file);
            } else {
                return copy($this->tempName, $file);
            }
            $file = file_put_contents($file, base64_decode($this->tempName));
            return $file;
        }

        return false;
    }

    /**
     * 是否base64上传
     * @return bool
     */
    public static function isBase64()
    {
        $mime = Yii::$app->request->getContentType();
        if (strncasecmp($mime, 'application/json', 16) === 0) {
            return true;
        }
        if (strncasecmp($mime, 'text/json', 9) === 0) {
            return true;
        }
        if (strncasecmp($mime, 'application/javascript', 22) === 0) {
            return true;
        }
        if (strncasecmp($mime, 'text/javascript', 15) === 0) {
            return true;
        }
        return false;
    }
}
