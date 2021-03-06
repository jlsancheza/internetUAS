<?php

namespace Intranet\Http\Controllers\Tutorship\Tutstudent;

use Illuminate\Http\Request;
use Intranet\Http\Requests;
use Intranet\Http\Requests\TutstudentRequest;
use Illuminate\Support\Facades\DB;
use Intranet\Http\Controllers\Controller;
use Intranet\Models\Tutstudent;
use Intranet\Models\Tutorship;
use Intranet\Models\User;
use Intranet\Models\Teacher;
use Illuminate\Support\Facades\Session;//<---------------------------------necesario para usar session
use Intranet\Http\Services\User\PasswordService;


class TutstudentController extends Controller
{
    protected $passwordService;

    public function __construct() {        
        $this->passwordService = new PasswordService;
    }

    public function downLoadExample() {
        return response()->download(public_path() . "/uploads/example.csv");
    }
    public function index(Request $request)
    {        
        $mayorId = Session::get('faculty-code');

        $filters = [
            "code" => $request->input('code'),
            "name" => $request->input('name'),
            "lastName" => $request->input('lastName'),
            "secondLastName" => $request->input('secondLastName'),
        ];

        $tutorId = $request->input('tutorId', null);

        $tutors = Teacher::getTutorsFiltered( [], $mayorId);
        
        $students = Tutstudent::getFilteredStudents($filters, $tutorId, $mayorId);

        $data = [
            'students' =>  $students,
            'tutors' => $tutors 
        ];

        return view('tutorship.tutstudent.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('tutorship.tutstudent.create');
    }

    public function createAll()
    {
        return view('tutorship.tutstudent.createAll');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TutstudentRequest $request)
    {
       
        try {
            //se busca un alumno con el mismo codigo
            $u = User::where('Usuario',$request['codigo'])->first();
            if($u!=null){
                return redirect()->route('alumno.create')->with('warning', 'El código de alumno que se intenta registrar ya existe.');
            }            

            // se crea un usuario primero
            $usuario = new User;
            $usuario->Usuario       = $request['codigo'];            
            $usuario->Contrasena    = bcrypt(123);//se le pone 123 por defecto pero encriptado
            $usuario->IdPerfil      = 0; //perfil 0 para el alumno
            $usuario->save();

            //se envia el correo para resetear el password
            if ($usuario) {
                $this->passwordService->sendSetPasswordLink($usuario, $request['correo']);
            }
            
            //ahora se busca ese usuario
            $usuarioCreado = User::where('Usuario',$request['codigo'])->first();

            //ahora se crea el alumno
            $student = new Tutstudent;
            $student->codigo           = $request['codigo'];
            $student->nombre           = $request['nombre'];
            $student->ape_paterno      = $request['app'];
            $student->ape_materno      = $request['apm'];
            $student->correo           = $request['correo'];
            $student->id_especialidad  = Session::get('faculty-code');
            $student->id_usuario       = $usuarioCreado->IdUsuario;

            //se guarda en la tabla Alumnos
            $student->save();

            //se regresa al indice de alumnos
            return redirect()->route('alumno.index')->with('success', 'El alumno se ha registrado exitosamente');
        } catch (Exception $e) {
            return redirect()->back()->with('warning', 'Ocurrió un error al hacer esta acción');
        }
    }

    public function storeAll(Request $request)
    {
        $csv_file = $request->file('csv_file');
        
        $mayor = Session::get('faculty-code');

        try {

            Tutstudent::loadStudents($csv_file->path(), $mayor);
            return redirect()->route('alumno.index')->with('success', 'El alumno se ha registrado exitosamente');

        } catch (InvalidTutStudentException $e) {
            return redirect()->back()->with('warning', 'Ocurrió un error al hacer esta acción');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $student       = Tutstudent::find($id);
        $data = [
            'student'      =>  $student,
        ];
        return view('tutorship.tutstudent.show', $data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $student       = Tutstudent::find($id);
        $data = [
            'student'      =>  $student,
        ];
        return view('tutorship.tutstudent.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        try {            
            //se busca un alumno con el mismo codigo
            $u = User::where('Usuario',$request['codigo'])->first();
            if($u!=null){
                return redirect()->route('alumno.create')->with('warning', 'El código de alumno que se intenta registrar ya existe.');
            }

            $student = Tutstudent::find($id);
            $user = User::find($student->id_usuario);
            //cambio el usuario del alumno
            $user->Usuario = $request['codigo'];
            $user->save();

            //cambio el alumno            
            $student->codigo       = $request['codigo'];            
            $student->nombre       = $request['nombre'];            
            $student->ape_paterno  = $request['app'];            
            $student->ape_materno  = $request['apm'];            
            $student->correo       = $request['correo'];
            $student->save();
            return redirect()->route('alumno.index', $id)->with('success', 'El alumno se ha actualizado exitosamente');
        } catch (Exception $e) {
            return redirect()->back()->with('warning', 'Ocurrió un error al hacer esta acción');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $student   = Tutstudent::find($id);
            $message = "";
            // if(count($area->investigators)){
            //     return redirect()->back()->with('warning', 'Esta area esta asignada a investigadores');
            // }
            $student->id_tutoria = null;
            $student->save();
            
            $student->delete();
            $student->tutorship->delete();

            return redirect()->route('alumno.index')->with('success', 'El alumno se ha desactivado exitosamente');
        } catch (Exception $e) {
            return redirect()->back()->with('warning', 'Ocurrió un error al hacer esta acción');
        }
    }

    public function restore($id) {
        $student = Tutstudent::withTrashed()->find($id);

        if($student) {
            $student->restore();
            return redirect()->route('alumno.index')->with('success', 'El alumno se ha activado exitosamente');
        }

        return redirect()->back()->with('warning', 'Ocurrió un error al hacer esta acción');
    } 

    public function assignTutor(){
        $idEspecialidad = Session::get('faculty-code');
        $students = Tutstudent::where('id_especialidad', $idEspecialidad)->where('id_tutoria',null)->get(); 
        $tutors = Teacher::where('IdEspecialidad',$idEspecialidad)->where('rolTutoria',1)->get();

        //dd(count($tutors->tutorships));

        $data = [
            'students'    =>  $students,
            'tutors'      => $tutors,            
        ];
        return view('tutorship.tutstudent.assign',$data);
    }

    public function assignTutorDo(Request $request){
               // dd($request['cant']);
        $sum=0;
        if($request['cant']!=null && $request['total']!=0){
            foreach($request['cant'] as $idTeacher => $value){                
                $sum = $sum + $value;                
            }
            if($sum!=$request['total']){
                return redirect()->back()->with('warning', 'Los campos deben sumar el total de alumnos.');
            }
            else{//se procede a asignar a los alumnos
                $idEspecialidad = Session::get('faculty-code');
                $students = Tutstudent::where('id_especialidad', $idEspecialidad)->where('id_tutoria',null)->get()->take($request['total']); 

                //por cada tutor
                $n_al=0;
                foreach($request['cant'] as $idTeacher => $value){                
                    for($i=0;$i< $value;$i++){
                        $tutorship = new Tutorship;
                        $tutorship->id_tutor = $idTeacher;
                        $tutorship->id_profesor = $idTeacher;
                        $tutorship->id_alumno = $students[$n_al]->id;
                        $tutorship->save();//se guarda la tutoria entre ambos

                        //ahora busco esa tutoria
                        $tutoriaIngresada = DB::table('tutorships')->where([
                            ['id_tutor', '=', $idTeacher],
                            ['id_alumno', '=', $students[$n_al]->id],
                            ['deleted_at', '=', null],//para que no asigne a una tutorship eliminada
                            ])->get()[0];

                        

                        //ahora el insert
                        DB::table('tutstudents')
                        ->where('id', $students[$n_al]->id)
                        ->update(['id_tutoria' => $tutoriaIngresada->id]);


                        $n_al++;
                    }
                }

            }               
            return redirect()->route('alumno.index')->with('success', 'Se asignaron tutores a: ('.$request['total'].') alumnos.');;
            
        }
        else{
            return redirect()->route('alumno.index')->with('warning', 'No se puede hacer la asignación.');;
        }        
        




        
    }
}
