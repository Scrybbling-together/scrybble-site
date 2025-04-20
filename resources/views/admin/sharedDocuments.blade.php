@extends('layouts.app')

<style>
    thead th {
        position: sticky;
        top: 0;
    }
</style>

@section('content')
    <div class="m-4">
        <h1>Shared documents</h1>
        <table class="table table-bordered table-striped">
            <thead>
            <tr>
                <th>Sync id</th>
                @if (Auth::user()->id === 1)
                <th>User</th>
                @endif
                <th>When</th>
                <th>Feedback</th>
                <th>Filename</th>
                <th>Generated output</th>
                <th>.rmn file</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($shared as $shared_file)
                <tr>
                    <td>{{$shared_file['id']}}</td>
                    @if (Auth::user()->id === 1)
                    <td>{{$shared_file['user_email']}}</td>
                    @else
                        <td>Must be logged in as an admin to view user e-mail</td>
                    @endif
                    <td>{{$shared_file['created_at']}}</td>
                    <td>{{$shared_file['feedback']}}</td>
                    <td>{{$shared_file['filename']}}</td>
                    <td>
                        @if($shared_file['output_href'])
                        <a href="{{$shared_file['output_href']}}">Download</a>
                        @else
                            Gone
                        @endif
                    </td>
                    <td>
                        @if($shared_file['input_href'])
                            <a href="{{ $shared_file['input_href'] }}">Download</a>
                        @else
                            Gone
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
