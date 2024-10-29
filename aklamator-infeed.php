<?php
/*
Plugin Name: Aklamator INfeed
Plugin URI: https://www.aklamator.com/wordpress
Description: Show your Instagram content on your blog, just login to Instagram and authorize and we will show your Instagram content. Drag and drop widget and show Instagram photos. Additionally Aklamator service enables you to add your media releases, sell PR announcements, cross promote web sites using RSS feed and provide new services to your clients in digital advertising.
Version: 2.0.0
Author: Aklamator
Author URI: https://www.aklamator.com/
License: GPL2

Copyright 2015 Aklamator.com (email : info@aklamator.com)

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

/*
 * Add setting link on plugin page
 */

if( !function_exists("aklamatorinfeed_plugin_settings_link")){
    // Add settings link on plugin page
    function aklamatorinfeed_plugin_settings_link($links) {
        $settings_link = '<a href="admin.php?page=aklamator-infeed">Settings</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}
add_filter("plugin_action_links_".plugin_basename(__FILE__), 'aklamatorinfeed_plugin_settings_link' );

/*
 * Add rate and review link in plugin section
 */
if( !function_exists("aklamatorinfeed_plugin_meta_links")) {
    function aklamatorINfeed_plugin_meta_links($links, $file)
    {
        $plugin = plugin_basename(__FILE__);
        // create link
        if ($file == $plugin) {
            return array_merge(
                $links,
                array('<a href="https://wordpress.org/support/plugin/aklamator-infeed-your-blog/reviews" target=_blank>Please rate and review</a>')
            );
        }
        return $links;
    }
}
add_filter( 'plugin_row_meta', 'aklamatorinfeed_plugin_meta_links', 10, 2);

/*
 * Activation Hook
 */

register_activation_hook( __FILE__, 'set_up_options_aklamator_infeed' );

function set_up_options_aklamatorinfeed(){
    add_option('aklamatorinfeed_username', '');
    add_option('aklamatorinfeed_profile_photo', '');
    add_option('aklamatorinfeed_user_id', '');
    add_option('aklamatorinfeed_access_token', '');
    add_option('aklamatorinfeedApplicationID', '');
    add_option('aklamatorinfeedPoweredBy', '');
    add_option('aklamatorinfeedSingleWidgetID', '');
    add_option('aklamatorinfeedPageWidgetID', '');
    add_option('aklamatorinfeedSingleWidgetTitle', '');
    add_option('aklamatorinfeedWidgets', '');

}

/*
 * Uninstall Hook
 */
register_uninstall_hook(__FILE__, 'aklamatorinfeed_uninstall');

function aklamatorinfeed_uninstall()
{
    delete_option('aklamatorinfeed_username');
    delete_option('aklamatorinfeed_profile_photo');
    delete_option('aklamatorinfeed_user_id');
    delete_option('aklamatorinfeed_access_token');
    delete_option('aklamatorinfeedApplicationID');
    delete_option('aklamatorinfeedPoweredBy');
    delete_option('aklamatorinfeedSingleWidgetID');
    delete_option('aklamatorinfeedPageWidgetID');
    delete_option('aklamatorinfeedSingleWidgetTitle');
    delete_option('aklamatorinfeedWidgets');

}



class AklamatorINfeedWidget
{
    private static $instance = null;

    /**
     * Get singleton instance
     */
    public static function init()
    {

        if (self::$instance == null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public $aklamator_url;
    public $api_data;

    public $popular_channels = array(
        array(
            'name' => 'YouTube Spotlight',
            'url' => 'https://www.youtube.com/user/youtube'
        ),
        array(
            'name' => 'PewDiePie',
            'url' => 'https://www.youtube.com/user/PewDiePie/'
        ),
        array(
            'name' => 'EmiMusic',
            'url' => 'https://www.youtube.com/user/emimusic'
        ),
        array(
            'name' => 'FunToyzCollector',
            'url' => 'https://www.youtube.com/user/disneycollectorbr'
        )

    );


    public function __construct()
    {

        $this->aklamator_url = "https://aklamator.com/";
//        $this->aklamator_url = "http://192.168.5.60/aklamator/www/";

        if (is_admin()) {
            add_action("admin_menu", array(
                &$this,
                "adminMenu"
            ));

            add_action('admin_init', array(
                &$this,
                "setOptions"
            ));
        }

        if (isset($_GET['page']) && $_GET['page'] == 'aklamator-infeed' ) {

            if (get_option('aklamatorinfeedApplicationID') !== '') {
                $this->api_data = $this->addNewWebsiteApi();
            }

            if (isset($this->api_data->flag) && $this->api_data->flag) {
                update_option('aklamatorinfeedWidgets', $this->api_data);
            }

        }
            if (get_option('aklamatorinfeedSingleWidgetID') !== 'none') {

                if (get_option('aklamatorinfeedSingleWidgetID') == '') {
                    if (isset($this->api_data->data)) {
                        $selected = "";
                        foreach ($this->api_data->data as $item) {
                            if ($item->title == 'Initial Instagram widget created') {
                                $selected = $item->uniq_name;
                            }
                        }
                        if ($selected != "") {
                            update_option('aklamatorinfeedSingleWidgetID', $selected);
                        } else {
                            update_option('aklamatorinfeedSingleWidgetID', $this->api_data->data[0]->uniq_name);
                        }

                    }
                }
                add_filter('the_content', array($this, 'bottom_of_every_post_infeed'));
            }

            if (get_option('aklamatorinfeedPageWidgetID') !== 'none') {

                if (get_option('aklamatorinfeedPageWidgetID') == '') {
                    if (isset($this->api_data->data)) {
                        $selected = "";
                        foreach ($this->api_data->data as $item) {
                            if ($item->title == 'Initial Instagram widget created') {
                                $selected = $item->uniq_name;
                            }
                        }
                        if ($selected != "") {
                            update_option('aklamatorinfeedPageWidgetID', $selected);
                        } else {
                            update_option('aklamatorinfeedPageWidgetID', $this->api_data->data[0]->uniq_name);
                        }

                    }
                }
                add_filter('the_content', array($this, 'bottom_of_every_post_infeed'));
            }


    }

    function bottom_of_every_post_infeed($content){

        /*  we want to change `the_content` of posts, not pages
            and the text file must exist for this to work */

        if (is_single()){
            $widget_id = get_option('aklamatorinfeedSingleWidgetID');
        }elseif (is_page()) {
            $widget_id = get_option('aklamatorinfeedPageWidgetID');
        }else{

            /*  if `the_content` belongs to a page or our file is missing
                the result of this filter is no change to `the_content` */

            return $content;
        }

        $return_content = $content;


        if (strlen($widget_id) >= 7) {

            $title = "";

            if (get_option('aklamatorinfeedSingleWidgetTitle') !== '') {
                $title .= "<h2>" . get_option('aklamatorinfeedSingleWidgetTitle') . "</h2>";
            }

            /*  append the text file contents to the end of `the_content` */
            $return_content .= $title . '<!-- created 2014-11-25 16:22:10 -->
            <div id="akla'.$widget_id.'"></div>
            <script>(function(d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElement(s); js.id = id;
            js.src = "'. $this->aklamator_url . 'widget/'.$widget_id.'";
            fjs.parentNode.insertBefore(js, fjs);
         }(document, \'script\', \'aklamator-$widget_id\'));</script>
        <!-- end -->' . '<br>';
        }

        return $return_content;

    }

    function getinfeedfeedforAklamator() {
        $empty = array(array(
            'link' => '',
            'created_time' => '',
            'thumbnail_url' => '',
            'caption' => '',
            'username' => '',
            'full_name' => '',
        ));
        $media = file_get_contents("http://instagram.com/{$this->username}/media");
        if(strlen($media) < 1) return $empty;
        $json = json_decode($media);
        if(!isset($json->items) || count($json->items) == 0) return $empty;
        $results = array();
        foreach( $json->items as $item ) {
            $results[] = array(
                'link' => $item->link,
                'created_time' => $item->created_time,
                'thumbnail_url' => $item->images->standard_resolution->url,
                'caption' => isset( $item->caption->text ) ? $item->caption->text : '',
                'username' => $item->user->username,
                'full_name' => $item->user->full_name,
            );
        }
        return $results;
    }

    function setOptions()
    {
        register_setting('aklamatorinfeed-options', 'aklamatorinfeed_username');
        register_setting('aklamatorinfeed-options', 'aklamatorinfeed_profile_photo');
        register_setting('aklamatorinfeed-options', 'aklamatorinfeed_user_id');
        register_setting('aklamatorinfeed-options', 'aklamatorinfeed_access_token');
        register_setting('aklamatorinfeed-options', 'aklamatorinfeedApplicationID');
        register_setting('aklamatorinfeed-options', 'aklamatorinfeedPoweredBy');
        register_setting('aklamatorinfeed-options', 'aklamatorinfeedSingleWidgetID');
        register_setting('aklamatorinfeed-options', 'aklamatorinfeedPageWidgetID');
        register_setting('aklamatorinfeed-options', 'aklamatorinfeedSingleWidgetTitle');

    }

    public function adminMenu()
    {
        add_menu_page('Aklamator - INfeed', 'Aklamator INfeed', 'manage_options', 'aklamator-infeed', array(
            $this,
            'createAdminPage'
        ), content_url() . '/plugins/aklamator-infeed/images/aklamator-icon.png');

    }


    public function getSignupUrl()
    {
        $user_info =  wp_get_current_user();
        
        return $this->aklamator_url . 'login/application_id?utm_source=wordpress&utm_medium=wpinfeed&e=' . urlencode(get_option('admin_email')) .
        '&pub=' .  preg_replace('/^www\./','',$_SERVER['SERVER_NAME']).
        '&un=' . urlencode($user_info->user_login). '&fn=' . urlencode($user_info->user_firstname) . '&ln=' . urlencode($user_info->user_lastname) .
        '&pl=infeed&return_uri=' . admin_url("admin.php?page=aklamator-infeed");

    }

    private function addNewWebsiteApi()
    {



        if (!is_callable('curl_init')) {
            return;
        }
        $service     = $this->aklamator_url . "wp-authenticate/user";
        $p['ip']     = $_SERVER['REMOTE_ADDR'];
        $p['domain'] = site_url();
        $p['source'] = "wordpress";
        $p['AklamatorApplicationID'] = get_option('aklamatorinfeedApplicationID');
        $p['aklamatorinstagram_user_id'] = get_option('aklamatorinfeed_user_id');
        $p['aklamatorinstagram_access_token'] = get_option('aklamatorinfeed_access_token');


        $data = wp_remote_post( $service, array(
                'method' => 'POST',
                'timeout' => 45,
                'redirection' => 5,
                'httpversion' => '1.0',
                'blocking' => true,
                'headers' => array(),
                'body' => $p,
                'cookies' => array()
            )
        );

        $ret_info = new stdClass();
        if(is_wp_error($data))
        {
            $this->curlfailovao=1;
        }
        else
        {
            $this->curlfailovao=0;
            $ret_info = json_decode($data['body']);
        }

        return $ret_info;

    }

    public function createAdminPage()
    {
        $code = get_option('aklamatorinfeedApplicationID');
        $user_id = get_option('aklamatorinfeed_user_id');
        $access_token = get_option('aklamatorinfeed_access_token');
        $username = "";
        $profile_photo = "";

        if (isset($_GET['instagram_username'])) {
            $username = $_GET['instagram_username'];
        }
        elseif (get_option('aklamatorinfeed_username') != "")
        {
            $username = get_option('aklamatorinfeed_username');
        }

        if (isset($_GET['instagram_profile_photo']))
        {
            $profile_photo = $_GET['instagram_profile_photo'];
        }
        elseif (get_option('aklamatorinfeed_profile_photo') != "")
        {
            $profile_photo = get_option('aklamatorinfeed_profile_photo');
        }
             ?>
        <style>
            #adminmenuback{ z-index: 0}
            #aklamatorinfeed-options ul { margin-left: 10px; }
            #aklamatorinfeed-options ul li { margin-left: 15px; list-style-type: disc;}
            #aklamatorinfeed-options h1 {margin-top: 5px; margin-bottom:10px; color: #00557f}
            .fz-span { margin-left: 23px;}

            .aklamator_button {
                vertical-align: top;
                width: auto;
                height: 30px;
                line-height: 30px;
                padding: 10px;
                font-size: 20px;
                color: white;
                text-align: center;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
                background: #c0392b;
                border-radius: 5px;
                border-bottom: 2px solid #b53224;
                cursor: pointer;
                -webkit-box-shadow: inset 0 -2px #b53224;
                box-shadow: inset 0 -2px #b53224;
                text-decoration: none;
                margin-top: 3px;
                margin-bottom: 10px;
            }
            
            .aklamatorinfeed-login-button {
                float: left;
            }


            .aklamatorinfeed-login-button:hover {
                cursor: pointer;
                color: lightskyblue;
            }

            .btn { font-size: 13px;
                border-radius: 5px;
                text-transform: uppercase;
                font-weight: 700;
                padding: 8px 10px;
                min-width: 162px;
                max-width: 100%;
                text-decoration: none;
                cursor: pointer;
                -webkit-box-shadow:0 0 4px #909090;
                box-shadow:0 0 4px #909090;}

            .btn-primary { background: #030925; border:1px solid #01030d; color: #fff; text-decoration: none}
            .btn-primary:hover, .btn-primary.hovered { background: #030925;  border:1px solid #167AC6; opacity:0.9; color: #fff }
            .btn-primary:Active, .btn-primary.pressed { background: #030925; border:1px solid #167AC6; color: #fff}

            .box{float: left; margin-left: 10px; width: 500px; background-color:#f8f8f8; padding: 10px; border-radius: 5px;}
            .right_sidebar{float: right; margin-left: 10px; width: 300px; background-color:#f8f8f8; padding: 10px; border-radius: 5px;}

            .alert{
                margin-bottom: 18px;
                color: #c09853;
                text-shadow: 0 1px 0 rgba(255,255,255,0.5);
                background-color: #fcf8e3;
                border: 1px solid #fbeed5;
                -webkit-border-radius: 4px;
                -moz-border-radius: 4px;
                border-radius: 4px;
                padding: 8px 35px 8px 14px;
            }
            .alert-msg {
                color: #3a87ad;
                background-color: #d9edf7;
                border-color: #bce8f1;
            }
            .alert_red{
                margin-bottom: 18px;
                margin-top: 10px;
                color: #c09853;
                text-shadow: 0 1px 0 rgba(255,255,255,0.5);
                background-color: #fcf8e3;
                border: 1px solid #fbeed5;
                -webkit-border-radius: 4px;
                -moz-border-radius: 4px;
                border-radius: 4px;
                padding: 8px 35px 8px 14px;
            }
            .alert-msg_red {
                color: #8f0100;
                background-color: #f6cbd2;
                border-color: #f68d89;
            }

            .aklamator_INlogin {
                padding: 10px;
                background-color: #000058;
                color: white;
                text-decoration: none;
                font-size: 15px;
                text-align: center;
                text-shadow: 0 1px 2px rgba(0, 0, 0, 0.25);
                border-radius: 5px;
                cursor: pointer;
                -webkit-box-shadow:0 0 4px #909090;
                box-shadow:0 0 4px #909090;
            }

            .aklamator_INlogin:hover {
                color: lightskyblue;
            }

            h3 {
                margin-bottom: 3px;
            }
            p {
                margin-top: 3px;
            }

        </style>
        <!-- Load css libraries -->

        <link href="//cdn.datatables.net/1.10.5/css/jquery.dataTables.min.css" rel="stylesheet" type="text/css">

        <div id="aklamatorinfeed-options" style="width:1160px;margin-top:10px;">
            <div class="left" style="float: left;">
                <div style="float: left; width: 300px;">

                    <a target="_blank" href="<?php echo $this->aklamator_url; ?>?utm_source=wp-plugin">
                        <img style="border-radius:5px;border:0px;" src=" <?php echo plugins_url('images/INfeedPromotionDash-300x250.png', __FILE__);?>" /></a>
                    <?php
                    if ($code != '') : ?>
                        <a target="_blank" href="<?php echo $this->aklamator_url; ?>dashboard?utm_source=wp-plugin">
                            <img style="border:0px;margin-top:5px;border-radius:5px;" src="<?php echo plugins_url('images/dashboard.jpg', __FILE__); ?>" /></a>

                    <?php endif; ?>

                    <a target="_blank" href="<?php echo $this->aklamator_url;?>/contact?utm_source=wp-plugin-contact">
                        <img style="border:0px;margin-top:5px; margin-bottom:5px;border-radius:5px;" src="<?php echo plugins_url('images/support.jpg', __FILE__); ?>" /></a>

                    <a target="_blank" href="http://qr.rs/q/4649f">
                        <img style="border:0px;margin-top:5px; margin-bottom:5px;border-radius:5px;" src="<?php echo plugins_url('images/promo-300x200.png', __FILE__); ?>" /></a>

                </div>
                <div class="box">

                    <h1 style="margin-bottom: 40px">Aklamator INfeed plugin</h1>

                    <form method="post" action="options.php">

                        <a class="aklamator_INlogin" href="https://instagram.com/oauth/authorize/?client_id=a60a3b65ba1944f79b95c18cface0ebb&scope=basic&redirect_uri=https://www.aklamator.com/instagram/auth2?return_uri=<?php echo admin_url('admin.php?page=aklamator-infeed') . '/'; ?>&response_type=code"><?php _e( 'Log in and get my Access Token and User ID', 'infeed-feed' ); ?></a>

                        <?php
                        settings_fields('aklamatorinfeed-options');

                        ?>
                        <input type="text" style="width: 250px; display: none;" name="aklamatorinfeed_username" id="aklamatorinfeed_username" value="<?php echo $username; ?>" maxlength="999" />
                        <input type="text" style="width: 250px; display: none;" name="aklamatorinfeed_profile_photo" id="aklamatorinfeed_profile_photo" value="<?php echo $profile_photo; ?>" maxlength="999" />
                        <?php

                        if ($username != "") {
                            if ($profile_photo != "") {
                                    ?>
                                <h3>Your Instagram profile</h3>
                                <div style="width: 400px; height: 40px; background-color: #d7ebf5; border-radius: 4px; border: 1px solid #bce8f1">
                                        <img style="float: left; height: 30px; margin: 5px;" src="<?php echo $profile_photo; ?>">
                                        <div style="float: left; height: 25px; margin: 10px 5px 10px 8px; font-size: 20px"><?php echo $username; ?></div>
                                    </div>
                                    <?php

                            }
                            else
                            {
                                ?>
                                <h3>Your Instagram username</h3>
                                <div style="width: 400px; height: 40px; background-color: #d7ebf5; border-radius: 4px; border: 1px solid #bce8f1">
                                    <div style="float: left; height: 25px; margin: 15px 5px 5px 8px; font-size: 20px"><?php echo $username; ?></div>
                                </div>
                                <?php
                            }
                        }

                        if ($user_id == '') : ?>
                            <h3>Step 1: Paste your Instagram user ID</h3>
                        <?php else :?>
                            <h3>Your Instagram user ID</h3>
                        <?php endif;?>
                        <p>
                            <input type="text" style="width: 400px" name="aklamatorinfeed_user_id" id="aklamatorinfeed_user_id" value="<?php
                            echo $user_id; ?>" maxlength="999" />

                        </p>

                        <?php if ($access_token == '') : ?>
                        <h3>Step 2: Paste your Instagram access token</h3>
                        <?php else :?>
                        <h3>Your Instagram access token</h3>
                        <?php endif;?>
                        <p>
                            <input type="text" style="width: 400px" name="aklamatorinfeed_access_token" id="aklamatorinfeed_access_token" value="<?php
                            echo $access_token; ?>" maxlength="999" />

                        </p>

                        <?php

                        if ($code == '' || isset($this->api_data->error)) : ?>
                            <h3 style="float: left">Step 3: Get your Aklamator Aplication ID</h3>
                            <a class='aklamatorinfeed-login-button aklamator_button' id="aklamatorinfeed-login-button" >Click here for FREE registration/login</a>
                            <div style="clear: both"></div>
                            <p>Or you can manually <a href="<?php echo $this->aklamator_url . 'registration/publisher'; ?>" target="_blank">register</a> or <a href="<?php echo $this->aklamator_url . 'login'; ?>" target="_blank">login</a> and copy paste your Application ID</p>

                        <?php endif; ?>

                        <div style="clear: both"></div>


                        <?php if ($code == '') { ?>
                            <h3>Step 4: &nbsp;&nbsp;&nbsp;&nbsp; Paste your Aklamator Application ID</h3>
                        <?php }else{ ?>
                            <h3>Your Aklamator Application ID</h3>
                        <?php } ?>

                        <p>
                            <input type="text" style="width: 400px" name="aklamatorinfeedApplicationID" id="aklamatorinfeedApplicationID" value="<?php
                            echo (get_option("aklamatorinfeedApplicationID"));
                            ?>" maxlength="50" onchange="appIDChange(this.value)"/>

                        </p>
                        <p>
                            <input type="checkbox" id="aklamatorinfeedPoweredBy" name="aklamatorinfeedPoweredBy" <?php echo (get_option("aklamatorinfeedPoweredBy") == true ? 'checked="checked"' : ''); ?> Required="Required">
                            <strong>Required</strong> I acknowledge there is a 'powered by aklamator' link on the widget. <br />
                        </p>

                        <p>
                        <div class="alert alert-msg">
                            <strong>Note </strong><span style="color: red">*</span>: By default, widget will show latest Instagram posts. If your Instagram is private, keep in mind that visitors can see thumbnail and title on widget, but If they click on post they will not be able to open it if they are not logged in/your followers.
                        </div>
                        </p>


                        <?php if(isset($this->api_data->flag) && $this->api_data->flag === false): ?>
                            <p id="aklamator_infeed_inactive" class="alert_red alert-msg_red"><span style="color:red"><?php echo $this->api_data->error; ?></span></p>
                        <?php endif; ?>

                        <?php if(get_option('aklamatorinfeedApplicationID') !=='' && $this->api_data->flag): ?>

                            <p>
                            <h1>Options</h1>
                            <h4>Select widget to be shown on bottom of the each:</h4>

                            <label for="aklamatorinfeedSingleWidgetTitle">Title Above widget (Optional): </label>
                            <input type="text" style="width: 300px; margin-bottom:10px" name="aklamatorinfeedSingleWidgetTitle" id="aklamatorinfeedSingleWidgetTitle" value="<?php echo (get_option("aklamatorinfeedSingleWidgetTitle")); ?>" maxlength="999" />

                            <?php

                            $widgets = $this->api_data->data;
                            /* Add new item to the end of array */
                            $item_add = new stdClass();
                            $item_add->uniq_name = 'none';
                            $item_add->title = 'Do not show';
                            $widgets[] = $item_add;

                            ?>

                            <label for="aklamatorinfeedSingleWidgetID">Single post: </label>
                            <select id="aklamatorinfeedSingleWidgetID" name="aklamatorinfeedSingleWidgetID">
                                <?php
                                foreach ( $widgets as $item ): ?>
                                    <option <?php echo (get_option('aklamatorinfeedSingleWidgetID') == $item->uniq_name)? 'selected="selected"' : '' ;?> value="<?php echo $item->uniq_name; ?>"><?php echo $item->title; ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input style="margin-left: 5px;" type="button" id="preview_single" class="button primary big submit" onclick="myFunction($('#aklamatorinfeedSingleWidgetID option[selected]').val())" value="Preview" <?php echo get_option('aklamatorinfeedSingleWidgetID')=="none"? "disabled" :"" ;?>>
                            </p>

                            <p>
                                <label for="aklamatorinfeedPageWidgetID">Single page: </label>
                                <select id="aklamatorinfeedPageWidgetID" name="aklamatorinfeedPageWidgetID">
                                    <?php
                                    foreach ( $widgets as $item ): ?>
                                        <option <?php echo (get_option('aklamatorinfeedPageWidgetID') == $item->uniq_name)? 'selected="selected"' : '' ;?> value="<?php echo $item->uniq_name; ?>"><?php echo $item->title; ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <input style="margin-left: 5px;" type="button" id="preview_page" class="button primary big submit" onclick="myFunction($('#aklamatorinfeedPageWidgetID option[selected]').val())" value="Preview" <?php echo get_option('aklamatorinfeedPageWidgetID')=="none"? "disabled" :"" ;?>>

                            </p>

                            <p>
                            <div class="alert alert-msg">
                                Or go to Appearance>widgets, and drag and drop widget where you want.
                            </div>
                            </p>
                        <?php endif; ?>
                        <input id="aklamator_infeed_save" class="aklamator_INlogin" style ="margin: 0; border: 0; float: left;" type="submit" value="<?php echo (_e("Save Changes")); ?>" />
                        <?php if(!isset($this->api_data->flag) || !$this->api_data->flag): ?>
                            <div style="float: left; padding: 7px 0 0 10px; color: red; font-weight: bold; font-size: 16px"> <-- In order to proceed save changes</div>
                        <?php endif ?>


                    </form>
                </div>


                <div style="clear:both"></div>
                <div style="margin-top: 20px; margin-left: 0px; width: 810px;" class="box">

                    <?php if (isset($this->curlfailovao) && $this->curlfailovao && get_option('aklamatorinfeedApplicationID') != ''): ?>
                        <h2 style="color:red">Error communicating with Aklamator server, please refresh plugin page or try again later. </h2>
                    <?php endif;?>
                    <?php if(!isset($this->api_data->flag) || !$this->api_data->flag): ?>
                        <a href="<?php echo $this->getSignupUrl(); ?>" target="_blank"><img style="border-radius:5px;border:0px;" src=" <?php echo plugins_url('images/teaser-810x262.png', __FILE__);?>" /></a>
                    <?php else : ?>
                        <!-- Start of dataTables -->
                        <div id="aklamatorinfeedPro-options">
                            <h1>Your Widgets</h1>
                            <div>In order to add new widgets or change dimensions please <a href="<?php echo $this->aklamator_url; ?>login" target="_blank">login to aklamator</a></div>
                        </div>
                        <br>
                        <table cellpadding="0" cellspacing="0" border="0"
                               class="responsive dynamicTable display table table-bordered" width="100%">
                            <thead>
                            <tr>
                                <th>Name</th>
                                <th>Domain</th>
                                <th>Settings</th>
                                <th>Image size</th>
                                <th>Column/row</th>
                                <th>Created At</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($this->api_data->data as $item): ?>

                                <tr class="odd">
                                    <td style="vertical-align: middle;" ><?php echo $item->title; ?></td>
                                    <td style="vertical-align: middle;" >
                                        <?php foreach($item->domain_ids as $domain): ?>
                                            <a href="<?php echo $domain->url; ?>" target="_blank"><?php echo $domain->title; ?></a><br/>
                                        <?php endforeach; ?>
                                    </td>
                                    <td style="vertical-align: middle"><div style="float: left; margin-right: 10px" class="button-group">
                                            <input type="button" class="button primary big submit" onclick="myFunction('<?php echo $item->uniq_name; ?>')" value="Preview Widget">
                                    </td>
                                    <td style="vertical-align: middle;" ><?php echo "<a href = \"$this->aklamator_url"."widget/edit/$item->id\" target='_blank' title='Click & Login to change'>$item->img_size px</a>";  ?></td>
                                    <td style="vertical-align: middle;" >
                                        <?php echo "<a href = \"$this->aklamator_url"."widget/edit/$item->id\" target='_blank' title='Click & Login to change'>".$item->column_number ." x ". $item->row_number."</a>"; ?>
                                        <div style="float: right;">
                                            <?php echo "<a class=\"btn btn-primary\" href = \"$this->aklamator_url"."widget/edit/$item->id\" target='_blank' title='Edit widget settings'>Edit</a>"; ?>
                                        </div>
                                    </td>
                                    <td style="vertical-align: middle;" ><?php echo $item->date_created; ?></td>
                                </tr>
                            <?php endforeach; ?>

                            </tbody>
                            <tfoot>
                            <tr>
                                <th>Name</th>
                                <th>Domain</th>
                                <th>Settings</th>
                                <th>Immg size</th>
                                <th>Column/row</th>
                                <th>Created At</th>
                            </tr>
                            </tfoot>
                        </table>
                    <?php endif; ?>
                </div>
            </div>
            <div class="right" style="float: right;">
                <!-- Right sidebar -->
                <div class="right_sidebar">
                    <iframe width="330" height="1024" src="<?php echo $this->aklamator_url; ?>wp-sidebar/right?plugin=youtube-your-blog" frameborder="0"></iframe>
                </div>
                <!-- End Right sidebar -->
            </div>
        </div>



        <!-- load js scripts -->

        <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
        <script type="text/javascript" src="<?php echo content_url(); ?>/plugins/aklamator-infeed/assets/dataTables/jquery.dataTables.min.js"></script>


        <script type="text/javascript">

            function appIDChange(val) {

                $('#aklamatorinfeedSingleWidgetID option:first-child').val('');
                $('#aklamatorinfeedPageWidgetID option:first-child').val('');

            }

            function myFunction(widget_id) {

                    var myWindow = window.open('https://aklamator.com/show/widget/'+widget_id);
                    myWindow.focus();

            }


            $(document).ready(function(){

                $('#aklamatorinfeed-login-button').click(function () {
                    var akla_login_window = window.open('<?php echo $this->getSignupUrl(); ?>','_blank');
                    var aklamator_interval = setInterval(function() {
                        var aklamator_infeed_hash = akla_login_window.location.hash;
                        var aklamator_infeed_api_id = "";
                        if (akla_login_window.location.href.indexOf('aklamator_wordpress_api_id') !== -1) {

                            aklamator_infeed_api_id = aklamator_infeed_hash.substring(28);
                            $("#aklamatorinfeedApplicationID").val(aklamator_infeed_api_id);
                            akla_login_window.close();
                            clearInterval(aklamator_interval);
                            $('#aklamator_infeed_inactive').css('display', 'none');
                        }
                    }, 1000);

                });

                $("#aklamatorinfeedSingleWidgetID").change(function(){

                    if($(this).val() == 'none'){
                        $('#preview_single').attr('disabled', true);
                    }else{
                        $('#preview_single').removeAttr('disabled');
                    }

                    $("#aklamatorinfeedSingleWidgetID option").each(function () {
//
                        if (this.selected) {
                           $(this).attr('selected', true);

                        }else{
                            $(this).removeAttr('selected');

                        }
                    });

                });


                $("#aklamatorinfeedPageWidgetID").change(function(){

                    if($(this).val() == 'none'){

                        $('#preview_page').attr('disabled', true);
                    }else{
                        $('#preview_page').removeAttr('disabled');
                    }

                    $("#aklamatorinfeedPageWidgetID option").each(function () {
//
                        if (this.selected) {
                            $(this).attr('selected', true);
                        }else{
                            $(this).removeAttr('selected');

                        }
                    });

                });

                $('#aklamatorinfeedApplicationID').on('input', function ()
                {
                    $('#aklamator_infeed_inactive').css('display', 'none');
                });


                $('#aklamator_infeed_save').click(function(event){
                    var infeed_url = $('#aklamatorinfeed_user_id');
                    var infeed_token = $('#aklamatorinfeed_access_token');
                    var aklainfeedaplicationID = $('#aklamatorinfeedApplicationID');

                    if(infeed_url.val() == ""){
                        alert('Instagram user ID can\'t be empty');
                        infeed_url.focus();
                        event.preventDefault();

                    }
                    else if(infeed_token.val() == ""){
                        alert('Instagram access token can\'t be empty');
                        infeed_token.focus();
                        event.preventDefault();
                    }
                    else if (aklainfeedaplicationID.val() == "")
                    {
                        alert("Paste your Aklamator Application ID");
                        aklainfeedaplicationID.focus();
                        event.preventDefault();
                    }
                });

                if ($('table').hasClass('dynamicTable')) {
                    $('.dynamicTable').dataTable({
                        "iDisplayLength": 10,
                        "sPaginationType": "full_numbers",
                        "bJQueryUI": false,
                        "bAutoWidth": false

                    });
                }
            });

            //Autofill the token and id
            var hash = window.location.hash,
                token = hash.substring(14),
                id = token.split('.')[0];

            //If there's a hash then autofill the token and id
            if(hash && !jQuery('#sbi_just_saved').length){
                if(token.length > 40) {
                    jQuery('#aklamatorinfeed_access_token').val(token);
                    jQuery('#aklamatorinfeed_user_id').val(id);
                }
            }


        </script>

    <?php
    }


}


//new AklamatorINfeedWidget();


// Widget section


add_action( 'after_setup_theme', 'vw_setup_vw_widgets_init_aklamatorinfeed' );
function vw_setup_vw_widgets_init_aklamatorinfeed() {
    add_action( 'widgets_init', 'vw_widgets_init_aklamatorinfeed' );
}

function vw_widgets_init_aklamatorinfeed() {
    register_widget( 'Aklamator_infeed_widget' );
}

class Aklamator_infeed_widget extends WP_Widget {


    private $default = array(
        'supertitle' => '',
        'title' => '',
        'content' => '',
    );


    public $aklamator_url;
    public $widget_data_infeed;

    public function __construct() {

        // widget actual processes
        parent::__construct(
            'Aklamator_infeed_widget', // Base ID
            'Aklamator INfeed', // Name
            array( 'description' => __( 'Display Aklamator Widgets in Sidebar')) // Widget Description
        );

        $this->widget_data_infeed = get_option('aklamatorinfeedWidgets');
        $this->aklamator_url = AklamatorINfeedWidget::init()->aklamator_url;

    }


    function widget( $args, $instance ) {
        extract($args);
        //var_dump($instance); die();

        $supertitle_html = '';
        if ( ! empty( $instance['supertitle'] ) ) {
            $supertitle_html = sprintf( __( '<span class="super-title">%s</span>', 'envirra' ), $instance['supertitle'] );
        }

        $title_html = '';
        if ( ! empty( $instance['title_infeed'] ) ) {
            $title = apply_filters( 'widget_title', $instance['title_infeed'], $instance, $this->id_base);
            $title_html = $supertitle_html.$title;
        }

        echo $before_widget;
        if ( $instance['title_infeed'] ) echo $before_title . $title_html . $after_title;
        ?>
        <?php echo $this->show_widget(do_shortcode( $instance['widget_id_infeed'] )); ?>
        <?php

        echo $after_widget;
    }

    public function show_widget($widget_id){
        $code = "";
//        $aklamator_url = "http://192.168.5.60/aklamator/www/";
//        $aklamator_url = "https://aklamator.com/";
        ?>
        <!-- created 2014-11-25 16:22:10 -->
        <div id="akla<?php echo $widget_id; ?>"></div>
        <script>(function(d, s, id) {
                var js, fjs = d.getElementsByTagName(s)[0];
                if (d.getElementById(id)) return;
                js = d.createElement(s); js.id = id;
                js.src = "<?php echo $this->aklamator_url . 'widget/' . $widget_id; ?>";
                fjs.parentNode.insertBefore(js, fjs);
            }(document, 'script', 'aklamator-<?php echo $widget_id; ?>'));</script>
        <!-- end -->
    <?php }

    function form( $instance ) {

        $widget_data = new AklamatorinfeedWidget();

        $instance = wp_parse_args( (array) $instance, $this->default );

        $title = isset($instance['title_infeed']) ? strip_tags( $instance['title_infeed'] ) : "";
        $widget_id = isset($instance['widget_id_infeed']) ? $instance['widget_id_infeed'] : "";


        if(!empty($this->widget_data_infeed) || ($this->widget_data_infeed->flag && !empty($this->widget_data_infeed->data))): ?>

            <!-- title -->
            <p>
                <label for="<?php echo $this->get_field_id('title_infeed'); ?>"><?php _e('Title (text shown above widget):','envirra-backend'); ?></label>
                <input class="widefat" id="<?php echo $this->get_field_id('title_infeed'); ?>" name="<?php echo $this->get_field_name('title_infeed'); ?>" type="text" value="<?php echo esc_attr($title); ?>" />
            </p>

            <!-- Select - dropdown -->
            <label for="<?php echo $this->get_field_id('widget_id_infeed'); ?>"><?php _e('Widget:','envirra-backend'); ?></label>
            <select id="<?php echo $this->get_field_id('widget_id_infeed'); ?>" name="<?php echo $this->get_field_name('widget_id_infeed'); ?>">
                <?php foreach ( $this->widget_data_infeed->data as $item ): ?>
                    <option <?php echo ($widget_id == $item->uniq_name)? 'selected="selected"' : '' ;?> value="<?php echo $item->uniq_name; ?>"><?php echo $item->title; ?></option>
                <?php endforeach; ?>
            </select>
            <br>
            <br>
            <br>
        <?php else :?>
            <br>
            <span style="color:red">Please make sure that you configured Aklamator plugin correctly</span>
            <a href="<?php echo admin_url(); ?>admin.php?page=aklamator-infeed">Click here to configure Aklamator plugin</a>
            <br>
            <br>
        <?php endif;

    }
}