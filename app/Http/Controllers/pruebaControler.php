<?php

namespace App\Http\Controllers;
use DB;
use Illuminate\Http\Request;
use SoapClient;
use Artisaninweb\SoapWrapper\SoapWrapper;
ini_set('max_execution_time', 1800);

class pruebaControler extends Controller
{
    public function home(){

        // 1) CONECTAR CON WS
        ini_set("soap.wsdl_cache_enabled", "0");
        $cliente = new \SoapClient('http://test.analitica.com.co/AZDigital_Pruebas/WebServices/ServiciosAZDigital.wsdl', [
            'trace' => true,
        ]);
        // 2) REGISTRAR END POINT
        $cliente->__setLocation('http://test.analitica.com.co/AZDigital_Pruebas/WebServices/SOAP/index.php');

        // 3) FILTROS DE CONSULTA
        $data = [
            'Condiciones' => [
                'Condicion'  => [
                'Tipo'=>"FechaInicial",
                'Expresion'=>"2019-07-01 00:00:00",
                ]
            ]
        ];
        // 4) METODO Y REGSITRO DE DATOS
        $resultado = $cliente->BuscarArchivo($data);

        // 5) RECORRER RESPUESA DE WS ARCHIVO
        foreach ($resultado as $dato) {

                // 5.0) RECORRE RESPUESTAS DE ARRAY ARCHIVO
                foreach ($dato as $dato2) {

                    //DEFINIR VARIABLES
                   $id=$dato2->Id;
                   $nombre=$dato2->Nombre;
                    // 5.1) EXTRAER POSIBLE EXTENCION
                    $porciones = explode(".",$nombre);
                    if (!isset($porciones[1])) {
                        $extencion=' ';
                    }else{
                        $extencion=$porciones[1];
                    }

                    // 5.2) INSERT DE TODAS LAS EXTECIONES ENCONTRADAS
                        DB::table('tipo_archivos')->insert(['descripcion' =>  $extencion ]);
                        // 5.3) INSERT DE TODOSLOS REGISTROS
                        try {
                            DB::table('trasnaccion')->insert(['id_tipo' => $id, 'descripcion' =>  $nombre ]);
                        } catch (\Exception $e) {
                            DB::rollback();
                            return 'Problema al insertar los datos en la tabla trasnaccion' . $e;
                        }

                        DB::commit();
                }
        }
        //dd($dato);

        $datosInsertados= DB::table('trasnaccion')
            ->select("id_tipo","descripcion")
            ->get();

        $cantidadExtenciones = DB::table('tipo_archivos')
            ->select('descripcion', DB::raw('count(descripcion) as total'))
            ->groupBy('descripcion')
            ->get();


        $data=array(
            "datosInsertados"=>$datosInsertados,
            "cantidadExtenciones"=>$cantidadExtenciones
        );

        return((String)\View::make("home", array("data" => $data)));


    }

    public function bd(){

        DB::table('tipo_archivos')->insert(['descripcion' =>  'pdf' ]);

        $cantidadExtenciones = DB::table('tipo_archivos')
            ->select('descripcion', DB::raw('count(descripcion) as total'))
            ->groupBy('descripcion')
            ->get();

        dd($cantidadExtenciones);
}

}
