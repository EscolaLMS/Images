<?php

return [
    /**
     * Arrays of allowed height and widths, leave empty if any size between min and max can be used
     */
    'allowed_heights' => [],
    'allowed_widths' => [],
    /**
     * Max and min sizes; 
     * if min size is not set, and requested size is less or equal to 0 it will be ignored during croping/resizing
     * if max size is not set, original image max width/height will be the constraint;
     */
    'max_height' => 1000,
    'max_width' => 1000,
    'min_height' => 10,
    'min_width' => 10,
    /**
     * Allow upscale - determines if width or height can be bigger than original image
     */
    'allow_upscale' => true,
    /**
     *  Predefined image sizes, which can be requested by frontend instead of specyfing width/height of image
     */
    'size_definitions' => [
        'thumbnail' => [
            'w' => 400,
            'h' => 300
        ],
    ]
];
