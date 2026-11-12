@extends('errors.layout')
@section('code')
    <x-icon name="construction" class="mx-auto size-16" />
@endsection
@section('title', 'Wartungsarbeiten')
@section('message', $message)
@section('actions')
    <a href="{{ route('login') }}" class="btn btn-muted">Admin-Login</a>
@endsection
