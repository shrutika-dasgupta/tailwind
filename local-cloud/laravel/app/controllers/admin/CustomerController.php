<?php namespace Admin;


use
    Auth,
    DataBaseInstance,
    Engine,
    Engines,
    Input,
    Models\Tailwind\Feature,
    Redirect,
    User,
    Users,
    UserLead,
    UserLeads,
    View,
    willwashburn\table;

/**
 * Class CustomerController
 *
 * @package Admin
 */
class CustomerController extends BaseController
{
    protected $layout = 'layouts.admin';

    /**
     * Show list of customers
     *
     * @author  Will
     * @author  Alex
     *
     */
    public function getIndex()
    {

        $this->layout->main_content = View::make('admin.customer_search_form');


        if(!empty($_GET['search_term'])){

            $search_term = $_GET['search_term'];

            $table = new table();
            $table->setId('customers');

            $customers = Users::findByAnything(
                array(
                     array(
                         'preload' => 'organization',
                         'include' => 'plan'
                     )

                ), $search_term, 15000, 0
            );

            foreach ($customers as $customer) {

                try {

                    /** @var $customer User */
                    if($customer->temporary_key == '') {
                        $customer->temporary_key = $customer->createTemporaryKey();
                    }

                    try {
                        $main_account = $customer->organization()->primaryAccount()->username;
                    }
                    catch (\Exception $e) {
                        $main_account = 'Not found';
                    }

                    $table->addRow(
                        array(
                             'cust id'                   =>
                             '<a href="/customer/' .
                             $customer->cust_id .
                             '/">' .
                             $customer->cust_id . '</a>',
                             'org id'                    =>
                                 '<a href="/org/' .
                                 $customer->org_id .
                             '">' .
                                 $customer->org_id. '</a>',
                             'account_id'                => $customer->organization()->primaryAccount()->account_id,
                             'Organization'              => $customer->organization()->org_name,
                             'Plan'                      =>
                                 '<a href="/plan/' .
                                 $customer->organization()->plan.
                                 '/">' .
                                 $customer->organization()->plan()->name.'</a>',
                             'Pinterest Account (guess)' => $main_account,
                             'email'                     => $customer->email,
                             'name'                      => $customer->getName(),
                             'chargify id'               => $customer->organization()->chargify_id,
                             'created_at'                => date('r', $customer->organization()->primaryAccount()->created_at),
                             'login' => '<a href="http://analytics.tailwindapp.com/login/auto/'.
                             $customer->email.'/'.$customer->temporary_key.
                             '" target="_blank">Auto-Login</a>'
                        )
                    );
                }
                catch (\Exception $e) {
                    //do nothing
                }
            }

            $vars = array(
                'customer_table' => $table->render(),
                'execution_time' => ''
            );

            $this->layout->main_content .= View::make('admin.customer_table', $vars);
        }


    }

    /**
     * @author  Will
     */
    public function getLeads()
    {

        $table = new table();
        $table->setId('customers');

        $leads = UserLeads::allByDistinctUsername();

        foreach ($leads as $lead) {

            /** @var $lead UserLead */
            $table->addRow(
                array(
                     '#'         => $lead->__count,
                     'username'  => $lead->username,
                     'browser'  => $lead->user_agent,
                     'ip'        => $lead->ip,
                     'timestamp' => date('r', $lead->timestamp),
                )
            );
        }


        $vars = array(
            'customer_table' => $table->render(),
            'execution_time' => ''
        );

        $this->layout->main_content = View::make('admin.customer_table', $vars);


    }

    /**
     * @author  Will
     * @param $cust_id
     */
    public function getHistory($cust_id) {

        $table = new table();
        $table->setId('customers');

        $user = User::find($cust_id);

        foreach ($user->getHistory() as $history) {

            /** @var $history /UserHistory */
            $table->addRow(
                array(
                     'type' => $history->type,
                     'action'=>$history->description,
                     'time'=>date('Y-M-d, g:ia',$history->timestamp)
                )
            );
        }

        $vars = [
            'name'    => $user->getName(),
            'cust_id' => $user->cust_id,
            'org_id'  => $user->org_id,
            'panel'   => $table->render()
        ];

        $this->layout->main_content = View::make('admin.customer_profile', $vars);
    }

    /**
     * /customer/{customer_id}/features
     * @param $cust_id
     */
    public function getFeatures($cust_id) {
        $table = new table;
        /**
         * @var $user User
         */
        $user = User::find($cust_id);

        $features = Feature::all();
        $features->sortByColumn('name');

        foreach ($features as $row) {
            $enabled = $user->hasFeature($row);
            if ($enabled) {
             $control = '<a class="btn btn-mini btn-warning" href="/customer/'.$cust_id.'/feature/'.$row->feature_id.'/disable">Disable</a>';
            } else {
            $control = '<a class="btn btn-mini " href="/customer/'.$cust_id.'/feature/'.$row->feature_id.'/enable">Enable</a>';
            }

            if ($user->getFeature($row)) {
                $remove = '<a class="btn btn-mini btn-danger" href="/customer/'.$cust_id.'/feature/'.$row->feature_id.'/reset"><i class="icon-remove"></i></a>';
            } else {
                $remove ='';
            }

            if($user->getFeature($row,true)->isEditable()) {

                    $control = '<form action="/customer/'.$cust_id.'/feature/'.$row->feature_id.'/edit">
                    <input style="width:10px" type="text" name="feature_value" value="'.
                        $user->featureValue($row).'" /><button class="btn btn-mini" type="submit">Save</button></form>';
            }

            switch($row->specificity) {
                default:
                    $set = $row->specificity;
                    break;
                case 'plan':
                    $set = '<a href="/plan/'.
                        $user->organization()->plan.
                        '/">'.
                        $row->specificity.'</a>';
                    break;
                case 'org':
                    $set = '<a href="/org/'.
                        $user->org_id.
                        '/">'.
                        $row->specificity.'</a>';
                 break;
                case 'user':
                    $set ='<b>'.$row->specificity.'</b>';
                    break;

            }

            $table->addRow(array(
                        'Feature' => $row->name,
                        'Set by' => $set,
                        'Enabled' => bool_as_string($enabled),
                        'Max Allowed' => $user->maxAllowed($row),
                        ''=>$control,
                        ' ' => $remove
                          )
            );
        }

        $vars = [
            'name'=>$user->getName(),
            'cust_id' => $user->cust_id,
            'org_id' => $user->org_id,
            'panel' => $table->render()
        ];

        $this->layout->main_content = View::make('admin.customer_profile',$vars);
    }

    /**
     * @param $cust_id
     */
    public function showCustomerProfile($cust_id) {
        return $this->getFeatures($cust_id);
    }

    /**
     * @param $cust_id
     * @param $feature_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function enableFeature($cust_id,$feature_id) {
        $feature = new \UserFeature();
        $feature->value = 1;
        $feature->feature_id = $feature_id;
        $feature->cust_id = $cust_id;
        $feature->insertUpdate();

        return \Redirect::back();

    }

    /**
     * @param $cust_id
     * @param $feature_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function editFeature($cust_id,$feature_id) {
        $feature = new \UserFeature();
        $feature->value = \Input::get('feature_value',0);
        $feature->feature_id = $feature_id;
        $feature->cust_id = $cust_id;
        $feature->insertUpdate();

        return \Redirect::back();

    }

    /**
     * @param $cust_id
     * @param $feature_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function disableFeature($cust_id,$feature_id) {

            $feature = new \UserFeature();
            $feature->value = 0;
            $feature->feature_id = $feature_id;
            $feature->cust_id = $cust_id;
            $feature->insertUpdate();

            return \Redirect::back();
    }

    /**
     * @param $cust_id
     * @param $feature_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function resetFeature($cust_id,$feature_id) {

        $DBH = \DatabaseInstance::DBO();
        $STH = $DBH->prepare("
            delete from user_features where cust_id = :cust_id and feature_id = :feature_id
        ");

        $STH->execute([':cust_id'=>$cust_id,':feature_id'=>$feature_id]);

        return \Redirect::back();
    }


    /**
     * @param $cust_id
     */
    public function turnOffEmails($cust_id) {

        /** @var User $user */
        $user = User::find($cust_id);
        $user->cancelFutureAutomatedEmails();

        foreach ($user->organization()->connectedUserAccounts() as $user_account) {
            $user->removeEmailPreferences($user_account);
        }

    }

    /**
     * GET /customer/{cust_id}/plan/edit?plan_id={plan_id}
     *
     * @param $cust_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function changePlan($cust_id) {

        $plan_id = Input::get('plan_id');

        /** @var User $user */
        $user = User::find($cust_id);

        $user->organization()->changePlan($plan_id,true,true);
        $user->recordEvent('Admin changed plan',['user'=> Auth::user()->email]);

        return Redirect::back();

    }
}