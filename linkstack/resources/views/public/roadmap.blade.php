@extends('layouts.public-marketing')

@section('page-title', 'Próximas implementações')
@section('page-description', 'Acompanhe as novidades do LinkFree. Somente ferramentas futuras confirmadas entram nesta página, sem promessas de prazo ou divulgação de recursos já disponíveis.')

@section('content')
<section class="lfp-hero">
  <div class="lfp-container">
    <span class="lfp-kicker">Evolução do produto</span>
    <h1 class="lfp-roadmap-heading">Próximas <em>implementações.</em></h1>
    <p class="lfp-intro">O LinkFree evolui com foco na experiência de quem cria, organiza e compartilha sua presença digital. Esta página apresenta apenas novas ferramentas ainda não disponibilizadas e com escopo confirmado.</p>
    <div class="lfp-actions">
      <a class="lfp-pill ghost" href="{{ route('public.features') }}">Conhecer os recursos disponíveis</a>
    </div>
    <p class="lfp-note">Sem datas inventadas, sem promessas prematuras e sem anunciar novamente funcionalidades já entregues.</p>
  </div>
</section>
<div class="lfp-container">
  @php $nextFeatures = \App\Services\PublicFeatureCatalog::upcoming(); @endphp
  @if(count($nextFeatures) > 0)
    <section class="lfp-section" aria-labelledby="roadmap-list-title">
      <div class="lfp-section-head">
        <span class="lfp-kicker">Em planejamento</span>
        <h2 id="roadmap-list-title">O que está no horizonte</h2>
        <p>Itens apresentados após confirmação do escopo, sujeitos a mudanças de prioridade durante o desenvolvimento.</p>
      </div>
      <div class="lfp-grid">
        @foreach($nextFeatures as $feature)
          <article class="lfp-card">
            <h3>{{ $feature['title'] }}</h3>
            <p>{{ $feature['description'] }}</p>
            <span class="lfp-feature-tag">{{ $feature['status'] ?? 'Em planejamento' }}</span>
          </article>
        @endforeach
      </div>
    </section>
  @else
    <section class="lfp-empty" aria-labelledby="roadmap-empty-title">
      <div class="lfp-symbol" aria-hidden="true">✦</div>
      <span class="lfp-kicker">Planejamento responsável</span>
      <h2 id="roadmap-empty-title">Nenhuma nova ferramenta anunciada no momento.</h2>
      <p>As propostas anteriores já contempladas no produto não entram nesta lista. As demais ainda não possuem definição suficiente para uma divulgação responsável.</p>
      <p>Quando uma nova ferramenta tiver escopo aprovado e ainda não estiver disponível, seus detalhes poderão ser apresentados aqui — sem confundir planejamento com entrega.</p>
      <div class="lfp-actions" style="justify-content:center">
        <a class="lfp-pill" href="{{ route('public.features') }}">Ver o que já posso usar</a>
      </div>
    </section>
  @endif
</div>
@endsection
