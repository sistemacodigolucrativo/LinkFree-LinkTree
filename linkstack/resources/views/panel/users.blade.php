@extends('layouts.sidebar')
@section('mobile-navbar-title'){{ __('messages.Manage Users') }}@endsection
@section('content')
<style>
  [x-cloak] {display:none!important}
  .lf-users-screen{max-width:100%;min-width:0;margin-top:0!important;padding:clamp(12px,2vw,24px)!important}
  .lf-users-screen :is(.card,.table-responsive){min-width:0}
  .lf-vip-switch{padding:12px 15px;background:var(--lf-raised);border:1px solid var(--lf-border)}
  .lf-vip-switch-copy{min-width:0;flex:1 1 195px}
  .lf-vip-switch-copy strong{font-size:.91rem;line-height:1.3}
  .lf-vip-switch-copy small{font-size:.8rem;line-height:1.4}
  .lf-vip-switch .form-switch{flex:0 0 auto}
  .lf-vip-switch .form-check-input{width:2.35rem;height:1.25rem;cursor:pointer}
  .lf-users-search{background:var(--lf-raised);border:1px solid var(--lf-border);border-radius:14px;padding:12px}
  .lf-users-results-card{border:1px solid var(--lf-border);background:var(--lf-surface)}
  .lf-users-add-icon{display:inline-flex;align-items:center;justify-content:center;
    width:38px;height:38px;min-width:38px;border-radius:10px;
    color:#127638!important;background:#e7f7eb;border:1px solid #82c99c;font-size:1.13rem}
  .lf-users-add-icon:hover,.lf-users-add-icon:focus-visible{background:#c8eed5;color:#065324!important}
  .lf-users-results-footer{margin-top:12px;padding-top:9px;border-top:1px solid var(--lf-border)}
  .lf-users-footer-tools{justify-content:flex-end}
  .lf-users-perpage{width:auto;min-width:86px}
  .lf-users-columns-menu{min-width:min(90vw,280px);max-height:min(55vh,350px);overflow:auto;
    background:var(--lf-surface);color:var(--lf-text);border:1px solid var(--lf-border)}
  .user-block .badge.bg-success,.user-email .badge.bg-success{color:#09140b!important;font-weight:700}
  @media(max-width:767.98px){
    .lf-users-screen{padding:10px!important}
    .lf-vip-switch{padding:11px}
    .lf-vip-switch-copy{flex-basis:100%}
    .lf-users-results-card .card-body{padding:12px!important}
    .lf-users-footer-tools{justify-content:flex-start}
  }
</style>

<div class="container-fluid content-inner lf-users-screen">
  <h1 class="h4 mb-3 d-none d-lg-block"><i class="bi bi-person" aria-hidden="true"></i> {{ __('messages.Manage Users') }}</h1>
  @if(session('success'))<div class="alert alert-success" role="status">{{ session('success') }}</div>@endif
  @if(session('fail'))<div class="alert alert-danger" role="alert">{{ session('fail') }}</div>@endif

  <form id="auto-vip-registration-form" method="POST" action="{{ route('admin.registration-vip') }}"
        class="lf-vip-switch d-flex flex-wrap align-items-center justify-content-between gap-2 rounded-3 mb-3"
        aria-label="Configuração de novos cadastros VIP">
    @csrf
    <div class="lf-vip-switch-copy">
      <strong class="d-block">Novos cadastros VIP</strong>
      <small class="text-muted d-block">Conceder VIP automaticamente a novas contas.</small>
    </div>
    <div class="form-check form-switch d-flex align-items-center gap-2 m-0">
      <input type="hidden" name="enabled" value="0">
      <input class="form-check-input m-0" type="checkbox" role="switch"
             id="auto-vip-new-users" name="enabled" value="1" @checked($autoVipNewUsers)
             aria-label="Ativar VIP para novos cadastros" onchange="this.form.requestSubmit()">
      <label for="auto-vip-new-users" class="form-check-label fw-semibold">
        {{ $autoVipNewUsers ? 'Ativado' : 'Desativado' }}
      </label>
    </div>
  </form>

  <div id="select-active" class="d-none" aria-hidden="true">
    <h2 class="h5">Ação selecionada</h2>
  </div>
  <p id="user-action-status" role="status" aria-live="polite"></p>
  <livewire:user-table />
</div>
<script type="text/javascript">
function labelLivewirePerPage() {
  const select = document.getElementById('perPage');
  if (select && !select.getAttribute('aria-label')) select.setAttribute('aria-label','Registros por página');
}
document.addEventListener('DOMContentLoaded',labelLivewirePerPage);
document.addEventListener('livewire:load',function(){
  labelLivewirePerPage();
  if (window.Livewire && typeof Livewire.hook === 'function') Livewire.hook('message.processed',labelLivewirePerPage);
});
document.addEventListener('click', function(e) {
 const button=e.target.closest('.confirmation,.user-email,.user-block');
 if(!button) return;
 e.preventDefault();
 if(button.disabled) return;
 const deleting=button.classList.contains('confirmation');
 if(deleting && !confirm(@json(__('messages.confirm.delete.user')))) return;
 const original=button.innerHTML;
 button.disabled=true;button.setAttribute('aria-busy','true');button.textContent='…';
 const xhr=new XMLHttpRequest();
 const id=button.getAttribute('data-id');
 xhr.open('POST',deleting?@json(route('deleteTableUser',['id'=>':id'])).replace(':id',id):id,true);
 xhr.timeout=20000;
 xhr.setRequestHeader('X-CSRF-TOKEN',@json(csrf_token()));
 xhr.setRequestHeader('Accept','application/json');
 xhr.setRequestHeader('Content-Type','application/json');
 const failed=()=>{button.disabled=false;button.removeAttribute('aria-busy');button.innerHTML=original;document.getElementById('user-action-status').textContent='Não foi possível concluir a ação. Verifique a conexão e tente novamente.';};
 xhr.onload=()=>{if(xhr.status>=200 && xhr.status<300 && !xhr.responseURL.includes('/login')) {button.disabled=false;button.innerHTML=original;button.removeAttribute('aria-busy');Livewire.components.getComponentsByName('user-table')[0].$wire.$refresh();} else failed();};
 xhr.onerror=failed;xhr.ontimeout=failed;
 xhr.send(deleting?JSON.stringify({id}):null);
});
</script>

@push('sidebar-stylesheets')
<script defer src="{{ url('assets/js/cdn.min.js') }}"></script>
<script src="{{ url('vendor/livewire/livewire/dist/livewire.js') }}"></script>
@endpush
@push('sidebar-scripts')
<livewire:scripts />
<script src="{{ url('assets/js/livewire-sortable.js') }}"></script>
@endpush
@endsection
