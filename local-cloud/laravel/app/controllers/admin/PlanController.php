<?php

namespace Admin;

use View;

use willwashburn\table;

use DatabaseInstance,
    User,
    Plan;

class PlanController extends BaseController
{
    protected $layout = 'layouts.admin';


    public function showPlan($plan_id) {
        return $this->getFeatures($plan_id);
    }

    /**
     * @param $plan_id
     */
    public function getFeatures($plan_id) {
        $table = new table;

        /**
         * @var $user User
         */
        $plan = Plan::find($plan_id);

        $features = \Feature::all();
        $features->sortBy('name');

        foreach ($features as $row) {
            $enabled = $plan->hasFeature($row);
            if ($enabled) {
                $control = '<a class="btn btn-mini btn-danger" href="/plan/'.$plan_id.'/feature/'.$row->feature_id.'/disable">Disable</a>';
            } else {
                $control = '<a class="btn btn-mini " href="/plan/'.$plan_id.'/feature/'.$row->feature_id.'/enable">Enable</a>';
            }

            if ($plan->getFeature($row)) {
                $remove = '<a class="btn btn-mini btn-danger" href="/plan/' . $plan_id . '/feature/' . $row->feature_id . '/reset"><i class="icon-remove"></i></a>';
            } else {
                $remove = '';

            }

            if($plan->getFeature($row,true)->isEditable()) {

                $control = '<form action="/plan/'.$plan_id.'/feature/'.$row->feature_id.'/edit">
                    <input style="width:10px" type="text" name="feature_value" value="'.
                    $plan->featureValue($row).'" /><button class="btn btn-mini" type="submit">Save</button></form>';
            }

            switch($row->specificity) {
                default:
                    $set = $row->specificity;
                    break;
                case 'plan':
                    $set ='<b>'.$row->specificity.'</b>';
                    break;
            }


            $table->addRow(array(
                                'Feature' => $row->name,
                                'Set by' => $set,
                                'Enabled' => bool_as_string($enabled),
                                'Max Allowed' => $plan->maxAllowed($row),
                                ''=>$control,
                                ' '=>$remove
                           )
            );
        }

        $header = View::make('admin.plan_header',['plan_id'=>$plan_id]);

        $this->layout->main_content = $header.$table->render();
    }


    /**
     * @param $plan_id
     * @param $feature_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enableFeature($plan_id,$feature_id) {
        $feature = new \PlanFeature();
        $feature->value = 1;
        $feature->feature_id = $feature_id;
        $feature->plan_id = $plan_id;
        $feature->insertUpdateDB();

        return \Redirect::back();

    }

    /**
     * @param $plan_id
     * @param $feature_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disableFeature($plan_id,$feature_id) {

        $feature = new \PlanFeature();
        $feature->value = 0;
        $feature->feature_id = $feature_id;
        $feature->plan_id = $plan_id;
        $feature->insertUpdateDB();

        return \Redirect::back();
    }

    /**
     *
     * @param $plan_id
     * @param $feature_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function editFeature($plan_id,$feature_id) {
        $feature = new \PlanFeature();
        $feature->value = \Input::get('feature_value',0);
        $feature->feature_id = $feature_id;
        $feature->plan_id = $plan_id;
        $feature->insertUpdateDB();

        return \Redirect::back();

    }

    /**
     * @param $plan_id
     * @param $feature_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetFeature($plan_id,$feature_id) {

        $DBH = \DatabaseInstance::DBO();
        $STH = $DBH->prepare("
            delete from plan_features where plan_id = :plan_id and feature_id = :feature_id
        ");

        $STH->execute([':plan_id'=>$plan_id,':feature_id'=>$feature_id]);

        return \Redirect::back();
    }
}