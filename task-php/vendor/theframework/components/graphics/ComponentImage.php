<?php
/**
 * @author Eduardo Acevedo Farje.
 * @link eduardoaf.com
 * @version 2.0.0
 * @name ComponentImage
 * @date 30-06-2019 09:40 (SPAIN)
 * @file ComponentImage.php
 * @requires:
 *
 */
class ComponentImage
{
    protected $extensions = array("jpg","png","jpeg","gif","tiff","tif","bmp");
    protected $pathFolderTarget;
    protected $pathFolderSource;

    protected $filenameSource;
    protected $filenameTarget;
    protected $extensionSource;
    protected $extensionTarget;
    protected $targetContent;

    protected $pathFileSource;
    protected $pathFileTarget;

    protected $thumbSizes;
    protected $thumbExtensions;

    protected $height;
    protected $width;
    protected $bitsSize;
    protected $pathsourceHttp;

    //META DATA
    protected $metaFilename;
    protected $metaFileDatetime;
    protected $metaFilesize;
    protected $metaFiletype;
    protected $metaMimetype;
    protected $metaSections;
    protected $metaHtml;
    protected $metaHeight;
    protected $metaWidth;
    protected $isMetaColor;
    protected $metaComment;

    public function __construct()
    {
        ;
    }

    protected function loadPathSource()
    {
        $this->pathFileSource = $this->buildFilepath();
    }

    protected function loadPathTarget()
    {
        $this->pathFileTarget = $this->buildFilepath(0);
    }

    public function loadTargetSize()
    {
        $pathFile = $this->buildFilepath(0);
        $size = getimagesize($pathFile);
        $this->width = $size[0];
        $this->height = $size[1];
    }

    public function loadTargetBytessize()
    {
        $pathFile = $this->buildFilepath(0);
        $this->bitsSize = filesize($pathFile);
    }

    private function loadMetadata($metaData)
    {
        $this->metaComment = $metaData["COMPUTED"]["UserComment"];
        $this->metaHtml = $metaData["COMPUTED"]["html"];
        $this->metaHeight = $metaData["COMPUTED"]["Width"];
        $this->metaHeight = $metaData["COMPUTED"]["Height"];
        $this->isMetaColor = $metaData["COMPUTED"]["IsColor"];
        $this->metaMimetype = $metaData["FILE"]["MimeType"];
        $this->metaFiletype = $metaData["FILE"]["FileType"];
        $this->metaFilesize = $metaData["FILE"]["FileSize"];
        $this->metaFilename = $metaData["FILE"]["FileName"];
        $this->metaFileDatetime = $metaData["FILE"]["FileDateTime"];
        $this->metaSections = $metaData["FILE"]["SectionsFound"];
    }

    public function loadTargetMetadata()
    {
        $pathFile = $this->buildFilepath(0);
        $metaData = exif_read_data($pathFile, 0, true);
        $this->loadMetadata($metaData);
    }

    public function loadSourceMetadata()
    {
        //sin argumentos carga source
        $pathFile = $this->buildFilepath();
        $metaData = exif_read_data($pathFile, 0, true);
        $this->loadMetadata($metaData);
    }

    protected function buildImageResized($imgToResize, $width, $height)
    {
        $imageCanvas = imagecreatetruecolor($width, $height);
        $srcWidth = imagesx($imgToResize);
        $srcHeight = imagesy($imgToResize);
        //imagecopyresized($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h)
        //imagecopyresized($oImageCanvas,$oImgToResize,0,0,0,0,$iWidth,$iHeight,$iSrcWidth,$iSrcHeight);
        imagecopyresampled($imageCanvas, $imgToResize, 0, 0, 0, 0, $width, $height, $srcWidth, $srcHeight);
        return $imageCanvas;
    }

    protected function buildImageCut($imgToCut, $width, $height)
    {
        //$dst_x, $dst_y: Empezar a escribir en el lienzo desde
        $imageCanvas = imagecreatetruecolor($width, $height);
        $srcWidth = imagesx($imgToCut);
        $srcHeight = imagesy($imgToCut);
        //imagecopyresampled($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
        //imagecopyresampled($oImageCanvas,$oImgToCut,0,0,100,0,$iWidth,$iHeight,$iSrcWidth,$iSrcHeight);
        //imagecopy($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h)
        imagecopy($imageCanvas, $imgToCut, 0, 0, 0, 0, $srcWidth, $srcHeight);
        return $imageCanvas;
    }

    protected function getPercent($currSize, $finalSize)
    {
        //x=fS.100/cS
        $percent = null;
        if ($currSize != 0) {
            //$iPercent = floor(100*($iBig-$iSmall)/$iBig);
            //$iPercent = floor(($iFinalSize*100/$iCurrSize));
            $percent = ($finalSize * 100 / $currSize);
        }
        return $percent;
    }

    protected function getAdjustedLength($length, $percent)
    {
        return ($percent * $length) / 100;
    }

    public function makeThumb()
    {
        $this->loadPathSource();
        $this->loadPathTarget();

        if (!$this->width) {
            $this->width = 0;
        }
        if (!$this->height) {
            $this->height = 0;
        }

        $extension = $this->getExtension($this->pathFileSource);
        if ($extension == "jpg" || $extension == "jpeg") {
            $srcImage = imagecreatefromjpeg($this->pathFileSource);
        } elseif ($extension == "png") {
            $srcImage = imagecreatefrompng($this->pathFileSource);
        } elseif ($extension == "bmp") {
            $srcImage = imagecreatefromwbmp($this->pathFileSource);
        } elseif ($extension == "gif") {
            $srcImage = imagecreatefromgif($this->pathFileSource);
        }
        //imagecreatefrompng($filename)
        //bug($oSrcImage,"osourceimage");
        $srcWidth = imagesx($srcImage);
        $srcHeight = imagesy($srcImage);
        //bug($this->sPathFileSource,"source");bug($this->sPathFileTarget,"target");
        $imageCut = null;
        //caso 1
        if ($srcWidth > $this->width || $srcHeight > $this->height) {
            $perWidth = $this->getPercent($srcWidth, $this->width);
            $perHeight = $this->getPercent($srcHeight, $this->height);

            $perAdjust = $perWidth;
            if ($perHeight > $perWidth) {
                $perAdjust = $perHeight;
            }

            $tempWidth = $this->getAdjustedLength($srcWidth, $perAdjust);
            $tempHeight = $this->getAdjustedLength($srcHeight, $perAdjust);

            //bug("fW:$this->iWidth,fW:$this->iHeight,srcW:$iSrcWidth,srcH:$iSrcHeight|perW:$fPerWidth,perH:$fPerHeight,perAdj:$fPerAdjust|tmpWidth:$iTempWidth,tmpHeight:$iTempHeight");
            $imageCut = $this->buildImageResized($srcImage, $tempWidth, $tempHeight);
            $imageCut = $this->buildImageCut($imageCut, $this->width, $this->height);
        }
        //bug("isrch:$iSrcHeight,isrcw:$iSrcWidth,thisw:$this->iWidth,thish:$this->iHeight");
        if ($imageCut) {
            //bug($oImageCut,"oImagecut");
            $isCreated = $this->createImageByExtension($imageCut, $this->pathFileTarget, $extension);
            //       header('Content-Type: image/jpeg');
            //imagejpeg($oImageCut);
            //bug($isCreated,"make_thumb");
            if (!$isCreated) {
                $this->addError("Error: Thumb not created");
            }
        } else {
            $this->addError("Error: No image cut to create thumb");
        }
    }//make_thumb()

    protected function createImageByExtension($image, $pathFileTarget, $extension)
    {
        $isCreated = false;
        $extension = strtolower(trim($extension));
        //bug($sExtension,"create_image_by_extension");
        //array("jpg","png","jpeg","gif","tiff","tif","bmp");
        switch($extension) {
            case "jpg":
            case "jpeg":
            case "tiff":
            case "tif":
                $isCreated = imagejpeg($image, $pathFileTarget, 100);
                break;
            case "png":
                $isCreated = imagepng($image, $pathFileTarget, 0);
                //bug($oImage,"oimage");bug($sPathFileTarget,"targetfile");bug($sIsCreated,"iscreated");die;
                break;
            case "gif":
                $isCreated = imagegif($image, $pathFileTarget);
                break;
            case "bmp":
                $isCreated = image2wbmp($image, $pathFileTarget);
                break;
            default:
                $isCreated = imagejpeg($image, $pathFileTarget, 100);
                break;
        }
        return $isCreated;
    }


    protected function loadSourceExtension()
    {
        $pathInfo = pathinfo($this->filenameSource);
        $extension = $extension = $pathInfo["extension"];
        $this->cleanExtension($extension);
        $this->extensionSource = $extension;
    }

    protected function loadTargetExtension()
    {
        $pathInfo = pathinfo($this->filenameTarget);
        $extension = $extension = $pathInfo["extension"];
        $this->cleanExtension($extension);
        $this->extensionSource = $extension;
    }

    protected function cleanExtension(&$extension)
    {
        foreach($this->extensions as $ext) {
            if (strstr($extension, $ext)) {
                $extension = $ext;
                return;
            }
        }
        $extension = "txt";
    }

    private function fixFolderpath($pathFolder)
    {
        $pathFolderDs = $pathFolder.DIRECTORY_SEPARATOR;
        //windows (cambia los dobles por uno solo)
        $pathFolderDs = str_replace("\\\\", DIRECTORY_SEPARATOR, $pathFolderDs);
        //unix
        $pathFolderDs = str_replace("//", DIRECTORY_SEPARATOR, $pathFolderDs);
        return $pathFolderDs;
    }

    private function buildFilepath($isSource = 1)
    {
        $pathFile = "";
        if ($isSource) {
            $pathFolder = $this->fixFolderpath($this->pathFolderSource);
            $pathFile = $pathFolder.$this->filenameSource;
            if ($this->pathsourceHttp) {
                $pathFile = $this->pathsourceHttp;
            }
        } else {
            $pathFolder = $this->fixFolderpath($this->pathFolderTarget);
            $pathFile = $pathFolder.$this->filenameTarget;
        }
        return $pathFile;
    }

    protected function getExtension($pathFile)
    {
        $extension = "";
        if (is_file($pathFile)) {
            $pathInfo = pathinfo($pathFile);
            $extension = $pathInfo["extension"];
            $this->cleanExtension($extension);
        } else {
            $extension = "no_extension_nofile";
        }
        return $extension;
    }

    //==================================
    //             SETS
    //==================================
    public function setFilenameSource($fileName)
    {
        $this->filenameSource = $fileName;
        $this->loadSourceExtension();
    }
    public function setFilenameTarget($fileName)
    {
        $this->filenameTarget = $fileName;
        $this->loadTargetExtension();
    }
    public function setPathFolderSource($pathFolder)
    {
        $this->pathFolderSource = $pathFolder;
    }
    public function setPathFolderTarget($pathFolder)
    {
        $this->pathFolderTarget = $pathFolder;
    }
    public function setTargetContent($content)
    {
        $this->targetContent = $content;
    }

    public function setPathsourceHttp($value)
    {
        $this->pathsourceHttp = $value;
    }
    public function setMetaFilename($value)
    {
        $this->metaFilename = $value;
    }
    public function setMetaFiledatetime($value)
    {
        $this->metaFileDatetime = $value;
    }
    public function setMetaFilesize($value)
    {
        $this->metaFilesize = $value;
    }
    public function setMetaFiletype($value)
    {
        $this->metaFiletype = $value;
    }
    public function setMetaMimetype($value)
    {
        $this->metaMimetype = $value;
    }
    public function setMetaSections($value)
    {
        $this->metaSections = $value;
    }
    public function setMetaHtml($value)
    {
        $this->metaHtml = $value;
    }
    public function setMetaHeight($value)
    {
        $this->metaHeight = $value;
    }
    public function setMetaWidth($value)
    {
        $this->metaWidth = $value;
    }
    public function setIsMetaColor($value)
    {
        $this->isMetaColor = $value;
    }
    public function setMetaContent($value)
    {
        $this->metaComment = $value;
    }
    public function setWidth($value)
    {
        $this->width = $value;
    }
    public function setHeight($value)
    {
        $this->height = $value;
    }

    //==================================
    //             GETS
    //==================================
    public function getWidth()
    {
        return $this->width;
    }
    public function getHeight()
    {
        return $this->height;
    }
    public function getPathsourceHttp()
    {
        return $this->pathsourceHttp;
    }
    public function getMetaFilename()
    {
        return $this->metaFilename;
    }
    public function getMetaFiledatetime()
    {
        return $this->metaFileDatetime;
    }
    public function getMetaFilesize()
    {
        return $this->metaFilesize;
    }
    public function getMetaFiletype()
    {
        return $this->metaFiletype;
    }
    public function getMetaMimetype()
    {
        return $this->metaMimetype;
    }
    public function getMetaSections()
    {
        return $this->metaSections;
    }
    public function getMetaHtml()
    {
        return $this->metaHtml;
    }
    public function getMetaHeight()
    {
        return $this->metaHeight;
    }
    public function getMetaWidth()
    {
        return $this->metaWidth;
    }
    public function getIsMetaColor()
    {
        return $this->isMetaColor;
    }
    public function getMetaContent()
    {
        return $this->metaComment;
    }
    public function getBytesSize()
    {
        return $this->bitsSize;
    }

    public function getSourceExtension()
    {
        return $this->extensionSource;
    }
    public function getTargetExtension()
    {
        return $this->extensionTarget;
    }

}
