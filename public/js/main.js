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
                    if (response.status == 'success') {
                        loader.addClass('hidden');
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
    });
})(jQuery);