<label for="field-title" class='form-label'>{{__('messages.Title')}}</label>
<input type='text' name='title' value='{{$title}}' placeholder="Leave blank for default video title" class='form-control' id="field-title" />

<label for="field-link" class='form-label'>{{__('messages.URL')}}</label>
<input type='url' name='link' value='{{$link}}' class='form-control' id="field-link" />
<span class='small text-muted'>{{__('messages.URL to the video')}}</span>
