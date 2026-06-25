@extends('layouts.app')

@php
/** @var array $heads */
/** @var array $tails */
@endphp

@section('content')
<div class="row">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white fw-bold text-center">TẦN SUẤT ĐẦU (0-9)</div>
            <ul class="list-group list-group-flush">
                @foreach($heads as $key => $count)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="fs-5 fw-bold">Đầu {{ $key }}</span>
                        <span class="badge bg-primary rounded-pill">{{ $count }} lần</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white fw-bold text-center">TẦN SUẤT ĐUÔI (0-9)</div>
            <ul class="list-group list-group-flush">
                @foreach($tails as $key => $count)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="fs-5 fw-bold">Đuôi {{ $key }}</span>
                        <span class="badge bg-success rounded-pill">{{ $count }} lần</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</div>
@endsection