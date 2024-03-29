@extends('spark::layouts.app') @section('content')
    <?php
    $value=0;

    if (session()->has('googlePrice')) {
    $value = session('googlePrice');
    session()->forget('googlePrice'); ?>

    <!-- Google Code for Ninja Report Sign Up Conversion Page -->
    <script type="text/javascript">
        /* <![CDATA[ */
        var google_conversion_id = 673853561;
        var google_conversion_label = "BsMPCO-i_r8BEPngqMEC";
        var google_conversion_value = "{{ $value }}";
        var google_conversion_currency = "USD";
        var google_remarketing_only = false;
        /* ]]> */
         fbq('track', 'CompleteRegistration');
         fbq('track', 'Purchase', {value: '{{ $value }}', currency: 'USD'});
    </script>
    <script type="application/javascript">
        ga('require', 'ecommerce');
        ga('ecommerce:addTransaction', {
            'id': '{{ uniqid() }}',                     // Transaction ID. Required.
            'revenue': '{{ $value }}',               // Grand Total.
        });
        ga('ecommerce:send');
    </script>
    <script type="text/javascript"
            src="//www.googleadservices.com/pagead/conversion.js">
    </script>
    <noscript>

        <div style="display:inline;">
            <img height="1"
                 width="1"
                 style="border-style:none;"
                 alt=""
                 src="//www.googleadservices.com/pagead/conversion/878550381/?value={{ $value }}&amp;currency_code=USD&amp;label=zVCpCNuH5n0Q7br2ogM&amp;guid=ON&amp;script=0"/>
        </div>
    </noscript>
    <?php } ?>
    <home :user="user"
          inline-template>
        <div class="container">
            <!-- Application Dashboard -->
            <div class="row">
                <div class="col-md-12">
                    @if($paused)
                        <div class="alert alert-danger">
                            <b>Upgrade
                                to
                                Resume</b><br/>
                            You
                            have
                            reached
                            the
                            limit
                            of
                            your
                            plan.
                            <a href="{{ url('settings#/subscription') }}" onClick="ga('send', 'event', 'button', 'click', 'upgrade_alert_dashboard');">Upgrade
                                your
                                plan</a>
                            to
                            resume
                            your
                            reports.
                        </div>
                    @endif
                    @include('common.flash')
                    <div class="panel panel-default panel-greetings">
                        <div class="panel-body">
                            <div class="row greetings">
                                <div class="col-md-8 col-sm-8 col-xs-12">
                                    <h1 class="title">
                                        Hello, {{ auth()->user()->name }}!</h1>
                                    <p>
                                        To
                                        get
                                        started,
                                        integrate
                                        your 
                                        applications, sync your account
                                        and then
                                        click
                                        the
                                        create
                                        report
                                        button.</p>
                                </div>
                                <div class="col-md-4 col-sm-4 col-xs-12 text-right greeting-button">
                                    @if($active_accounts > 0)
                                        <a href="{{ route('reports.chooseTemplate') }}"
                                           class="btn btn-black btn-create-report">Create
                                            Report
                                            &nbsp;&nbsp;<i
                                                    class="fa fa-caret-right"
                                                    aria-hidden="true"></i></a>
                                    @else
                                        <button class="btn btn-black btn-create-report nr_connect_accounts_button" onClick="ga('send', 'event', 'button', 'click', 'create_report_dashboard');">
                                            Create
                                            Report&nbsp;&nbsp;<i
                                                    class="fa fa-caret-right"
                                                    aria-hidden="true"></i>
                                            <span class="nr-loader fa fa-spin fa-spinner margin-left-5 hidden"></span>
                                        </button>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="panel panel-default panel-grey panel-connect-accounts">
                        <div class="panel-body">
                            <div class="row">
                               
                                <div class="col-md-12">
                                    <h4 class="title">
                                        Connect
                                        your
                                        Accounts</h4>
                                    <p>
                                        Integrate
                                        your
                                        online
                                        applications
                                        with
                                        Ninja
                                        Reports
                                        and
                                        schedule
                                        daily,
                                        weekly
                                        or
                                        monthly
                                        reports.
                                        Click
                                        Add
                                        Integration
                                        to
                                        allow
                                        Ninja
                                        Reports
                                        to
                                        access
                                        your
                                        account
                                        data
                                        and
                                        start
                                        automating
                                        your
                                        reports.
                                    </p>
                                    <button class="btn btn-black nr_connect_accounts_button" onClick="ga('send', 'event', 'button', 'click', 'add_integration_dashboard');">
                                        <i class="fa fa-plus-circle"
                                           aria-hidden="true"></i>
                                        Add
                                        Integration
                                        <span class="nr-loader fa fa-spin fa-spinner margin-left-5 hidden"></span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-sm-12">
                    <div class="panel panel-default panel-grey panel-plan-usage">
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-5 col-sm-5 col-xs-5">
                                    <div class="main-content">
                                        <h4 class="title">
                                            Plan
                                            Usage</h4>
                                        <p>
                                            <b>{{ $reports_sent_count }}</b>
                                            of {{ $plan['reports'] }}
                                            Reports
                                            Sent
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-7 col-sm-7 col-xs-7">
                                    <div class="chartjs">
                                        <div class="chart-canvas">
                                            <canvas id="pie-chart"
                                                    width="100"
                                                    height="100"></canvas>
                                        </div>
                                        <div id="js-legend"
                                             class="chart-legend"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-sm-12">
                    <div class="panel panel-default panel-grey panel-current-plan">
                        <div class="panel-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="main-content">
                                        <div class="h4 title">
                                            <span>Current Plan: {{ ucfirst(str_replace('_', ' ', $plan['title'])) }}</span>
                                            @if($plan['title'] != 'white_label')
                                                <a href="{{ url('settings#/subscription') }}"
                                                   class="btn btn-black pull-right upgrade-btn"
                                                   onClick="ga('send','event','button', 'click', 'upgrade');">Upgrade</a>
                                            @endif
                                        </div>
                                        <p>
                                            {{ $plan['reports'] }}
                                            Reports/Month
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="connect_accounts_modal_section"></div>
            </div>
        </div>
    </home>
@endsection
@section('page_scripts')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.7.1/Chart.bundle.min.js"></script>
    <script>
        new Chart(document.getElementById("pie-chart"), {
            type: 'pie',
            data: {
                labels: ["Sent", "Remaining"],
                datasets: [{
                    backgroundColor: ["#1e90ff", "#00bfff"],
                    data: [{{ $reports_sent_count }},{{ $plan['reports']-$reports_sent_count }}]
                }]
            }
        });
    </script>
@endsection
