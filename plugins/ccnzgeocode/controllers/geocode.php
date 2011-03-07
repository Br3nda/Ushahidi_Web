<?php defined('SYSPATH') or die('No direct script access.');
/**
 * SMS Automate Administrative Controller
 *
 * @author	   John Etherton
 * @package	   SMS Automate
 */

class Geocode_Controller extends Controller
{
    public function json() {
        if (!isset($_GET['address']) || empty($_GET['address'])) {
            echo json_encode(array('status' => 'notfound'));
            exit;
        }

        $address = strtolower($_GET['address']);

        /* Check the cache */
        $cache = ORM::factory('ccnzgeocode_cache')
            ->where('address', $address)
            ->find_all()->current();
        if ($cache) {
            echo json_encode(
                array(
                    'status' => 'OK',
                    'lat' => $cache->lat,
                    'lon' => $cache->lon
                    ));
            exit;
        }
        // Append nz to search string to ensure New Zealand results
        $address_url = rawurlencode($address . ' nz');
        $url = "http://maps.googleapis.com/maps/api/geocode/json?address=".$address_url."&sensor=false";
        //Adding bounds that make the geocoder prefer results that are in those bounds
        $url .= "&bounds=-48.123079,164.619141|-39.250405,175.957031";
        if ($json = file_get_contents($url)) {
            $result = json_decode($json);
            if ($result->status != "OK" || !($location = current($result->results))) {
                echo json_encode(array('status' => 'notfound'));
                exit;
            }

            /* Save to cache */
            $cache = ORM::factory('ccnzgeocode_cache');
            $cache->address = $address;
            $cache->lat = $location->geometry->location->lat;
            $cache->lon = $location->geometry->location->lng;
            $cache->save();

            /* Output to browser */
            echo json_encode(
                array(
                    'status' => 'OK',
                    'lat' => $location->geometry->location->lat,
                    'lon' => $location->geometry->location->lng
                    ));
            exit;
        }

        echo json_encode(array('status' => 'notfound'));
        exit;

    }
}
