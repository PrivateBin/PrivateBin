<?php
/**
 * VizHash_GD
 *
 * Visual Hash implementation in php4+GD,
 * stripped down and modified version for ZeroBin
 *
 * @link      http://sebsauvage.net/wiki/doku.php?id=php:vizhash_gd
 * @copyright 2012 Sébastien SAUVAGE (sebsauvage.net)
 * @license   http://www.opensource.org/licenses/zlib-license.php The zlib/libpng License
 * @version   0.0.4 beta ZeroBin 0.22
 */

/**
 * vizhash16x16
 *
 * Example:
 * $vz = new vizhash16x16();
 * $data = $vz->generate('hello');
 * header('Content-type: image/png');
 * echo $data;
 * exit;
 */

class vizhash16x16
{
    /**
     * hash values
     *
     * @access private
     * @var    array
     */
    private $VALUES;

    /**
     * index of current value
     *
     * @access private
     * @var    int
     */
    private $VALUES_INDEX;

    /**
     * image width
     *
     * @access private
     * @var    int
     */
    private $width;

    /**
     * image height
     *
     * @access private
     * @var    int
     */
    private $height;

    /**
     * salt used when generating the image
     *
     * @access private
     * @var    string
     */
    private $salt;

    /**
     * constructor
     *
     * @access public
     * @return void
     */
    public function __construct()
    {
        $this->width  = 16;
        $this->height = 16;
        $this->salt   = serversalt::get();
    }

    /**
     * Generate a 16x16 png corresponding to $text.
     *
     * @access public
     * @param  string $text
     * @return string PNG data. Or empty string if GD is not available.
     */
    public function generate($text)
    {
        if (!function_exists('gd_info')) return '';

        // We hash the input string.
        $hash=hash('sha1',$text.$this->salt).hash('md5',$text.$this->salt);
        $hash=$hash.strrev($hash);  # more data to make graphics

        // We convert the hash into an array of integers.
        $this->VALUES=array();
        for($i=0; $i<strlen($hash); $i=$i+2){ array_push($this->VALUES,hexdec(substr($hash,$i,2))); }
        $this->VALUES_INDEX=0; // to walk the array.

        // Then use these integers to drive the creation of an image.
        $image = imagecreatetruecolor($this->width,$this->height);

        $r0 = $this->getInt();$r=$r0;
        $g0 = $this->getInt();$g=$g0;
        $b0 = $this->getInt();$b=$b0;

        // First, create an image with a specific gradient background.
        $op='v'; if (($this->getInt()%2)==0) { $op='h'; };
        $image = $this->degrade($image,$op,array($r0,$g0,$b0),array(0,0,0));

        for($i=0; $i<7; $i=$i+1)
        {
            $action=$this->getInt();
            $color = imagecolorallocate($image, $r,$g,$b);
            $r = ($r0 + $this->getInt()/25)%256;
            $g = ($g0 + $this->getInt()/25)%256;
            $b = ($b0 + $this->getInt()/25)%256;
            $r0=$r; $g0=$g; $b0=$b;
            $this->drawshape($image,$action,$color);
        }

        $color = imagecolorallocate($image,$this->getInt(),$this->getInt(),$this->getInt());
        $this->drawshape($image,$this->getInt(),$color);
        ob_start();
        imagepng($image);
        $imagedata = ob_get_contents();
        ob_end_clean();
        imagedestroy($image);

        return $imagedata;
    }

    /**
     * Returns a single integer from the $VALUES array (0...255)
     *
     * @access private
     * @return int
     */
    private function getInt()
    {
        $v= $this->VALUES[$this->VALUES_INDEX];
        $this->VALUES_INDEX++;
        $this->VALUES_INDEX %= count($this->VALUES); // Warp around the array
        return $v;
    }

    /**
     * Returns a single integer from the array (roughly mapped to image width)
     *
     * @access private
     * @return int
     */
    private function getX()
    {
        return $this->width*$this->getInt()/256;
    }

    /**
     * Returns a single integer from the array (roughly mapped to image height)
     *
     * @access private
     * @return int
     */
    private function getY()
    {
        return $this->height*$this->getInt()/256;
    }

    /**
     * Gradient function
     *
     * taken from:
     * http://www.supportduweb.com/scripts_tutoriaux-code-source-41-gd-faire-un-degrade-en-php-gd-fonction-degrade-imagerie.html
     *
     * @access private
     * @param  resource $img
     * @param  string $direction
     * @param  array $color1
     * @param  array $color2
     * @return resource
     */
    private function degrade($img,$direction,$color1,$color2)
    {
            if($direction=='h') { $size = imagesx($img); $sizeinv = imagesy($img); }
            else { $size = imagesy($img); $sizeinv = imagesx($img);}
            $diffs = array(
                    (($color2[0]-$color1[0])/$size),
                    (($color2[1]-$color1[1])/$size),
                    (($color2[2]-$color1[2])/$size)
            );
            for($i=0;$i<$size;$i++)
            {
                    $r = $color1[0]+($diffs[0]*$i);
                    $g = $color1[1]+($diffs[1]*$i);
                    $b = $color1[2]+($diffs[2]*$i);
                    if($direction=='h') { imageline($img,$i,0,$i,$sizeinv,imagecolorallocate($img,$r,$g,$b)); }
                    else { imageline($img,0,$i,$sizeinv,$i,imagecolorallocate($img,$r,$g,$b)); }
            }
            return $img;
    }

    /**
     * Draw a shape
     *
     * @access private
     * @param  resource $image
     * @param  int $action
     * @param  int $color
     * @return void
     */
    private function drawshape($image,$action,$color)
    {
        switch($action%7)
        {
            case 0:
                ImageFilledRectangle ($image,$this->getX(),$this->getY(),$this->getX(),$this->getY(),$color);
                break;
            case 1:
            case 2:
                ImageFilledEllipse ($image, $this->getX(), $this->getY(), $this->getX(), $this->getY(), $color);
                break;
            case 3:
                $points = array($this->getX(), $this->getY(), $this->getX(), $this->getY(), $this->getX(), $this->getY(),$this->getX(), $this->getY());
                ImageFilledPolygon ($image, $points, 4, $color);
                break;
            default:
                $start=$this->getInt()*360/256; $end=$start+$this->getInt()*180/256;
                ImageFilledArc ($image, $this->getX(), $this->getY(), $this->getX(), $this->getY(),$start,$end,$color,IMG_ARC_PIE);
        }
    }
}
