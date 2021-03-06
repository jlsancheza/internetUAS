<?php 

namespace Intranet\Http\Services\Psp;

use DB;
use Auth;
use Session;

use Intranet\Models\PspProcess;
use Intranet\Models\AcademicCycle;
use Intranet\Models\CoursexTeacher;
use Intranet\Models\CoursexCycle;
use Intranet\Models\Schedule;
use Intranet\Models\SchedulexTeacher;
use Intranet\Models\Student;

use Intranet\Http\Services\Cicle\CicleService;
use Intranet\Http\Services\Course\CourseService;
use Intranet\Http\Services\Teacher\TeacherService;

class PspProcessService{

	public function find(){
		//$proc = PspProcess::where('Vigente',1)->first()
		//					->where('idEspecialidad',Session::get('faculty-code'))->first();
		$proceso = PspProcess::where('idEspecialidad',Session::get('faculty-code'))->get()->toArray();
		$this->courseService = new CourseService;
		$this->cicleService = new CicleService;
		$cont=0;
		if($proceso!=null){
			foreach ($proceso as $proc) {
				$proc['nomCurso'] = $this->courseService->findCourseById($proc['idCurso'])->Nombre;
				$proc['codCurso'] = $this->courseService->findCourseById($proc['idCurso'])->Codigo;
				$request['cicle_code'] = $proc['idCiclo'];
				$ciclo = $this->cicleService->findCicle($request)->IdCiclo; //idciclo de cicloxespecialidad
				$proc['ciclo']=AcademicCycle::where('IdCicloAcademico',$ciclo)->first()->Descripcion;
				array_push($proceso, $proc);
				$cont++;
			}
			array_splice($proceso, 0,$cont);
		}
		return $proceso;
	}

	public function findById($id){
		$proc = PspProcess::where('id',$id)->first();
		$this->courseService = new CourseService;
		$this->cicleService = new CicleService;

		if($proc!=null){
				$proc->nomCurso = $this->courseService->findCourseById($proc->idCurso)->Nombre;
				$proc['codCurso'] = $this->courseService->findCourseById($proc['idCurso'])->Codigo;
				$request['cicle_code'] = $proc['idCiclo'];
				$ciclo = $this->cicleService->findCicle($request)->IdCiclo; //idciclo de cicloxespecialidad
				$proc['ciclo']=AcademicCycle::where('IdCicloAcademico',$ciclo)->first()->Descripcion;
		}
		return $proc;
	}

	public function retrieveTeachers($request){
		$teachers = CoursexTeacher::where('IdCurso',$request)->get()->toArray();
		$this->teacherService = new TeacherService;
		$cont=0;
		foreach($teachers as $t){
			$t['nombre']=$this->teacherService->findTeacherById($t['IdDocente'])->Nombre;
			$t['apellidoPat']=$this->teacherService->findTeacherById($t['IdDocente'])->ApellidoPaterno;
			$t['apellidoMat']=$this->teacherService->findTeacherById($t['IdDocente'])->ApellidoMaterno;
			$t['codigo']=$this->teacherService->findTeacherById($t['IdDocente'])->Codigo;
			$t['IdUsuario']=$this->teacherService->findTeacherById($t['IdDocente'])->IdUsuario;
			array_push($teachers, $t);
			$cont++;
		}
		array_splice($teachers, 0,$cont);
		return $teachers;
	}


	public function haveStudents($request){
		$IdDocente =    $request['idProfesor'];
		$proceso = PspProcess::where('id',$request['idProceso'])->first();
		
		
		$cursoxciclo = CoursexCycle::where('IdCurso',$proceso->idCurso)->where('IdCicloAcademico',$proceso->idCiclo)->first();
		$horarios = Schedule::where('IdCursoxCiclo',$cursoxciclo->IdCursoxCiclo)->get();
		$horarioAct = null;

		foreach ($horarios as $horario) {
			$temp = SchedulexTeacher::where('IdDocente',$IdDocente)->where('IdHorario',$horario->IdHorario)->first();
			if($temp!=null)
				$horarioAct = $horario;
		}
		if($horarioAct!=null)
				$alumnos = Student::where('IdHorario',$horarioAct->IdHorario)->get();
		else 
			$alumnos = null;	
		return $alumnos;
	}

	public function desactivateStudents($id){
		$proceso = PspProcess::where('id',$id)->first();
		
		$cursoxciclo = CoursexCycle::where('IdCurso',$proceso->idCurso)->where('IdCicloAcademico',$proceso->idCiclo)->first();
		$horarios = Schedule::where('IdCursoxCiclo',$cursoxciclo->IdCursoxCiclo)->get();

		if($horarios!=null){
			foreach ($horarios as $horario) {
				$alumnos=Student::where('IdHorario',$horarioAct->IdHorario)->get();
			}	
		}else
			$alumnos = null;
		
		return $alumnos;
	}
}