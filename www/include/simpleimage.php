<?php
/*
 * File: SimpleImage.php
* Author: Simon Jarvis
* Copyright: 2006 Simon Jarvis
* Date: 08/11/06
* Link: http://www.white-hat-web-design.co.uk/articles/php-image-resizing.php
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details:
* http://www.gnu.org/licenses/gpl.html
*
* Modification: Artem Yankovskiy (artemyankovskiy@gmail.com)
* Date: 26/02/2015
*/
class SimpleImage {

    private $image;
    private $image_type;

    /**
     * Загружает файл в память
     * @param string $filename имя файла
     */
    public function load($filename) {
        $image_info = getimagesize($filename);
        $this->image_type = $image_info[2];

        if ( $this->image_type == IMAGETYPE_JPEG ) {
            $this->image = imagecreatefromjpeg($filename);
        } elseif ( $this->image_type == IMAGETYPE_GIF ) {
            $this->image = imagecreatefromgif($filename);
        } elseif ( $this->image_type == IMAGETYPE_PNG ) {
            $this->image = imagecreatefrompng($filename);
        }
    }

    /**
     * Сохраняет изображение из памяти в файл
     * @param string $filename имя файла
     * @param int $image_type тим изображения (IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG)
     * @param int $compression дополнительный параметр отвечающий за сжатие изображение. Принимает значение
     * от 0 до 10. 0 - худшее качество, минимальный размер; 100 - лучшее качество, максимальный размер.
     * Значение по умолчанию 75
     * @param int $permissions дополнительный параметр отвечающий за права выставляемые на файл.
     * Принимает значения для unix'ной команды chmod, либо null если права выставлять не нужно.
     * По умолчанию null
     */
    public function save($filename, $image_type = IMAGETYPE_JPEG, $compression = 75, $permissions = null) {
        if ( $image_type == IMAGETYPE_JPEG ) {
            imagejpeg($this->image,$filename,$compression);
        } elseif ( $image_type == IMAGETYPE_GIF ) {
            imagegif($this->image,$filename);
        } elseif ( $image_type == IMAGETYPE_PNG ) {
            imagepng($this->image,$filename);
        }

        if ( $permissions != null ) {
            chmod($filename,$permissions);
        }
    }

    /**
     * Выводит изображение из памяти в браузер
     * @param int $image_type тим изображения (IMAGETYPE_JPEG, IMAGETYPE_GIF, IMAGETYPE_PNG)
     */
    public function output($image_type = IMAGETYPE_JPEG) {
        if ( $image_type == IMAGETYPE_JPEG ) {
            imagejpeg($this->image);
        } elseif ( $image_type == IMAGETYPE_GIF ) {
            imagegif($this->image);
        } elseif ( $image_type == IMAGETYPE_PNG ) {
            imagepng($this->image);
        }
    }

    /**
     * Возвращает ширину изображения
     * @return int ширина изображения
     */
    public function getWidth() {
        return imagesx($this->image);
    }

    /**
     * Возвращает высоту изображения
     * @return int высота изображения
     */
    public function getHeight() {
        return imagesy($this->image);
    }

    /**
     * Изменение разрешения изображения по высоте с сохранением пропорций
     * @param int $height новое значение высоты изображения
     */
    public function resizeToHeight($height) {
        $ratio = $height / $this->getHeight();
        $width = $this->getWidth() * $ratio;
        $this->resize($width, $height);
    }

    /**
     * Изменение разрешения изображения по ширине с сохранением пропорций
     * @param int $width новое значение ширины изображения
     */
    public function resizeToWidth($width) {
        $ratio = $width / $this->getWidth();
        $height = $this->getheight() * $ratio;
        $this->resize($width, $height);
    }

    /**
     * Масштабирование изображения
     * @param float $scale новый масштаб изображения в процентах от оригинального
     */
    public function scale($scale) {
        $width = $this->getWidth() * $scale/100;
        $height = $this->getheight() * $scale/100;
        $this->resize($width, $height);
    }

    /**
     * Изменение размера  изображения без сохранения пропорций
     * @param int $width новая ширина изображения
     * @param int $height новая высота изображения
     */
    public function resize($width, $height) {
        $new_image = imagecreatetruecolor($width, $height);
        imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, $width, $height, $this->getWidth(), $this->getHeight());
        $this->image = $new_image;
    }

    /**
     * Обрабатывает загруженное изображение.
     * Изменяет размер загруженного изображения, меняет качество
     * @param $_FILE["image"] $file загруженное изображение
     * @param string $uploadfile путь до сохраняемого изображения
     * @param int $maxDimen - максимальное разрешение по длинной стороне
     * @param boolean $isSquare - true если необходимо подогнать под квадрат
     * @return boolean true в случае успешной обработки изображения
     */
    public static function handleUploadedFile($file, $uploadfile, $maxDimen, $isSquare = false) {
        $success = false;
        $whitelist = array(".jpg",".jpeg");
        try {

            if ($file["size"] > 1512000 || $file["size"] < 4096) {
                throw new Exception("Некорректный размер изображения");
            }

            $i = 0;
            foreach ($whitelist as $item) {
                if(preg_match("/$item/i", $file["name"])) {
                    $i++;
                }
            }
            if($i!=1) {
                throw new Exception("Неразрешенное расширение файла");
            }

            if ($file["type"] != "image/jpeg") {
                throw new Exception("Неразрешенный формат файла");
            }

            $image = new SimpleImage();
            $image->load($file["tmp_name"]);

            if (!$isSquare) {
                $width = $image->getWidth();
                $height = $image->getHeight();

                if ($width > $height) {
                    if ($width > $maxDimen) {
                        $width = $maxDimen;
                    }

                    $image->resizeToWidth($width);
                } else {
                    if ($height > $maxDimen) {
                        $height = $maxDimen;
                    }

                    $image->resizeToHeight($height);
                }
            } else {
                $image->resize($maxDimen, $maxDimen);
            }

            $image->save($uploadfile, IMAGETYPE_JPEG, 60);

            $success = true;
        } catch (Exception $e) {

        }
        return $success;
    }

}