<?php

if (!defined('PERCH_DB_PREFIX')) {
    exit;
}

$sql = "
    CREATE TABLE `__PREFIX__root_locator_addresses` (
      `addressID` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `addressTitle` varchar(255) NOT NULL DEFAULT '',
      `addressBuilding` varchar(255) DEFAULT '',
      `addressStreet` varchar(255) DEFAULT '',
      `addressTown` varchar(255) DEFAULT '',
      `addressRegion` varchar(255) DEFAULT '',
      `addressPostcode` varchar(15) DEFAULT '',
      `addressCountry` varchar(3) DEFAULT '',
      `addressLatitude` decimal(9,6),
      `addressLongitude` decimal(9,6),
      `addressError` varchar(255),
      `addressDynamicFields` text,
      `addressUpdated` datetime NOT NULL,
      PRIMARY KEY (`addressID`),
      FULLTEXT KEY `root_locator_search_index` (`addressTitle`,`addressBuilding`,`addressStreet`,`addressPostcode`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
    
    CREATE TABLE `__PREFIX__root_locator_index` (
      `indexID` int(10) NOT NULL AUTO_INCREMENT,
      `itemKey` char(64) NOT NULL DEFAULT '-',
      `itemID` int(10) NOT NULL DEFAULT '0',
      `indexKey` char(64) NOT NULL DEFAULT '-',
      `indexValue` char(255) NOT NULL DEFAULT '',
      PRIMARY KEY (`indexID`),
      KEY `idx_fk` (`itemKey`,`itemID`),
      KEY `idx_key` (`indexKey`),
      KEY `idx_key_val` (`indexKey`,`indexValue`),
      KEY `idx_keys` (`itemKey`,`indexKey`)
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;
    
    CREATE TABLE `__PREFIX__root_locator_tasks` (
      `taskID` int(11) unsigned NOT NULL AUTO_INCREMENT,
      `taskKey` VARCHAR(255) NOT NULL,
      `addressID` int(11) unsigned NOT NULL,
      `taskAttempt` int(1) unsigned NOT NULL DEFAULT 1,
      `taskStart` datetime NOT NULL,
      PRIMARY KEY (`taskID`)
    ) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 ROW_FORMAT=DYNAMIC;
";

$sql = str_replace('__PREFIX__', PERCH_DB_PREFIX, $sql);

// Install
$statements = explode(';', $sql);
foreach ($statements as $statement) {
    $statement = trim($statement);
    if ($statement != '') {
        $this->db->execute($statement);
    }
}

// Permissions
$API = new PerchAPI(1.0, 'root_locator');
$UserPrivileges = $API->get('UserPrivileges');
$UserPrivileges->create_privilege('root_locator', 'Access the locator app');
$UserPrivileges->create_privilege('root_locator.import', 'Mass import location data');

// Categories
$Core_CategorySets = new PerchCategories_Sets();
$Core_Categories = new PerchCategories_Categories();
$Set = $Core_CategorySets->get_by('setSlug', 'locator');

if (!$Set) {
    $Set = $Core_CategorySets->create(array(
        'setTitle'         => PerchLang::get('Locator'),
        'setSlug'          => 'locator',
        'setTemplate'      => '~/root_locator/templates/locator/category_set.html',
        'setCatTemplate'   => '~/root_locator/templates/locator/category.html',
        'setDynamicFields' => '[]'
    ));
}

// Installation check
$sql = 'SHOW TABLES LIKE "' . $this->table . '"';
$result = $this->db->get_value($sql);

return $result;