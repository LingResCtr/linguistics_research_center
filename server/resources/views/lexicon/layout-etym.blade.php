@extends('lexicon.layout')

@section('search-item-list')
    @foreach (\App\Services\Lexicon\SidebarSorter::byEntry($lexicon->etyma) as $etymon)
        <li data-sidebar-id="{{$etymon->id}}"><sup>*</sup><a href="/lexicon/{{$lexicon->slug}}/etymon/{{$etymon->id}}">{!! $etymon->entry !!} @homograph_number($etymon->homograph_number)</a></li>
    @endforeach
@endsection

@section('sidebar')
    @include('lexicon.layout-sidebar', ['search_types'=>['headword', 'category']])
@endsection
