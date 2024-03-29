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
                                 <tr style="display:none;">
                                    <td>
                                        <img src="{{ asset('img/stripe.png') }}"/>
                                    </td>
                                    <td>Stripe Connect</td>
                                    <td>{!! in_array('stripe', $accounts) ? '<button class="btn btn-xs btn-default">Connected</button>':'
										<a class="btn btn-xs btn-black" href="'.route('connect.stripe').'">Connect</a>' !!}</td>
                                </tr>
                                <tr>
                                    <td>
                                        <img src="{{ asset('img/google-search.png') }}"/>
                                    </td>
                                    <td>Google Search Console</td>
                                    <td>
                                        {!! in_array('google-search', $accounts) ? '<button class="btn btn-xs btn-default">Connected</button>':'
										<a class="btn btn-xs btn-black" href="'.route('connect.google.search').'">Connect</a>' !!}
                                    </td>
                                </tr>
                                         <tr>
                                    <td>
                                        <img src="{{ asset('img/facebook.png') }}"/>
                                    </td>
                                    <td>Facebook Ads</td>
                                    <td>{!! in_array('facebook', $accounts) ? '<button class="btn btn-xs btn-default">Connected</button>':'
                                        <a class="btn btn-xs btn-black" href="'.route('connect.facebook').'">Connect</a>' !!}</td>
                                </tr>
                                <tr style="display:none;">
                                    <td>
                                        <img src="{{ asset('img/salesforce.png') }}"/>
                                    </td>
                                    <td>Salesforce</td>
                                    <td>
                                        <button disabled="disabled" class="btn btn-xs btn-default">Coming Soon...
                                        </button>
                                    </td>
                                </tr>
                               <tr style="display:none;">

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