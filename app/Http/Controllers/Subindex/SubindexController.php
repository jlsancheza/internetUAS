<?php namespace Intranet\Http\Controllers\Subindex;

use View;
use Session;
use Illuminate\Routing\Controller as BaseController;
use Intranet\Http\Services\Faculty\FacultyService;
use Intranet\Http\Services\Course\CourseService;

class SubindexController extends BaseController {
    protected $facultyService;
    protected $courseService;


    public function __construct(){
        $this->facultyService= new FacultyService();
        $this->courseService = new CourseService();
    }

    public function index() {
        $data['title'] = "Especialidades";
        $data['faculties'] = [];

        $user = $data['userSession'] = Session::get('user');
      
        try {
            $allFaculties = $this->facultyService->retrieveAll();               
            if (($user->IdDocente!=null)  && ($user->IdDocente == 0)){//detect admin
                $data['faculties'] = $allFaculties;
                $data['isEmpty'] = false;
            } else if (($user->IdDocente!=null)  && $user->IdDocente>=1){//check if user is teacher or coordinator
                if ($user->user->IdPerfil == 1){
                    array_push($data['faculties'], $this->facultyService->find($user->IdEspecialidad));
                    $data['isEmpty'] = false;
                }else{
                    $courses = $data['teachersDictatedCourses'] = $this->courseService->findCoursesByTeacher($user->IdDocente);
                    $teachersFaculties = [];
                    if ($courses && $allFaculties){
                        foreach ($allFaculties as $fac){
                            foreach ($courses as $course){
                                if ($course->IdEspecialidad == $fac->IdEspecialidad){
                                    array_push($teachersFaculties, $fac);
                                    break;
                                }
                            }
                        }
                    }
                    $data['faculties'] = $teachersFaculties;
                    if(empty($teachersFaculties)){
                        $data['isEmpty'] = true;
                    }else{
                        $data['isEmpty'] = false;
                    }
                }
            } else if ($user->user->IdPerfil == 5){ //Investigadores
                array_push($data['faculties'], $this->facultyService->find($user->id_especialidad));
                $data['isEmpty'] = false;
            }else if ($user->user->IdPerfil== 6 || !$user->user->idPerfil){ //Supervisores
                array_push($data['faculties'], $this->facultyService->find($user->idFaculty));
                $data['isEmpty'] = false;
            } else { // Logic of ACREDITORS
                $data['isEmpty'] = false;

                if($user->user->IdPerfil== 4 || $user->user->IdPerfil> 5){ // check if user is admin or general accreditor
                    array_push($data['faculties'], $this->facultyService->find($user->IdEspecialidad));
                }else if($user->user->IdPerfil==3 || $user->user->IdPerfil==5){ // check if user is admin or general accreditor
                    $data['faculties'] = $allFaculties;
                }
            }

            Session::forget("numFaculties");
            Session::put("numFaculties",count($data['faculties']));
        } catch(\Exception $e) {
            dd($e);
        }
        //dd($data);
        return view('subindex.index',$data);
    }
}