<?php

namespace Analytics;

use
    Config,
    Input,
    Pinleague\Timezone,
    Redirect,
    Request,
    Response;

use Geocoder\Formatter\Formatter,
    Geocoder\Geocoder,
    Geocoder\HttpAdapter\GuzzleHttpAdapter,
    Geocoder\Provider\ChainProvider,
    Geocoder\Provider\GeonamesProvider,
    Geocoder\Provider\GoogleMapsProvider,
    Geocoder\Provider\OpenStreetMapProvider,
    Geocoder\Provider\YandexProvider;

/**
 * Geo controller.
 *
 * Interacts with Geocoder to return place information.
 *
 * @author Janell
 *
 * @package Analytics
 */
class GeoController extends BaseController
{
    /**
     * The http adapter interface used for Geocoder requests.
     *
     * @var GuzzleHttpAdapter
     */
    private $adapter;

    /**
     * The main Geocoder object.
     *
     * @var Geocoder
     */
    private $geocoder;

    /**
     * GET /geo/city/timezone
     *
     * Returns a formatted city name and timezone for the requested city. Optionally, the current
     * local time may be returned.
     *
     * @author Janell
     *
     * @expects
     *      city
     *      return_current_time
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCityTimezone()
    {
        if (Request::ajax() == false) {
            Redirect::back();
        }

        $city        = Input::get('city');
        $return_time = Input::get('return_current_time', false);

        if (empty($city)) {
            return Response::json(array(
                'success' => false,
                'message' => 'Missing required parameter: city',
            ));
        }

        try {
            $this->setupLocationTimezoneGeocoder();

            $result = $this->geocoder->geocode($city);

            $timezone = $result->getTimezone();
            if (empty($timezone)) {
                throw new Exception();
            }
        } catch (Exception $e) {
            return Response::json(array(
                'success' => false,
                'message' => 'Invalid city',
            ));
        }

        $return_array = array(
            'success'        => true,
            'message'        => 'Valid city',
            'timezone'       => $timezone,
            'city'           => $result->getCity(),
            'region'         => $result->getRegion(),
            'country'        => $result->getCountry(),
            'city_formatted' => $this->formatCity($result),
        );

        if ($return_time) {
            $return_array['current_time'] = Timezone::instance($timezone)->getCurrentTime();
        }

        return Response::json($return_array);
    }

    /**
     * GET /geo/cities
     *
     * Returns an array of city data for the requested city name. Optionally, the current local
     * time may be returned.
     *
     * @author Janell
     *
     * @expects
     *      city
     *      return_current_time
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCities()
    {
        if (Request::ajax() == false) {
            Redirect::back();
        }

        $city        = Input::get('city');
        $return_time = Input::get('return_current_time', false);

        if (empty($city)) {
            return Response::json(array(
                'success' => false,
                'message' => 'Missing required parameter: city',
            ));
        }

        try {
            $this->setupLocationTimezoneGeocoder();

            $result = $this->geocoder->geocode($city);
        } catch (Exception $e) {
            return Response::json(array(
                'success' => false,
                'message' => 'No results',
            ));
        }

        $cities = array();
        if (!is_array($result)) {
            $result = array($result);
        }

        foreach ($result as $data) {
            $city_data = array(
                'timezone'       => $data->getTimezone(),
                'city'           => $data->getCity(),
                'region'         => $data->getRegion(),
                'country'        => $data->getCountry(),
                'city_formatted' => $this->formatCity($data),
            );

            if ($return_time) {
                $city_data['current_time'] = Timezone::instance($city_data['timezone'])->getCurrentTime();
            }

            $cities[] = $city_data;
        }

        return Response::json(array(
            'success' => true,
            'message' => 'Found cities',
            'cities'  => $cities,
        ));
    }

    /**
     * Sets up the Geocoder object and adapter, then registers place providers which return timezone
     * data.
     *
     * @author Janell
     *
     * @return void
     */
    private function setupLocationTimezoneGeocoder()
    {
        $this->adapter  = new GuzzleHttpAdapter();
        $this->geocoder = new Geocoder();
        $this->geocoder->registerProvider(
            new GeonamesProvider($this->adapter, Config::get('geocoder.GEONAMES_USERNAME'), 'en_US')
        );
    }

    /**
     * Sets up the Geocoder object and adapter, then registers place providers. At the moment, these
     * do not return timezone information.
     *
     * @author Janell
     *
     * @return void
     */
    private function setupLocationGeocoder()
    {
        $this->adapter  = new GuzzleHttpAdapter();
        $this->geocoder = new Geocoder();
        $chain          = new ChainProvider(array(
            new GoogleMapsProvider($this->adapter, 'en_US', 'United States', true),
            new OpenStreetMapProvider($this->adapter, 'en_US'),
            new YandexProvider($this->adapter, 'en_US'),
        ));

        $this->geocoder->registerProvider($chain);
    }

    /**
     * Returns a formatted city string from a Geocoder result.
     *
     * @author Janell
     *
     * @param ResultInterface $result
     *
     * @return string
     */
    private function formatCity($result)
    {
        $formatter = new Formatter($result);

        $format = ($result->getCountryCode() == 'US') ? '%L, %R - %C' : '%L - %C';

        return $formatter->format($format);
    }
}