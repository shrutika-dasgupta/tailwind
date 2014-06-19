<?php

namespace Analytics;
use \View;
use \Response;
use \Redirect;
use \Pinleague\Pinterest;
use \DatabaseInstance;
use \Pinleague\Pinterest\BasePinterest;
use Config;
use Pinleague\Pinterest\Transports\CurlAdapter;

class TestController extends BaseController
{

    protected $layout = 'layouts.ajax_response';

    /**
     *
     */
    public function showApiTest()
    {
        $vars = $this->baseLegacyVariables();
        extract($vars);

        if(isset($cust_is_admin)){
            if($cust_is_admin){

                $pinterest = new BasePinterest(Config::get('pinterest.client_id'),
                    Config::get('pinterest.secret'),
                    '',
                    new CurlAdapter
                );


                try{
                    //$data = $pinterest->call('/v3/pin/180636635027697512/related/friend/');
                    //$parameters = array('bookmark'=>'b28yMDB8Y2M3ZjE2ZTNlYjIwZmFhZjFiZTM3Y2I2MWUzMjVkNzRkNTIwNmRhNjQ2NDVmMmI4ODJiMjY4NTY3MDc4MTJlMg==');
                    //$data = $pinterest->call('/v3/search/user_pins/58687738802605391', $parameters);
                    $data = $pinterest->getBoardInformation(83387099289523909, ['add_fields' => 'board.collab_board_email,board.access()']);

//

                    if($data['code']==0){
//                        foreach($data['data'] as $d)
//                        {
//                            $response = pp("key: " .$d['key']);
//                            $response .= pp("name: " . $d['name'] . "<br><br>");
//                        }
                        $response = json_encode($data);

                    } else {
                        $response = "api error code: " . $data['code'];
                    }








                    //$pin_ids_or_pin_id = array(57913545180273165);
//
//                    $pin_ids = implode(',', $pin_ids_or_pin_id);
//
//                    $url = 'http://api.pinterest.com/v3/pidgets/pins/info/?pin_ids=' . $pin_ids;
//
//                    $curl = curl_init();
//                    curl_setopt_array($curl, array(
//                                                  CURLOPT_RETURNTRANSFER => 1,
//                                                  CURLOPT_URL            => $url,
//                                                  CURLOPT_HTTPHEADER     => array(
//                                                      'Content-Type: application/json',
//                                                      'Accept: application/json'
//                                                  )
//                                             ));
//
//                    $resp = curl_exec($curl);
//                    curl_close($curl);
//
//                    return $resp;
//
//                    $resp_json = json_decode($resp);
//
//
//                    $result = array();
//                    foreach ($resp_json->data as $resp) {
//                        try {
//                            $result[$resp->id] = $resp->pinner
//                                ->id;
//                        }
//                        catch (Exception $e) {
//                            echo 'Caught exception: ', $e->getMessage(), "\n";
//                        }
//                    }
//
//                    var_dump($resp_json);



                }
                catch (Exception $e) {
                    $response = "whoops.. some sort of error.";
                }

                $this->layout->response = $response;




            }
        }


    }

}
