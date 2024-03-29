<spark-update-logo :user="user" inline-template v-if="user.current_billing_plan === 'white_label'">
    <div>
        <div class="panel panel-default" v-if="user">
            <div class="panel-heading">White Label Logo</div>

            <div class="panel-body">
                <div class="alert alert-danger" v-if="form.errors.has('photo')">
                    @{{ form.errors.get('logo') }}
                </div>

                <form class="form-horizontal" role="form">
                    <!-- Photo Preview-->
                    <div class="form-group">
                        <label class="col-md-3 control-label">&nbsp;</label>

                        <div class="col-md-6" style="text-align:center;">
                            <span role="img" class="white-photo-preview white-label-logo"
                                :style="previewStyle">
                            </span>
                        </div>
                         <label class="col-md-3 control-label">&nbsp;</label>
                    </div>

                    <!-- Update Button -->
                    <div class="form-group">
                        <label class="col-md-3 control-label">&nbsp;</label>

                        <div class="col-md-6" style="text-align:center;">
                            <label type="button" class="btn btn-primary btn-upload" :disabled="form.busy">
                                <span>Select New logo</span>

                                <input ref="logo" type="file" class="form-control" name="logo" @change="update">

                            </label>
                            <br>
                            <small>Recommended 300x100 Pixels</small>
                        </div>
                         <label class="col-md-3 control-label">&nbsp;</label>
                    </div>
                </form>
            </div>
        </div>
    </div>
</spark-update-logo>
