<html><head>
		{{ HTML::style('css/bootstrap.min.css') }}
		{{ HTML::style('css/bootstrap-theme.min.css') }}
		<style type="text/css">
			#content table, #content th, #content td {
			   border: 1px solid black;
			   font-size:12px;
			}
			#content p{
				font-size:12px;
			 }
            thead:before, thead:after { display: none; }
            tbody:before, tbody:after { display: none; }
            .table-row{
            	border-bottom: 1px solid black;
            	width: 100%;
            	padding: 0;
            	page-break-inside: avoid;
            }
            .table-col-4{
            	display: inline-block;
            	width: 30%;
            	float: left;
            	padding: 5px;
            }
            .table-col-8{
            	border-left: 1px solid black;
            	display: inline-block;
            	width: 60%;
            	float: left;
            	padding: 5px;
            }
		</style>
	</head><body>
		<div id="wrap">
		    <div class="container-fluid">
		        <div class="row">
					@include("reportHeader")
					<div id="content">
						<strong>
							<p>
								{{trans('messages.patient-report').' - '.date('d-m-Y')}}
							</p>
						</strong>
						<table class="table" style="border: 1px solid #000;"  width="100%">
							<tbody>
								<tr align="left">
									<td><strong>{{ trans('messages.patient-name')}}</strong></td>
									<td>{{ $patient->name }}</td>
									<td><strong>{{ trans('messages.gender')}}</strong></td>
									<td>{{ $patient->getGender() }}</td>
								</tr>
								<tr align="left">
									<td><strong>{{ trans("messages.patient-number")}}</strong></td>
									<td>{{ $patient->patient_number}}</td>
									<td><strong>{{ trans('messages.age')}}</strong></td>
									<td>{{ $patient->getAge()}}</td>
								</tr>
								<tr align="left">
									<td><strong>{{ trans('messages.patient-lab-number')}}</strong></td>
									<td>{{ $patient->id }}</td>
									<td><strong>{{ trans('messages.requesting-facility-department')}}</strong></td>
									<td>{{ Config::get('kblis.organization') }}</td>
								</tr>
							</tbody>
						</table>
						<br>
						<table class="table" style="border: 1px solid #000;" width="100%">
							<tbody align="left">
								<tr>
									<td colspan="7"><strong>{{ trans('messages.specimen') }}</strong></td>
								</tr>
								<tr>
									<td><strong>{{ Lang::choice('messages.specimen-type', 1)}}</strong></td>
									<td><strong>{{ Lang::choice('messages.test', 2)}}</strong></td>
									<td><strong>{{ trans('messages.date-ordered') }}</strong></td>
									<td><strong>{{ Lang::choice('messages.test-category', 2)}}</strong></td>
									<td><strong>{{ trans('messages.specimen-status')}}</strong></td>
									<td><strong>{{ trans('messages.collected-by')."/".trans('messages.rejected-by')}}</strong></td>
									<td><strong>{{ trans('messages.date-checked')}}</strong></td>
								</tr>
								@forelse($tests as $test)
									<tr>
										<td>{{ $test->specimen->specimenType->name }}</td>
										<td>{{ $test->testType->name }}</td>
										<td>{{ $test->isExternal()?$test->external()->request_date:$test->time_created }}</td>
										<td>{{ $test->testType->testCategory->name }}</td>
										@if($test->specimen->specimen_status_id == Specimen::NOT_COLLECTED)
											<td>{{trans('messages.specimen-not-collected') }}</td>
											<td>&nbsp;</td>
											<td>&nbsp;</td>
										@elseif($test->specimen->specimen_status_id == Specimen::ACCEPTED)
											<td>{{trans('messages.specimen-accepted')}}</td>
											<td>{{$test->specimen->acceptedBy->name}}</td>
											<td>{{$test->specimen->time_accepted}}</td>
										@elseif($test->specimen->specimen_status_id == Specimen::REJECTED)
											<td>{{trans('messages.specimen-rejected')}}</td>
											<td>{{$test->specimen->rejectedBy->name}}</td>
											<td>{{$test->specimen->time_rejected}}</td>
										@endif
									</tr>
								@empty
									<tr>
										<td colspan="7">{{ trans("messages.no-records-found") }}</td>
									</tr>
								@endforelse

							</tbody>
						</table>
						<br>
						<p style="font-size:1.2em;"><strong>{{trans('messages.test-results')}}</strong></p>
						@forelse($tests as $test)
							<div style="border: 1px solid black;">
							<div class="table-row">
								<div class="table-col-4">
									<strong>{{Lang::choice('messages.test-type', 1)}}</strong></div>
								<div class="table-col-8">
									{{ $test->testType->name }}</div>
							</div>
							<div class="table-row">
								<div class="table-col-4">
									<strong>{{trans('messages.tested-by')}}</strong></div>
								<div class="table-col-8">
									{{ $test->tested_by > 0 ? $test->testedBy->name : trans('messages.pending')}}</div>
							</div>
							<div class="table-row">
								<div class="table-col-4">
									<strong>{{trans('messages.results-entry-date')}}</strong></div>
								<div class="table-col-8">
									{{ $test->testResults->last()->time_entered }}</div>
							</div>
							<div class="table-row">
								<div class="table-col-4">
									<strong>{{trans('messages.date-tested')}}</strong></div>
								<div class="table-col-8">
									{{ $test->time_completed }}</div>
							</div>
							<div class="table-row">
								<div class="table-col-4">
									<strong>{{trans('messages.verified-by')}}</strong></div>
								<div class="table-col-8">
									{{ $test->verified_by > 0 ? $test->verifiedBy->name : trans('messages.verification-pending') }}</div>
							</div>
							<div class="table-row">
								<div class="table-col-4">
									<strong>{{trans('messages.date-verified')}}</strong></div>
								<div class="table-col-8">
									{{ $test->time_verified }}</div>
							</div>
							<div class="table-row" style="border-top: 1px solid black;">
								<div class="table-col-4">
									<strong>{{trans('messages.test-results-values')}}</strong></div>
								<div class="table-col-8">
									@foreach($test->testResults as $result)
									<p>
										{{ Measure::find($result->measure_id)->name }}: {{ $result->result }}
										{{ str_replace("( - )", "", "(" . $result->range_lower . " - " . $result->range_upper . ")") }}
										{{ $result->unit }}
									</p>
									@endforeach
								</div>
							</div>
							<div class="table-row">
								<div class="table-col-4">
									<strong>{{trans('messages.test-remarks')}}</strong></div>
								<div class="table-col-8">
									{{ $test->interpretation }} &nbsp;</div>
							</div>
							</div>
						@empty
							<p>{{trans("messages.no-records-found")}}</p>
						@endforelse
					</div>
				</div>
				<div class="row">
					<br>
					<hr style="border: 1px solid black;">
					<div style="border: 1px solid black; page-break-inside: avoid;padding: 5px;">
						<div style="clear:both">&nbsp;</div>
						<div style="display: inline-block;width: 47%;float: left;">
							<p><strong>{{ trans('messages.authorized-by') }}</strong> {{ trans('messages.signature-holder') }}</p>
						</div>
						<div style="display: inline-block;width: 47%;float: left;">
							<p><strong>{{ Lang::choice('messages.name', 1).":" }}</strong> {{ trans('messages.signature-holder') }}</p>
						</div>

						<div style="clear:both">&nbsp;</div>
						<div style="display: inline-block;width: 30%;float: left;">
							<p>{{ Config::get('kblis.lab-quality-manager-name') }}</p>
							<p><u><strong>{{ trans('messages.quality-manager') }}</p>
							<p><strong>{{ Config::get('kblis.patient-report-no') }}</p>
						</div>
						<div style="display: inline-block;width: 30%;float: left;">
							<p>{{ Config::get('kblis.lab-manager-name') }}</p>
							<p><u><strong>{{ trans('messages.lab-manager') }}</p>
							<p>&nbsp;</p>
						</div>
						<div style="display: inline-block;width: 30%;float: left;">
							<p>{{ Config::get('kblis.lab-director-name') }}</p>
							<p><u><strong>{{ trans('messages.lab-director') }}</strong></u></p>
							<p><strong>{{ Config::get('kblis.patient-report-version') }}</p>
						</div>
					</div>
				</div>
			</div>
		</div>
		<script type="text/php">
		    if (isset($pdf)) {
		        $x = 250;
		        $y = $pdf->get_height()-35;
		        $text = "Page {PAGE_NUM} of {PAGE_COUNT}";
		        $font = null;
		        $size = 12;
		        $color = array(0.1,0.1,0.1);
		        $word_space = 0.0;  //  default
		        $char_space = 0.0;  //  default
		        $angle = 0.0;   //  default
		        $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
		    }
		</script>
	</body></html>