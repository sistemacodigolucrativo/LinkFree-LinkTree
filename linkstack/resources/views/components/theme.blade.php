@extends('layouts.updater')
@foreach($information as $info)

@Push('updater-head')
<title>{{ $userinfo->name }} 🌇 {{ ucfirst($info->theme ?: 'default') }} </title>
<meta name="robots" content="noindex">
<style>
.container-theme {
  width:100%; max-width:960px; box-sizing:border-box;
  margin:0 auto; padding:24px; min-width:0;
}
.container-theme pre {
  display:block; max-width:100%; white-space:pre-wrap;
  overflow-wrap:anywhere; text-align:left;
}
.container-theme h1 {overflow-wrap:anywhere}
</style>
@endpush

@Push('updater-body')


@if(file_exists(base_path("themes/$info->theme/readme.md")))


<div class="container-theme">
<h1>Theme: {{ ucfirst($info->theme ?: 'default') }}</h1>

<?php
$str = file_get_contents('themes/' . $info->theme . '/readme.md');
$url_pattern = '/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/';
$str= preg_replace($url_pattern, '<a href="$0">$0</a>', $str);
$txtspc = '<pre>' . $str . '</pre>';
echo preg_replace('/[ \t]+/', ' ', preg_replace('/\s\S*$^\s\S*/m', "\n", $txtspc));
?>

</div>

@else
<div class="container"><p>File not found</p></div>
@endif


@endforeach
@endpush
