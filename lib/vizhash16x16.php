<?php
// VizHash_GD 0.0.4 beta ZeroBin 0.15
// Visual Hash implementation in php4+GD, stripped down and modified version for ZeroBin
// See: http://sebsauvage.net/wiki/doku.php?id=php:vizhash_gd
// This is free software under the zlib/libpng licence
// http://www.opensource.org/licenses/zlib-license.php
/* Example:
    $vz = new vizhash16x16();
    $data = $vz->generate('hello');
    header('Content-type: image/png');
    echo $data;
    exit;
*/
class vizhash16x16
{
    private $VALUES;
    private $VALUES_INDEX;
    private $width;
    private $height;
    private $salt;
    function __construct()
    {
        $this->width=16;
        $this->height=16;

        // Read salt from file (and create it if does not exist).
        // The salt will make vizhash avatar unique on each ZeroBin installation
        // to prevent IP checking.
        $saltfile = PATH . 'data/salt.php';
        if (!is_file($saltfile))
            file_put_contents($saltfile,'<?php /* |'.$this->randomSalt().'| */ ?>');
        $items=explode('|',file_get_contents($saltfile));
        $this->salt = $items[1];
    }

    // Generate a 16x16 png corresponding to $text.
    // Input: $text (string)
    // Output: PNG data. Or empty string if GD is not available.
    function generate($text)
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

    // Generate a large random hexadecimal salt.
    private function randomSalt()
    {
        $randomSalt='';
        for($i=0;$i<6;$i++) { $randomSalt.=base_convert(mt_rand(),10,16); }
        return $randomSalt;
    }


    private function getInt() // Returns a single integer from the $VALUES array (0...255)
    {
        $v= $this->VALUES[$this->VALUES_INDEX];
        $this->VALUES_INDEX++;
        $this->VALUES_INDEX %= count($this->VALUES); // Warp around the array
        return $v;
    }
    private function getX() // Returns a single integer from the array (roughly mapped to image width)
    {
        return $this->width*$this->getInt()/256;
    }

    private function getY() // Returns a single integer from the array (roughly mapped to image height)
    {
        return $this->height*$this->getInt()/256;
    }

    # Gradient function taken from:
    # http://www.supportduweb.com/scripts_tutoriaux-code-source-41-gd-faire-un-degrade-en-php-gd-fonction-degrade-imagerie.html
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
            case 4:
            case 5:
            case 6:
                $start=$this->getInt()*360/256; $end=$start+$this->getInt()*180/256;
                ImageFilledArc ($image, $this->getX(), $this->getY(), $this->getX(), $this->getY(),$start,$end,$color,IMG_ARC_PIE);
                break;
        }
    }
}

?>