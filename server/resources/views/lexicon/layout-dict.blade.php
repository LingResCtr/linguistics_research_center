@extends('lexicon.layout')

@section('search-item-list')
    @foreach (\App\Services\Lexicon\SidebarSorter::byEntries($language->reflexes) as $reflex)
        <li data-sidebar-id="{{$reflex->id}}"><a href="/lexicon/{{$lexicon->slug}}/word/{{$reflex->id}}">{{$reflex->getEntriesCSV()}}</a></li>
    @endforeach
@endsection

@section('sidebar')
    @include('lexicon.layout-sidebar', ['search_types'=>['headword', 'category']])
@endsection
