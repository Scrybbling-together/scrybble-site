<header>
    <nav class="navbar navbar-light navbar-expand-md shadow-sm mb-4 bg-secondary">
        <div class="container-fluid">
            <a href="/" class="navbar-brand text-decoration-none">Scrybble</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarText"
                    aria-controls="navbarText" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarText">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a href="/about" class="nav-link">About</a>
                    </li>
                    <li class="nav-item">
                        <a href="/roadmap" class="nav-link">Roadmap</a>
                    </li>
                    <li class="nav-item">
                        <a href="/support" class="nav-link">Support</a>
                    </li>
                    <li class="mx-2">
                        <hr class="vr">
                    </li>
                    <li class="nav-item" >
                        <a href="/news" class="nav-link">News</a>
                    </li>
                    <li class="nav-item" >
                        <a href="/blog" class="nav-link">Blog</a>
                    </li>
                </ul>

                <ul class="navbar-nav">
                    @auth
                        <li class="nav-item">
                            <a href="/profile" class="nav-link">{{ auth()->user()->name }}</a>
                        </li>
                        <li class="nav-item">
                            <form action="{{ route('logout') }}" method="POST" style="display:inline;">
                                @csrf
                                <button type="submit" class="nav-link btn btn-link">Logout</button>
                            </form>
                        </li>
                    @endauth
                    @guest
                        <li class="nav-item">
                            <a href="{{ route('login') }}" class="nav-link">Login</a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('register') }}" class="nav-link">Register</a>
                        </li>
                    @endguest
                </ul>
            </div>
        </div>
    </nav>
</header>
