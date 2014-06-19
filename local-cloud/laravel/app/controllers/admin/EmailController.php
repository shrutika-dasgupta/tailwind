<?php namespace Admin;

use Redirect,
    DatabaseInstance,
    Auth,
    View;

use User,
    UserEmail,
    UserHistory;

use Exception;

/**
 * Class EmailController
 *
 * @package Admin
 */
class EmailController extends BaseController
{

    /*
     * The layout that should be used for responses.
     */
    protected $layout = 'layouts.admin';

    /**
     * @author  Will
     *
     * @param $email_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cancelEmailSend($email_id)
    {

        try {

            $email         = UserEmail::find($email_id);
            $email->status = UserEmail::STATUS_CANCELLED;
            $email->saveToDB();
        }
        catch (Exception $e) {
        }

        return Redirect::back();
    }

    /**
     * @author  Will
     *
     * @param $email_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function deleteFromQueue($email_id)
    {

        try {

            $email = UserEmail::find($email_id);
            $email->removeFromDB();
        }
        catch (Exception $e) {
            Redirect::back()
            ->with('flash_error', $e->getMessage());
        }

        return Redirect::back();
    }

    /**
     * @author  Will
     */
    public function getQueue()
    {
        $this->showQueue('queued');
    }

    /**
     * @author  Will
     *
     * @param $email_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function requeueEmail($email_id)
    {

        try {

            $email = UserEmail::find($email_id);

            switch ($email->status) {
                default:
                    break;

                case UserEmail::STATUS_SENT:
                    $email->repeat = UserEmail::REPEATING_NO;
                    break;
            }

            $email->status = UserEmail::STATUS_QUEUED;


            $email->saveToDB();
        }
        catch (Exception $e) {

        }

        return Redirect::back();
    }

    /**
     * @author  Will
     *
     * @param $email_id
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendPreview($email_id)
    {

        $email = UserEmail::find($email_id);
        $email->prepareToSend();
        $email->to = Auth::user()->email;
        $email->send();

        return Redirect::to('/email/queue');
    }

    /**
     * /email/preview/{id}/resend
     * @author  Will
     */
    public function resend($email_id) {

        $email = UserEmail::find($email_id);
        $email->send();

        $email->customer()->recordEvent(
              UserHistory::EMAIL_SEND
        );

        return Redirect::to('/email/queue');

    }

    /**
     * @author  Will
     *
     * @param $email_id
     *
     * @return \View
     */
    public function showPreview($email_id)
    {
        $email = UserEmail::find($email_id);

        return $email->render();

    }

    /**
     * @author  Will
     *
     * @param $filter
     */
    public function showQueue($filter)
    {


        $time = strtotime('3 days ago');
        $DIR = 'ASC';

        switch ($filter) {

            default:
            case 'all':
                $where = '';
                break;

            case 'queued':

                $where = "and status = 'Q'";
                break;

            case 'cancelled':

                $where = "and status = 'C'";
                break;
            case 'processing':

                $where = "and status = 'P'";
                break;

            case 'sent':
                $where = "and (status = 'S' or status='F') ";
                $DIR = 'DESC';
                break;

            case 'failed':
                $where = "and status = 'F'";
                $DIR = 'DESC';
                break;


        }


        $DBH = DatabaseInstance::DBO();
        $STH = $DBH->prepare("
                SELECT * FROM `user_email_queue` where send_at > 0
                $where order by send_at $DIR LIMIT 0,1000;

            ");

        $STH->execute();

        $emails = $STH->fetchAll();

        $prepared_email_list = array();

        foreach ($emails as $raw) {

            $customer = User::find($raw->cust_id);
            $emailObject = UserEmail::find($raw->id);

            $email             = new \stdClass();
            $email->email      = $emailObject->emailAddresses();
            $email->id         = $raw->id;
            $email->username = $emailObject->profiles();
            $email->cust_id    = $raw->cust_id;
            $email->customer   = $customer->getName();
            $email->repeat     = $raw->repeat;
            $email->send_at    =
                date('M j, Y g:ia (D)', $raw->send_at);
            $email->name       = ucwords(str_replace('_', ' ', $raw->email_name));
            $email->cancelable = true;
            $email->action .= '<a class="btn btn-mini" href="/email/requeue/'.$email->id.'/">Requeue</a>';
            $email->action .= '<a class="btn btn-mini pull-right" href="/email/cancel/'.$email->id.'/">Cancel</a>';

            switch ($raw->status) {
                case 'C':
                    $email->label      = '';
                    $email->cancelable = false;
                    $email->status     = 'Cancelled';
                    break;

                case 'Q':
                    $email->label  = 'info';
                    $email->status = 'Queued';
                    break;

                case 'S':
                    $email->label      = 'success';
                    $email->status     = 'Sent';
                    $email->cancelable = false;
                    break;

                case 'F':
                    $email->label      = 'important';
                    $email->status     = 'Failed';
                    $email->cancelable = true;

                    break;

                default:
                    $email->label  = 'alert';
                    $email->status = $raw->status;
                    break;

            }

            $prepared_email_list[] = $email;
        }


        $vars = array(
            'emails' => $prepared_email_list,
            'preview_email' => Auth::user()->email
        );

        $this->layout->main_content = View::make('admin/email_queue', $vars);


    }
}



