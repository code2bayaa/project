<?php

namespace Modules\COD\Http\Controllers;

use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Application\Entities\AdmissionApproval;
use Modules\Application\Entities\Application;
use Modules\Application\Entities\Education;
use Modules\Finance\Entities\FinanceLog;
use Modules\COD\Entities\CODLog;
use Auth;
use Modules\Registrar\Entities\Attendance;
use Modules\Registrar\Entities\AvailableCourse;
use Modules\Registrar\Entities\Campus;
use Modules\Registrar\Entities\Courses;
use Modules\Registrar\Entities\Intake;
use Modules\Registrar\Entities\KuccpsApplicant;
use Modules\Registrar\Entities\KuccpsApplication;

class CODController extends Controller
{

    public function applications(){

        $applications = Application::where('cod_status', '>=', 0)
            ->where('department_id', auth()->guard('user')->user()->department_id)
            ->where('dean_status', null)
            ->orWhere('dean_status', 3)
            ->orderby('id', 'DESC')
            ->get();

        return view('cod::applications.index')->with('apps', $applications);
    }

    public function viewApplication($id){

        $app = Application::find($id);
        $school = Education::where('applicant_id', $app->applicant->id)->first();

        return view('cod::applications.viewApplication')->with(['app' => $app, 'school' => $school]);
    }

    public function previewApplication($id){

        $app = Application::find($id);
        $school = Education::where('applicant_id', $app->applicant->id)->first();
        return view('cod::applications.preview')->with(['app' => $app, 'school' => $school]);
    }

    public function acceptApplication($id){

        $app = Application::find($id);
        $app->cod_status = 1;
        $app->cod_comments = NULL;
        $app->save();

        $logs = new CODLog;
        $logs->application_id = $app->id;
        $logs->user = Auth::guard('user')->user()->name;
        $logs->user_role = Auth::guard('user')->user()->role_id;
        $logs->activity = 'Application accepted';
        $logs->save();

        return redirect()->route('cod.applications')->with('success', '1 applicant approved');
    }

    public function rejectApplication(Request $request, $id){


            $request->validate([
                'comment' => 'required|string'
            ]);
        $app = Application::find($id);
        $app->cod_status = 2;
        $app->cod_comments = $request->comment;
        $app->save();

        $logs = new CODLog;
        $logs->application_id = $app->id;
        $logs->user = Auth::guard('user')->user()->name;
        $logs->user_role = Auth::guard('user')->user()->role_id;
        $logs->comments = $request->comment;
        if ($app->dean_status === 3){
            $logs->activity = 'Application reviewed by COD';
        }
        $logs->activity = 'Application rejected';
        $logs->save();

        return redirect()->route('cod.applications')->with('success', 'Application declined');
    }

    public function batch(){
        $apps = Application::where('cod_status', '>', 0)
            ->where('department_id', auth()->guard('user')->user()->department_id)
            ->where('dean_status', null)
            ->orWhere('dean_status', 3)
            ->where('cod_status', '!=', 3)
            ->get();

        return view('cod::applications.batch')->with('apps', $apps);
    }

    public function batchSubmit(Request $request){

        foreach ($request->submit as $item){
            $app = Application::find($item);
            $app->dean_status = 0;
            $app->save();

            $logs = new CODLog;
            $logs->application_id = $app->id;
            $logs->user = Auth::guard('user')->user()->name;
            $logs->user_role = Auth::guard('user')->user()->role_id;
            $logs->activity = "Application awaiting Dean's Verification";
            $logs->save();

        }

        return redirect()->route('cod.batch')->with('success', '1 Batch elevated for Dean approval');
    }


    public function admissionsSelf(){

        $applicant = Application::where('cod_status', 1)
            ->where('department_id', auth()->guard('user')->user()->department_id)
            ->where('registrar_status', 3)
            ->where('status', 0)
            ->get();

        return view('cod::admissions.index')->with('applicant', $applicant);
    }

    public function reviewAdmission($id){
        $app = Application::find($id);
        $school = Education::find($id);

        return view('cod::admissions.review')->with(['app' => $app, 'school' => $school]);
    }

    public function acceptAdmission($id){

        $app = AdmissionApproval::where('application_id', $id)->first();

        if ($app === NULL){
            $adm = new AdmissionApproval;
            $adm->application_id = $id;
            $adm->cod_status = 1;
            $adm->save();
        }else{
            AdmissionApproval::where('application_id', $id)->update(['cod_status' => 1]);
        }

        return redirect()->route('cod.selfAdmissions')->with('success', 'New student admitted successfully');
    }

    public function rejectAdmission(Request $request, $id){

            $app = AdmissionApproval::where('application_id', $id)->first();

            if ($app === NULL){

                $adm = new AdmissionApproval;
                $adm->application_id = $id;
                $adm->cod_status = 2;
                $adm->cod_comments = $request->comment;
                $adm->save();

            }else{

                AdmissionApproval::where('application_id', $id)->update(['cod_status' => 2, 'cod_comments' => $request->comment]);

            }

        return redirect()->route('cod.selfAdmissions')->with('warning', 'Admission request rejected');
    }
    public function rejectAdmJab(Request $request, $id){

        if (AdmissionApproval::where('application_id', $id)->exists()) {
            AdmissionApproval::where('application_id', $id)->update(['cod_status' => 2, 'cod_comments' => $request->comment]);
        }else{
            $adm = new AdmissionApproval;
            $adm->application_id = $id;
            $adm->cod_status = 2;
            $adm->cod_comments = $request->comment;
            $adm->save();
        }

        return redirect()->back()->with('warning', 'Admission request rejected');
    }

    public function submitAdmission($id){

        AdmissionApproval::where('application_id', $id)->update(['finance_status' => 0]);

        Application::where('id', $id)->update(['status' => 1]);

        return redirect()->back()->with('success', 'Record submitted to finance');
    }


    public function submitAdmJab($id){

        AdmissionApproval::where('application_id', $id)->update(['finance_status' => 0]);

        KuccpsApplication::where('applicant_id', $id)->update(['registered' => Carbon::now()]);

        return redirect()->back()->with('success', 'Record submitted to finance');
    }

    public function courses(){
            $courses = Courses::where('department_id', auth()->guard('user')->user()->department_id)->get();

            return view('cod::courses.index')->with('courses', $courses);
    }

    public function intakes(){
            $intakes = Intake::all();

            return view('cod::intakes.index')->with('intakes', $intakes);
    }

    public function intakeCourses($id){

            $modes = Attendance::all();
            $campuses = Campus::all();
            $intake = Intake::find($id);
            $courses = Courses::where('department_id', auth()->guard('user')->user()->department_id)->get();

            return view('cod::intakes.addCourses')->with(['intake' => $intake, 'courses' => $courses, 'modes' => $modes, 'campuses' => $campuses]);

    }

    public function addAvailableCourses(Request $request){

//        $request->validate([
//            'intake_id' => 'required',
//            'course_id' => 'required',
//            'campus_id' => 'required',
//            'course_code' => 'required',
//            'attendance_id' => 'required',
//            'attendance_code' => 'required',
//
//        ]);

        return $request->json()->all();


        print_r(json_encode($request->json()->all()));

/*

        return $request->all();

        foreach($request->input('course') as $course){
            $intakes              =          new AvailableCourse;
            $intakes->course_id   =          $course->course_id;
            $intakes->intake_id   =          $course->intake_id;
            $intakes->save();
        }

        return redirect()->back()->with('success', 'Course added to available intake');
        */

    }



    /**
     * Display a listing of the resource.
     * @return Renderable
     */
    public function index()
    {
        return view('cod::index');
    }

    /**
     * Show the form for creating a new resource.
     * @return Renderable
     */
    public function create()
    {
        return view('cod::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Renderable
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        return view('cod::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        return view('cod::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Renderable
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Renderable
     */
    public function destroy($id)
    {
        //
    }
}
