<?php namespace Pinleague;

use Config,
    DateTime,
    DateTimeZone,
    Lang;

/**
 * Timezone wrapper class.
 *
 * @author Janell
 */
class Timezone
{
    /**
     * A timezone identifier supported by PHP.
     *
     * @var string
     */
    public $identifier;

    /**
     * A friendlier display name for this timezone.
     *
     * @var string
     */
    public $display_name;

    /**
     * Initializes the class.
     *
     * @param string $identifier
     *
     * @return void
     */
    public function __construct($identifier)
    {
        $this->identifier = $identifier;
    }

    /**
     * Gets a new timezone instance.
     *
     * @param string $identifier
     *
     * @return \Pinleague\Timezone
     */
    public static function instance($identifier = null)
    {
        if (is_null($identifier) || !in_array($identifier, DateTimeZone::listIdentifiers())) {
            $identifier = Config::get('app.timezone');
        }
        return new self($identifier);
    }

    /**
     * Returns display names for timezones that may be presented as options to a user.
     *
     * @return array
     */
    public static function getDisplayNames()
    {
        return Lang::get('timezones.display_names');
    }

    /**
     * Returns display names for timezones with their GMT offset included.
     *
     * @return array
     */
    public static function getDisplayNamesWithOffset()
    {
        $display_names = self::getDisplayNames();
        $offset_names = array();

        foreach ($display_names as $display_name => $identifier) {
            $timezone = self::instance($identifier);
            $offset_names[$display_name . ' (' . $timezone->getOffset() . ')'] = $identifier;
        }

        return $offset_names;
    }

    /**
     * Returns display names for timezones with the current local time included.
     *
     * @return array
     */
    public static function getDisplayNamesWithTime()
    {
        $display_names = self::getDisplayNames();
        $time_names = array();

        foreach ($display_names as $display_name => $identifier) {
            $timezone = self::instance($identifier);
            $time_names[$display_name . ' (' . $timezone->getCurrentTime() . ')'] = $identifier;
        }

        return $time_names;
    }

    /**
     * Returns the formatted GMT offset for this timezone.
     *
     * @return string
     */
    public function getOffset()
    {
        $time = new DateTime('now', new DateTimeZone($this->identifier));

        return $time->format('P');
    }

    /**
     * Returns the formatted current local time for this timezone.
     *
     * @return string
     */
    public function getCurrentTime()
    {
        $time = new DateTime('now', new DateTimeZone($this->identifier));

        return $time->format('g:i A');
    }
}