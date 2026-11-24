<style>
.lf-impersonation-bar{position:fixed;top:0;left:0;width:100%;min-height:60px;background:#4d4c51;color:#fff;z-index:911;display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 16px;box-shadow:0 1px 2px rgba(0,0,0,.1)}
.lf-impersonation-bar a{color:#fff;overflow-wrap:anywhere}
.lf-impersonation-bar button{color:#fff;background:transparent;border:1px solid #fff;border-radius:6px;padding:8px;cursor:pointer}
body{padding-top:60px}
</style>
<div class="lf-impersonation-bar" role="region" aria-label="Sessão administrativa de impersonação">
  <a href="{{ url('dashboard') }}">{{ $member->name }}</a>
  <form method="POST" action="{{ route('authAs') }}" class="m-0">
    @csrf
    <button type="submit">Voltar ao administrador</button>
  </form>
</div>
