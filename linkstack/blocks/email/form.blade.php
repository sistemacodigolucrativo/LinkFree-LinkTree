<?php use App\Models\Button; $button = Button::find($button_id); if(isset($button->name)){$buttonName = $button->name;}else{$buttonName = 0;} ?>

<select style="display:none" name="button" class="form-control"><option class="button button-default email" value="default email">{{__('messages.Default Email')}}</option></select>

<label for="field-title" class='form-label'>{{__('messages.Custom Title')}}</label>
<input type='text' name='title' value='{{$title}}' class='form-control' id="field-title" />
<span class='small text-muted'>{{__('messages.Leave blank for default title')}}</span><br>

<label for="field-link" class='form-label'>{{__('messages.E-Mail address')}}</label>
<input type='email' name='link' value='{{str_replace("mailto:", "", $link)}}' class='form-control' required id="field-link" />
<span class='small text-muted'>{{__('messages.Enter your E-Mail')}}</span>
