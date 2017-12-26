@extends('spark::layouts.app')

@section('content')
<home :user="user" inline-template>
    <div class="container dashboard">
        <!-- Application Dashboard -->
        <div class="row">
            <div class="col-md-12">
                <div class="panel panel-default panel-greetings">
                    <div class="panel-body">
                        <div class="row greetings">
                            <div class="col-md-8 col-sm-8 col-xs-12">
                                <h2 class="title">Good Day, Taylor!</h2>
                                <p>Welcome to your account dashboard. To get started, created a report!</p>
                            </div>
                            <div class="col-md-4 col-sm-4 col-xs-12 text-right greeting-button">
                                <button class="btn btn-black btn-create-report">
                                        Create Report
                                </button>
                            </div>
                        </div>                                               
                    </div>
                </div>
                <div class="panel panel-default panel-grey panel-connect-accounts">
                    <div class="panel-body">
                        <div class="row">                            
                            <div class="col-md-12">
                                <img class="social-icon" src="/img/social-icon.png" alt="">
                                <div class="main-content col-xs-12">
                                    <h4 class="title">Connect your Accounts</h4>
                                    <p>
                                        Lorem ipsum dolor sit amet, consectetur adipisicing elit. Delectus, ducimus illo iste deserunt, eaque sunt officiis incidunt possimus, voluptas itaque eligendi impedit quisquam omnis asperiores nobis dolorem! Nisi, eos, ipsum. Lorem ipsum dolor sit amet, consectetur adipisicing elit. Modi nisi voluptas delectus eos libero corporis suscipit porro minima, aperiam. 
                                    </p>
                                    <button class="btn btn-black">Connect Accounts</button>
                                </div>                                
                            </div>
                        </div>                                               
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-sm-12">
                <div class="panel panel-default panel-grey panel-plan-usage">
                    <div class="panel-body">
                        <div class="row">                            
                            <div class="col-md-5 col-sm-5 col-xs-5">
                                <div class="main-content">
                                    <h4 class="title">Plan Usage</h4>
                                    <p>
                                        <b>15</b> of 30 Reports Sent 
                                    </p>                                    
                                </div>                               
                            </div>
                            <div class="col-md-7 col-sm-7 col-xs-7">
                                <div class="chartjs">
                                    <div class="chart-canvas">
                                        <canvas id="myChart" width="120" height="120"></canvas>
                                    </div>                               
                                    <div id="js-legend" class="chart-legend"></div>
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
                                        <span>Current Plan: Lite</span>
                                        <button class="btn btn-black">Upgrade</button>
                                    </div>
                                    <p>
                                        30 Reports/Month
                                    </p>                                    
                                </div>                                
                            </div>
                        </div>                                               
                    </div>
                </div>
            </div>
        </div>
    </div>
</home>
@endsection
@section('custom-scripts')
<script src="/js/Chart.min.js"></script>
<script>
    /*var ctx = document.getElementById("myChart").getContext('2d');
    var myChart = new Chart(ctx, {
      type: 'pie',
      data: {
        labels: ["Sent", "Remaining"],
        datasets: [{
          backgroundColor: [
            "#0F81F3",
            "#55A8EE"
          ],
          data: [50, 50]
        }]
      },
      options: {
            title: {
                display: false,
                text: 'Custom Chart Title'
            },
            legend:{
                display: true,
                labels: {
                    fontColor: 'rgb(255, 99, 132)'
                },
                position: 'top'
            },
        }
    });
    document.getElementById('js-legend').innerHTML = myChart.generateLegend();*/

    var data = [
       {
        value: 50,
        color: "#0F81F3",
        label: "Sent"
    }, {
        value: 50,
        color: "#55A8EE",
        label: "Remaining"
    }];

    var options = {
    }

    var ctx = document.getElementById("myChart").getContext("2d");

    var myChart = new Chart(ctx).Pie(data, options);

    // Note - tooltipTemplate is for the string that shows in the tooltip

    // legendTemplate is if you want to generate an HTML legend for the chart and use somewhere else on the page

    // e.g:

    document.getElementById('js-legend').innerHTML = myChart.generateLegend();

</script>
@endsection
