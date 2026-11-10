@extends('layouts.inner')

@section('content')
    @forelse ($result->items as $item)
        <p>{{ $item['name'] }} — {{ $item['email'] }}</p>
    @empty
        <p>@lang('DummySlug::index.no_records')</p>
    @endforelse
@endsection
