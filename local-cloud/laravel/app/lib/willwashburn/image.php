<?php namespace willwashburn;

/**
 * Class image
 *
 * @requires PHP 5.5.*
 *
 * @package  willwashburn
 */
class Image
{
    /**
     * The absolute path to the image source
     *
     * @var string
     */
    protected $image_source;

    /**
     * The original height and width of the image
     *
     * @var int
     */
    protected $original_height;
    protected $original_width;

    /**
     * What we'd like the height and the width of the image to be when
     * it's viewed
     *
     * @var int
     */
    protected $target_height;
    protected $target_width;

    /**
     * If we'd like to return the image as a square
     *
     * @var bool
     */
    protected $is_square;

    /**
     * When we are doing a cover, we mask the image behind a div
     * These values store the offsets
     *
     * @var int
     */
    protected $offset_top;
    protected $offset_left;

    /**
     * The type of tag to do the mask with
     *
     * @var string
     */
    protected $mask_tag = 'div';

    /**
     * If we are doing a cover, this giv's that tag these attributes
     *
     * @var string
     */
    protected $mask_attributes;

    /*
     * @param image_source          string      | absolute path to source
     * $param src                   string      | alias for image_source
     * @param temp_folder           string      | absolute path to temp folder
     * @param destination_folder    string      | absolute path to where images go
     * @param path_to_images_folder string
     * @param original_height       int
     * @param original_width        int
     * @param target_height         int
     * @param target_width          int
     * @param is_square             bool        | is the height = width
     * @param offset_top            int         | for fitting in squares
     * @param offset_left           int         | for fitting in squares
     * @param parent_div_style      string      | the parent div style setting
     *
     *
     *  //Wish List
     * @param return_square         bool        | do we return a square?
     * @param return_max_width      int         | can be less than this, but no more
     * @param return_min_width      int         | can be more than this, but no less
     * @param return_max_height     int         | can be less than this, but nor more
     * @param return_min_height     int
     * @param return_set_height     int         | has to be this height - variable width - cant be used with any other
     * @param return_set_width      int         | has to be this width - variable height - cant be used with any other
     *
     * //CLEAN
     * unknown from before
     * image, image_type
     *
     * @DEPENDENCY url class
     *

    public $image_source, $src, $original_height, $original_width, $offset_top, $offset_left, $parent_div_style, $target_height, $target_width;
        protected $path_to_images_folder, $temp_folder, $destination_folder, $is_square;

        // Clean this
        public $image;
        public $image_type;

        /**
         * Constructor
         * @author  Will
         *
         * set the image source
         * check if it exists
         *  ->if not, throw exception
         * get the height/width
         *
         */
    public function __construct($image_source, $config = array())
        {
            ///Define the image source
            $this->image_source = $image_source;
            $this->src          = $image_source;

            //This also checks to make sure that there IS an image
            $height_and_width = $this->height_and_width();

            $this->original_height = $height_and_width['height'];
            $this->original_width  = $height_and_width['width'];

            $this->target_height = $height_and_width['height'];
            $this->target_width  = $height_and_width['width'];

            /// Test if the image is square
            if ($this->original_width == $this->original_height) {
                $this->is_square = true;
            } else {
                $this->is_square = false;
            }

            $this->offset_left      = 0;
            $this->offset_top       = 0;
            $this->parent_div_style = '';


        }

        /**
         * Render
         * @author Will
         *
         * returns html of the image
         *
         */
        public function render()
        {

            $html = '<img ' . $this->img_attributes() . ' />';

            return $html;
        }

        /**
         * Display attributes
         * Returns img tag attributes to display based on what you need
         * @author Will
         *
         * @param string $return_as
         * @internal param string $return_as or array
         *
         * @return array|bool|string
         */
        public function img_attributes($return_as = 'string')
        {

            switch ($return_as) {
                case 'string':
                    $attribute_string = " src = '$this->image_source' width = '$this->target_width' height ='$this->target_height'";

                    if ($this->offset_left != 0 || $this->offset_top != 0) {
                        $attribute_string .= ' style="position:absolute; top:' . $this->offset_top . 'px; left:' . $this->offset_left . 'px;"';
                    }

                    return $attribute_string;
                    break;

                case 'array':
                    $return_array                = array();
                    $return_array['src']         = $this->image_source;
                    $return_array['height']      = $this->target_height;
                    $return_array['width']       = $this->target_width;
                    $return_array['offset_left'] = $this->offset_left;
                    $return_array['offset_top']  = $this->offset_top;

                    return $return_array;
                    break;
            }

            return false;
        }

        /**
         * Set Width
         * Aspect ratio set the new h/w
         * @author Will
         *
         * @notes
         * Doesn't actually change the size of the file
         * overrides set_height if called after
         *
         */
        public function set_width($target_width)
        {
            $this->target_width  = $target_width;
            $this->target_height = $this->aspect_ratio_scale($target_width, $this->original_width, $this->original_height);

            return $this;

        }

        /**
         * Set Height
         * Aspect ratio set the new w/h
         * @author Will
         *
         */
        public function set_height($target_height)
        {
            $this->target_height = $target_height;
            $this->target_width  = $this->aspect_ratio_scale($target_height, $this->original_height, $this->original_width);

            return $this;
        }

        /**
         * Fit to square
         * Make a non square image fit inside a square { MAGIC! }
         * @author  Will
         *
         */
        public function fit_to_box($width, $height = false, $position = 'center', $fill_type = 'fill')
        {
            //Reset the offsets
            $this->offset_left = 0;
            $this->offset_top  = 0;

            if (!$height) {
                $height = $width;
            }

            $height_difference = $height - $this->original_height;
            $width_difference  = $width - $this->original_width;

            switch ($fill_type) {

                case 'fill':

                    if ($height_difference >= $width_difference) {
                        $this->set_height($height);
                    } else {
                        $this->set_width($width);
                    }

                    //Sanity Checks to make sure we're in the right
                    if ($this->target_height < $height) {
                        $this->set_height($height);
                    }

                    if ($this->target_width < $width) {
                        $this->set_width($width);
                    }

                    break;

                case 'fit':

                    if ($height_difference < $width_difference) {
                        $this->set_height($height);
                    } else {
                        $this->set_width($width);
                    }
                    break;


            }

            $target_height_difference = abs($height - $this->target_height);
            $target_width_difference  = abs($width - $this->target_width);

            $center_vertically   = false;
            $center_horizontally = false;
            $divide_by           = 2;

            switch ($position) {
                case 'center':
                    $center_horizontally = true;
                    $center_vertically   = true;
                    break;
                case 'top center':
                case 'center top':
                    $center_horizontally = true;
                    break;
                case 'center left':
                case 'left center':
                    $center_vertically = true;
                    break;
                case 'center right':
                case 'right center':
                    $center_horizontally = true;
                    $divide_by           = 1;
                    break;
                case 'bottom center':
                case 'center bottom':
                    $center_vertically = true;
                    $divide_by         = 1;
                    break;
                case 'bottom right':
                case 'right bottom':
                    $center_horizontally = true;
                    $center_vertically   = true;
                    $divide_by           = 1;
                    break;
            }

            //If we are supposed to, go ahead and center it
            if ($center_vertically && $target_height_difference > 0) {
                $this->offset_top = -round($target_height_difference / $divide_by);
            }

            if ($center_horizontally && $target_width_difference > 0) {
                $this->offset_left = -round($target_width_difference / $divide_by);
            }

            $this->parent_div_style = 'width:' . $width . 'px; height:' . $height . 'px; overflow:hidden;';

            return $this;
        }

        #### Misc. Functions ###

        /**
         * Height and Width
         * Return the height and width of the image
         * @author Will
         *
         */
        public function height_and_width()
        {
            $return = false;

            @$original_dimensions = getimagesize($this->image_source); //quiet the errors here because we're going to catch

            if (!$original_dimensions) {
                throw new ImageException('Image could not be found at ' . $this->src, 404);
            } else {

                $return['width']  = $original_dimensions[0];
                $return['height'] = $original_dimensions[1];
                $return['string'] = $original_dimensions[3];
            }

            return $return;
        }

        /**
         * Aspect Ratio Scale
         * find the missing y size based on new x and originals
         * @author  Will
         *
         * simple math brahspeh
         *
         */
        public function aspect_ratio_scale($set_x, $original_x, $original_y)
        {

            if ($original_x == 0 || $original_y == 0 || $set_x == 0) {
                throw new ImageException('The image cant be a length of 0');
            } else {

                $ratio  = $set_x / $original_x;
                $result = round($original_y * $ratio);

                return $result;
            }
        }


    }

    /**
     * Class ImageException
     * @package willwashburn
     *
     * Codes
     * 0 -Error
     * 404 - Image not found
     */
    class ImageException extends \Exception
    {

    }