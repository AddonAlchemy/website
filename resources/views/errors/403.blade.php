@extends('errors.layout')
@section('code', '403')
@section('title', 'Kein Zugriff')
@section('message', $exception->getMessage() ?: 'Du hast keine Berechtigung, diese Seite aufzurufen.')
