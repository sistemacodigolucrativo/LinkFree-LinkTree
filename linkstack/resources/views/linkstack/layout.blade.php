<!DOCTYPE html>
@include('layouts.lang')
<head>
   @stack('linkstack-head')
   @stack('linkstack-head-end')
   @isset($userinfo)
   <link rel="stylesheet" href="{{ asset('assets/linkstack/css/public-accessibility.css') }}">
   <script defer src="{{ asset('assets/js/public-contrast.js') }}"></script>
   @endisset
</head>
<body @isset($userinfo) class="lf-public-profile" @endisset>
   @stack('linkstack-body-start')
   <div class="container">
      <div class="row">
         <div class="column" style="margin-top: 5%">
            @stack('linkstack-content')
         </div>
      </div>
   </div>
   @stack('linkstack-body-end')
</body>
</html>