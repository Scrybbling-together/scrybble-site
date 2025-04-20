<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Dashboard') }}
        </h2>
    </x-slot>

    @section('content')
        <div id="root" class="h-100 w-100"></div>
        <style>
            a {
                text-decoration: none;
                font-weight: bold;
            }
        </style>
        <div class="page-centering-container">
            @if($gumroadLicenseValid)
                @if ($isRmApiAuthenticated)
                    <x-remarkable-tree :currentWorkingDirectory="$currentWorkingDirectory" :ls="$ls"/>
                @else
                    <x-one-time-code/>
                @endif
            @else
                <div class="card" style="width: 32rem">
                    <div class="card-header">
                        <span class="fs-4">Connect your gumroad license <span class="fs-5 text-muted">(step 1/2)</span></span>
                    </div>
                    <div class="card-body">
                        <div>
                            <h2></h2>
                            @if ($errors->any())
                                <div class="alert alert-danger">
                                    <ul class="list-unstyled" style="margin-bottom: 0">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <form method="POST" action="/connect-license">
                                @csrf
                                <div class="input-group">
                                    <input class="form-control input-group-text" required
                                           name="license" type="text" placeholder="Your license" value="{{old('license')}}">
                                    <button class="btn btn-primary" type="submit">Submit</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    @endsection


</x-app-layout>
