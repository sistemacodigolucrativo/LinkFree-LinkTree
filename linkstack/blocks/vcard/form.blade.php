<?php use JeroenDesloovere\VCard\VCard; use App\Models\Button; $button = Button::find($button_id); if(isset($button->name)){$buttonName = $button->name;}else{$buttonName = 0;} ?>

<select style="display:none" name="button" class="form-control"><option class="button button-default email" value="vcard">{{__('messages.Vcard')}}</option></select>

@php
try {
$data = json_decode($link);

$prefix = $data->prefix;
$firstName = $data->first_name;
$middleName = $data->middle_name;
$lastName = $data->last_name;
$suffix = $data->suffix;
$organization = $data->organization;
$vtitle = $data->vtitle;
$role = $data->role;
$workUrl = $data->work_url;
$email = $data->email;
$workEmail = $data->work_email;
$homePhone = $data->home_phone;
$workPhone = $data->work_phone;
$cellPhone = $data->cell_phone;
$homeAddressLabel = $data->home_address_label;
$homeAddressStreet = $data->home_address_street;
$homeAddressCity = $data->home_address_city;
$homeAddressState = $data->home_address_state;
$homeAddressZip = $data->home_address_zip;
$homeAddressCountry = $data->home_address_country;
$workAddressLabel = $data->work_address_label;
$workAddressStreet = $data->work_address_street;
$workAddressCity = $data->work_address_city;
$workAddressState = $data->work_address_state;
$workAddressZip = $data->work_address_zip;
$workAddressCountry = $data->work_address_country;
}
catch (exception $e) {}
@endphp

<label for="field-link_title" class='form-label'>{{__('messages.Custom Title')}}</label>
<input type='text' name='link_title' value='{{ $title }}' class='form-control' id="field-link_title" />
<span class='small text-muted'>{{__('messages.Leave blank for default title')}}</span><br>

{{-- <br><h5>Upload existing file</h5>
<div class="form-group col-lg-8">
    <label for="field-vcard">Vcard</label>
    <input type="file" accept="text/vcard" class="form-control-file" name="vcard" id="field-vcard">
</div> --}}

<br><br><h4>{{__('messages.Name')}}</h4>
<label for="field-prefix" class='form-label'>{{__('messages.Prefix')}}</label>
<input type='text' name='prefix' value='{{$prefix ?? ''}}' class='form-control'id="field-prefix" />
<br>
<label for="field-first_name" class='form-label'>{{__('messages.First Name')}}</label>
<input type='text' name='first_name' value='{{$firstName ?? ''}}' class='form-control'id="field-first_name" />
<br>
<label for="field-middle_name" class='form-label'>{{__('messages.Middle Name')}}</label>
<input type='text' name='middle_name' value='{{$middleName ?? ''}}' class='form-control'id="field-middle_name" />
<br>
<label for="field-last_name" class='form-label'>{{__('messages.Last Name')}}</label>
<input type='text' name='last_name' value='{{$lastName ?? ''}}' class='form-control'id="field-last_name" />
<br>
<label for="field-suffix" class='form-label'>{{__('messages.Suffix')}}</label>
<input type='text' name='suffix' value='{{$suffix ?? ''}}' class='form-control'id="field-suffix" />
<br>
{{-- <label for="field-nickname" class='form-label'>Nickname</label>
<input type='text' name='nickname' value='{{ ?? ''}}' class='form-control'id="field-nickname" />
<br> --}}

<br><h4>{{__('messages.Work')}}</h4>
<label for="field-organization" class='form-label'>{{__('messages.Organization')}}</label>
<input type='text' name='organization' value='{{$organization ?? ''}}' class='form-control'id="field-organization" />
<br>
<label for="field-vtitle" class='form-label'>{{__('messages.Title')}}</label>
<input type='text' name='vtitle' value='{{$vtitle ?? ''}}' class='form-control'id="field-vtitle" />
<br>
<label for="field-role" class='form-label'>{{__('messages.Role')}}</label>
<input type='text' name='role' value='{{$role ?? ''}}' class='form-control'id="field-role" />
<br>
<label for="field-work_url" class='form-label'>{{__('messages.Work URL')}}</label>
<input type='url' name='work_url' value='{{$workUrl ?? ''}}' class='form-control'id="field-work_url" />
<br>

<br><h4>{{__('messages.Emails')}}</h4>
<label for="field-email" class='form-label'>{{__('messages.Email')}}</label>
<input type='email' name='email' value='{{$email ?? ''}}' class='form-control'id="field-email" />
<span class='small text-muted'>{{__('messages.Enter your personal email')}}</span>
<br>
<label for="field-work_email" class='form-label'>{{__('messages.Work Email')}}</label>
<input type='email' name='work_email' value='{{$workEmail ?? ''}}' class='form-control'id="field-work_email" />
<span class='small text-muted'>{{__('messages.Enter your work email')}}</span>
<br>

<br><h4>{{__('messages.Phones')}}</h4>
<label for="field-home_phone" class='form-label'>{{__('messages.Home Phone')}}</label>
<input type='tel' name='home_phone' value='{{$homePhone ?? ''}}' class='form-control'id="field-home_phone" />
<br>
<label for="field-work_phone" class='form-label'>{{__('messages.Work Phone')}}</label>
<input type='tel' name='work_phone' value='{{$workPhone ?? ''}}' class='form-control'id="field-work_phone" />
<br>
<label for="field-cell_phone" class='form-label'>{{__('messages.Cell Phone')}}</label>
<input type='tel' name='cell_phone' value='{{$cellPhone ?? ''}}' class='form-control'id="field-cell_phone" />
<br>

<br><h4>Home Address</h4>
<label for="field-home_address_label" class='form-label'>{{__('messages.Label')}}</label>
<input type='text' name='home_address_label' value='{{$homeAddressLabel ?? ''}}' class='form-control'id="field-home_address_label" />
<br>
<label for="field-home_address_street" class='form-label'>{{__('messages.Street')}}</label>
<input type='text' name='home_address_street' value='{{$homeAddressStreet ?? ''}}' class='form-control'id="field-home_address_street" />
<br>
<label for="field-home_address_city" class='form-label'>{{__('messages.City')}}</label>
<input type='text' name='home_address_city' value='{{$homeAddressCity ?? ''}}' class='form-control'id="field-home_address_city" />
<br>
<label for="field-home_address_state" class='form-label'>{{__('messages.State/Province')}}</label>
<input type='text' name='home_address_state' value='{{$homeAddressState ?? ''}}' class='form-control'id="field-home_address_state" />
<br>
<label for="field-home_address_zip" class='form-label'>{{__('messages.Zip/Postal Code')}}</label>
<input type='text' name='home_address_zip' value='{{$homeAddressZip ?? ''}}' class='form-control'id="field-home_address_zip" />
<br>
<label for="field-home_address_country" class='form-label'>{{__('messages.Country')}}</label>
<input type='text' name='home_address_country' value='{{$homeAddressCountry ?? ''}}' class='form-control'id="field-home_address_country" />
<br>
<br><h4>{{__('messages.Work Address')}}</h4>
<label for="field-work_address_label" class='form-label'>{{__('messages.Label')}}</label>
<input type='text' name='work_address_label' value='{{$workAddressLabel ?? ''}}' class='form-control'id="field-work_address_label" />
<br>
<label for="field-work_address_street" class='form-label'>{{__('messages.Street')}}</label>
<input type='text' name='work_address_street' value='{{$workAddressStreet ?? ''}}' class='form-control'id="field-work_address_street" />
<br>
<label for="field-work_address_city" class='form-label'>{{__('messages.City')}}</label>
<input type='text' name='work_address_city' value='{{$workAddressCity ?? ''}}' class='form-control'id="field-work_address_city" />
<br>
<label for="field-work_address_state" class='form-label'>{{__('messages.State/Province')}}</label>
<input type='text' name='work_address_state' value='{{$workAddressState ?? ''}}' class='form-control'id="field-work_address_state" />
<br>
<label for="field-work_address_zip" class='form-label'>{{__('messages.Zip/Postal Code')}}</label>
<input type='text' name='work_address_zip' value='{{$workAddressZip ?? ''}}' class='form-control'id="field-work_address_zip" />
<br>
<label for="field-work_address_country" class='form-label'>{{__('messages.Country')}}</label>
<input type='text' name='work_address_country' value='{{$workAddressCountry ?? ''}}' class='form-control'id="field-work_address_country" />
<br>
