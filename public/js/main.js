(function ($) {
    $(document).ready(function () {
        var site_url = $('meta[name="site-url"]').attr('content') + '/';
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

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

        // Sync Facebook Ad Accounts
        $(document).on('click', '.nr_sync_ad_accounts_button', function () {
            toastr.remove();
            var btn = $(this);
            var loader = btn.find('.nr-loader');
            loader.removeClass('hidden');
            $.ajax({
                url: site_url + 'accounts/sync/facebook/adaccounts',
                success: function (response) {
                    loader.addClass('hidden');
                    if (response.status == 'success') {
                        toastr.success('Ad accounts synchronized.');
                        $('.synchronized_facebook_ad_accounts').html(response.html);
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

        // Get FB Ad Accounts
        $(document).on('change', '.ad-account-types', function () {
            toastr.remove();
            var account = $(this).val();
            if (account) {
                var loader = $('.ad-account-types-loader');
                loader.removeClass('hidden');
                $('.fb_accounts_html').html('');
                if (account === 'facebook') {
                    $.ajax({
                        url: site_url + 'reports/facebook/adaccounts',
                        success: function (response) {
                            loader.addClass('hidden');
                            if (response.status == 'success') {
                                $('.sub_accounts_html').html(response.html);
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
            }
        });
    });
})(jQuery);