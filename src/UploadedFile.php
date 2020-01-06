<?php

namespace haohetao\file;

use yii\base\ErrorException;
use yii\helpers\BaseFileHelper;
use Yii;

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
     * @param string $method
     * @return UploadedFile|null
     * @throws ErrorException
     */
    public static function uploadBase64File($inputName, $method = 'post')
    {
        $file = \Yii::$app->getRequest()->$method($inputName);
        if (!$file) {
            return null;
        }
        if (strncmp($file, 'data:', 5) == 0) {
            $fileParse = explode(',', $file);
            $file = $fileParse[1];
        }
        $fileDecoded = base64_decode($file);
        $f = finfo_open();

        if (empty($mimeType)) {
            $mimeType = finfo_buffer($f, $fileDecoded, FILEINFO_MIME_TYPE);
        }
        $sizes = strlen($fileDecoded);
        $ext = BaseFileHelper::getExtensionsByMimeType($mimeType);
        $tempName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('upload_') . '.' . $ext[0];
        if (!file_put_contents($tempName, $fileDecoded)) {
            throw new ErrorException($tempName);
        }
        if (!self::$_files) {
            self::$_files = [];
        }

        self::$_files[$inputName] = [
            'name' => "{$inputName}.{$ext[0]}",
            'tempName' => $tempName,
            'type' => $mimeType,
            'size' => $sizes,
            'error' => UPLOAD_ERR_OK,
        ];
        return isset(self::$_files[$inputName]) ? new static(self::$_files[$inputName]) : null;
    }

    /**
     * @param $inputName
     * @param string $method
     * @return UploadedFile[]|null
     */
    public static function uploadBase64Files($inputName, $method = 'post')
    {
        $files = \Yii::$app->getRequest()->$method($inputName);
        if (!$files) {
            return null;
        }
        $fileInstances = [];
        foreach ($files as $file) {
            if (strncmp($file, 'data:', 5) == 0) {
                $fileParse = explode(',', $file);
                $file = $fileParse[1];
            }
            $fileDecoded = base64_decode($file);
            $f = finfo_open();

            if (empty($mimeType)) {
                $mimeType = finfo_buffer($f, $fileDecoded, FILEINFO_MIME_TYPE);
            }
            $sizes = strlen($fileDecoded);
            $ext = BaseFileHelper::getExtensionsByMimeType($mimeType);
            $tempName = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('upload_') . '.' . $ext[0];
            if (!file_put_contents($tempName, $fileDecoded) ){
                throw new ErrorException('file write failed:' . $tempName);
            }
            if (!self::$_files) {
                self::$_files = [];
            }
            self::$_files[$inputName][] = [
                'name' => "{$inputName}.{$ext[0]}",
                'tempName' => $tempName,
                'type' => $mimeType,
                'size' => $sizes,
                'error' => UPLOAD_ERR_OK,
            ];
            $fileInstances[] = new Static([
                'name' => "{$inputName}.{$ext[0]}",
                'tempName' => $tempName,
                'type' => $mimeType,
                'size' => $sizes,
                'error' => UPLOAD_ERR_OK,
            ]);
        }
        return isset(self::$_files[$inputName]) ? $fileInstances : null;
    }

    /**
     * Saves the uploaded file.
     * Note that this method uses php's move_uploaded_file() method. If the target file `$file`
     * already exists, it will be overwritten.
     *
     * @param string $file           the file path used to save the uploaded file
     * @param bool   $deleteTempFile whether to delete the temporary file after saving.
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
                return rename($this->tempName , $file);
            } else {
                return copy($this->tempName , $file);
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
    public function isBase64()
    {
        $mime = Yii::$app->request->getContentType();
        if (strtolower($mime) == 'application/json') {
            return true;
        }
        if (strtolower($mime) == 'text/json') {
            return true;
        }
        if (strtolower($mime) == 'application/javascript') {
            return true;
        }
        if (strtolower($mime) == 'text/javascript') {
            return true;
        }
        return false;
    }
}
