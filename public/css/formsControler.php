<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use DB;
use App\User;
use Mail;
use App\Notifications\RapivEmail;
use Session;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade as PDF;


class formsControler extends Controller
{
    public function login(){
        return view('login');
    }
    /*public function superDAto(){
        dd('ENTRO');
    }*/
    /*public function emailPru(){
        return view('emails.newsinfo');
    }*/

    //VALIDACION DE QUE EL USAURIO Y CLAVE EXISTAN Y ESTEN CORRECTOS EN EL SISTEMA
    public function validarLogin(Request $request){

        $correoFrm=$request->input("correo");
        $ccFuncionario=$request->input("ccFuncionario");
        $ccUsuario=$request->input("ccUsuario");

        $user = DB::table('SCCPB_USUARIO')->where('email',\DB::raw("'".$correoFrm."'"))->where('numero_identificacion', $ccFuncionario)->first();
        if ($user === null) {
            return '|0|Los datos ingresados no coinciden con algun usuario del sistema por favor contacte con el administrador.';
        }else{

            $semilla=mt_rand(1000,9999);
            \Session::put("emailUsuario",$correoFrm);
            \Session::put("ccFuncionario",$ccFuncionario);
            \Session::put("ccUsuario",$ccUsuario);
            \Session::put("Semilla",$semilla);
            \Session::save();

            $mailid = $correoFrm;
            $subject = 'Codigo para acceso a SCCPB';
            $data = array('email' => $mailid, 'subject' => $subject, 'Semilla'=>$semilla);
            Mail::send('emails.newsinfo', $data, function ($message) use ($data) {
                $message->from('master@personeriabogota.gov.co', 'Codigo para acceso a SCCPB');
                $message->to($data['email']);
                $message->subject($data['subject']);
            });

            try {
                DB::table('SCCPB_LOG_INGRESO')->insert([
                    ['EMAIL' => $correoFrm,'CODIGO' => $semilla,'IDENTIFICACION_CONSULTA' =>$ccUsuario ]
                ]);
            } catch (\Exception $e) {
                DB::rollback();
                return '|0|Problema al insetar los datos de seguridad en el sistema.';
            }



            return '|1|Los datos ingresados fueron correctos <br> Por favor verifique su correo electrónico y registre el código de seguridad enviado a este.';
        }

    }
    //VALIDACION DE LA SEMILLA ENVIADA AL CORREO DEL FUNCIONARIO SOLICITANTE DE INGREO
    public function registroLogin(Request $request){

        $semilla=$request->input("semilla");
        $correoFrm=Session::get('emailUsuario');
        //$ccFuncionario=Session::get('ccFuncionario');

        $date = date_create(Carbon::today());
        $carbon_today=date_format($date, 'd/m/Y h:m:s');

        $user = DB::table('SCCPB_LOG_INGRESO')->where('email',$correoFrm)->where('estado',0)->where('CODIGO', $semilla)->first();
        if ($user === null) {
            return '|0|El código ingresado no coinciden o fue usado por el usuario solicitante, por favor verifique el código enviado a su correo electrónico';
        }else{
            try {
                DB::table('SCCPB_LOG_INGRESO')
                    ->where('email',$correoFrm)
                    ->where('estado', 0)
                    ->where('CODIGO', $semilla)
                    ->update(['FECHA_INGRESO' => \DB::raw("TO_DATE('".$carbon_today."','DD/MM/YYYY HH24:MI:SS')"),'estado' => 1]);
            } catch (\Exception $e) {
                DB::rollback();
                return '|0|Problema al actualizar  los datos de seguridad en el sistema.';
            }

            return '|1|Ok ';
        }

    }
    //DIRECCIONAMIENTO AL FORMULARIO PARA REGISTRO DE NUEVO CIUDADANO
    public function moduloGestion(){

        $ccUsuario=Session::get('ccUsuario');

        $datosCiudadano = DB::table('USUARIO_ROL as UR')
            ->select('UR.NOMBRE','UR.APELLIDO','UR.CONSEC','UR.CEDULA')
            ->where("UR.CEDULA",$ccUsuario)
            ->get();

        $registrosCiudadano = DB::table('TRAMITEUSUARIO as TU')
            ->select('TU.ID_TRAMITE', 'TU.NUM_SOLICITUD', 'TU.VIGENCIA', 'TU.ESTADO_TRAMITE', 'T.NOM_TRAMITE', 'TU.FEC_SOLICITUD_TRAMITE', 'TU.TEXTO08')
            ->join("TRAMITE as T","TU.ID_TRAMITE","T.ID_TRAMITE")
            ->where("TU.ID_USUARIO_REG",$ccUsuario)
            ->where("TU.ID_TRAMITE","<>",52)
            ->orderBy('TU.FEC_SOLICITUD_TRAMITE')
            ->get();

        $data=array(
            "datosCiudadano"=>$datosCiudadano,
            "registrosCiudadano"=>$registrosCiudadano
        );
        return((String)\View::make("moduloGestion", array("data" => $data)));
    }
    //IMPRESION DEL PDF
    public function generatePDF(){

        $ccUsuario=Session::get('ccUsuario');

        $datosCiudadano = DB::table('USUARIO_ROL as UR')
            ->select('UR.NOMBRE','UR.APELLIDO','UR.CONSEC','UR.CEDULA')
            ->where("UR.CEDULA",$ccUsuario)
            ->get();

        $registrosCiudadano = DB::table('TRAMITEUSUARIO as TU')
            ->select('TU.ID_TRAMITE', 'TU.NUM_SOLICITUD', 'TU.VIGENCIA', 'TU.ESTADO_TRAMITE', 'T.NOM_TRAMITE', 'TU.FEC_SOLICITUD_TRAMITE', 'TU.TEXTO08')
            ->join("TRAMITE as T","TU.ID_TRAMITE","T.ID_TRAMITE")
            ->where("ID_USUARIO_REG",$ccUsuario)
            ->orderBy('TU.FEC_SOLICITUD_TRAMITE')
            ->get();

        $data=array("datosCiudadano"=>$datosCiudadano, "registrosCiudadano"=>$registrosCiudadano);

        $pdf = PDF::loadView('moduloGestion2', $data);
        return $pdf->download('reporteDatosCiudadano.pdf');

    }
    //MODULO PARA EL CIERRE DEL SISTEMA
    public function cerrar_session(){
        Session::flush();
        return redirect('/');
    }
    // MODAL CON LA INFRMACION GENERADA
    public function moduloDetalleInfo(Request $request){
        $numSolicitud=$request->input("numSolicitud");
        $idTramite=$request->input("idTramite");
        $vigencia=$request->input("vigencia");

        $trazaDeLaInformacion=DB::table('tramiterespuesta as tr')
            ->select("trps.nom_paso","dpas.descripcion as depasigna","dprc.descripcion as deprecibe","uras.nombre as nombrere","uras.apellido as apellidore","urac.nombre","urac.apellido","tr.tex_respuesta",
                "tr.estado_tramite","tr.consecutivo",\DB::raw("TO_CHAR(tr.fec_respuesta,'DD/MM/YYYY HH24:MI:SS') FEC_RESPUESTA")
            )
            ->join("tramitepaso as trps","tr.num_paso","trps.num_paso")
            ->leftJoin("dependencia as dpas","tr.id_dependencia_asig","dpas.consecutivo")
            ->join("usuario_rol as uras","tr.id_usu_adm_contesta","uras.consec")
            ->join("usuario_rol as urac","tr.id_usu_adm","urac.consec")
            ->leftJoin("dependencia as dprc","tr.id_dependencia_reg","dprc.consecutivo")
            ->where("tr.NUM_SOLICITUD",$numSolicitud)
            ->where("tr.ID_TRAMITE",$idTramite)
            ->where("tr.VIGENCIA",$vigencia)
            ->where("trps.ID_TRAMITE",$idTramite)
            ->where("VAL_DEPENDENCIA","<>",0)
            ->orderBy('TR.CONSECUTIVO')
            ->orderBy('TR.FEC_RESPUESTA')
            ->get();
        //dd($trazaDeLaInformacion);
        $data=array("trazaDeLaInformacion"=>$trazaDeLaInformacion);
        return((String)\View::make("unp.ModalDetalleInformacion", array("data" => $data)));
    }


}


