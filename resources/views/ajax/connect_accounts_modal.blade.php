<!-- Connect Accounts Modal -->
<div id="connect_accounts_modal" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <!-- Modal content-->
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Add Integration</h4>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <div class="table-responsive">
                            <table class="table table-stripped accounts_connect_table">
                                <tbody>
                                <tr>
                                    <td>
                                        <img src="{{ asset('img/facebook.png') }}"/>
                                    </td>
                                    <td>Facebook Ads</td>
                                    <td>{!! in_array('facebook', $accounts) ? '<button class="btn btn-xs btn-default">Connected</button>':'
										<a class="btn btn-xs btn-black" href="'.route('connect.facebook').'">Connect</a>' !!}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <img src="{{ asset('img/analytics.png') }}"/>
                                    </td>
                                    <td>Google Analytics</td>
                                    <td>{!! in_array('analytics', $accounts) ? '<button class="btn btn-xs btn-default">Connected</button>':'
										<a class="btn btn-xs btn-black" href="'.route('connect.analytics').'">Connect</a>' !!}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <img src="{{ asset('img/adword.png') }}"/>
                                    </td>
                                    <td>Google Adwords</td>
                                    <td>{!! in_array('adword', $accounts) ? '<button class="btn btn-xs btn-default">Connected</button>':'
										<a class="btn btn-xs btn-black" href="'.route('connect.adwords').'">Connect</a>' !!}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <svg width="62" height="25"><title>Stripe</title>
                                            <path d="M5 10.1c0-.6.6-.9 1.4-.9 1.2 0 2.8.4 4 1.1V6.5c-1.3-.5-2.7-.8-4-.8C3.2 5.7 1 7.4 1 10.3c0 4.4 6 3.6 6 5.6 0 .7-.6 1-1.5 1-1.3 0-3-.6-4.3-1.3v3.8c1.5.6 2.9.9 4.3.9 3.3 0 5.5-1.6 5.5-4.5.1-4.8-6-3.9-6-5.7zM29.9 20h4V6h-4v14zM16.3 2.7l-3.9.8v12.6c0 2.4 1.8 4.1 4.1 4.1 1.3 0 2.3-.2 2.8-.5v-3.2c-.5.2-3 .9-3-1.4V9.4h3V6h-3V2.7zm8.4 4.5L24.6 6H21v14h4v-9.5c1-1.2 2.7-1 3.2-.8V6c-.5-.2-2.5-.5-3.5 1.2zm5.2-2.3l4-.8V.8l-4 .8v3.3zM61.1 13c0-4.1-2-7.3-5.8-7.3s-6.1 3.2-6.1 7.3c0 4.8 2.7 7.2 6.6 7.2 1.9 0 3.3-.4 4.4-1.1V16c-1.1.6-2.3.9-3.9.9s-2.9-.6-3.1-2.5H61c.1-.2.1-1 .1-1.4zm-7.9-1.5c0-1.8 1.1-2.5 2.1-2.5s2 .7 2 2.5h-4.1zM42.7 5.7c-1.6 0-2.5.7-3.1 1.3l-.1-1h-3.6v18.5l4-.7v-4.5c.6.4 1.4 1 2.8 1 2.9 0 5.5-2.3 5.5-7.4-.1-4.6-2.7-7.2-5.5-7.2zm-1 11c-.9 0-1.5-.3-1.9-.8V10c.4-.5 1-.8 1.9-.8 1.5 0 2.5 1.6 2.5 3.7 0 2.2-1 3.8-2.5 3.8z"></path>
                                        </svg>
                                    </td>
                                    <td>Stripe Connect</td>
                                    <td>{!! in_array('stripe', $accounts) ? '<button class="btn btn-xs btn-default">Connected</button>':'
										<a class="btn btn-xs btn-black" href="'.route('connect.stripe').'">Connect</a>' !!}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <img src="{{ asset('img/salesforce.png') }}"/>
                                    </td>
                                    <td>Salesforce</td>
                                    <td>
                                        <button disabled="disabled" class="btn btn-xs btn-default">Coming Soon...
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <img src="{{ asset('img/quickbooks.png') }}"/>
                                    </td>
                                    <td>Quickbooks</td>
                                    <td>
                                        <button disabled="disabled" class="btn btn-xs btn-default">Coming Soon...
                                        </button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>

    </div>
</div>