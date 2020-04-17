
@extends('main')

@section('title','PRUEBAS')

@section('AddScritpHeader')

@endsection


@section('content')
    <div class="row">
        <div class="col-6">EXTENCION</div>
        <div class="col-6">CANTIDAD</div>
    </div>
    <div class="row">
        @foreach ($data['cantidadExtenciones'] as $info)
            <div class="col-6">
                {!! $info->descripcion !!}
            </div>
            <div class="col-6">
                {!! $info->total !!}
            </div>
        @endforeach
    </div>

    <table id="example">
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
