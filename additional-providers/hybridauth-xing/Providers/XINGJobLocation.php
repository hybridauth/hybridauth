<?php

/**
 * XingJobLocation - Used to search for jobs in specific locations
 *
 * A geo coordinate in the format latitude, longitude, radius. Radius is specified in kilometers.
 * Example: “51.1084,13.6737,100”
 *
 */
class XingJobLocation
{
    private $lat;
    private $lon;
    private $radius;

    /**
     * XingJobLocation constructor.
     *
     * Create location that is used to query api in job search
     * @param  float $lat
     * @param  float $lon
     * @param  float $radius the radius size of the area search
     * @throws Exception
     */
    public function __construct( $lat, $lon, $radius )
    {
        $this->lat = $lat;
        $this->lon = $lon;
        $this->radius = $radius;
    }

    public function __toString() {
        return implode( ',', array( $this->lat, $this->lon, $this->radius ) );
    }
}

