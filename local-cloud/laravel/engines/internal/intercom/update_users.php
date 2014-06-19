<?php

/**
 * Updates profile information and preferences from Intercom.
 *
 * @author Janell
 */

chdir(__DIR__);
include '../../../bootstrap/bootstrap.php';

use Pinleague\IntercomWrapper;
use Pinleague\CLI;

Log::setLog(__FILE__, 'DataEngines', 'intercom_update_users');

try {
    CLI::h1('Starting engine');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        throw new EngineException('Engine is running');
    }

    $engine->start();
    CLI::write(Log::info('Engine started'));

    CLI::write(Log::info('Creating Intercom instance'));
    $intercom = IntercomWrapper::instance();

    $DBH = DatabaseInstance::DBO();
    CLI::write(Log::debug('Connected to database'));

    $next_page   = 1;
    $total_pages = 1;
    $per_page    = 500;

    while ($next_page) {
        $request_msg = "Requesting $per_page users on page $next_page";

        if ($total_pages > 1) {
            $request_msg .= " of $total_pages";
        }

        CLI::h2(Log::info($request_msg));
        $response = $intercom->getUsers($next_page, $per_page);

        if (isset($response->error)) {
            throw new Exception($response->error->message);
        }

        $total_pages = $response->total_pages;

        // Put Intercom users into an array indexed by cust_id. We will compare these against the db.
        $intercom_users = array();
        foreach ($response->users as $user) {
            // A few Intercom users do not actually have Tailwind accounts. Let's skip those.
            if (empty($user->user_id)) {
                continue;
            }

            $intercom_users[$user->user_id] = $user;
        }

        $customer_ids_implode = implode(', ', array_keys($intercom_users));
        $limit = count($intercom_users);

        if ($limit == 0) {
            $next_page = $response->next_page;
            continue;
        }

        // Grab all of our customer records for this set of Intercom users.
        $STH = $DBH->query(
            "SELECT * FROM users
             WHERE cust_id IN ($customer_ids_implode)
             LIMIT $limit"
        );

        $customers = array();
        foreach ($STH->fetchAll() as $data) {
            $customers[$data->cust_id] = User::createFromDBData($data);
        }

        CLI::write(Log::debug('Fetched user records from database'));

        // Fetch session_counts for these customers.
        $STH = $DBH->query(
            "SELECT cust_id, count
             FROM user_properties
             WHERE cust_id IN ($customer_ids_implode)
             AND property = '" . UserProperty::SESSION_COUNT . "'
             LIMIT $limit"
        );

        $session_counts = array();
        foreach ($STH->fetchAll() as $data) {
            $session_counts[$data->cust_id] = $data->count;
        }

        CLI::write(Log::debug('Fetched session_counts from database'));

        // Fetch Intercom email preferences for these customers.
        $STH = $DBH->query(
            "SELECT cust_id, frequency
             FROM user_email_preferences
             WHERE cust_id IN ($customer_ids_implode)
             AND name = '" . UserEmail::INTERCOM_EMAIL . "'
             LIMIT $limit"
        );

        $email_preferences = array();
        foreach ($STH->fetchAll() as $data) {
            $email_preferences[$data->cust_id] = $data->frequency;
        }

        CLI::write(Log::debug('Fetched Intercom email preferences from database'));

        // Fetch social properties for these customers.
        $STH = $DBH->query(
            "SELECT cust_id, type, name, value
             FROM user_social_properties
             WHERE cust_id IN ($customer_ids_implode)"
        );

        $social_properties = array();
        foreach ($STH->fetchAll() as $data) {
            if (!isset($social_properties[$data->cust_id])) {
                $social_properties[$data->cust_id] = array();
            }

            if (!isset($social_properties[$data->cust_id][$data->type])) {
                $social_properties[$data->cust_id][$data->type] = array();
            }

            $social_properties[$data->cust_id][$data->type][$data->name] = $data->value;
        }

        CLI::write(Log::debug('Fetched social properties from database'));

        // Compare each Intercom user to our current customer data and update as needed.
        foreach ($intercom_users as $cust_id => $intercom_user) {
            CLI::h3(Log::debug("Updating user with cust_id $cust_id"));

            if (empty($customers[$cust_id])) {
                CLI::alert(Log::warning("No user found for cust_id $cust_id"));
                continue;
            }

            $customer = $customers[$cust_id];

            // Update customer's location and timezone.
            $city     = isset($intercom_user->location_data->city_name) ? $intercom_user->location_data->city_name : null;
            $region   = isset($intercom_user->location_data->region_name) ? $intercom_user->location_data->region_name : null;
            $country  = isset($intercom_user->location_data->country_name) ? $intercom_user->location_data->country_name : null;
            $timezone = isset($intercom_user->location_data->timezone) ? $intercom_user->location_data->timezone : null;

            $location_changes = array();

            if (!empty($city) && empty($customer->city)) {
                $location_changes['city'] = array(
                    'old' => $customer->city,
                    'new' => $city,
                );
                $customer->city = $city;
            }

            if (!empty($region) && empty($customer->region)) {
                $location_changes['region'] = array(
                    'old' => $customer->region,
                    'new' => $region,
                );
                $customer->region = $region;
            }

            if (!empty($country) && empty($customer->country)) {
                $location_changes['country'] = array(
                    'old' => $customer->country,
                    'new' => $country,
                );
                $customer->country = $country;
            }

            if (!empty($timezone)
                && (empty($customer->timezone) || preg_match('^([\+-]\d{1,2}:\d{2})$', $customer->timezone))
            ) {
                $location_changes['timezone'] = array(
                    'old' => $customer->timezone,
                    'new' => $timezone,
                );
                $customer->timezone = $timezone;
            }

            if (!empty($location_changes)) {
                $customer->saveToDB();
                CLI::write(Log::debug('Updated location and timezone', $location_changes));
            } else {
                CLI::write(Log::debug("Location and timezone did not change"));
            }

            // Update session count.
            $session_count     = isset($intercom_user->session_count) ? $intercom_user->session_count : 0;
            $old_session_count = isset($session_counts[$cust_id]) ? $session_counts[$cust_id] : 0;

            if ($session_count != $old_session_count) {
                $customer->setUserProperty(UserProperty::SESSION_COUNT, $session_count);
                CLI::write(Log::debug('Updated session count', array(
                    'old' => $old_session_count,
                    'new' => $session_count,
                )));
            } else {
                CLI::write(Log::debug("Session count did not change"));
            }

            // Update email subscription preference.
            $subscribed     = ! (bool) $intercom_user->unsubscribed_from_emails;
            $preference     = $subscribed ? UserEmailPreference::ON : UserEmailPreference::OFF;
            $old_preference = isset($email_preferences[$cust_id]) ? $email_preferences[$cust_id] : null;

            if ($preference != $old_preference) {
                try {
                    IntercomWrapper::saveSubscriptionPreference($customer, $subscribed);
                    CLI::write(Log::debug('Updated subscription preference', array(
                        'old' => $old_preference,
                        'new' => $preference,
                    )));
                } catch (Exception $e) {
                    CLI::alert(Log::error('Error updating subscription preference: ' . $e->getMessage()));
                }
            } else {
                CLI::write(Log::debug("Subscription preference did not change"));
            }

            // Grab social profile information.
            $profile_changes = array();
            if (!empty($intercom_user->social_profiles)) {
                foreach ($intercom_user->social_profiles as $social_profile) {
                    $type           = str_replace(' ', '_', $social_profile->type);
                    $property_names = array_keys(get_object_vars($social_profile));

                    foreach ($property_names as $property_name) {
                        $property_name = str_replace(' ', '_', $property_name);

                        if ($property_name == 'type') {
                            continue;
                        }

                        if (isset($social_properties[$cust_id][$type][$property_name])
                            && $social_properties[$cust_id][$type][$property_name] == $social_profile->$property_name
                        ) {
                            continue;
                        }

                        $customer->setSocialProperty(
                            $type,
                            $property_name,
                            $social_profile->$property_name
                        );

                        if (!isset($profile_changes[$type])) {
                            $profile_changes[$type] = array();
                        }

                        $profile_changes[$type][$property_name] = $social_profile->$property_name;
                    }
                }
            }

            if (!empty($profile_changes)) {
                CLI::write(Log::debug('Updated social properties', $profile_changes));
            } else {
                CLI::write(Log::debug("Social properties did not change"));
            }
        }

        $next_page = $response->next_page;
    }

    $engine->complete();
    CLI::write(Log::info('Engine completed'));

    CLI::write(Log::runtime() . ' total runtime');
    CLI::write(Log::memory() . ' peak memory usage');

    CLI::end();

} catch (EngineException $e) {
    CLI::alert(Log::error($e));
    CLI::stop();

} catch (Exception $e) {
    $engine->fail();

    CLI::alert(Log::error($e));
    CLI::alert(get_class($e) . ' Exception');
    CLI::alert($e->getFile() . ' line:' . $e->getLine());

    CLI::stop();
}