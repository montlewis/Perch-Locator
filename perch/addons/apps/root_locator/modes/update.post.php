<div class="main">
    <div class="body">
        <div class="inner">
            <h1>Software Update</h1>
            <?php
            if (!$Paging->is_last_page()) {
                echo '<ul class="updates">';
                echo '<li class="icon success">Importing legacy locations ' . $Paging->lower_bound() . ' to ' . $Paging->upper_bound() . ' of ' . $Paging->total() . '.</li>';
                echo '</ul>';
            } else {
                echo '<p class="info">You should now manually remove the previous database tables: "perch2_jw_locator_locations", "perch2_jw_locator_markers", "perch2_jw_locator_failed_jobs".</p>';
                echo '<p class="info"><a href="' . $API->app_path() . '" class="button">Continue</a></p>';
            }
            ?>
        </div>
    </div>

<?php
if (!$Paging->is_last_page()) {
    $paging = $Paging->to_array();
    echo "
    <script>
        window.setTimeout(function(){
            window.location='" . PerchUtil::html($paging['next_url'], true) . "';
        }, 0);
    </script>";
}
?>