<?php 

namespace Intranet\Http\Controllers\Psp\Template;

use Illuminate\Http\Request;
use Auth;
use Intranet\Http\Requests;
use Intranet\Http\Controllers\Controller;
use Intranet\Models\Template;
use Intranet\Http\Requests\TemplateRequest;
use Intranet\Http\Requests\TemplateEditRequest;
use Intranet\Models\Teacher;
use Intranet\Models\User;
use Intranet\Models\PspDocument;
use Intranet\Models\Phase;
use Intranet\Models\Student;
use Intranet\Models\Supervisor;

class TemplateController extends Controller
{

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $templates = Template::get();

        $data = [
            'templates'    =>  $templates,
        ];
        return view('psp.template.index', $data);
        //return view('template.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['phases'] = Phase::get();
        return view('psp.template.create',$data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(TemplateRequest $request)
    {
        try {
            $template = new Template;
            $template->idPhase       = $request['fase']; 

            //$template->idTipoEstado  = 1;
            if(Auth::User()->IdPerfil==6){
                $supervisors = Supervisor::where('IdUser',Auth::User()->IdUsuario)->get();  
                $supervisor  =$supervisors->first();             
                $template->idSupervisor  = $supervisor->id;

            }
            if(Auth::User()->IdPerfil==2){
                $teacher = Teacher::where('IdUsuario',Auth::User()->IdUsuario)->first();  
                //teacher =$teacherss->first();
                if($teacher!=null){
                $template->idProfesor  = $teacher->IdDocente;
                }
            }
            if(Auth::User()->IdPerfil==3){
                //$admin = Admin::where('idUser',Auth::User()->IdUsuario)->first(); 
                $template->idAdmin   = Auth::User()->IdUsuario;
            }
            /*
            $template->idProfesor  = Auth::User()->IdUsuario;
            $template->idSupervisor  = null;
            $template->idAdmin  = null;
            */
            $template->titulo  = $request['titulo'];
            if($request['obligatorio']==true)
                $template->idTipoEstado  = 1;
            else
                $template->idTipoEstado  = 2;
            $template->save();
            if(isset($request['ruta']) && $request['ruta'] != ""){
                $destinationPath = 'uploads/templates/'; // upload path
                $extension = $request['ruta']->getClientOriginalExtension();
                $filename = $template->id.'.'.$extension; 
                $request['ruta']->move($destinationPath, $filename);

                $template->ruta = $destinationPath.$filename;
                $template->save();

                $pspstudents=Student::get();
                //$pspstudents=Student::where('lleva_psp','t')->get();
                foreach($pspstudents as $psp) {
                    if($psp!=null){
                    $PspDocument = new PspDocument;
                    $PspDocument->idStudent= $psp->IdAlumno;
                    $PspDocument->idTemplate=$template->id;
                    $PspDocument->idTipoEstado=3;
                    if($template->idTipoEstado  == 1)
                       $PspDocument->esObligatorio='s';
                   else
                       $PspDocument->esObligatorio='n';
                    $PspDocument->fecha_limite=Phase::find($request['fase'])->fecha_fin;
                    $PspDocument->save();
                    }
                }

            }
            return redirect()->route('template.index')->with('success', 'La plantilla se ha registrado exitosamente');
        } catch (Exception $e) {
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
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $template     = Template::find($id);

        $data = [
            'template'    =>  $template,
        ];
        $data['phases'] = Phase::get();
        return view('psp.template.edit', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(TemplateEditRequest $request, $id)
    {
        try {
            $template = Template::find($id);
            $template->idPhase       = $request['fase'];
            $template->titulo  = $request['titulo'];
            if($request['obligatorio']==true)
                $template->idTipoEstado  = 1;
            else
                $template->idTipoEstado  = 2;
            $template->save();
            if(isset($request['ruta']) && $request['ruta'] != ""){
                if(file_exists($template->ruta)){
                    unlink($template->ruta);
                }
                $destinationPath = 'uploads/templates/'; // upload path
                $extension = $request['ruta']->getClientOriginalExtension();
                $filename = $template->id.'.'.$extension; 
                $request['ruta']->move($destinationPath, $filename);
                $template->ruta = $destinationPath.$filename;
                $template->save();
            }
            return redirect()->route('template.index')->with('success', 'La plantilla se ha modificado exitosamente');
        } catch (Exception $e) {
            return redirect()->back()->with('warning', 'Ocurrió un error al hacer esta acción');
        }
    }

    public function get($filename){

        $template = Template::find($filename);
        $file=public_path()."/";
        $file .=$template->ruta;
        if(file_exists($file)) {
            return response()->download($file);
        }
        else{
            return redirect()->back()->with('warning', 'No existe el archivo a descargar');
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
            $template   = Template::find($id);
            
            //Restricciones
            if(!empty($template)){
                $template->delete();
                return redirect()->route('template.index')->with('success', 'La plantilla se ha eliminado exitosamente');
            }else{
                return redirect()->route('template.index')->with('success', 'La plantilla se ha eliminado exitosamente');
            }
        } catch (Exception $e){
            return redirect()->back()->with('warning', 'Ocurrió un error al hacer esta acción');
        } 
    }
}
