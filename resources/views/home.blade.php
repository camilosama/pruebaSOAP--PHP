
@extends('main')

@section('title','PRUEBAS')

@section('AddScritpHeader')

@endsection


@section('content')

    <div class="alert alert-success" role="alert">
        EXTENCIONES DEL WS
    </div>

    <table class="table table-striped table-bordered nowrap" width="100%" id="myTable">
        <thead>
        <tr>
            <th>EXTENCION</th>
            <th>CANTIDAD</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($data['cantidadExtenciones'] as $info)
            <tr>
                <td>{!! $info->descripcion !!}</td>
                <td>{!! $info->total !!}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <hr>

    <div class="alert alert-success" role="alert">
        ARCHIVOS DEL WS
    </div>

    <table class="table table-striped table-bordered nowrap" width="80%" id="myTable">
        <thead>
        <tr>
            <th>ID</th>
            <th>NOMBRE</th>
        </tr>
        </thead>
        <tbody>
        @foreach ($data['datosInsertados'] as $info)
            <tr>
                <td>{!! $info->id_tipo !!}</td>
                <td>{!! $info->descripcion !!}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

@endsection

@section('AddScriptFooter')

@endsection
