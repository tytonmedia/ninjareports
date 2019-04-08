@extends('spark::layouts.app')

 @section('content') @section('page_styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/css/bootstrap-timepicker.min.css"
/> 
@endsection

<div class="spark-screen container">
    <div class="panel panel-default p-5 color-black">
        <div class="panel-body">
        <h3>Reports Settings</h3>
            <form method="post" id="post_report" action="{{ $postUrl }}">
            {{ csrf_field() }}
                <div class="row">
                    <div class="col-md-6">
                        <p>Report Settings</p>
                        <hr class="mt-0">

                        <div class="form-horizontal">
                            <div class="form-group">
                                <div class="col-md-4">
                                    <label class="control-label color-black-bold">Name</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="name" name="title"  class="form-control" id="" placeholder="" value="{{ old('title', $reportData->title) }}" >
                                    <div class="error error_title">
										@if ($errors->has('title')) {{ $errors->first('title') }} @endif
									</div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-4">
                                    <label class="control-label color-black-bold">Template</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="text" name="template" value="{{$report->template->name}}" class="form-control" disabled>
                                </div>
                            </div>
                            @if(count($report->accounts) > 0)
                            @foreach($report->accounts as $account)
                            <div class="form-group">
                                <div class="col-md-4">
                                    <label class="control-label color-black-bold">{{$account->title}}
                                        @if($account->type == 'analytics')
                                        <span class="analytics-properties-loader fa fa-spin fa-spinner margin-left-5 hidden"></span>
                                        @endif
                                    </label>
                                </div>
                                <div class="col-md-8">
                                    <select data-id="{{$account->id}}" data-type="{{$account->type}}" name="sources[{{$account->id}}][ad_account_id]" id="" class="form-control @if($account->type == 'analytics') ninja-ad-account analytics_{{$account->id}} @endif">
                                    @foreach($account->ad_accounts as $adAccount)
                                        <option 
                                        @if($reportData->accounts) 
                                            @foreach($reportData->accounts as $rAc) 
                                                @if(
                                                    $rAc->ad_account->ad_account_id && $rAc->ad_account->ad_account_id == $adAccount->ad_account_id
                                                ) 
                                                    selected="selected"
                                                @endif 
                                            @endforeach 
                                        @endif value="{{$adAccount->ad_account_id}}">{{$adAccount->title}}</option>
                                    @endforeach
                                    </select>
                                </div>
                            </div>
                            @if($account->type == 'analytics')
                            @if($edit == true)
                            @php
                            $propertyHtml = '';
                            $profileHtml = '';
                            if($account->type == 'analytics' && $reportData->accounts){
                                foreach($reportData->accounts as $rAc){
                                    if(
                                        $rAc->ad_account->ad_account_id
                                    ){
                                        $ninja_ad_account_id = $rAc->ad_account->ad_account_id;
                                        if($rAc->property){
                                            $ninja_property_id = $rAc->property->id;
                                            $ninja_property_property = $rAc->property->property;
                                            $propertyHtml = getProperties('analytics', $ninja_ad_account_id, $ninja_property_id);
                                            
                                        }
                                        if($rAc->profile){
                                            $ninja_profile_id = $rAc->profile->id;
                                            $profileHtml = getProfiles('analytics', $ninja_ad_account_id, $ninja_property_property, $ninja_profile_id);
                                        }
                                    }
                                }
                            }
                            @endphp
                            <div class="properties_html">{!! $propertyHtml !!}</div>
                            <div class="views_html">{!! $profileHtml !!}</div>
                            @else
                            <div class="properties_html"></div>
                            <div class="views_html"></div>
                            @endif
                            @endif
                            @endforeach
                            @endif
                        </div>
						
                    </div>           
                    <div class="col-md-6">
                        <p>Email Settings</p>
                        <hr class="mt-0">

                        <div class="form-horizontal">
                            <div class="form-group">
                                <div class="col-md-4">
                                    <label class="control-label color-black-bold">Recipients</label>
                                </div>
                                <div class="col-md-8">
                                    <textarea name="recipients" id="" class="form-control">{{ old('recipients', $reportData->recipients) }}</textarea>
                                    <small>comma separated emails</small>
                                    <div class="error error_recipients">
										@if ($errors->has('recipients')) {{ $errors->first('recipients') }} @endif
									</div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-4">
                                    <label class="control-label color-black-bold">Attachment</label>
                                </div>
                                <div class="col-md-8">
                                    <div id="tab" class="btn-group btn-group-justified attachment" data-toggle="buttons">
                                        <a href="#none" class="btn btn-default{{ old('attachment_type', $reportData->attachment_type) == 'none' ? ' active': (!old('attachment_type', $reportData->attachment_type) ? ' active':'') }}"
                                            data-toggle="tab">
                                            <input type="radio" name="attachment_type" value="none" {{ old( 'attachment_type', $reportData->attachment_type)=='none' ? 'checked': (!old(
                                                'attachment_type', $reportData->attachment_type) ? 'checked': '') }} />None
                                        </a>
                                        <a href="#pdf" class="btn btn-default{{ old('attachment_type', $reportData->attachment_type) == 'pdf' ? ' active':'' }}" data-toggle="tab">
                                            <input type="radio" name="attachment_type" value="pdf" {{ old( 'attachment_type', $reportData->attachment_type)=='pdf' ? 'checked': '' }} />PDF
                                        </a>
                                        <a href="#scv" class="hidden btn btn-default{{ old('attachment_type', $reportData->attachment_type) == 'csv' ? ' active':'' }}" data-toggle="tab">
                                            <input type="radio" name="attachment_type" value="csv" {{ old( 'attachment_type', $reportData->attachment_type)=='csv' ? 'checked': '' }} />CSV
                                        </a>
                                    </div>
                                    <div class="error">
                                        @if ($errors->has('attachment_type')) {{ $errors->first('attachment_type') }} @endif
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-4">
                                    <label class="control-label color-black-bold">Subject</label>
                                </div>
                                <div class="col-md-8">
                                    <input type="subject" name="email_subject" value="{{ old('email_subject', $reportData->email_subject) }}" class="form-control" id="" placeholder="">
                                    <div class="error error_email_subject">
										@if ($errors->has('email_subject')) {{ $errors->first('email_subject') }} @endif
									</div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-4">
                                    <label class="control-label color-black-bold">Frequency</label>
                                </div>
                                <div class="col-md-8">

                                    <div class="row">
                                        <div class="col-xs-4">
                                            <select name="frequency" id="" class="form-control frequency">
                                                @if(!$reportTemplate->integrations->contains('slug','google-search'))
                                                <option {{ old('frequency', $reportData->frequency) == 'daily' ? "selected":"" }} value="daily">Daily</option>
                                                @endif
                                                <option {{ old('frequency', $reportData->frequency) == 'weekly' ? "selected":"" }} value="weekly">Weekly</option>
                                                <option {{ old('frequency', $reportData->frequency) == 'monthly' ? "selected":"" }} value="monthly">Monthly</option>
                                            </select>
                                        </div>
                                        <div class="col-xs-3 pr-0 pl-0 month_date hide">
                                            <div class="input-group set_month_date">
                                            </div>
                                        </div>
                                        <div class="col-xs-5">
                                            <div class="input-group">
                                                <div class="input-group-addon">At</div>
                                                <input id="ends_time" type="text" name="ends_time" readonly class="custom-readonly form-control timepicker" />
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-md-4">
                                    <label class="control-label color-black-bold">Data from</label>
                                </div>
                                <div class="col-md-8 date_from">
                                    <select name="data_from" id="data_from" class="form-control">
                                        <option value="same_day"> Data from Same day </option>
                                        <option value="prev_day"> Data from previous day </option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <br>
                <div class="error error_general">
                </div>
                @if($edit)
                <button class="btn btn-primary border-radius-0 pull-right">
                    Update Report <span class="saving-loader fa fa-spin fa-spinner margin-left-5 hidden"></span>
                </button>
                @else
                <button class="btn btn-primary border-radius-0 pull-right">
                    Create Report <span class="saving-loader fa fa-spin fa-spinner margin-left-5 hidden"></span>
                </button>
                @endif
            </form>  
        </div>
    </div>
</div>
@endsection
@section('page_scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-timepicker/0.5.2/js/bootstrap-timepicker.min.js"></script>
<script>
var data_from = '';
var ends_at = '';
var ends_time = '';
var property_id = '';
var profile_id = '';
var edit = false;
var site_url = $('meta[name="site-url"]').attr('content') + '/';
//// analytics accounts
analyticAccounts = function(that){
    toastr.remove();
    var account = that.data('type');
    
    if (account === 'analytics') {
        var ad_account_id = that.val();
        var loader = $('.analytics-properties-loader');
        loader.removeClass('hidden');
        $.ajax({
            url: site_url + 'reports/' + account + '/account_properties/' + ad_account_id,
            success: function (response) {
                loader.addClass('hidden');
                if (response.status === 'success') {
                    $('.properties_html').html(response.html);
                    if(edit == false){
                        $('.ninja-ad-property').change();
                    } 
                    edit = true;
                    $('#test_report').show();
                } else {
                    toastr.error('Something went wrong. Please try again.');
                    $('#test_report').hide();
                }
            },
            error: function () {
                loader.addClass('hidden');
                toastr.error('Something went wrong. Please try again.');
            }
        });
    }
}
var ajaxRedir = function(url){
    window.location.href = url;
}
var showError = function(subject, error){
    jQuery(subject).html(error);
}
var cleanError = function(){
    $( ".error" ).each(function( index ) {
        $(this).html('');
    }); 
}
$(document).ready(function() {
    getFrequencyHtml()
    
    @if($edit)
    edit = true;
    data_from = '{{$reportData->data_from}}';
    ends_at = '{{$reportData->ends_on}}';
    ends_time = '{{$reportData->ends_at}}';
    @endif

    $(".frequency").change(function(){
        getFrequencyHtml()
    })
    function getFrequencyHtml(){  
        var frequency = $('.frequency').val();
        var html='';
        var date_from='';
        if(frequency=='daily'){
            $(".month_date").addClass("hide");
            date_from = '<select name="data_from" id="data_from" class="form-control">'
                            +'<option value="same_day"> Data from Same day </option>'
                            +'<option value="prev_day"> Data from previous day </option>'
                        +'</select>';
        }
        if(frequency=='weekly'){
            $(".month_date").removeClass("hide");
            html = '<div class="input-group-addon">On</div><select id="ends_at_id" name="ends_at" class="form-control">'
                    +'<option value="Mon">Mon</option>'
                    +'<option value="Tue">Tue</option>'
                    +'<option value="Wed">Wed</option>'
                    +'<option value="Thu">Thu</option>'
                    +'<option value="Fri">Fri</option>'
                    +'<option value="Sat">Sat</option>'
                    +'<option value="Sun">Sun</option>';
                    
            date_from = '<select name="data_from" id="data_from" class="form-control">'
                        +'<option value="7_days"> Last 7 days </option>'
                        +'</select>';
        }
        if(frequency=='monthly'){
            $(".month_date").removeClass("hide");
            html = '<div class="input-group-addon">On</div><select id="ends_at_id" name="ends_at" class="form-control">';
                for (i = 1; i <= 31; i++) {
                    html += '<option value="' + i + '">' + i + '</option>';
                }
            html += '</select>';
            date_from = '<select name="data_from" id="data_from" class="form-control">'
                        +'<option value="30_days">  Last 30 days </option>'
                        +'</select>';
        }
        // var frequency_html = html;
        $('.set_month_date').html(html);
        $('.date_from').html(date_from);
        if(data_from){
            $('#data_from').val(data_from);
            data_from = '';
        }
        if(ends_at && (frequency=='monthly' || frequency=='weekly')){
            $('#ends_at_id').val(ends_at);
            
        }
        ends_at = '';
        if(ends_time){
            $('#ends_time').val(ends_time);
            ends_time = '';
        }
        
    }
    // Get Google Analytics Properties
    $(document).on('change', '.ninja-ad-account', function () {
        var id = $(this).attr('data-id');
        analyticAccounts($('.analytics_'+id));
    });

    // Get Google Analytics Views
    $(document).on('change', '.ninja-ad-property', function () {
        console.log('here')
        toastr.remove();
        var account = $(this).data('type');
        if (account == 'analytics') {
            var ad_account_id = $(this).data('ad_account');
            var property_id = $(this).val();
            var loader = $('.analytics-views-loader');
            loader.removeClass('hidden');
            $.ajax({
                url: site_url + 'reports/' + account + '/account_properties/' + ad_account_id + '/profiles/' + property_id,
                success: function (response) {
                    loader.addClass('hidden');
                    if (response.status == 'success') {
                        $('.views_html').html(response.html);
                    } else {
                        toastr.error('Something went wrong. Please try again.');
                    }
                },
                error: function () {
                    loader.addClass('hidden');
                    toastr.error('Something went wrong. Please try again.');
                }
            });
        }
    });
    
    if(edit != true){
        $( ".ninja-ad-account" ).each(function( index ) {
            analyticAccounts($(this));
        }); 
    }

    /////// ajax posting form
    $( "#post_report" ).submit(function( event ) {
        event.preventDefault();
        var loader = $('.saving-loader');
        loader.removeClass('hidden');
        var postUrl = $( "#post_report" ).attr('action');
        var myform = document.getElementById("post_report");
        var postData = new FormData( myform );
        cleanError();
        $.ajax({
            url: postUrl,
            data: postData,
            cache: false,
            processData: false,
            contentType: false,
            type: 'POST',
            success: function (data) {
                if(data.data.redirect && data.data.redirect == true){
                    ajaxRedir(data.data.url);
                }
                loader.addClass('hidden');
            },
            error : function(error) {
                error = error.responseJSON;
                if(error.message.redirect && error.message.redirect == true){
                    ajaxRedir(error.message.url);
                } else {

                    jQuery.each(error.message, function(key, value){
                        if(value.constructor === Array || value.constructor === Object){
                            jQuery.each(value, function(key1, value1){
                                showError('.error_'+key, value1);
                            });
                        } else {
                            showError('.error_general', value);
                        }
                    });
                }
                loader.addClass('hidden');
            }
        });
    });
});


</script>

@endsection