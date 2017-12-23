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
                            <div class="col-md-8">
                                <h2 class="title">Good Day, Taylor!</h2>
                                <p>Welcome to your account dashboard. To get started, created a report!</p>
                            </div>
                            <div class="col-md-4 text-right greeting-button">
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
                                <div class="main-content">
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
            <div class="col-md-6">
                <div class="panel panel-default panel-grey panel-plan-usage">
                    <div class="panel-body">
                        <div class="row">                            
                            <div class="col-md-12">
                                <div class="main-content">
                                    <h4 class="title">Plan Usage</h4>
                                    <p>
                                        <b>15</b> of 30 Reports Sent 
                                    </p>
                                    <canvas id="myChart" width="120" height="120"></canvas>
                                </div>                                
                            </div>
                        </div>                                               
                    </div>
                </div>
            </div>            
            <div class="col-md-6">
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
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/2.1.4/Chart.min.js"></script>
<script>
    var ctx = document.getElementById("myChart").getContext('2d');
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
      }
    });
</script>
@endsection
