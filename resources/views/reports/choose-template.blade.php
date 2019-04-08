@extends('spark::layouts.app')
@section('content')
<div class="spark-screen container">
<div class="col-md-11 col-center-block">
    <div class="row">
        <div class="col-md-12">
            <h1 style="margin-top:0">Select a Report Template</h1>
            <p>Choose a report template below or click <i>send test</i> to recieve a test report sent to your email.</p>
        </div>
    </div>
</div>
    <div class="col-md-11 col-center-block">
        <div class="row">
            @foreach($templates as $template)
            <div class="col-md-4 report-template-block">
                <div class="panel panel-default">
                    <div class="panel-body">
                        <img src="{{ $template->logo_url }}" class="img-responsive col-center-block" alt="" srcset="">
                        <h2>{{$template->name}}</h2>
                        <div class="row my-10">
                            <div class="col-md-6 pr-0">
                                <p>Required Integrations</p>
                            </div>
                            <div class="col-md-6 pr-0">
                                @foreach($template->integrations as $integration)
                                <img src="{{$integration->logo_url}}" class="mr-3" width="24" height="24" alt="">
                                @endforeach
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-6 pl-0 pr-0 test-btn-div">
                                <button type="button" onclick="sentTestReport(this)" data-template-id="{{$template->slug}}" class="btn btn-link btn-lg btn-block border-none border-radius-0" style="text-decoration:none;">Send
                                    Test</button>
                            </div>
                            <div class="col-lg-6 pl-0 pr-0">
                                <button type="button" onclick="chooseReportTemplate(this,'{{$template->slug}}')" data-template-id="{{$template->id}}" class="btn btn-primary btn-lg btn-block border-radius-0">Choose
                                    Report</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
    </div>

</div>

<div class="modal fade color-black" id="integrationErrorInfoModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-none">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body p-50 pt-0">
                <h2 class="mt-0">Woops</h2>
                <p>The report requires the following integrations</p>
                <div class="my-10" id="required-integrations">
                   
                </div>
                <p class="my-10">
                    Please head to the integrations tab to connect these data sources
                </p>
            </div>
        </div>
    </div>
</div>

<div class="modal fade color-black" id="testReportInfoModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header border-none">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body p-50 pt-0">
                <div class="my-10" id="required-test">
                   
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('page_scripts')
<script>

    var reportTemplates = JSON.parse('<?= json_encode($templates) ?>')

    // function arrayContains(arrayOne,arrayTwo) {
    //     let containing = false
    //     arrayOne.forEach(function(item){
    //         if (arrayTwo.includes(item)) {
    //             containing = true;
    //         } else {
    //             containing = false;
    //         }
    //     })
    //     return containing;
    // }
    
    function sentTestReport(elm){
        var slug = $(elm).data('templateId');
        $(elm).html('Sending <i class="fa fa-spinner fa-pulse fa-fw"></i>')
        $.post( "<?= url("reports/test") ?>",{'slug':slug} ,function( data ) {
            $(elm).text('SEND TEST REPORT') 
            swal("Test Report Send", "Report send to your email", "success")
        });
    }

    function chooseReportTemplate(elm,slug) {
        // var that = this;
        // const slug = slug;
        var selectedTemplateId = $(elm).data('templateId')
        var selectedTemplate = reportTemplates.find(function(template){
            return template.id === selectedTemplateId
        });
        // var requiredIntegrationsHtml = ''
        // console.log(selectedTemplate)
        // requiredIntegrationsHtml = selectedTemplate.integrations.reduce(function(html,integration){
        //     return html+'<div class="mb-5">'+
        //             '<img src="'+integration.logo_url+'" width="24" height="24">'+ 
        //             '<span class="ml-5">'+integration.name+'</span>'+
        //             '</div>'   
        // },'')

        var templateIntegrations = selectedTemplate.integrations
                .reduce(function(list,integration){
                    list.push(integration.slug)
                    return list;
                },[])

        $('#required-integrations').html('')
        $(elm).html('Verifying <i class="fa fa-spinner fa-pulse fa-fw"></i>')
        $.get('<?= url("user/me/integrations") ?>')
            .done(function(data){
                var userIntegrations = data.accounts.reduce(function(list,account){
                    list.push(account.type)
                    return list;
                },[])
                let found = templateIntegrations.every(r=> userIntegrations.includes(r) )
                // if (arrayContains(templateIntegrations,userIntegrations)) {
                if (found) {
                    window.location = "<?= url('reports/settings')?>/"+slug
                } else {
                    var unselectedIntegration = templateIntegrations.filter(function(item) {
                    return !userIntegrations.includes(item) ? true : userIntegrations.splice(userIntegrations.indexOf(item),1) && false;
                    });
                    $('#required-integrations').html(checkIntegration(elm,unselectedIntegration))
                    // $('#required-integrations').html(requiredIntegrationsHtml)
                    $('#integrationErrorInfoModal').modal('show')
                    $(elm).text('Choose Report')  
                }
            })
        // setTimeout(function(){
            
            
        // },1000);
        
    }
    function checkIntegration(elm,integration){
        var selectedTemplateId = $(elm).data('templateId')
        var selectedTemplate = reportTemplates.find(function(template){
            return template.id === selectedTemplateId
        });
        var requiredIntegrationsHtml = ''
        
        selectedTemplate.integrations.forEach(function(item){
            if (integration.includes(item.slug)) {
                item.integration="<span style='color:red'>Not Integrated</span>"
            } else {
                item.integration="<span style='color:green'>Integrated</span>"
            }
        })
        requiredIntegrationsHtml = selectedTemplate.integrations.reduce(function(html,integration){
            return html+'<div class="mb-5">'+
                    '<img src="'+integration.logo_url+'" width="24" height="24">'+ 
                    '<span class="ml-5">'+integration.name+'</span>'+
                    '<span class="ml-5">'+integration.integration+'</span>'+
                    '</div>'   
        },'')
        return requiredIntegrationsHtml;
    }
</script>
@endsection

