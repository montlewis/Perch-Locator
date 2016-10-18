<?php

$Addresses = new RootLocator_Addresses($API);
$Tasks = new RootLocator_Tasks($API);

$Paging->set_per_page(10);

if ($Paging->is_first_page()) {

    // Update permissions
    $UserPrivileges = $API->get('UserPrivileges');
    $UserPrivileges->create_privilege('root_locator', 'Access the locator app');
    $UserPrivileges->create_privilege('root_locator.import', 'Mass import location data');

}

if ($Settings->get('root_locator_update')->val() != '2.0.0') {
    $legacy = $Addresses->getLegacyData($Paging);

    if (PerchUtil::count($legacy)) {
        foreach ($legacy as $row) {

            // Ok, we have an error and it's not a quota issue, so just save as is.
            if (isset($row['errorMessage']) && !empty($row['errorMessage']) && $row['errorMessage'] == 'The address could not be found.') {
                $Addresses->create([
                    'addressTitle'         => $row['locationTitle'],
                    'addressBuilding'      => $row['locationBuilding'],
                    'addressStreet'        => $row['locationStreet'],
                    'addressTown'          => $row['locationTown'],
                    'addressRegion'        => $row['locationRegion'],
                    'addressPostcode'      => $row['locationPostcode'],
                    'addressCountry'       => $row['locationPostcode'],
                    'addressDynamicFields' => $row['locationDynamicFields'],
                    'addressError'         => 'no_results'
                ]);

                continue;
            }

            // Do we have some existing location data we can just simply shift over?
            if (isset($row['markerLatitude']) && isset($row['markerLongitude'])) {
                $Addresses->create([
                    'addressTitle'         => $row['locationTitle'],
                    'addressBuilding'      => $row['locationBuilding'],
                    'addressStreet'        => $row['locationStreet'],
                    'addressTown'          => $row['locationTown'],
                    'addressRegion'        => $row['locationRegion'],
                    'addressPostcode'      => $row['locationPostcode'],
                    'addressCountry'       => $row['locationPostcode'],
                    'addressDynamicFields' => $row['locationDynamicFields'],
                    'addressLatitude'      => $row['markerLatitude'],
                    'addressLongitude'     => $row['markerLongitude']
                ]);

                continue;
            }

            // Ok, default action is to just save the row and queue it for later.
            $legacyAddress = $Addresses->create([
                'addressTitle'         => $row['locationTitle'],
                'addressBuilding'      => $row['locationBuilding'],
                'addressStreet'        => $row['locationStreet'],
                'addressTown'          => $row['locationTown'],
                'addressRegion'        => $row['locationRegion'],
                'addressPostcode'      => $row['locationPostcode'],
                'addressCountry'       => $row['locationPostcode'],
                'addressDynamicFields' => $row['locationDynamicFields']
            ]);

            $Tasks->add('address.geocode', $legacyAddress->id());
        }

    } else {
        $Settings->set('root_locator_update', '2.0.0');
        PerchUtil::redirect($API->app_path());
    }
}

if ($Paging->is_last_page()) {
    $Settings->set('root_locator_update', '2.0.0');
}
