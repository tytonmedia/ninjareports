@extends('spark::layouts.app')
@section('content')
<div class="spark-screen container">
    <div class="panel panel-default p-5 color-black">
        <div class="panel-body">
            <h3>Reports Settings</h3>
            <div class="row">
                <div class="col-md-6">
                    <p>Report Settings</p>
                    <hr class="mt-0">

                    <div class="form-horizontal">
                        <div class="form-group">
                            <label for="" class="col-sm-3 control-label">Name</label>
                            <div class="col-sm-9">
                                <input type="name" class="form-control" id="" placeholder="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="" class="col-sm-3 control-label">Template</label>
                            <div class="col-sm-9">
                                <input type="template" class="form-control" id="" placeholder="" disabled>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="" class="col-sm-3 control-label">Analytics Source</label>
                            <div class="col-sm-9">
                                <select name="" id="" class="form-control">
                                    <option value=""> --- select ---</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="" class="col-sm-3 control-label">GWT Source</label>
                            <div class="col-sm-9">
                                <select name="" id="" class="form-control">
                                    <option value=""> --- select ---</option>
                                </select>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="col-md-6">
                    <p>Email Settings</p>
                    <hr class="mt-0">

                    <div class="form-horizontal">
                        <div class="form-group">
                            <label for="" class="col-sm-3 control-label">Recipients</label>
                            <div class="col-sm-9">
                                <textarea name="" id="" class="form-control"></textarea>
                                <small>comma seprated emails</small>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="" class="col-sm-3 control-label">Attachment</label>
                            <div class="col-sm-9">
                                <div class="btn-group" role="group" aria-label="...">
                                    <button type="button" class="btn btn-default active border-radius-0">&nbsp;&nbsp;PDF&nbsp;</button>
                                    <button type="button" class="btn btn-default border-radius-0">None</button>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="" class="col-sm-3 control-label">Subject</label>
                            <div class="col-sm-9">
                                <input type="subject" class="form-control" id="" placeholder="">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="" class="col-sm-3 control-label">Frequency</label>
                            <div class="col-sm-9">

                                <div class="row">
                                    <div class="col-xs-4">
                                        <select name="" id="" class="form-control">
                                            <option value="">Weekly</option>
                                        </select>
                                    </div>
                                    <div class="col-xs-4 pr-0 pl-0">
                                        <div class="input-group">
                                            <div class="input-group-addon">On</div>
                                            <select name="" id="" class="form-control">
                                                <option value="">Sunday</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-xs-4">
                                        <div class="input-group">
                                            <div class="input-group-addon">At</div>
                                            <input type="text" class="form-control">
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="" class="col-sm-3 control-label">Date from</label>
                            <div class="col-sm-9">
                                <select name="" id="" class="form-control">
                                    <option value=""> Last 7 days</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <br>
            <button class="btn btn-primary border-radius-0 pull-right">
                Create Report
            </button>
        </div>
    </div>
</div>
@endsection