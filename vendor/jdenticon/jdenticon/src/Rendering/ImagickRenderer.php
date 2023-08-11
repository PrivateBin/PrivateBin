<?php
namespace Jdenticon\Rendering;

class ImagickRendererLine
{
    /**
     * Creates a new line from a vector.
     * 
     * @param float $x0 Vector start x coordinate.
     * @param float $y0 Vector start y coordinate.
     * @param float $x1 Vector end x coordinate.
     * @param float $y1 Vector end y coordinate.
     */
    public static function fromVector($x0, $y0, $x1, $y1) 
    {
        $line = new ImagickRendererLine();

        $line->Px = $x0;
        $line->Py = $y0;
        
        $line->rx = $x1 - $x0;
        $line->ry = $y1 - $y0;

        return $line;
    }

    /**
     * Moves the line to the right relative the direction vector.
     * 
     * @param float $distance  The number of pixels to move the line.
     */
    public function moveRight($distance) 
    {
        // Ortogonal direction vector
        $rx = -$this->ry;
        $ry = $this->rx;
        
        $multiplier = $distance / sqrt($rx * $rx + $ry * $ry);

        $this->Px += $rx * $multiplier;
        $this->Py += $ry * $multiplier;
    }

    /**
     * Computes the point at which two lines intersect.
     * 
     * @return Point|null
     */
    public static function intersection(
        ImagickRendererLine $l1,
        ImagickRendererLine $l2
    ) {
        $rs = $l1->rx * $l2->ry - $l1->ry * $l2->rx;

        if ($rs == 0) {
            return null;
        }
        
        $u = (($l2->Px - $l1->Px) * $l1->ry - ($l2->Py - $l1->Py) * $l1->rx) / $rs;

        return new Point(
            $l2->Px + $u * $l2->rx,
            $l2->Py + $u * $l2->ry
        );
    }

    /**
     * X coordiate of a point on the line.
     * @var float 
     */
    public $Px;
    /**
     * Y coordiate of a point on the line.
     * @var float 
     */
    public $Py;

    /**
     * X component of the direction vector.
     * @var float 
     */
    public $rx;
    /**
     * Y component of the direction vector.
     * @var float 
     */
    public $ry;
}

/**
 * Renders icons as PNG using ImageMagick.
 * 
 * Unfortunately the ImageMagick vector renderer has a lot of quirks that
 * we need to accomodate. The most obvious is that the renderer always render
 * all polygons 1/2 pixel too large. This behavior is documented here:
 * http://www.imagemagick.org/Usage/draw/#bounds
 * 
 * To prevent this we shrink all polygons with 1/2 pixels before passing them
 * to ImageMagick. 
 * 
 * Another quirk is that if the polygon including the 1/2 pixel invisible border
 * align perfectly to the pixel grid, white pixels will appear near edge
 * intersections. Paths containing arcs will sometimes appear with horizontal 
 * lines drawn to the right side of the image.
 * 
 * To prevent this (in most cases) we add 0.00013 to all coordinates.
 * 
 */
class ImagickRenderer extends AbstractRenderer
{
    private $draw;
    private $polygon;
    private $width;
    private $height;

    /**
     * This constant is added to all coordinates to avoid white pixels
     * that sometimes appear near edge intersections when a polygon including
     * its 1/2 invisible border is perfectly aligned to the pixel grid. 
     */
    const PREVENT_WHITE_PIXELS_OFFSET = -0.00013;

    /**
     * Creates an instance of the class ImagickRenderer.
     *
     * @param int $width The width of the icon in pixels.
     * @param int $height The height of the icon in pixels.
     */
    public function __construct($width, $height)
    {
        parent::__construct();
        $this->draw = new \ImagickDraw();
        $this->draw->setStrokeWidth(1);
        
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Gets the MIME type of the renderer output.
     *
     * @return string
     */
    public function getMimeType()
    {
        return 'image/png';
    }

    /**
     * Adds a circle without translating its coordinates.
     *
     * @param  float $x  The x-coordinate of the bounding rectangle 
     *      upper-left corner.
     * @param  float $y  The y-coordinate of the bounding rectangle 
     *      upper-left corner.
     * @param  float $size  The size of the bounding rectangle.
     * @param  bool $counterClockwise  If true the circle will be drawn 
     *      counter clockwise.
     */
    protected function addCircleNoTransform($x, $y, $size, $counterClockwise)
    {
        if ($counterClockwise) {
            $x -= $size + 0.5;
            $y -= 1;
        } else {
            $size -= 1;
            $y -= 0.5;
        }

        $radius = $size / 2;
        $this->draw->pathMoveToAbsolute(
            $x + $size + self::PREVENT_WHITE_PIXELS_OFFSET, 
            $y + $radius + self::PREVENT_WHITE_PIXELS_OFFSET);
        $this->draw->pathEllipticArcRelative($radius, $radius, 
            M_PI * 2, true, $counterClockwise, 0, 1);
        $this->draw->pathClose();
    }

    /**
     * Adds a polygon without translating its coordinates.
     *
     * @param  array $points  An array of the points that the polygon consists of.
     */
    protected function addPolygonNoTransform($points)
    {
        $firstPoint = $points[0];
        $lastPoint = end($points);

        // Ensure polygon is closed
        if ($firstPoint->x != $lastPoint->x ||
            $firstPoint->y != $lastPoint->y
        ) {
            $points[] = $firstPoint;
        }

        // Determine if polygon is an outer ring
        // (source: https://stackoverflow.com/a/1165943)
        $sum = 0;
        $previousPoint = null;

        foreach ($points as $point) {
            if ($previousPoint !== null) {
                $sum += ($point->x - $previousPoint->x) * ($point->y + $previousPoint->y);
            }
            $previousPoint = $point;
        }

        $isOuterRing = $sum < 0;

        // ImageMagick draws all polygons 1 pixel too large. To prevent this,
        // shrink polygons by 1 pixel.
        $lines = array();
        $previousPoint = null;

        // Transform all edges to lines.
        foreach ($points as $point) {
            if ($previousPoint !== null) {
                $lines[] = $line = ImagickRendererLine::fromVector(
                    $previousPoint->x, $previousPoint->y,
                    $point->x, $point->y
                );

                // ImageMagick draws a 1px border along the outer ring. To 
                // prevent the border overlaps inner rings too close to the 
                // outer ring, only inflate inner rings by 1/4 pixel.
                $line->moveRight($isOuterRing ? 0.5 : 0.25);
            }
            $previousPoint = $point;
        }

        $first = true;
        $previousLine = end($lines);

        // Reconstruct point array from line intersections and draw the polygon
        foreach ($lines as $line) {
            $points[] = $point = ImagickRendererLine::intersection($previousLine, $line);

            // Subtract 1/2 pixel to align the shapes to the pixel grid.
            if ($first) {
                $this->draw->pathMoveToAbsolute(
                    $point->x - 0.5 + self::PREVENT_WHITE_PIXELS_OFFSET,
                    $point->y - 0.5 + self::PREVENT_WHITE_PIXELS_OFFSET);
                    
                $first = false;
            } else {
                $this->draw->pathLineToAbsolute(
                    $point->x - 0.5 + self::PREVENT_WHITE_PIXELS_OFFSET,
                    $point->y - 0.5 + self::PREVENT_WHITE_PIXELS_OFFSET);
            }

            $previousLine = $line;
        }

        $this->draw->pathClose();
    }

    /**
     * Begins a new shape. The shape should be ended with a call to endShape.
     *
     * @param \Jdenticon\Color $color The color of the shape.
     */
    public function beginShape(\Jdenticon\Color $color)
    {
        $this->draw->setFillColor($color->__toString());
        $this->draw->pathStart();
    }
    
    /**
     * Ends the currently drawn shape.
     */
    public function endShape()
    {
        $this->draw->pathFinish();
    }

    /**
     * Renders this image as a PNG data stream.
     * 
     * @return string
     */
    public function getData()
    {
        $imagick = new \Imagick();
        $imagick->newImage($this->width, $this->height, $this->backgroundColor->__toString());
        $imagick->setImageFormat('png');

        if (method_exists($imagick, 'setImageProperty')) {
            $imagick->setImageProperty('Software', 'Jdenticon');
        } else {
            $imagick->setImageAttribute('Software', 'Jdenticon');
        }
        
        $imagick->drawImage($this->draw);
        return $imagick->getImageBlob();
    }
}
