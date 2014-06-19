<?php

/**
 * Processes updated feed entries that need to be reindexed for search.
 * 
 * @author Daniel
 */

chdir(__DIR__);
include '../../../bootstrap/bootstrap.php';

use Pinleague\CLI,
    Content\DataFeedEntry,
    Content\DataFeedEntries;

Log::setLog(__FILE__, 'CLI');

try {
    CLI::h1('Starting Program');
    $engine = new Engine(__FILE__);

    if ($engine->running()) {
        Log::warning('Engine Already Running');
        CLI::sleep(10);
        CLI::stop();
    }

    $engine->start();
    Log::info('Engine Started');

    $indexer = new SearchIndexer();
    $indexer->execute();

    $engine->complete();

    Log::runtime();
    Log::memory();
}
catch (Exception $e) {
    Log::error($e);
    $engine->fail();

    CLI::stop();
}

/**
 * Syncs recent DB changes to the search index.
 */
class SearchIndexer
{
    /**
     * Search index object.
     *
     * @var AlgoliaSearch\Index
     */
    protected $index;

    /**
     * Updated entries to sync to the search index.
     *
     * @var Content\DataFeedEntries
     */
    protected $updates;

    /**
     * Updated entries to remove from the search index.
     *
     * @var Content\DataFeedEntries
     */
    protected $deletes;

    /**
     * Initializes the class.
     */
    public function __construct()
    {
        $client = new \AlgoliaSearch\Client(
            Config::get('algolia.app_id'),
            Config::get('algolia.write_api_key')
        );

        $this->index = $client->initIndex('feed_entries');

        $this->updates = new DataFeedEntries();
        $this->deletes = new DataFeedEntries();
    }

    /**
     * Executes the subcomponents.
     *
     * @return void
     */
    public function execute()
    {
        $this->processEntries();
        $this->updateIndex();
    }

    /**
     * Fetches and processes recently updated entries.
     *
     * @return void
     */
    protected function processEntries()
    {
        Log::info('Processing Entries For Reindexing');
        $entries = DataFeedEntry::find(array('reindex' => 1, 'limit' => 200));

        foreach ($entries as $entry) {
            if ($entry->curated < 0) {
                $this->deletes->add($entry);
            } else {
                $this->updates->add($entry);
            }
        }
    }

    /**
     * Syncs updated entries to the search index.
     *
     * @return void
     */
    protected function updateIndex()
    {
        if ($this->updates->isNotEmpty()) {
            Log::info("Reindexing {$this->updates->count()} Updated Entries");

            $updates = array();
            foreach ($this->updates as $entry) {
                // Reindex the entry if it contains all required search index data.
                if ($data = $entry->getSearchIndexData()) {
                    $updates[] = $data;
                }
            }

            try {
                // Update objects in search index.
                $this->index->saveObjects($updates);
            } catch (Exception $e) {
                Log::error($e);
            }

            try {
                // Flag entries as having been reindexed.
                $this->updates->reindexed();
                $this->updates->insertUpdateDB();
            } catch (Exception $e) {
                Log::error($e);
            }
        }

        if ($this->deletes->isNotEmpty()) {
            Log::info("Removing {$this->deletes->count()} Entries From Index");

            $deletes = (array) $this->deletes->pluck('id');

            try {
                // Delete objects from search index.
                $this->index->deleteObjects($deletes);
            } catch (Exception $e) {
                Log::error($e);
            }

            try {
                // Flag entries as having been reindexed.
                $this->deletes->reindexed();
                $this->deletes->insertUpdateDB();
            } catch (Exception $e) {
                Log::error($e);
            }
        }
    }
}
