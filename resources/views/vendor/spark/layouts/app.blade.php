<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Meta Information -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
	<meta name="site-url" content="{{ url('/') }}">
    <title>@yield('title', config('app.name'))</title>

    <!-- Fonts -->
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:300,400,600' rel='stylesheet' type='text/css'>
    <link href='https://fonts.googleapis.com/css?family=Roboto:400,600,700' rel='stylesheet' type='text/css'>

    <link rel="shortcut icon" href="{{{ asset('img/favicon.png') }}}">
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css' rel='stylesheet' type='text/css'>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.css" />
    @yield('page_styles')
    <!-- CSS -->
    <link href="/css/sweetalert.css" rel="stylesheet">
    <link href="{{ mix('css/app.css') }}" rel="stylesheet">
    <link href="/css/custom.css" rel="stylesheet">
    <link href="/css/override.css" rel="stylesheet">
    @yield('custom_styles')

    <!-- Scripts -->
    @yield('scripts', '')

    <!-- Global Spark Object -->
    <script>
        window.Spark = <?php echo json_encode(array_merge(
    Spark::scriptVariables(), []
)); ?>;
        var canRunAds = true;
    </script>
    <!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-79012395-19"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-79012395-19');
</script>
<script>
  window['GoogleAnalyticsObject'] = 'ga';
  window['ga'] = window['ga'] || function() {
    (window['ga'].q = window['ga'].q || []).push(arguments)
  };
</script>
<!-- Start of ninjareports Zendesk Widget script -->
<script>/*<![CDATA[*/window.zEmbed||function(e,t){var n,o,d,i,s,a=[],r=document.createElement("iframe");window.zEmbed=function(){a.push(arguments)},window.zE=window.zE||window.zEmbed,r.src="javascript:false",r.title="",r.role="presentation",(r.frameElement||r).style.cssText="display: none",d=document.getElementsByTagName("script"),d=d[d.length-1],d.parentNode.insertBefore(r,d),i=r.contentWindow,s=i.document;try{o=s}catch(e){n=document.domain,r.src='javascript:var d=document.open();d.domain="'+n+'";void(0);',o=s}o.open()._l=function(){var e=this.createElement("script");n&&(this.domain=n),e.id="js-iframe-async",e.src="https://assets.zendesk.com/embeddable_framework/main.js",this.t=+new Date,this.zendeskHost="ninjareports.zendesk.com",this.zEQueue=a,this.body.appendChild(e)},o.write('<body onload="document._l();">'),o.close()}();
/*]]>*/</script>
<!-- End of ninjareports Zendesk Widget script -->
<!--PROOF PIXEL--><script src='https://cdn.useproof.com/proof.js?acc=Rye9tlLGt3Z1rKYkmABBYg6sEnO2' async></script><!--END PROOF PIXEL-->
<!-- Facebook Pixel Code -->
<script>
!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window,document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '223802198014729'); 
fbq('track', 'PageView');
</script>
<noscript>
<img height="1" width="1" 
src="https://www.facebook.com/tr?id=223802198014729&ev=PageView
&noscript=1"/>
</noscript>
<!-- End Facebook Pixel Code -->

</head>
<body class="with-navbar">
    <script>
      if( canRunAds === undefined ){
        // adblocker detected, show fallback
        showFallbackImage();
      }
    </script>
    <div id="spark-app" v-cloak>
        <!-- Navigation -->
        @if (Auth::check())
            @include('spark::nav.user')
        @else
            @include('spark::nav.guest')
        @endif

        <!-- Main Content -->
        @yield('content')

        <!-- Application Level Modals -->
        @if (Auth::check())
            @include('spark::modals.notifications')
            @include('spark::modals.support')
            @include('spark::modals.session-expired')
        @endif
    </div>
  <footer class="footer">
      <div class="container">
        <div class="row footer-row">
             <div class="col-md-6 socials" style="text-align:left;">
              <ul class="social-links">
                <li><a href="https://www.ninjareports.com/"><img src="{{{ asset('img/ninja_small.png') }}}" alt="ninja reports"/></a></li>
                  <li><a target="_blank" href="https://www.facebook.com/ninjareports/">Facebook</a></li>
                  <li><a target="_blank" href="https://twitter.com/ninja_reports">Twitter</a></li>
                  <li><a target="_blank" href="https://www.youtube.com/channel/UCcjm2lXhxAYGoKS1nCe3vIQ">Youtube</a></li>
                   <li><a target="_blank" href="https://www.linkedin.com/company/ninja-reports">LinkedIn</a></li>
                  <li><a href="https://www.ninjareports.com/blog/">Blog</a></li>
              </ul>
            </div>
                   <div class="col-md-6 footer-right-container" style="text-align:right;">
                    <a target="_blank" href="https://ninjareports.zendesk.com/hc/en-us">Support</a>  |
                    <a href="https://www.ninjareports.com/terms-conditions/">Terms &amp; conditions</a>  |  <a href="https://www.ninjareports.com/privacy-policy/">Privacy policy</a>
                </div>
            </div>
             <div class="row copyright">
                  <div class="col-md-12" style="text-align:center;">
                      <p>Copyright 2018 © Ninja Reports - <i>A Tyton Media Company</i></p>
             </div>
         </div>
        </div>
      </div>
    </footer>
    <!-- JavaScript -->
    <script src="{{ mix('js/app.js') }}"></script>
    <script src="/js/sweetalert.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/2.1.4/toastr.min.js"></script>
    @yield('page_scripts')
    <script src="/js/main.js"></script>
    @yield('custom_scripts')
</body>
</html>
