<?php

/**
 * Class RootLocator_Address
 *
 * @author James Wigger <james@rootstudio.co.uk>
 */
class RootLocator_Address extends PerchAPI_Base
{
    /**
     * Database table
     *
     * @var string
     */
    protected $table = 'root_locator_addresses';
    /**
     * Primary key
     *
     * @var string
     */
    protected $pk = 'addressID';

    /**
     * Address index table
     *
     * @var string
     */
    protected $index_table = 'root_locator_index';

    /**
     * Modified date column
     *
     * @var string
     */
    protected $modified_date_column = 'addressUpdated';

    /**
     * Return data from set field on address record
     *
     * @param string $field
     *
     * @return bool
     */
    public function getField($field)
    {
        $data = $this->to_array();

        if (isset($data[$field])) {
            $Template = $this->api->get('Template');
            $Template->set('locator/address.html', 'locator');
            $Tag = $Template->find_tag($field);

            if ($Tag) {
                if ($Tag->is_set('suppress')) {
                    $Tag->set('suppress', false);
                }

                $Template->set_from_string(PerchXMLTag::create('perch:locator', 'single', $Tag->get_attributes()), 'locator');

                return $Template->render($data);
            }

            return $data[$field];
        }

        return false;
    }

    /**
     * Return full address string
     *
     * @return string
     */
    public function fullAddress()
    {
        $address = [
            $this->addressBuilding(),
            $this->addressStreet(),
            $this->addressTown(),
            $this->addressRegion(),
            $this->addressPostcode(),
            $this->addressCountry()
        ];

        return implode(', ', $address);
    }

    /**
     * Transform given address details into geographic coordinates
     *
     * @param bool $log
     *
     * @return mixed
     */
    public function geocode($log = false)
    {
        $Geocoder = RootLocator_GeocoderFactory::createGeocoder();
        $result = $Geocoder->geocode($this->fullAddress());

        if ($result->hasError()) {
            if ($log) {
                $this->update([
                    'addressError' => $result->getErrorKey()
                ]);
            }

            return false;
        }

        $coordinates = $result->getFirstCoordinates();

        return $this->update([
            'addressLatitude'  => $coordinates['latitude'],
            'addressLongitude' => $coordinates['longitude'],
            'addressError'     => null
        ]);
    }

    /**
     * Update object attributes and persist to data store
     *
     * @param array $data
     * @param bool  $geocode
     *
     * @return mixed
     */
    public function update($data, $geocode = false)
    {
        $result = parent::update($data);

        if ($geocode) {
            $result = $this->geocode(true);
        }

        return $result;
    }

    /**
     * Returns if the object has been given coordinates
     *
     * @return bool
     */
    public function hasCoordinates()
    {
        return ($this->addressLatitude() && $this->addressLongitude());
    }

    /**
     * Fetch coordinates array (useful for find nearby runtime function)
     *
     * @return array|bool
     */
    public function getCoordinates()
    {
        if($this->hasCoordinates()) {
            return [$this->addressLatitude(), $this->addressLongitude()];
        }

        return false;
    }

    /**
     * Returns if object has experienced an error
     *
     * @return bool
     */
    public function hasError()
    {
        return !is_null($this->addressError());
    }

    /**
     * Only queue again when address data changes
     *
     * @param $data
     *
     * @return bool
     */
    public function shouldQueue($data)
    {
        $check = md5(implode('', [
            $this->addressBuilding(),
            $this->addressStreet(),
            $this->addressTown(),
            $this->addressRegion(),
            $this->addressPostcode(),
            $this->addressCountry()
        ]));

        $compare = md5(implode('', [
            $data['addressBuilding'],
            $data['addressStreet'],
            $data['addressTown'],
            $data['addressRegion'],
            $data['addressPostcode'],
            $data['addressCountry']
        ]));

        return $check !== $compare;
    }

    /**
     * Return google static map preview of coordinates
     *
     * @return bool|string
     */
    public function preview()
    {
        $PerchSettings = PerchSettings::fetch();
        $apiKey = $PerchSettings->get('root_locator_google_api_key')->settingValue();

        if (!$this->hasCoordinates() || !$apiKey) {
            return false;
        }

        $base_url = 'https://maps.googleapis.com/maps/api/staticmap?';
        $parameters = [
            'size'    => '400x200',
            'maptype' => 'roadmap',
            'markers' => 'color:red|' . $this->addressLatitude() . ',' . $this->addressLongitude(),
            'key'     => $apiKey
        ];

        return '<img class="map-preview" src="' . $base_url . http_build_query($parameters) . '" />';
    }

    /**
     * Delete record from database
     */
    public function delete()
    {
        $this->db->delete(PERCH_DB_PREFIX . 'root_locator_tasks', 'addressID', $this->id());

        return parent::delete();
    }
}