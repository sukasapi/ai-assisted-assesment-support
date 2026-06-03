@extends('layouts.app')

@section('title', 'Ubah sesi — ' . config('app.name'))

@section('content')
    <x-ui.page-header title="Ubah sesi" :back-url="route('sesi-asesmen.show', $item)" back-label="Kembali" />
    @include('assessment-sessions._form', ['action' => route('sesi-asesmen.update', $item), 'method' => 'PUT', 'item' => $item])
@endsection
