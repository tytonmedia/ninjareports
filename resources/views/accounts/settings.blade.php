@extends('spark::layouts.app') @section('content')
    <div class="spark-screen container">
        <div class="row">
            <div class="col-md-12">
                @if($paused)
                    <div class="alert alert-danger">
                        <b>Upgrade to Resume</b><br/>
                        You have reached the limit of your plan. <a
                                onClick="ga('send', 'event', 'button', 'click', 'upgrade_alert_settings');"
                                href="{{ url('settings#/subscription') }}">Upgrade your plan</a> to resume your reports.
                    </div>
                @endif
                @include('common.flash')
                <div class="panel panel-default panel-accounts">
                    <div class="panel-body">
                        <div class="row">
                            <div class="col-md-6 col-sm-6 col-xs-6">
                                <h1 class="title">{{ $type == 'google-search' ? 'Google Search Console' : ucfirst($type) }}
                                    Settings</h1>
                            </div>
                            <div class="col-md-6 col-sm-6 col-xs-6 text-right greeting-button">
                                <a href="{{ route('reports.create') }}" class="btn btn-black">Create Report</a>
                                <button data-type="{{ $type }}" class="btn btn-black nr_sync_ad_accounts_button">Sync
                                    Account
                                    <span class="nr-loader fa fa-spin fa-spinner margin-left-5 hidden"></span>
                                </button>
                            </div>
                        </div>
                        <div class="connect_accounts_modal_section"></div>
                        <hr/>
                        <div class="synchronized_ad_accounts">{!! $ad_accounts_html !!}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
