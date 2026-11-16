@if(env('ENABLE_BUTTON_EDITOR') === true)
@extends('layouts.sidebar')

@include('components.favicon')
@include('components.favicon-extension')

@section('content')
<div class="conatiner-fluid content-inner mt-n5 py-0">
  <div class="row">

   <div class="col-lg-12">
      <div class="card   rounded">
          <div class="card-body">
             <div class="row">
                 <div class="col-sm-12">

                  @push('sidebar-scripts')
                  <?php function strp($urlStrp){return str_replace(array('http://', 'https://'), '', $urlStrp);} ?>
                    <!-- start button editor -->
                    <script>
                      var button_css = {
                        color: 'inherit',
                        background_image: 'inherit'
                      };

                      function setButtonTextColor(value) {
                        var match = /^#?([0-9a-f]{6})$/i.exec(String(value).trim());
                        var hexInput = document.getElementById('button-color-hex');
                        if (!match) {
                          if (hexInput) hexInput.setAttribute('aria-invalid', 'true');
                          return false;
                        }

                        var color = '#' + match[1].toUpperCase();
                        button_css.color = color;
                        var sample = document.getElementById('sample');
                        var nativeInput = document.getElementById('button-color-native');
                        if (sample) sample.style.color = color;
                        if (hexInput) {
                          hexInput.value = color;
                          hexInput.removeAttribute('aria-invalid');
                        }
                        if (nativeInput) nativeInput.value = color;
                        if (window.jQuery) jQuery('#color').css('background-color', color);
                        return true;
                      }

                      $(document).ready(function() {
                        $('#generate-css').click(function() {
                          const style = document.createElement('span').style;
                          style.cssText = $('#sCode').val();
                          if(button_css.color !== 'inherit') style.color = button_css.color;
                          if(button_css.background_image !== 'inherit') style.backgroundImage = button_css.background_image;
                          $('#sCode').val(style.cssText);
                        });

                        $('a.modal-close-profile, .modal-lightsout').click(function() {
                          $('.modal-profile').fadeOut("slow");
                          $('.modal-lightsout').fadeOut("slow");
                        });

                        var $button = $("#sample");
                        $("#ex1").gradientPicker({
                          change: function(points, styles) {
                            for (i = 1; i < styles.length; ++i) {
                              $button.css("background-image", styles[i]);
                              button_css.background_image = styles[i];
                            }
                          },
                          fillDirection: "45deg",
                          controlPoints: ["white 0%", "#888888 100%"]
                        });

                        $('#color').ColorPicker({
                          color: '#000000',
                          onShow: function(colpkr) {
                            $(colpkr).fadeIn(500);
                            return false;
                          },
                          onHide: function(colpkr) {
                            $(colpkr).fadeOut(500);
                            return false;
                          },
                          onChange: function(hsb, hex, rgb) {
                            setButtonTextColor('#' + hex);
                          }
                        });

                        $('#button-color-hex').on('input change', function() {
                          setButtonTextColor(this.value);
                        });
                      });
                      </script>

                  <link rel="stylesheet" type="text/css" href="{{asset('assets/button-editor/styles/button-gen.css')}}">
                  <link rel="stylesheet" href="{{asset('assets/button-editor/styles/jquery.gradientPicker.css')}}" type="text/css" />
                  <link rel="stylesheet" href="{{asset('assets/button-editor/styles/colorpicker_1.css')}}" type="text/css" />
                  <link rel="stylesheet" type="text/css" href="{{asset('assets/button-editor/themes/blackboard.css')}}">
                  <link rel="stylesheet" href="{{asset('assets/linkstack/css/animations.css') }}">
                  <link rel="stylesheet" type="text/css" href="{{asset('assets/button-editor/css/style.css')}}">

                    <script src="{{asset('assets/button-editor/js/rainbow.js')}}"></script>
                    <script src="{{asset('assets/button-editor/js/css.js')}}"></script>

                    <script src="{{asset('assets/button-editor/js/jquery-1.7.js')}}"></script>
                    <script src="{{asset('assets/button-editor/js/jqueryUI-custom.js')}}"></script>
                    <script src="{{asset('assets/button-editor/js/colorpicker.js')}}"></script>
                    <script src="{{asset('assets/button-editor/js/jquery.gradientPicker.js')}}"></script>
                    <!-- end button editor -->
                  @endpush
                    <script src="{{ asset('assets/external-dependencies/fontawesome.js') }}" crossorigin="anonymous"></script>

                  <div>
                  <section class="shadow text-gray-400">
                  <h2 class="mb-4 card-header"><i class="bi bi-pen">{{__('messages.Button Editor')}}</i></h2>
                          <div class="card-body p-0 p-md-3">

                  <a class="btn btn-primary" href="{{ url('/studio/links') }}"><i class="bi bi-arrow-left-short"></i>{{__('messages.Back')}}</a><br><br>

                          <h2>{{__('messages.Custom Button')}}</h2><br>

                          <!-- start button editor -->
                  <div style="left: 10px;">
                    <div class="modal-profile">
                  <h2>{{__('messages.CSS')}}</h2>
                      <a class="modal-close-profile" title="Close profile window" href="#"><img href="{{asset('assets/button-editor/images/close.png')}}" alt="Close profile window" /></a>
                       <div id="output">
                       </div>
                   </div>


                      <div class="modal-lightsout"></div>





                  <aside id="panel-right">



                  </br>


                  <div class="tool">
                  <heading>{{__('messages.Color')}} &nbsp;<span>{{__('messages.Text')}}</span></heading>
                  </br>
                  <label for="button-color-native" class="form-label">Cor do texto</label>
                  <input id="button-color-native" type="color" aria-label="Cor do texto" value="#000000" oninput="setButtonTextColor(this.value)">
                  <label for="button-color-hex" class="form-label ms-2">Cor em hexadecimal</label>
                  <input id="button-color-hex" class="form-control d-inline-block" style="width:8rem" type="text" inputmode="text" autocomplete="off" maxlength="7" pattern="#[0-9A-Fa-f]{6}" value="#000000" aria-describedby="button-color-hex-help">
                  <small id="button-color-hex-help" class="d-block">Use o formato #RRGGBB.</small>
                  <input aria-label="Seletor de cor avançado" type="button" id="color" style="background-color:#FFFFFF; border:thin solid white;height:50px;width:50px;margin-left:5px;">


                  </div>


                  </br>


                  <div class="tool">
                  <heading>{{__('messages.background')}} &nbsp;<span>{{__('messages.gradient')}}</span></heading>
                  </br>
                  <div class="grad_ex" id="ex1"></div>
                  </div>


                  </br>




                  </aside>

                  <section id="preview">
                  @if($buttonId == 1)
                  <center><div id="sample" style="border-radius: 8px !important; max-width: 350px; height: 48px; display: flex; align-items: center; justify-content: center;font-size: 18px;" class="button-demo button"><img class="icon-btn hvr-icon" src="{{ asset('\/assets/linkstack/icons\/') . 'custom' }}.svg">{{ $title }}</div></center>
                  @else
                  <center><div id="sample" style="border-radius: 8px !important; max-width: 350px; height: 48px; display: flex; align-items: center; justify-content: center;font-size: 18px;" class="button-demo button"><img class="wicon hvr-icon" src="@if(file_exists(base_path("assets/favicon/icons/").localIcon($id))){{url('assets/favicon/icons/'.localIcon($id))}}@else{{getFavIcon($id)}}@endif">{{ $title }}</div></center>
                  @endif

                  </section>
                  </div>
                  <!-- end button editor -->

                  <br>
                  <form action="{{ route('editCSS', $id) }}" method="post">
                  @csrf
                  <details>
                  <summary>{{__('messages.Show CSS')}}</summary>
                  <div style="padding-left: 15px;">

                          <div class="form-group col-lg-8"><br>
                            <p>{{__('messages.Custom CSS')}}</p>

                            <textarea name="custom_css" rows="9" aria-label="CSS personalizado" id="sCode" type="text" value="{{ $custom_css }}" class="form-control" required>{{ $custom_css }}</textarea>
                          </div>
                  </div>
                  </details>
                  <br><button type="button" id="generate-css" class="btn btn-secondary">Aplicar cores ao CSS</button> <button type="submit" id="code" class="btn btn-primary">{{__('messages.Save')}}</button><br>
                        </form>

                        <form action="{{ route('editCSS', $id) }}" method="post">
                          @csrf
                          <div class="form-group col-lg-8">
                  <textarea style="display: none;" rows="9" type="text" name="custom_css" value="" class="form-control">
                  NULL
                  </textarea>
                          </div>
                          <button type="submit" class="btn btn-danger">{{__('messages.Reset to default')}}</button>
                        </form>

                  <br><br><div id="result" style="left: auto; position: relative; background-color:#2c2d3a; border-radius: 25px; width:100%; min-width:0; box-sizing:border-box; margin-inline:auto; max-width:950px; height:300px; box-shadow: 0 10px 20px -10px rgba(0,0,0, 0.6);">
                    <div style="position: relative; top: 50%; transform: translateY(-50%);">
                      <h2 align="center" style="color:white">{{__('messages.Result')}}</h2>
                        @if($custom_css === "" or $custom_css === "NULL" and $buttonId == 1)
                          <center><div style="--delay: 1s" class="button-entrance"><div class="button-demo button-custom button hvr-grow hvr-icon-wobble-vertical"><img class="icon-btn hvr-icon fa {{$custom_icon}}">{{ $title }}</div></div></center>
                          @elseif($custom_css === "" or $custom_css === "NULL" and $buttonId == 2)
                          <center><div style="--delay: 1s" class="button-entrance"><div class="button-custom_website button hvr-grow hvr-icon-wobble-vertical"><img class="wicon hvr-icon" src="@if(file_exists(base_path("assets/favicon/icons/").localIcon($id))){{url('assets/favicon/icons/'.localIcon($id))}}@else{{getFavIcon($id)}}@endif">{{ $title }}</div></div></center>
                          @elseif($custom_css != "" and $buttonId == 2)
                          <center><div style="--delay: 1s" class="button-entrance"><div style="{{ $custom_css }}" class="button-custom_website button hvr-grow hvr-icon-wobble-vertical"><img class="wicon hvr-icon" src="@if(file_exists(base_path("assets/favicon/icons/").localIcon($id))){{url('assets/favicon/icons/'.localIcon($id))}}@else{{getFavIcon($id)}}@endif">{{ $title }}</div></div></center>
                          @else
                          <center><div style="--delay: 1s" class="button-entrance"><div style="{{ $custom_css }}" class="button-demo hvr-grow hvr-icon-wobble-vertical"><i style="color: {{$custom_icon}}" class="icon-btn hvr-icon fa {{$custom_icon}}"></i>{{ $title }}</div></div></center>
                        @endif
                        </div>
                  </div>
                          <br><br>
                          @if($buttonId == 1)
                          <form action="{{ route('editCSS', $id) }}" method="post">
                          @csrf
                          <div class="form-group col-lg-8">
                            <h2>{{__('messages.Custom Icon')}}</h2>
                            <textarea aria-label="Ícone personalizado" id="textareabox" type="text" name="custom_icon" value="{{ $custom_icon }}" class="form-control" required>{{ $custom_icon }}</textarea>
                          </div>
                          <script type="text/javascript">
                          // Displays alert if custom icon doesn't contain string "fa-"
                          var textarea = document.getElementById('textareabox');
                          var word = 'fa-';
                          var textValue=textarea.value;
                          if (textValue.indexOf(word)==-1)
                            {
                             alert(@json(__('messages.Custom Alert')))
                            }
                            </script>

                          <details>
                          <p>{!!__('messages.cb.description.1-4')!!}</p>
                          <p>{!!__('messages.cb.description.2-4')!!}</p>
                          <p>{!!__('messages.cb.description.3-4')!!}</p>
                          <p>{!!__('messages.cb.description.4-4')!!}</p>
                          <br>
                          <div class="table-responsive">
                          <table class="table table-striped">
                          <thead>
                            <tr>
                              <th scope="col">{{__('messages.Style')}}</th>
                              <th scope="col">{{__('messages.Prefix')}}</th>
                              <th scope="col" style="max-width: 1%">{{__('messages.Icon')}}</th>
                              <th scope="col">{{__('messages.Short Code')}}</th>
                            </tr>
                          </thead>
                          <tbody>
                          <tr>
                              <td>{{__('messages.Regular')}}</td>
                              <td></td>
                              <td><i class="fa fa-user"></i></td>
                              <td><p>fa-user</p></td>
                            </tr>
                            <tr>
                              <td>{{__('messages.Brands')}}</td>
                              <td>fab</td>
                              <td><i class="fab fa-github"></i></td>
                              <td><p>fab fa-github</p></td>
                            </tr>
                            <tr>
                              <td>{{__('messages.Color')}}</td>
                              <td>{{__('messages.Color name')}};</td>
                              <td><i style="color: red;" class="fa fa-ghost"></i></td>
                              <td><p style="color: red;">red; fa-ghost</p></td>
                            </tr>
                            <tr>
                              <td>{{__('messages.Color HEX')}}</td>
                              <td>{{__('messages.Color HEX1')}};</td>
                              <td><i style="color: #1DA1F2;" class="fab fa-twitter"></i></td>
                              <td><p style="color: #1DA1F2;">#1DA1F2; fab fa-twitter</p></td>
                            </tr>
                          </tbody>
                          </table>
                        </div>
                          </details>

                          <br>
                          <button type="submit" class="btn btn-primary">{{__('messages.Update icon')}}</button>
                          <a class="btn btn-primary" href="https://fontawesome.com/search?m=free" target="_blank">{{__('messages.See all icons')}}</a>

                        </form><br><br><br><br>
                        @endif

                  </div>

                  <a class="btn btn-primary" href="{{ url('/studio/links') }}"><i class="bi bi-arrow-left-short"></i>{{__('messages.Back')}}</a>

                            </div>
                  </section>
<style>@media(max-width:600px){#color,.colorpicker{display:none!important}#button-color-hex{max-width:100%;margin-top:.35rem}} #result .button{max-width:100%;white-space:normal;height:auto;min-height:48px}</style>

                 </div>
             </div>
          </div>
       </div>
      </div>

    </div>
  </div>
@endsection
@endif
