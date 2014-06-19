<?php
/**
 * The engine computes the footprints across a most influential
 * pinners across a domain
 */

chdir(__DIR__);
include '../../bootstrap/bootstrap.php';

use Pinleague\Pinterest;
use Pinleague\PinterestException;
use Pinleague\CLI;

Log::setLog(__FILE__, 'CLI');

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);
    Log::info('Engine started');

    if ($engine->running()) {
        throw new EngineException('Engine is running');
    }

    /*
     * Fetch the user_ids to be computed
     */
    $domains_to_compute = StatusDomains::fetch(
                                        $flag="calculate_influencers_footprint",
                                        $limit = 150);
    if (empty($domains_to_compute)) {
        Log::notice("No more users to compute");
        $engine->complete();
        CLI::stop();
    }

    $influencer_footprints = new CacheDomainInfluencersFootprint();

    foreach ($domains_to_compute as $domain_to_compute) {

        $computed_domains[] = $domain_to_compute->domain;

        $category_aggregate =
            MapProfilesCategoryHash::
                computeFootprint('footprint_count',
                                 $domain_to_compute->domain,
                                 'domain');

        foreach ($category_aggregate as $details) {

            if (!empty($details->category)) {
                $influencer_footprint = new CacheDomainInfluencerFootprint();

                $influencer_footprint->domain = $domain_to_compute->domain;
                $influencer_footprint->category = $details->category;
                $influencer_footprint->footprint_count = $details->sum;
                $influencer_footprint->period = $details->period;

                $influencer_footprints->add($influencer_footprint);
            }
        }
    }

    try {
        $influencer_footprints->saveModelsToDB();
    } catch (CollectionException $e) {
        Log::warning("No more footprints to save");
    }

    Log::debug("Updated computed user_ids in status_profiles");

    $computed_domains = '"' . implode('","', $computed_domains) . '"';

    $DBH = DatabaseInstance::DBO();

    $current_time = time();

    $STH = $DBH->prepare("UPDATE status_domains
                          SET calculate_influencers_footprint = :current_time
                          WHERE domain in ($computed_domains)");
    $STH->execute([":current_time" => $current_time]);

    $engine->complete();

    CLI::write(Log::runtime(). 'total runtime');
    CLI::write(Log::memory().' peak memory usage');

    CLI::h1(Log::info('Complete'));


} catch (Exception $e) {
    $engine->fail();
    Log::error($e);
    CLI::stop();
}
