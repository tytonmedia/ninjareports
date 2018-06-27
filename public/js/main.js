(function ($) {
    $(document).ready(function () {
        var site_url = $('meta[name="site-url"]').attr('content') + '/';
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        if ($('.timepicker').length) {
            $('.timepicker').timepicker({
                minuteStep: 15,
                showInputs: false,
            });
        }

        // Frequncy Options
        $(document).on('change', '.frequency', function () {
            toastr.remove();
            var frequency = $(this).val();
            getFrequencyHtml(frequency);
        });

        function getFrequencyHtml(frequency) {
            var label = 'on';
            var html = '';
            if (frequency === 'daily') {
                label = 'at';
                html = '<div class="col-md-5"><input type="text" name="ends_at" readonly class="form-control timepicker" /></div>';
            }
            if (frequency === 'weekly') {
                html = '<div class="col-md-5"><select name="ends_at" class="form-control"><option value="Mon">Mon</option><option value="Tue">Tue</option><option value="Wed">Wed</option><option value="Thu">Thu</option><option value="Fri">Fri</option><option value="Sat">Sat</option><option value="Sun">Sun</option></select></div>';
            }
            if (frequency === 'monthly') {
                html = '<div class="col-md-5"><select name="ends_at" class="form-control">';
                for (i = 1; i <= 31; i++) {
                    html += '<option value="' + i + '">' + i + '</option>';
                }
                html += '</select></div>';
            }
            if (frequency === 'yearly') {
                html = '<div class="col-md-2"><select name="ends_at_day" class="form-control">';
                for (i = 1; i <= 31; i++) {
                    html += '<option value="' + i + '">' + i + '</option>';
                }
                html += '</select></div>';
                html += '<div class="col-md-3"><select name="ends_at_month" class="form-control"><option value="1">Jan</option><option value="2">Feb</option><option value="3">Mar</option><option value="4">Apr</option><option value="5">May</option><option value="6">Jun</option><option value="7">Jul</option><option value="8">Aug</option><option value="9">Sep</option><option value="10">Oct</option><option value="11">Nov</option><option value="12">Dec</option></select></div>';
            }
            var frequency_html = '<div class="col-md-1"><label class="control-label color-black-bold">' + label + '</label></div>' + html;
            $('.ends_at_section').html(frequency_html);
            if ($('.timepicker').length) {
                $('.timepicker').timepicker({
                    minuteStep: 15,
                    showInputs: false,
                });
            }
        }

        // Connect Accounts Modal
        $(document).on('click', '.nr_connect_accounts_button', function () {
            toastr.remove();
            var btn = $(this);
            var loader = btn.find('.nr-loader');
            loader.removeClass('hidden');
            $.ajax({
                url: site_url + 'accounts/connect',
                success: function (response) {
                    loader.addClass('hidden');
                    if (response.status == 'success') {
                        $('.connect_accounts_modal_section').html(response.html);
                        $('#connect_accounts_modal').modal();
                    } else {
                        toastr.error('Something went wrong. Please try again.');
                    }
                },
                error: function () {
                    loader.addClass('hidden');
                    toastr.error('Something went wrong. Please try again.');
                }
            });
        });

        // send test email button and confirmation
          $(document).on('click', '#test_report', function () {
             toastr.remove();
             var btn = $(this);
             var loader = btn.find('.nr-loader');
            var value = $('.ad-account-types').val();
             loader.removeClass('hidden');
             var ajaxurl = site_url + 'reports/tester/' + value;
             $.ajax({
                url: ajaxurl,
                success: function (response) {
                    if (response.status == 'success') {
                      //alert('test email sent!');

                      toastr.success('Example report sent to the account email.');
                       $('#test_report').hide();
                    } else {
                        toastr.error('Something went wrong. Please try again.');
                       alert(response);
                    }
                },
                error: function () {
                     loader.addClass('hidden');
                    toastr.error('Something went wrong. Please try again.');
                }
            });
        });

        // Sync Facebook Ad Accounts
        $(document).on('click', '.nr_sync_ad_accounts_button', function () {
            toastr.remove();
            var btn = $(this);
            var type = btn.data('type');
            var loader = btn.find('.nr-loader');
            loader.removeClass('hidden');
            $.ajax({
                url: site_url + 'accounts/sync/' + type + '/adaccounts',
                success: function (response) {
                    loader.addClass('hidden');
                    if (response.status == 'success') {
                        toastr.success('Properties synchronized.');
                        $('.synchronized_ad_accounts').html(response.html);
                     //   $('#test_report').show();
                    } else {
                        toastr.error('Something went wrong. Please try again.');
                    }
                },
                error: function () {
                    loader.addClass('hidden');
                    toastr.error('Something went wrong. Please try again.');
                }
            });
        });

        // Get Ad Accounts
        $(document).on('change', '.ad-account-types', function () {
            toastr.remove();
            var account = $(this).val();
            $('.sub_accounts_html').html('');
            $('.properties_html').html('');
            $('.views_html').html('');
            if (account) {
                var loader = $('.ad-account-types-loader');
                loader.removeClass('hidden');
                $.ajax({
                    url: site_url + 'reports/' + account + '/adaccounts',
                    success: function (response) {
                        loader.addClass('hidden');
                        if (response.status == 'success') {
                            $('.sub_accounts_html').html(response.html);
                            $('.nr-ad-account').change();
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
        });

        // Get Google Analytics Properties
        $(document).on('change', '.nr-ad-account', function () {
            toastr.remove();
            var account = $(this).data('type');
            if (account == 'analytics') {
                var ad_account_id = $(this).val();
                var loader = $('.analytics-properties-loader');
                loader.removeClass('hidden');
                $.ajax({
                    url: site_url + 'reports/' + account + '/properties/' + ad_account_id,
                    success: function (response) {
                        loader.addClass('hidden');
                        if (response.status == 'success') {
                            $('.properties_html').html(response.html);
                            $('.nr-ad-property').change();
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
        });

        // Get Google Analytics Views
        $(document).on('change', '.nr-ad-property', function () {
            toastr.remove();
            var account = $(this).data('type');
            if (account == 'analytics') {
                var ad_account_id = $(this).data('ad_account');
                var property_id = $(this).val();
                var loader = $('.analytics-views-loader');
                loader.removeClass('hidden');
                $.ajax({
                    url: site_url + 'reports/' + account + '/properties/' + ad_account_id + '/profiles/' + property_id,
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

        // Start/Pause Reports
        $(document).on('click', '.toggle_report', function(){
            toastr.remove();
            var btn = $(this);
            btn.html('<i class="fa fa-spin fa-spinner"></i>');
            var report_id = btn.data('report_id');
            var status = btn.data('status');
            if(status === 'yes'){
                var is_paused = 0;
                var btn_value = 'Pause';
                btn.data('status', 'no');
            } else {
                var is_paused = 1;
                var btn_value = 'Start';
                btn.data('status', 'yes');
            }
            $.ajax({
                type: 'POST',
                url: site_url + 'reports/' + report_id  + '/pause/' + is_paused,
                success: function (response) {
                    btn.html(btn_value);
                    if (response.status == 'success') {
                        toastr.success('Status Changes Successfully.');
                    } else {
                        toastr.error('Something went wrong. Please try again.');
                    }
                },
                error: function () {
                    btn.html(btn_value);
                    toastr.error('Something went wrong. Please try again.');
                }
            });
        });
    });
})(jQuery);