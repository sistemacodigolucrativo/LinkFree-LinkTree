<label for="predefined-button" class='form-label'>{{__('messages.Select a predefined site')}}</label>
<?php use App\Models\Button; $button = Button::find($button_id); if(isset($button->name)){$buttonName = $button->name;}else{$buttonName = 0;} ?>

<select id='predefined-button' name='button' class='form-control'>
        @if($buttonName != 0)<option value='{{$buttonName}}'>{{ucfirst($buttonName)}}</option>@endif
    @foreach ($buttons as $b)
        @if($b["exclude"] != true)
        <option class='button button-{{$b["name"]}}' value='{{$b["name"]}}' {{ $b["selected"] == true ? "selected" : ""}}>{{$b["title"]}}</option>
        @endif
    @endforeach
</select>

<label for='field-title' class='form-label'>{{__('messages.Custom Title')}}</label>
<input type='text' name='title' value='{{$title}}' class='form-control' id="field-title" />
<span class='small text-muted'>{{__('messages.Leave blank for default title')}}</span><br>

<label for="field-link" class='form-label'>{{__('messages.URL')}}</label>
<input type='url' name='link' value='{{$link}}' class='form-control' required id="field-link" />
<span class='small text-muted'>{{__('messages.Enter the link URL')}}</span>
