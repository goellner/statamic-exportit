@extends('layout')

@section('content')
    <div class="card">
        <a href="{{ route('exportit.download') }}"  type="button" class="btn btn-default">Export</a>
    </div>
@endsection
