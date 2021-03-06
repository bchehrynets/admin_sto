@extends('layouts.basic_bootstrap_layout')

@section('content')
    {{-- Если клиент задан изначально--}}
    @if(!empty($client))
        <h2>Добавить машину клиента: {{ $client->general_name }}</h2>

        {{-- Форма добавления машины для клиента --}}
        <form action="{{ url('admin/cars_in_service/add') }}" method="POST">
            @csrf
            {{-- ID клиента --}}
            <input type="hidden" name="client_id" value="{{ $client->id }}">

            <div class="form-group">
                <label>Название</label>
                <input class="form-control" type="text" name="car_general_name">
            </div>
            
            <button type="submit" class="btn btn-primary">
                Сохранить
            </button>
        </form>
        <hr>
        {{-- Вернуться в карточку клиента --}}
        <a href="{{ url('/admin/view_client/'.$client->id) }}">
            <div class="btn btn-secondary">
                Вернуться
            </div>
        </a>
    
    @else
        {{-- Если клиент НЕ задан --}}
        {{-- и если база клиентов не пустая--}}
        @if(!empty($clients))

        {{-- Форма добавления машины с выбором клиента --}}
        <h2>Форма добавления авто</h2>
        <form action="{{ url('admin/cars_in_service/add') }}" method="POST">
            @csrf
            
                {{-- Выбор клиента --}}
                <div class="form-group">
                    <label>Выберите клиента</label>
                    <select class="form-control" name="client_id">
                        @foreach($clients as $client)
                            <option value="{{ $client->id }}">{{ $client->general_name }}</option>
                        @endforeach

                    </select>
                </div>

                <p>Если вашего клиента нет в списке, пожалуйста, добавьте сначала нового клиента. Это можно сделать, нажав на вот эту кнопку:
                    
                </p>
                {{-- Добавить клиента : переход на страницу --}}
                <a href="{{ url('admin/add_client') }}">
                    <div class="btn btn-secondary">
                        Добавить клиента
                    </div>
                </a>
                <hr>
                {{-- Название машины --}}
                <div class="form-group">
                    <label>Название машины</label>
                    <input type="text" name="car_general_name" class="form-control">
                </div>

                <button type="submit" class="btn btn-success">
                    Добавить авто
                </button>
                
            </form>
        @else
        {{-- Если клиентов нет --}}
            <p>На данный момент в базе нет клиентов. Пожалуйста, добавьте сначала нового клиента. Это можно сделать, нажав на вот эту кнопку:</p>
               {{-- Добавить клиента : переход на страницу --}}
               <a href="{{ url('admin/add_client') }}">
                    <div class="btn btn-secondary">
                        Добавить клиента
                    </div>
                </a>
        @endif {{-- Конец условия проверки пустая ли база клиентов--}}
            


        

    @endif




    


    
@endsection