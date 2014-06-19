<?php
/**
 * @author Alex
 * Date: 8/28/13 9:57 PM
 * 
 */

namespace Admin;
use \View;
use \Response;
use \Exception;
use \Pinleague\Pinterest;

class AjaxController extends BaseController
{

    protected $layout = 'layouts.ajax_response';

    /**
     * Check username to see if a profile exists on Pinterest and return basic profile stats
     *
     * @author  Alex
     */
    public function checkUsername($username)
    {
        header("Content-Type: text/html");

        $cust_username = $username;

        $username_exists = false;

        if(!$username_exists){

            $pinterest = new Pinterest;

            try{
                $data = $pinterest->getProfileInformation($cust_username);

                if($data['code']==0){
                    $username_exists=true;

                    $cust_first_name = $data['data']['first_name'];
                    $cust_last_name = $data['data']['last_name'];
                    $cust_image = $data['data']['image_large_url'];
                    $cust_about = $data['data']['about'];
                    $cust_domain = $data['data']['domain_url'];
                    $cust_board_count = number_format($data['data']['board_count'],0);
                    $cust_pin_count = number_format($data['data']['pin_count'],0);
                    $cust_like_count = number_format($data['data']['like_count'],0);
                    $cust_follower_count = number_format($data['data']['follower_count'],0);
                    $cust_following_count = number_format($data['data']['following_count'],0);
                }
            }
            catch (Exception $e) {
                $username_exists = false;
            }
        }

        if($username_exists){

            $response = "<div class='status'>1</div>
                <div class='data' style='text-align:center;margin-bottom:20px;'>
                    <div class='alert alert-success'>Lookin' good!</div>
                        <div class='well' style='display:inline-block'><div>
                        <h2 style='margin-top:0px'>$cust_first_name $cust_last_name</h2>
                    </div>
                    <div class=''>
                        <img src='$cust_image'>
                    </div>
                    <div>
                        <div>
                            <div class='label pull-left' style='margin:10px 10px'>
                                <strong>$cust_board_count</strong> Boards
                            </div>
                            <div class='label pull-left' style='margin:10px 10px'>
                                <strong>$cust_pin_count</strong> Pins
                            </div>
                            <div class='label pull-left' style='margin:10px 10px'>
                                <strong>$cust_like_count</strong> Likes
                            </div>
                            <br>
                            <div class='label pull-left' style='margin:10px 10px'>
                                <strong>$cust_follower_count</strong> Followers
                            </div>
                            <div class='label pull-left' style='margin:10px 10px'>
                                <strong>$cust_following_count</strong> Following
                            </div>
                        </div>
                    </div>
                </div>
                <div class='clearfix'></div>
                <div class='clearfix'></div>
            </div>
            <div class='domain'>$cust_domain</div>
            <div class='first_name'>$cust_first_name</div>
            <div class='last_name'>$cust_last_name</div>";

        } else {

            $response = "<div class='status'>0</div>
                <div class='data' style='text-align:center'>
                    <div class='alert alert-error'>
                        <strong>Whoops!</strong><br>
                        <a href='http://pinterest.com/$cust_username'
                          target='_blank'>http://pinterest.com/$cust_username</a>
                          does not seem to exist on Pinterest!  Please check the URL of the
                          Pinterest profile you'd like to track.
                    </div>
                </div>";

        }

        $this->layout->response = $response;
    }




}

