<?php
/*
Plugin Name: free disk space
Plugin URI: http://wordpress.stackexchange.com/q/67876/12615
Description: List the following folder sizes in a Dashboard Widget: Uploads dir, WP Content dir, WordPress base dir. 
Observation: PHP folder size functions from this Answer: https://stackoverflow.com/a/8348396/1287812
*/

add_action('wp_dashboard_setup', 'wpse_67876_wp_dashboard_setup');

function wpse_67876_wp_dashboard_setup()
{
    // Admins only
    if (current_user_can('install_plugins'))
        wp_add_dashboard_widget('wpse_67876_folder_sizes', __('Disk Usages'), 'wpse_67876_wp_add_dashboard_widget');
}

function wpse_67876_wp_add_dashboard_widget()
{
    $upload_dir     = wp_upload_dir();
    $upload_space   = wpse_67876_foldersize($upload_dir['basedir']);
    $content_space  = wpse_67876_foldersize(WP_CONTENT_DIR);
    $wp_space       = wpse_67876_foldersize(ABSPATH);



    /* ABSOLUTE paths not being shown in Widget */

    echo '<b>' . $upload_dir['basedir'] . ' </b>';
    echo ': ' . wpse_67876_format_size($upload_space) . '<br /><br />';

    echo '<b>' . WP_CONTENT_DIR . ' </b>';
    echo ': ' . wpse_67876_format_size($content_space) . '<br /><br />';

    if (is_multisite()) {
        echo '<i>wp-content/blogs.dir</i>: ' . wpse_67876_format_size(wpse_67876_foldersize(WP_CONTENT_DIR . '/blogs.dir')) . '<br /><br />';
    }

    echo '<b>' . ABSPATH . ' </b>';
    echo ': ' . wpse_67876_format_size($wp_space);

    $calc_root_path = ABSPATH;
    /* get disk space free (in bytes) */
    $df = disk_free_space($calc_root_path);
    /* and get disk space total (in bytes)  */
    $dt = disk_total_space($calc_root_path);
    /* now we calculate the disk space used (in bytes) */
    $du = $dt - $df;
    /* percentage of disk used - this will be used to also set the width % of the progress bar */
    $dp = sprintf('%.2f', ($du / $dt) * 100);

    /* and we formate the size from bytes to MB, GB, etc. */
    $df = my_custom_format_size($df);
    $du = my_custom_format_size($du);
    $dt = my_custom_format_size($dt);

?>
    <style type='text/css'>
        .progress {
            border: 2px solid #5E96E4;
            height: 32px;
            margin: 30px auto;
        }

        .progress .prgbar {
            background: #A7C6FF;
            position: relative;
            height: 32px;
            z-index: 999;
        }

        .progress .prgtext {
            color: #286692;
            text-align: center;
            font-size: 13px;
            padding: 9px 0 0;
            width: 540px;
            position: absolute;
            z-index: 1000;
        }

        .progress .prginfo {
            margin: 3px 0;
        }
    </style>
    <div class='progress'>
        <div class='prgtext'><?php echo $dp; ?>% Disk Used</div>
        <div class='prgbar' style="width:<?php echo $dp; ?>%;"></div>
        <div class='prginfo'>
            <span style="margin-right: 10px;">Total: <?php echo $dt; ?></span>
            <span style="margin-right: 10px;">Used: <?php echo $du; ?></span>
            <span>Free: <?php echo $df; ?></span>
        </div>
    </div>
<?php
}

if (!function_exists('my_custom_format_size')) {
    function my_custom_format_size($bytes)
    {
        $types = array('B', 'KB', 'MB', 'GB', 'TB');
        for ($i = 0; $bytes >= 1024 && $i < (count($types) - 1); $bytes /= 1024, $i++);
        return (round($bytes, 2) . " " . $types[$i]);
    }
}


function wpse_67876_foldersize($path)
{
    $total_size = 0;
    $files = scandir($path);
    $cleanPath = rtrim($path, '/') . '/';

    foreach ($files as $t) {
        if ('.' != $t && '..' != $t) {
            $currentFile = $cleanPath . $t;
            if (is_dir($currentFile)) {
                $size = wpse_67876_foldersize($currentFile);
                $total_size += $size;
            } else {
                $size = filesize($currentFile);
                $total_size += $size;
            }
        }
    }

    return $total_size;
}

function wpse_67876_format_size($size)
{
    $units = explode(' ', 'B KB MB GB TB PB');

    $mod = 1024;

    for ($i = 0; $size > $mod; $i++)
        $size /= $mod;

    $endIndex = strpos($size, ".") + 3;

    return substr($size, 0, $endIndex) . ' ' . $units[$i];
}
