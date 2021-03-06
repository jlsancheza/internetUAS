@extends('app')
@section('content')

	<div class="page-title">
		<div class="title_left">
			<h3>Ciclo Académico</h3>
		</div>
	</div>
	<div class="clearfix"></div>
	<div class="separator"></div>

	<div class="clearfix"></div>
	<div class="row">
		<div class="col-md-12 col-sm-12 col-xs-12">
			<a href="{{ route('form.academicCycle') }}" class="btn btn-success pull-right"><i class="fa fa-plus"></i> Nuevo Ciclo Académico</a>
		</div>
	</div>

	<div class="clearfix"></div>
	<div class="row">
		<div class="col-md-12 col-sm-12 col-xs-12">
			<div class="x_panel">
				<div class="x_content">
					<table class="table table-striped responsive-utilities jambo_table bulk_action">
	                    <thead>
		                    <tr class="headings">
		                        <th>Ciclo Académico</th>
		                    </tr>
	                    </thead>
	                    <tbody>
	                         @foreach($academicCycle as $ac)
	                    	<tr>
	                          	<td class="cycleId" hidden="true">{{$ac->IdCicloAcademico}}</td>
	                    		<td>{{$ac->Descripcion}}</td>
	                    		
	                    	</tr>
	                         @endforeach
	                    </tbody>
	                </table>
				</div>
			</div>
		</div>
	</div>
	<script src="{{ URL::asset('js/myvalidations/academicCycle.js')}}"></script>

@endsection