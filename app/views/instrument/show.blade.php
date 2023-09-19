@extends("layout")
@section("content")
	<div>
		<ol class="breadcrumb">
		  <li><a href="{{{URL::route('user.home')}}}">{{trans('messages.home')}}</a></li>
		  <li><a href="{{ URL::route('instrument.index') }}">{{Lang::choice('messages.instrument',2)}}</a></li>
		  <li class="active">{{trans('messages.instrument-details')}}</li>
		</ol>
	</div>
	<div class="panel panel-primary">
		<div class="panel-heading ">
			<span class="glyphicon glyphicon-cog"></span>
			{{trans('messages.instrument-details')}}
			<div class="panel-btn">
				<a class="btn btn-sm btn-info" href="{{ URL::route('instrument.edit', array($instrument->id)) }}">
					<span class="glyphicon glyphicon-edit"></span>
					{{trans('messages.edit')}}
				</a>
			</div>
		</div>
		<div class="panel-body">
			<div class="display-details">
				<h3 class="view"><strong>{{Lang::choice('messages.name',1)}}</strong>{{ $instrument->name }} </h3>
				<p class="view-striped"><strong>{{trans('messages.description')}}</strong>
					{{ $instrument->description }}</p>
				<p class="view"><strong>{{trans('messages.ip')}}</strong>
					{{ $instrument->ip }}</p>
				<p class="view-striped"><strong>{{trans('messages.host-name')}}</strong>
					{{ $instrument->hostname }}</p>
				<p class="view-striped">
					<strong>{{trans('messages.compatible-test-types')}}</strong>
					<a class="btn btn-sm btn-info" href="{{ URL::route('instrument.addtests', array($instrument->id)) }}">
						<span class="glyphicon glyphicon-add"></span>
						{{trans('messages.add')}} {{Lang::choice('messages.test',2)}}
					</a>
					<br>
					<?php
						$testTypes = $instrument->testTypes->all();
					?>
					<div>
						<table class="table table-striped table-hover table-condensed search-table">
							<thead>
								<tr>
									<th>{{Lang::choice('messages.test',1)}}</th>
									<th>{{trans('messages.actions')}}</th>
								</tr>
							</thead>
							<tbody>
							@foreach($instrument->testTypes->all() as $testType)
								<tr>
									<td>{{ $testType->name }}</td>

									<td>
										<!-- edit this instrument  -->
										<a class="btn btn-sm btn-info" href="{{ URL::route('instrument.mapping', array($instrument->id, $testType->id)) }}" >
											<span class="glyphicon glyphicon-cog"></span>
											Configure Mapping
										</a>
										<!-- delete this instrument -->
										<button class="btn btn-sm btn-danger delete-item-link"
											data-toggle="modal" data-target=".confirm-delete-modal"	
											data-id="{{ URL::route('instrument.deletetesttype', array($instrument->id, $testType->id)) }}">
											<span class="glyphicon glyphicon-trash"></span>
											{{trans('messages.delete')}}
										</button>

									</td>
								</tr>
							@endforeach
							</tbody>
						</table>
					</div>
				</p>
				<p class="view-striped"><strong>{{trans('messages.date-created')}}</strong>
					{{ $instrument->created_at }}</p>
			</div>
		</div>
	</div>
@stop