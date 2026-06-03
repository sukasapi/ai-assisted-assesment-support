@extends('layouts.app')

@section('title', 'Buat sesi — ' . config('app.name'))

@section('content')
    <x-ui.page-header title="Buat sesi assessment" :back-url="route('sesi-asesmen.index')" back-label="Kembali" />
    @include('assessment-sessions._form', ['action' => route('sesi-asesmen.store'), 'method' => 'POST', 'item' => null])
@endsection
