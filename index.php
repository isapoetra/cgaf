<?php
xhprof_enable();
if (!defined('CGAF')) {
    include "cgafinit.php";
}
if (!CGAF::isInstalled()) {
    include "install.php";
} else {
    CGAF::Run();
    if (!\Request::isDataRequest()) {
        $xhprof_data = xhprof_disable();
        $XHPROF_ROOT = "/Data/DevTools/xhprof/";
        include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_lib.php";
        include_once $XHPROF_ROOT . "/xhprof_lib/utils/xhprof_runs.php";

        $xhprof_runs = new XHProfRuns_Default();
        $run_id = $xhprof_runs->save_run($xhprof_data, "xhprof_testing");
        echo '<a href="http://localhost/xhprof_html/index.php?run=' . $run_id . '&source=xhprof_testing" target="__blank">View Profile</a>';

    }
}


?>