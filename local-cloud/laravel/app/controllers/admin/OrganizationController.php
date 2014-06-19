<?php

namespace Admin;

use View;

use willwashburn\table;

use
    DatabaseInstance,
    Input,
    Organization,
    Models\Tailwind\OrganizationFeature,
    Redirect,
    User,
    UserLead,
    UserLeads;

class OrganizationController extends BaseController
{
    protected $layout = 'layouts.admin';

    public function showOrgProfile($org_id)
    {
        return $this->getFeatures($org_id);
    }

    /**
     * @param $org_id
     */
    public function getFeatures($org_id)
    {
        $table = new table;

        /**
         * @var $user User
         */
        $organization = Organization::find($org_id);

        $features = \Feature::all();
        $features->sortBy('name');

        foreach ($features as $row) {
            $enabled = $organization->hasFeature($row);
            if ($enabled) {
                $control = '<a class="btn btn-mini btn-warning" href="/org/' . $org_id . '/feature/' . $row->feature_id . '/disable">Disable</a>';
            } else {
                $control = '<a class="btn btn-mini " href="/org/' . $org_id . '/feature/' . $row->feature_id . '/enable">Enable</a>';
            }

            if ($organization->getFeature($row)) {
                $remove = '<a class="btn btn-mini btn-danger" href="/org/' . $org_id . '/feature/' . $row->feature_id . '/reset"><i class="icon-remove"></i></a>';
            } else {
                $remove = '';

            }

            switch($row->specificity) {
                default:
                    $set = $row->specificity;
                    break;
                case 'plan':
                    $set = '<a href="/plan/'.
                        $organization->plan.
                        '/">'.
                        $row->specificity.'</a>';
                    break;
                case 'org':
                    $set ='<b>'.$row->specificity.'</b>';
                    break;
            }

            $table->addRow(array(
                                'Feature'     => $row->name,
                                'Set by'      => $set,
                                'Enabled'     => bool_as_string($enabled),
                                'Max Allowed' => $organization->maxAllowed($row),
                                ''            => $control,
                                ' '           => $remove
                           )
            );
        }

        $header = View::make('admin.org_header',['org_id'=>$org_id,'plan_id'=>$organization->plan]);

        $this->layout->main_content = $header.$table->render();
    }

    /**
     * @param $org_id
     * @param $feature_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enableFeature($org_id, $feature_id)
    {
        $feature             = new OrganizationFeature();
        $feature->value      = 1;
        $feature->feature_id = $feature_id;
        $feature->org_id     = $org_id;
        $feature->insertUpdate();

        return \Redirect::back();

    }

    /**
     * @param $org_id
     * @param $feature_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disableFeature($org_id, $feature_id)
    {

        $feature             = new \OrganizationFeature();
        $feature->value      = 0;
        $feature->feature_id = $feature_id;
        $feature->org_id     = $org_id;
        $feature->insertUpdate();

        return \Redirect::back();
    }

    /**
     * @param $org_id
     * @param $feature_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetFeature($org_id, $feature_id)
    {

        $DBH = \DatabaseInstance::DBO();
        $STH = $DBH->prepare("
            DELETE FROM user_organization_features WHERE org_id = :org_id AND feature_id = :feature_id
        ");

        $STH->execute([':org_id' => $org_id, ':feature_id' => $feature_id]);

        return \Redirect::back();
    }

    /**
     * GET /organization/{org_id}/plan/edit?plan_id={plan_id}
     *
     * @param $org_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePlan($org_id) {

        $plan_id = Input::get('plan_id');

        /** @var Organization $org */
        $org = Organization::find($org_id);

        $org->changePlan($plan_id,true,true);
        $org->billingUser()->recordEvent('Admin changed plan',['user'=> \Auth::user()->email]);

        return Redirect::back();

    }
}