<?php
/**
 * Updating locale in status_profiles by using
 * facebook open graph
 *
 * @author yesh
 */

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\CLI;


try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {

        throw new EngineException('Engine is running');

    } else {

        $engine->start();
        CLI::write('Engine started');
        // Setting the limit for number of profiles
        // pulled
        $profile_fb_limit = 50;

        // The flag to keep track of the index of
        // $profiles_to_check array
        $dead_flag  = -1;
        $return_out = array();
        $config     = array();
        $flag_ids   = array();

        $current_time = time();
        $old_time     = $current_time - (10 * 60);

        $mch = curl_multi_init();

        // Initialise connection to DB
        $DBH = DatabaseInstance::DBO();
        CLI::write('Connected to Database');

        $proxy_port_db = $DBH->query("
                                SELECT proxy_port, proxy_user, proxy_pw
                                FROM adp_proxies
                                WHERE last_update < $old_time
                                LIMIT 1")
                         ->fetchAll();

        $proxy_user    = $proxy_port_db[0]->proxy_user;
        $proxy_pw      = $proxy_port_db[0]->proxy_pw;
        $proxy_loginpw = $proxy_user . ":" . $proxy_pw;

        $proxy_ip_port = explode(":", $proxy_port_db[0]->proxy_port);
        $proxy_ip      = $proxy_ip_port[0];
        $proxy_port    = intval($proxy_ip_port[1]);

        CLI::h1("Grabbing all available fb ids");

        $profiles_to_check = $DBH->query("
                                          SELECT facebook_url
                                          FROM data_profiles_new
                                          WHERE (facebook_url <> '' OR facebook_url <> NULL) AND locale IS NULL
                                          LIMIT $profile_fb_limit")
                             ->fetchAll();

        // Implementing muticurl referencing
        // http://arguments.callee.info/2010/02/21/multiple-curl-requests-with-php/

        for ($i = 0; $i < count($profiles_to_check); $i++) {
            $keyword = $profiles_to_check[$i]->facebook_url;
            CLI::h3($keyword);
            $ch[$i] = curl_init();
            curl_setopt($ch[$i], CURLOPT_URL,
                'http://graph.facebook.com/' . $keyword);
            curl_setopt($ch[$i], CURLOPT_PROXY, $proxy_ip);
            curl_setopt($ch[$i], CURLOPT_PROXYPORT, $proxy_port);
            curl_setopt($ch[$i], CURLOPT_USERAGENT,
                'Fb-uid');
            curl_setopt($ch[$i], CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch[$i], CURLOPT_HEADER, false);
            curl_setopt($ch[$i], CURLOPT_PROXYUSERPWD, $proxy_loginpw);
            curl_setopt($ch[$i], CURLOPT_TIMEOUT, 1000);
            curl_multi_add_handle($mch, $ch[$i]);
        }

        // execute the requests simultaneously
        $running = 0;
        do {
            try {
                curl_multi_exec($mch, $running);
            }
            catch (Exception $e) {
                echo $e;
            }

        } while ($running > 0);

        for ($i = 0; $i < count($profiles_to_check); $i++) {
            $ret = json_decode(curl_multi_getcontent($ch[$i]));
            array_push($return_out, $ret);
        }
        curl_multi_close($mch);

        CLI::h2("Update information in database");
        foreach ($return_out as $out) {
            $dead_flag += 1;
            if (isset($out->gender) and isset($out->locale)) {
                CLI::h2("With gender field from Fb:");
                CLI::h3($out->id);
                CLI::h3($out->username);

                if ($out->gender == "male") {
                    $out->gender = "MA";
                } elseif ($out->gender == "female") {
                    $out->gender = "FE";
                }
                $STH = $DBH->prepare("UPDATE data_profiles_new
                         SET gender = :gender,locale = :locale
                         WHERE facebook_url = :facebook_id OR facebook_url = :facebook_name");
                $STH->execute(array(":gender"      => $out->gender, ":locale" => $out->locale,
                                    ":facebook_id" => $out->id, ":facebook_name" => $out->username));
            } elseif (isset($out->locale)) {
                CLI::h2("Without gender fields from Fb:");
                CLI::h3($out->id);
                CLI::h3($out->username);
                $STH = $DBH->prepare("UPDATE data_profiles_new
                         SET locale = :locale
                         WHERE facebook_url = :facebook_id OR facebook_url = :facebook_name");
                $STH->execute(array(":locale"      => $out->locale,
                                    ":facebook_id" => $out->id, ":facebook_name" => $out->username));
            } elseif (isset($out->error)) {
                CLI::alert("Person not found!!");
                CLI::alert($profiles_to_check[$dead_flag]->facebook_url);
                CLI::h2('Setting locale to flag 0');

                $STH = $DBH->prepare("
                                     UPDATE data_profiles_new
                                     SET locale = 0
                                     WHERE facebook_url = :facebook_url");
                $STH->execute(array(":facebook_url" => $profiles_to_check[$dead_flag]->facebook_url));

            }

        }
        $STH = $DBH->prepare("UPDATE adp_proxies
                            SET last_update = :time
                            WHERE proxy_port = :proxy_port");
        $STH->execute(array(":time" => $current_time, ":proxy_port" => $proxy_port_db[0]->proxy_port));
    }
    CLI::h1("Finishing db update");
    $engine->complete();

    CLI::write(Log::runtime(). 'total runtime');
    CLI::write(Log::memory().' peak memory usage');
}
catch (EngineException $e) {

    CLI::alert($e->getMessage());
    CLI::stop();
}
catch (Exception $e) {

    CLI::alert($e->getMessage());
    $engine->fail();
    CLI::stop();

}