@extends("layout")
@section("content")
<div>
	<ol class="breadcrumb">
	  <li><a href="{{{URL::route('user.home')}}}">{{trans('messages.home')}}</a></li>
	  <li><a href="{{ URL::route('instrument.index') }}">{{Lang::choice('messages.instrument',2)}}</a></li>
	  <li class="active">{{trans('messages.edit-instrument')}}</li>
	</ol>
</div>
<div class="panel panel-primary">
	<div class="panel-heading ">
		<span class="glyphicon glyphicon-cog"></span>
		Configure Measure Mappings for <strong>{{$testType->alias}}</strong> Test and <strong>{{$instrument->name}}</strong> Equipment
	</div>
	{{ Form::open(array('route' => 'instrument.savemapping', 'id' => 'form-instrument-mapping', 'role' => 'form', 'method' => 'post')) }}
		<div class="panel-body">
			@if($errors->all())
				<div class="alert alert-danger">
					{{ HTML::ul($errors->all()) }}
				</div>
			@endif
			{{ Form::hidden('instrument_id', $instrument->id) }}
			{{ Form::hidden('testtype_id', $testType->id) }}
			<div class="form-group">
				{{ Form::label('measurename', 'BLIS Name') }}
				{{ Form::label('instrumentmeasurename', 'Equipment Name') }}
			</div>
			@foreach($testType->measures as $measure)
				<?php
				$mapping = head(array_where($mappings, function($key, $value) use ($measure){
										return $value->measure_id == $measure->id;
									}));
				?>
				<div class="form-group">
					{{ Form::label('m_'.$measure->id, $measure->name) }}
					{{ Form::text('m_'.$measure->id, is_object($mapping)?$mapping->mapping:'', array('class' => 'form-control')) }}
				</div>
			@endforeach
		</div>
		<div class="panel-footer">
			<div class="form-group actions-row">
				{{ Form::button('<span class="glyphicon glyphicon-save"></span> '.trans('messages.save'), 
					['class' => 'btn btn-primary', 'onclick' => 'submit()']
				) }}
				{{ Form::button(trans('messages.cancel'), 
					['class' => 'btn btn-default', 'onclick' => 'javascript:history.go(-1)']
				) }}
			</div>
		</div>
	{{ Form::close() }}
</div>
@stop