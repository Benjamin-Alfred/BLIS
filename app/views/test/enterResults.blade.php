@extends("layout")
@section("content")
    <div>
        <ol class="breadcrumb">
          <li><a href="{{{URL::route('user.home')}}}">{{ trans('messages.home') }}</a></li>
          <li><a href="{{ URL::route('test.index') }}">{{ Lang::choice('messages.test',2) }}</a></li>
          <li class="active">{{ trans('messages.enter-test-results') }}</li>
        </ol>
    </div>
    <div class="panel panel-primary">
        <div class="panel-heading ">
            <div class="container-fluid">
                <div class="row less-gutter">
                    <div class="col-md-11">
                        <span class="glyphicon glyphicon-user"></span> {{ trans('messages.test-results') }}
                        @if($test->testType->instruments->count() > 0 || $test->testType->automated == true)
                        <div class="panel-btn">
                            <form id="fetch-form" class="form-inline" enctype="multipart/form-data" action="{{URL::route('instrument.getResult')}}" method="POST">
                                @if($test->testType->isChemistryTest())
                                    <input class="form-control" type="text" name="sample_id" value="{{$test->visit->patient->patient_number}}">
                                @endif
                                <a class="btn btn-sm btn-info fetch-test-data" href="javascript:void(0)"
                                    title="{{trans('messages.fetch-test-data-title')}}">
                                    <span class="glyphicon glyphicon-plus-sign"></span>
                                    {{trans('messages.fetch-test-data')}}
                                </a>
                                <input type="file" id="file-to-fetch" name="file-to-fetch" style="display: none;">
                                <input type="hidden" name="specimen_id" value="{{$test->specimen_id}}">
                                <input type="hidden" name="test_type_id" value="{{$test->testType->id}}">
                                <input type="hidden" name="instrument_count" value="{{$test->testType->instruments->count()}}">
                            </form>
                            <script>
                            </script>
                        </div>
                        @endif
                    </div>
                    <div class="col-md-1">
                        <a class="btn btn-sm btn-primary pull-right"  href="#" onclick="window.history.back();return false;"
                            alt="{{trans('messages.back')}}" title="{{trans('messages.back')}}">
                            <span class="glyphicon glyphicon-backward"></span></a>
                    </div>
                </div>
            </div>
        </div>
        <div class="panel-body">
        <!-- if there are creation errors, they will show here -->
            
            @if($errors->all())
                <div class="alert alert-danger">
                    {{ HTML::ul($errors->all()) }}
                </div>
            @endif
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-7">
                    {{ Form::open(array('route' => array('test.saveResults',$test->id), 'method' => 'POST',
                        'id' => 'form-enter-results')) }}
                        @foreach($test->testType->measures as $measure)
                            <div class="form-group">
                                <?php
                                $ans = "";
                                foreach ($test->testResults as $res) {
                                    if($res->measure_id == $measure->id)$ans = $res->result;
                                }
                                $fieldName = "m_".$measure->id;
                                ?>
                                @if ( $measure->isNumeric() ) 
                                    {{ Form::label($fieldName , $measure->name) }}
                                    {{ Form::text($fieldName, $ans, array(
                                        'class' => 'form-control result-interpretation-trigger',
                                        'data-url' => URL::route('test.resultinterpretation'),
                                        'data-age' => $test->visit->patient->dob,
                                        'data-gender' => $test->visit->patient->gender,
                                        'data-measureid' => $measure->id,
                                        'data-test_id' => $test->id
                                        ))
                                    }}
                                    <span class='units'>

                                        {{Measure::getRange($test->visit->patient, $measure->id)}}
                                        {{$measure->unit}}
                                    </span>
                                @elseif ( $measure->isAlphanumeric() || $measure->isAutocomplete() ) 
                                    <?php
                                    $measure_values = array();
                                    $measure_values[] = '';
                                    foreach ($measure->measureRanges as $range) {
                                        $measure_values[$range->alphanumeric] = $range->alphanumeric;
                                    }
                                    ?>
                                    {{ Form::label($fieldName , $measure->name) }}
                                    {{ Form::select($fieldName, $measure_values, array_search($ans, $measure_values),
                                        array('class' => 'form-control result-interpretation-trigger',
                                        'data-url' => URL::route('test.resultinterpretation'),
                                        'data-measureid' => $measure->id
                                        )) 
                                    }}
                                @elseif ( $measure->isFreeText() ) 
                                    {{ Form::label($fieldName, $measure->name) }}
                                    <?php
                                        $sense = '';
                                        $readonly = '';
                                        if(strtolower($measure->name)=="culture")
                                            $sense .= ' text-culture';
                                        if(strtolower($measure->name)=="sensitivity"){
                                            $sense .= ' text-sensitivity sense'.$test->id;
                                            $readonly = 'readonly';
                                        }
                                    ?>
                                    {{Form::text($fieldName, $ans, array('class' => 'form-control'.$sense, $readonly))}}
                                @endif
                            </div>
                        @endforeach
                        <div class="form-group">
                            {{ Form::label('interpretation', trans('messages.interpretation')) }}
                            {{ Form::textarea('interpretation', $test->interpretation, 
                                array('class' => 'form-control result-interpretation', 'rows' => '2')) }}
                        </div>
                        <div class="form-group actions-row">
                            {{ Form::button('<span class="glyphicon glyphicon-save">
                                </span> '.trans('messages.save-test-results'),
                                array('class' => 'btn btn-default', 'onclick' => 'submit()')) }}
                        </div>
                    {{ Form::close() }}
                    @if($test->testType->isCultureTest())
                        @if($test->testType->automated == true)

                            {{ Form::open(array('','id' => 'drugSusceptibilityForm_0', 'name' => 'ast_form', 'style'=>'')) }}
                                {{ Form::hidden('action', 'V2-ast', array('id' => 'action', 'name' => 'action')) }}
                                {{ Form::hidden('test_id', $test->id, array('id' => 'test_id', 'name' => 'test_id')) }}
                                {{ Form::hidden('ast-organism', '', array('id' => 'ast-organism', 'name' => 'ast-organism')) }}
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>&nbsp;</th>
                                        <th>Isolate</th>
                                        <th>Antibiotic</th>
                                        <th>MIC</th>
                                        <th>{{ trans('messages.interp')}}</th>
                                    </tr>
                                </thead>
                                <tbody id="ast_table">
                                </tbody>
                                </tfooter>
                                    <tr>
                                        <td>&nbsp;</td>
                                        <td>
                                        {{ Form::select('organism', $organisms, '',
                                            array('class' => 'form-control', 'id' => 'organism')) }}
                                        </td>
                                        <td>
                                        {{ Form::select('antibiotic', $antibiotics, '',
                                            array('class' => 'form-control', 'id' => 'antibiotic')) }}
                                        </td>
                                        <td>{{Form::text('sensitivity_mics', '', array('class' => 'form-control', 'id' => 'sensitivity_mics'))}}</td>
                                        <td><div class="row">
                                            <div class="col-sm-6">{{ Form::select('sensitivity_interpetation', ['S','I','R'], '',
                                            array('class' => 'form-control', 'id' => 'sensitivity_interpetation')) }}</div>
                                            <div class="col-sm-6"><a class="btn btn-default" href="javascript:void(0)" onclick="appendToASTTable()">
                                                {{ trans('messages.add') }}</a></div></div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" align="right">
                                            <div class="col-sm-6" id="ast-save-msg">
                                            </div>
                                            <div class="col-sm-6">
                                                <a class="btn btn-default" href="javascript:void(0)" onclick="saveDrugSusceptibility({{$test->id}},0)">
                                                {{ trans('messages.save') }} Sensitivity Result</a>
                                            </div>
                                        </td>
                                    </tr>
                                </tfooter>
                            </table>
                            {{ Form::close() }}

                        @elseif(count($test->testType->organisms)>0)
                            <div class="panel panel-success">  <!-- Culture Worksheet -->
                                <div class="panel-heading">
                                    <h3 class="panel-title">{{trans("messages.culture-worksheet")}}</h3>
                                </div>
                                <div class="panel-body">
                                    <p><strong>{{trans("messages.culture-work-up")}}</strong></p>
                                    <table class="table table-bordered">
                                        <thead>
                                            <tr>
                                                <th width="15%">{{ trans('messages.date')}}</th>
                                                <th width="10%">{{ trans('messages.tech-initials')}}</th>
                                                <th>{{ trans('messages.observations-and-work-up')}}</th>
                                                <th width="10%"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbbody_<?php echo $test->id ?>">
                                            @if(($observations = $test->culture) != null)
                                                @foreach($observations as $observation)
                                                <tr>
                                                    <td>{{ Culture::showTimeAgo($observation->created_at) }}</td>
                                                    <td>{{ User::find($observation->user_id)->name }}</td>
                                                    <td>{{ $observation->observation }}</td>
                                                    <td></td>
                                                </tr>
                                                @endforeach
                                                <tr>
                                                    <td>{{ Culture::showTimeAgo(date('Y-m-d H:i:s')) }}</td>
                                                    <td>{{ Auth::user()->name }}</td>
                                                    <td>{{ Form::textarea('observation', $test->interpretation, 
                                                        array('class' => 'form-control result-interpretation', 'rows' => '2', 'id' => 'observation_'.$test->id)) }}
                                                    </td>
                                                    <td><a class="btn btn-xs btn-success" href="javascript:void(0)" onclick="saveObservation(<?php echo $test->id; ?>, <?php echo Auth::user()->id; ?>, <?php echo "'".Auth::user()->name."'"; ?>)">
                                                        {{ trans('messages.save') }}</a>
                                                    </td>
                                                </tr>
                                            @else
                                                <tr>
                                                    <td>{{ Culture::showTimeAgo(date('Y-m-d H:i:s')) }}</td>
                                                    <td>{{ Auth::user()->name }}</td>
                                                    <td>{{ Form::textarea('observation', '', 
                                                        array('class' => 'form-control result-interpretation', 'rows' => '2', 'id' => 'observation_'.$test->id)) }}
                                                    </td>
                                                    <td><a class="btn btn-xs btn-success" href="javascript:void(0)" onclick="saveObservation(<?php echo $test->id; ?>, <?php echo Auth::user()->id; ?>, <?php echo "'".Auth::user()->name."'"; ?>)">
                                                        {{ trans('messages.save') }}</a>
                                                    </td>
                                                </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                    <p><strong>{{trans("messages.susceptibility-test-results")}}</strong></p>
                                    <div class="form-group">
                                        <div class="form-pane panel panel-default">
                                            <div class="container-fluid">
                                                <?php 
                                                    $cnt = 0;
                                                    $zebra = "";
                                                    $checked="";
                                                    $susOrgIds = array();
                                                ?>
                                                @foreach($test->testType->organisms as $key=>$value)
                                                    {{ ($cnt%4==0)?"<div class='row $zebra'>":"" }}
                                                    <?php
                                                        $cnt++;
                                                        $zebra = (((int)$cnt/4)%2==1?"row-striped":"");
                                                    ?>
                                                    <div class="col-md-4">
                                                        <label  class="checkbox">
                                                            <input type="checkbox" name="organism[]" value="{{ $value->id}}" {{ count($test->susceptibility)>0?(in_array($value->id, $test->susceptibility->lists('organism_id'))?'checked':''):'' }} onchange="javascript:showSusceptibility(<?php echo $value->id; ?>)" />{{$value->name}}
                                                        </label>
                                                    </div>
                                                    {{ ($cnt%4==0)?"</div>":"" }}
                                                @endforeach
                                            </div>
                                        </div>
                                    </div>
                                    @foreach($test->testType->organisms as $key=>$value)
                                        {{--*/$checker = 0/*--}}
                                        @if(count($test->susceptibility)>0)
                                            <?php 
                                                if(in_array($value->id, $test->susceptibility->lists('organism_id')))
                                                    $checker=1;
                                            ?>
                                        @endif
                                        <?php if($checker==1){$display='display:block';}else if($checker==0){$display='display:none';} ?>
                                        {{ Form::open(array('','id' => 'drugSusceptibilityForm_'.$value->id, 'name' => 'drugSusceptibilityForm_'.$value->id, 'style'=>$display)) }}
                                        <table class="table table-bordered">
                                            <thead>
                                                <tr>
                                                    <th colspan="3">{{ $value->name }}</th>
                                                </tr>
                                                <tr>
                                                    <th width="50%">{{ Lang::choice('messages.drug',1) }}</th>
                                                    <th>{{ trans('messages.zone-size')}}</th>
                                                    <th>{{ trans('messages.interp')}}</th>
                                                </tr>
                                            </thead>
                                            <tbody id="enteredResults_<?php echo $value->id; ?>">
                                                @foreach($value->drugs as $drug)
                                                {{ Form::hidden('test[]', $test->id, array('id' => 'test[]', 'name' => 'test[]')) }}
                                                {{ Form::hidden('drug[]', $drug->id, array('id' => 'drug[]', 'name' => 'drug[]')) }}
                                                {{ Form::hidden('organism[]', $value->id, array('id' => 'organism[]', 'name' => 'organism[]')) }}
                                                <tr>
                                                    <td>{{ $drug->name }}</td>
                                                    <td>
                                                        {{ Form::select('zone[]', ['' => '']+range(0, 50), '', ['class' => 'form-control', 'id' => 'zone[]', 'style'=>'width:auto']) }}
                                                    </td>
                                                    <td>{{ Form::select('interpretation[]', array('' => '', 'S' => 'S', 'I' => 'I', 'R' => 'R'),'', ['class' => 'form-control', 'id' => 'interpretation[]', 'style'=>'width:auto']) }}</td>
                                                </tr>
                                                @endforeach
                                                <tr id="submit_drug_susceptibility_<?php echo $value->id; ?>">
                                                    <td colspan="3" align="right">
                                                        <div class="col-sm-offset-2 col-sm-10">
                                                            <a class="btn btn-default" href="javascript:void(0)" onclick="saveDrugSusceptibility(<?php echo $test->id; ?>, <?php echo $value->id; ?>)">
                                                            {{ trans('messages.save') }}</a>
                                                        </div>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                        {{ Form::close() }}
                                    @endforeach
                                </div><!-- ./ panel-body -->
                            </div>
                        @endif
                    @endif
                    </div>
                    <div class="col-md-5">
                        <div class="panel panel-info">  <!-- Patient Details -->
                            <div class="panel-heading">
                                <h3 class="panel-title">{{trans("messages.patient-details")}}</h3>
                            </div>
                            <div class="panel-body">
                                <div class="container-fluid">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <p><strong>{{trans("messages.patient-number")}}</strong></p></div>
                                        <div class="col-md-9">
                                            {{$test->visit->patient->patient_number}}
                                            <span id="patient-id-analyzer"></span>
                                        </div></div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <p><strong>{{ Lang::choice('messages.name',1) }}</strong></p></div>
                                        <div class="col-md-9">
                                            {{$test->visit->patient->name}}
                                            <span id="patient-name-analyzer"></span>
                                        </div></div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <p><strong>{{trans("messages.age")}}</strong></p></div>
                                        <div class="col-md-9">
                                            {{$test->visit->patient->getAge()}}</div></div>
                                    <div class="row">
                                        <div class="col-md-3">
                                            <p><strong>{{trans("messages.gender")}}</strong></p></div>
                                        <div class="col-md-9">
                                            {{$test->visit->patient->gender==0?trans("messages.male"):trans("messages.female")}}
                                        </div></div>
                                </div>
                            </div> <!-- ./ panel-body -->
                        </div> <!-- ./ panel -->
                        <div class="panel panel-info"> <!-- Specimen Details -->
                            <div class="panel-heading">
                                <h3 class="panel-title">{{trans("messages.specimen-details")}}</h3>
                            </div>
                            <div class="panel-body">
                                <div class="container-fluid">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><strong>{{ Lang::choice('messages.specimen-type',1) }}</strong></p>
                                        </div>
                                        <div class="col-md-8">
                                            {{strlen($test->specimen->specimenType->name) > 0 ? $test->specimen->specimenType->name : trans('messages.pending') }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><strong>{{trans('messages.specimen-number')}}</strong></p>
                                        </div>
                                        <div class="col-md-8">
                                            {{$test->specimen->id or trans('messages.pending') }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><strong>{{trans('messages.specimen-status')}}</strong></p>
                                        </div>
                                        <div class="col-md-8">
                                            {{trans('messages.'.$test->specimen->specimenStatus->name) }}
                                        </div>
                                    </div>
                                @if($test->specimen->isRejected())
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><strong>{{trans('messages.rejection-reason-title')}}</strong></p>
                                        </div>
                                        <div class="col-md-8">
                                            {{$test->specimen->rejectionReason->reason or trans('messages.pending') }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><strong>{{trans('messages.reject-explained-to')}}</strong></p>
                                        </div>
                                        <div class="col-md-8">
                                            {{$test->specimen->reject_explained_to or trans('messages.pending') }}
                                        </div>
                                    </div>
                                @endif
                                @if($test->specimen->isReferred())
                                <br>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><strong>{{trans("messages.specimen-referred-label")}}</strong></p>
                                        </div>
                                        <div class="col-md-8">
                                            @if($test->specimen->referral->status == Referral::REFERRED_IN)
                                                {{ trans("messages.in") }}
                                            @elseif($test->specimen->referral->status == Referral::REFERRED_OUT)
                                                {{ trans("messages.out") }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><strong>{{Lang::choice("messages.facility", 1)}}</strong></p>
                                        </div>
                                        <div class="col-md-8">
                                            {{$test->specimen->referral->facility->name }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><strong>{{trans("messages.person-involved")}}</strong></p>
                                        </div>
                                        <div class="col-md-8">
                                            {{$test->specimen->referral->person }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><strong>{{trans("messages.contacts")}}</strong></p>
                                        </div>
                                        <div class="col-md-8">
                                            {{$test->specimen->referral->contacts }}
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <p><strong>{{trans("messages.referred-by")}}</strong></p>
                                        </div>
                                        <div class="col-md-8">
                                            {{ $test->specimen->referral->user->name }}
                                        </div>
                                    </div>
                                @endif
                                </div>
                            </div>
                        </div> <!-- ./ panel -->
                        <div class="panel panel-info">  <!-- Test Results -->
                            <div class="panel-heading">
                                <h3 class="panel-title">{{trans("messages.test-details")}}</h3>
                            </div>
                            <div class="panel-body">
                                <div class="container-fluid">
                                    <div class="display-details">
                                        <?php
                                            $creator = trans('messages.unknown');
                                            try{
                                                $creator = $test->createdby->name;
                                            }catch(\Exception $e){
                                                Log::error($e);
                                            }
                                        ?>
                                        <p class="view"><strong>{{ Lang::choice('messages.test-type',1) }}</strong>
                                            {{ $test->testType->name or trans('messages.unknown') }}</p>
                                        <p class="view"><strong>{{trans('messages.visit-number')}}</strong>
                                            {{$test->visit->visit_number or trans('messages.unknown') }}</p>
                                        <p class="view"><strong>{{trans('messages.date-ordered')}}</strong>
                                            {{ $test->isExternal()?$test->external()->request_date:$test->time_created }}</p>
                                        <p class="view"><strong>{{trans('messages.lab-receipt-date')}}</strong>
                                            {{$test->time_created}}</p>
                                        <p class="view"><strong>{{trans('messages.test-status')}}</strong>
                                            {{trans('messages.'.$test->testStatus->name)}}</p>
                                        <p class="view-striped"><strong>{{trans('messages.physician')}}</strong>
                                            {{$test->requested_by or trans('messages.unknown') }}</p>
                                        <p class="view-striped"><strong>{{trans('messages.request-origin')}}</strong>
                                            @if($test->specimen->isReferred() && $test->specimen->referral->status == Referral::REFERRED_IN)
                                                {{ trans("messages.in") }}
                                            @else
                                                {{ $test->visit->visit_type }}
                                            @endif</p>
                                        <p class="view-striped"><strong>{{trans('messages.registered-by')}}</strong>
                                            {{$creator}}</p>
                                        @if($test->isCompleted())
                                        <p class="view"><strong>{{trans('messages.tested-by')}}</strong>
                                            {{$test->tested_by > 0 ? $test->testedBy->name : trans('messages.unknown')}}</p>
                                        @endif
                                        @if($test->isVerified())
                                        <p class="view"><strong>{{trans('messages.verified-by')}}</strong>
                                            {{$test->verified_by > 0 ? $test->verifiedBy->name : trans('messages.verification-pending')}}</p>
                                        @endif
                                        @if((!$test->specimen->isRejected()) && ($test->isCompleted() || $test->isVerified()))
                                        <!-- Not Rejected and (Verified or Completed)-->
                                        <p class="view-striped"><strong>{{trans('messages.turnaround-time')}}</strong>
                                            {{$test->getFormattedTurnaroundTime()}}</p>
                                        @endif
                                    </div>
                                </div>
                            </div> <!-- ./ panel-body -->
                        </div>  <!-- ./ panel -->
                    </div>                    
                </div>
            </div>
        </div>
    </div>
@stop