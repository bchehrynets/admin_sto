@extends('layouts.limitless')

@section('page_name')
Добавление скана паспорта
@endsection

@section('content')

{{-- Форма --}}
<form action="{{ url('/add_passport_scan_post/'.$employee->id) }}" method="POST" enctype="multipart/form-data">
    @csrf
    {{-- ID сотрудника --}}
    <input type="hidden" name="employee_id" value="{{ $employee->id }}">
    <div>
        {{ $employee->general_name}}
    </div>
    <div class="row">
        
        <div>
            <input type="file" name="scan">
        </div>

        <div>
            <button type="submit" class="btn btn-success">Загрузить</button>
        </div>
    </div>
</form>
{{-- Конец формы --}}

@endsection