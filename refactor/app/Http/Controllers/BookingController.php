<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Job;
use Auth;
use DTApi\Http\Requests;
use DTApi\Models\Distance;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;

/**
 * Class BookingController
 * @package DTApi\Http\Controllers
 */
class BookingController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {

        $this->repository = $bookingRepository;

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {

        if($user_id = $request->get('user_id')) {

            $response = $this->repository->getUsersJobs($user_id);

        } elseif($request->__authenticatedUser->user_type == env('ADMIN_ROLE_ID') || $request->__authenticatedUser->user_type == env('SUPERADMIN_ROLE_ID'))  {

            $response = $this->repository->getAll($request);

        }

        return response($response);
    }

    /**
     * @param $id
     * @return mixed
     */
    public function show($id)
    {

        return response(
            $this->repository->with('translatorJobRel.user')->find($id)
        );

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function store(Request $request)
    {

        return response(
            $this->repository->store(
                $request->__authenticatedUser,
                $request->all()
            )
        );

    }

    /**
     * @param $id
     * @param Request $request
     * @return mixed
     */
    public function update($id, Request $request)
    {

        return  response(
            $this->repository->updateJob(
                $id,
                $request->except(['_token', 'submit']),
                $request->__authenticatedUser
            )
        );

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function immediateJobEmail(Request $request)
    {

        return response(
            $this->repository->storeJobEmail(
                $request->all()
            )
        );

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getHistory(Request $request)
    {

        if($user_id = $request->get('user_id')) {

            return response(
                $this->repository->getUsersJobsHistory(
                    $user_id,
                    $request
                )
            );

        } else {

            return false;

        }

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function acceptJob(Request $request)
    {

        return response(
            $this->repository->acceptJob(
                $request->all(),
                $request->__authenticatedUser
            )
        );

    }

    public function acceptJobWithId(Request $request)
    {

        return response(
            $this->repository->acceptJobWithId(
                $request->get('job_id'),
                $request->__authenticatedUser
            )
        );

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function cancelJob(Request $request)
    {

        return response(
            $this->repository->cancelJobAjax(
                $request->all(),
                $request->__authenticatedUser
            )
        );

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function endJob(Request $request)
    {

        return response(
            $this->repository->endJob(
                $request->all()
            )
        );

    }

    public function customerNotCall(Request $request)
    {

       return response(
           $this->repository->customerNotCall(
               $request->all()
           )
       );

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function getPotentialJobs(Request $request)
    {

        return response($this->repository->getPotentialJobs(
            $request->__authenticatedUser
        ));

    }

    public function distanceFeed(Request $request)
    {

        $data = $request->all();

        if (isset($data['distance']) && $data['distance'] != "") {
            $distance = $data['distance'];
        } else {
            $distance = "";
        }
        
        if (isset($data['time']) && $data['time'] != "") {
            $time = $data['time'];
        } else {
            $time = "";
        }

        if (isset($data['jobid']) && $data['jobid'] != "") {
            $jobid = $data['jobid'];
        }

        if (isset($data['session_time']) && $data['session_time'] != "") {
            $session = $data['session_time'];
        } else {
            $session = "";
        }

        if ($data['flagged'] == 'true') {
            if($data['admincomment'] == '') return "Please, add comment";
            $flagged = 'yes';
        } else {
            $flagged = 'no';
        }
        
        if ($data['manually_handled'] == 'true') {
            $manually_handled = 'yes';
        } else {
            $manually_handled = 'no';
        }

        if ($data['by_admin'] == 'true') {
            $by_admin = 'yes';
        } else {
            $by_admin = 'no';
        }

        if (isset($data['admincomment']) && $data['admincomment'] != "") {
            $admincomment = $data['admincomment'];
        } else {
            $admincomment = "";
        }

        if ($time || $distance) {
            $affectedRows = Distance::where('job_id', '=', $jobid)->update(array('distance' => $distance, 'time' => $time));
        }

        if ($admincomment || $session || $flagged || $manually_handled || $by_admin) {
            $affectedRows1 = Job::where('id', '=', $jobid)->update(array('admin_comments' => $admincomment, 'flagged' => $flagged, 'session_time' => $session, 'manually_handled' => $manually_handled, 'by_admin' => $by_admin));
        }

        return response('Record updated!');

    }

    public function reopen(Request $request)
    {

        return response(
                $this->repository->reopen(
                $request->all()
            )
        );

    }

    public function resendNotifications(Request $request)
    {
        $job = $this->repository->find($request->get('jobid'));

        $this->repository->sendNotificationTranslator(
            $job,
            $this->repository->jobToData($job),
            '*'
        );

        return response(['success' => 'Push sent']);

    }

    /**
     * Sends SMS to Translator
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function resendSMSNotifications(Request $request)
    {

        $job = $this->repository->find($request->get('jobid'));

        try {

            $this->repository->sendSMSNotificationToTranslator($job);
            return response(['success' => 'SMS sent']);

        } catch (\Exception $e) {

            return response(['success' => $e->getMessage()]);

        }
        
    }

}
