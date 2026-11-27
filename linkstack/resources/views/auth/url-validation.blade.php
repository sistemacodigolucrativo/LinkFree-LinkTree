@if(!empty($registrationHandleValidation))
<script>{!! file_get_contents(base_path("assets/js/jquery.min.js")) !!}</script>
<script>
$(function () {
  const input = $('#littlelink_name'), message = $('#username-error'), submit = $('#submit-btn');
  const invalid = @json(__('registration.invalid', ['field' => __('messages.Page URL')]));
  const taken = @json(__('registration.unique', ['field' => __('messages.Page URL')]));
  let request = null, sequence = 0, timer = null;
  input.on('input', function () {
    const value = input.val(), current = ++sequence;
    clearTimeout(timer);
    if (request) request.abort();
    input.removeClass('is-valid is-invalid').removeAttr('aria-invalid');
    message.text('').prop('hidden', true);
    submit.prop('disabled', false);
    if (!value.trim()) return;
    timer = setTimeout(function () {
      request = $.ajax({type:'POST', url:@json(url('/validate-handle')),
        data:{_token:@json(csrf_token()), littlelink_name:value}})
        .done(function (data) {
          if (current !== sequence || input.val() !== value) return;
          if (data.valid) { input.addClass('is-valid'); return; }
          input.addClass('is-invalid').attr('aria-invalid','true');
          message.text(data.reason === 'unique' ? taken : invalid).prop('hidden',false);
          submit.prop('disabled',true);
        });
    }, 250);
  });
});
</script>
@else
<script>{!! file_get_contents(base_path("assets/js/jquery.min.js")) !!}</script>
<script>
    $(document).ready(function () {
      var submitBtn = $('#submit-btn');
      
      $('#littlelink_name').on('keyup', function () {
        var littlelinkName = $(this).val();
  
        if (littlelinkName.trim() !== '') {
          $.ajax({
            type: 'POST',
            url: '{{url("/validate-handle")}}',
            data: {
              '_token': '{{ csrf_token() }}',
              'littlelink_name': littlelinkName
            },
            success: function (data) {
              $('#littlelink_name').removeClass('is-valid is-invalid');
              $('#username-error').remove();
  
              if (typeof exceptionvar !== 'undefined' && littlelinkName.trim() === exceptionvar) {
                submitBtn.prop('disabled', false);
              } else {
                if (data.valid) {
                  $('#littlelink_name').addClass('is-valid');
                  submitBtn.prop('disabled', false);
                } else {
                  $('#littlelink_name').addClass('is-invalid');
                  $('<div id="username-error" class="invalid-feedback">That username is already taken</div>').insertAfter('#littlelink_name');
                  submitBtn.prop('disabled', true);
                }
              }
            }
          });
        } else {
          $('#littlelink_name').removeClass('is-valid is-invalid');
          $('#username-error').remove();
          submitBtn.prop('disabled', true);
        }
      });
    });
  </script>
@endif
