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
		<span class="glyphicon glyphicon-edit"></span>
		Select Tests performed by the <strong>{{$instrument->name}}</strong> Equipment
	</div>
	{{ Form::model($instrument, array(
			'route' => array('instrument.testtypes', $instrument->id), 'method' => 'POST',
			'id' => 'form-edit-instrument-testtypes'
		)) }}
		<div class="panel-body">
			@if($errors->all())
				<div class="alert alert-danger">
					{{ HTML::ul($errors->all()) }}
				</div>
			@endif
			<table class="table table-striped table-hover table-condensed">
				<thead>
					<tr><th></th></tr>
				</thead>
				<tbody>
				@foreach($testtypes as $key => $value)
					<tr @if(Session::has('activetesttype'))
	                            {{(Session::get('activetesttype') == $value->id)?"class='info'":""}}
	                        @endif
	                        >
						<td>
							<label  class="checkbox">
								<input type="checkbox" name="testtypes[]" value="{{ $value->id}}" 
								{{ in_array($value->id, $instrument->testTypes->lists('id'))?"checked":"" }} />
								{{$value->name }}
							</label>
						</td>
					</tr>
				@endforeach
				</tbody>
			</table>

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