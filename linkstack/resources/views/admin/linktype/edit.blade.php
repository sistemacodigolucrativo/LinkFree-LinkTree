@extends('layouts.sidebar')
@push('sidebar-stylesheets')
<link rel="stylesheet" href="{{ asset('assets/css/linkfree-admin-management.css') }}?v={{ filemtime(base_path('assets/css/linkfree-admin-management.css')) }}">
@endpush

@section('content')
<div class="container-fluid content-inner py-2 lf-block-types-page">

<div class="card lf-type-form rounded-4">
    <div class='card-header'>
        <h4 class='card-title'>Editar tipo de bloco</h4>
    </div>
    <div class="card-body p-3 p-md-4">

        <!-- if there are creation errors, they will show here -->
        @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif



        <form action='/admin/linktype/{{$linktype->id}}' method="POST">
            @method('PUT')

            @csrf



            <div class="form-group">
                <label for="icon">Ícone</label>
                <input type='text' value="{{$linktype->icon}}" name='icon' class="form-control @error('icon') is-invalid @enderror">
                <a href="https://icons.getbootstrap.com/" target="_blank">View Icons Here</a>

                @error('icon')
                    <span class="text-danger d-blcok">{{ $message }}</span>
                @enderror
            </div>


            <div class="form-group">
                <label for="typename">Identificador curto</label>
                <input type='text' value="{{$linktype->typename}}" name='typename' readonly class="form-control @error('typename') is-invalid @enderror">


                @error('typename')
                    <span class="text-danger">{{ $message }}</span>
                @enderror

            </div>

            <div class="form-group">
                <label for="field-title">Título</label>
                <input type='text' name='title' value="{{$linktype->title}}" class='form-control @error("title") is-invalid @enderror'>

                @error('title')
                    <span class="text-danger">{{ $message }}</span>
                @enderror

            </div>

            <div class="form-group">
                <label for="field-description">Descrição</label>
                <textarea name='description' class="form-control @error('description') is-invalid @enderror" rows='3' id="field-description">{{$linktype->description}}</textarea>



                @error('description')
                    <span class="text-danger">{{ $message }}</span>
                @enderror

            </div>

            <div class="form-group">
                <label for="field-params">Parâmetros</label>
                <textarea name='params' class="form-control  @error('params') is-invalid @enderror" rows='20' id="field-params">{{$linktype->params}}</textarea>



                @error('params')
                    <span class="text-danger">{{ $message }}</span>
                @enderror

            </div>


            <div>
                <button type="submit" class="btn btn-primary">Salvar</button>

            </div>
        </form>
    </div>
</div>
</div>
@endsection
