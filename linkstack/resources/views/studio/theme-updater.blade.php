@if(auth()->check() && auth()->user()->role === 'admin')
<section class="border rounded p-3" aria-label="Atualizações de temas GitHub">
  <h3 class="h5">Temas GitHub</h3>
  <p class="small text-muted">A verificação de atualizações utiliza somente o repositório GitHub configurado.</p>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-primary" href="{{ route('githubThemes') }}">Selecionar temas GitHub</a>
    <a class="btn btn-outline-primary" href="{{ route('githubThemes', ['updates' => 1]) }}">Verificar atualizações</a>
  </div>
</section>
@endif
