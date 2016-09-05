<?php include('../../../../core/inc/api.php');

// Perch API
$API = new PerchAPI(1.0, 'jw_locator');

// Language instance
$Lang = $API->get('Lang');

// Page Meta
$Perch->page_title = $Lang->get('Updating Locator');

// Page Initialising
include('../modes/update.pre.php');

// Perch Frame
include(PERCH_CORE . '/inc/top.php');

// Page
include('../modes/update.post.php');

// Perch Frame
include(PERCH_CORE . '/inc/btm.php');