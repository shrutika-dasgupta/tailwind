<?php namespace Presenters\Dashboard;

use View,
    Caches\DomainDailyCounts,
    UserAccount,
    Log;

/**
 * Class Widget
 *
 * @package Presenters\Dashboard
 */
class DomainPinsWidget extends Widget implements WidgetInterface
{
    /**
     * @var \UserAccountsDomains
     */
    protected $domains;

    /**
     * @author  Will
     *
     * @param UserAccount  $user_account
     * @param \UserAccount $reference_time
     */
    public function __construct(UserAccount $user_account, $reference_time)
    {
        parent::__construct($user_account, $reference_time);

        $this->domains
            = $user_account->domains();

        $this->sentiment_metric = $this->domains->count();

        $this->setViewName('new_domain_pins');
    }

    /**
     * @author  Will
     * @return string
     */
    public function render()
    {
        $new_domain_pins = false;
        $last_weeks_domain_pins = 0;

        foreach ($this->domains as $domain) {

            if (!empty($domain->domain)) {

                $new_domain_pins +=
                    DomainDailyCounts::sumDuring(
                                     $domain->domain,
                                     'pin_count',
                                     $this->reference_time,
                                     time()
                    );

                $last_weeks_domain_pins +=
                    DomainDailyCounts::sumDuring(
                                     $domain->domain,
                                     'pin_count',
                                     strtotime('-1 week',$this->reference_time),
                                     $this->reference_time
                    );
            }
        }

        $new_domain_pins_count = within_limit($new_domain_pins, 3, 7);

        $vars = array(
            'domains'          => $this->domains,
            'last_week' =>$last_weeks_domain_pins,
            'change_in_growth' =>round($new_domain_pins - $last_weeks_domain_pins),
            'new_organic_pins' => number_format($new_domain_pins),
            'domain_pins'      => $this->domains->getRecentPins($new_domain_pins_count),
            'domain_pinners'   => $this->domains->topPinners(7, 3)
        );

        return View::make($this->viewPath(), $vars);
    }

}