<section class="text-gray-400">
  @if(session('success')) <p role="status">{{ session('success') }}</p> @endif
  @if($errors->any()) <div role="alert">{{ $errors->first() }}</div> @endif
  <form action="{{ route('editENV') }}" method="POST">
    @csrf
    @foreach(['APP_NAME' => 'Nome da aplicação', 'APP_LOCALE' => 'Idioma', 'LOG_LEVEL' => 'Nível de log'] as $key => $label)
      @if(\GeoSot\EnvEditor\Facades\EnvEditor::keyExists($key))
        <div class="mb-3">
          <label for="env-{{ $key }}">{{ $label }} ({{ $key }})</label>
          <input class="form-control" id="env-{{ $key }}" name="{{ $key }}" value="{{ env($key) }}" required>
        </div>
      @endif
    @endforeach
    <button class="btn btn-primary" type="submit">Salvar configurações permitidas</button>
  </form>
  <p class="mt-3">Outras variáveis, incluindo credenciais, permanecem preservadas e devem ser administradas pelo procedimento seguro do servidor.</p>
</section>
