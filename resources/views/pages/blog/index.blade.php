@extends('layouts.app')

@section('head')
    <meta name="description"
          content="Deep dives into how Scrybble works — coordinate systems, rendering, file formats, and the behind-the-scenes engineering of reMarkable integration.">
    <meta name="keywords" content="blog, explorable explanations, reMarkable, coordinate systems, annotations, Scrybble">

    <!-- Open Graph -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="Scrybble Blog">
    <meta property="og:description" content="Explorable explanations of how reMarkable and Scrybble work under the hood.">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary">
    <meta name="twitter:title" content="Scrybble Blog">
    <meta name="twitter:description" content="Explorable explanations of how reMarkable and Scrybble work under the hood.">

    <link rel="canonical" href="{{ url()->current() }}">
@endsection

@section('content')
    <!-- Hero Section -->
    <section class="bg-secondary-subtle p-3 p-lg-5">
        <div class="container">
            <div class="text-center">
                <h1 class="display-2 fw-bolder mb-4">Blog</h1>
                <p class="fs-5 text-muted mx-auto" style="max-width: 40rem;">
                    Explorable explanations of how reMarkable works under the hood — coordinate systems, rendering, file formats, and the engineering behind Scrybble.
                </p>
            </div>
        </div>
    </section>

    <section class="p-3 p-lg-5">
        <div class="container">
            <div class="d-flex flex-column gap-4" style="max-width: 40rem; margin: 0 auto;">

                <!-- Post: Understanding the reMarkable coordinate system -->
                <article class="card shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <div class="mb-2">
                            <span class="badge bg-primary">Explorable Explanation</span>
                        </div>

                        <h3 class="card-title h4">
                            <a href="{{ route('blog.show', 'understanding-remarkable-coordinate-system') }}"
                               class="text-decoration-none text-dark">
                                Understanding the reMarkable coordinate system
                            </a>
                        </h3>

                        <p class="card-text text-muted flex-grow-1">
                            reMarkable's coordinate system is a little different from what you're used to. In this interactive explorable explanation, we look at how the reMarkable coordinate system works, why the origin is in the horizontal center, and how it powers zooming, panning, and cross-device notebook scaling.
                        </p>

                        <div class="mt-auto">
                            <div class="d-flex justify-content-between align-items-center text-muted small">
                                <span>By Laura Brekelmans</span>
                                <span>2025</span>
                            </div>

                            <div class="mt-2">
                                <a href="{{ route('blog.show', 'understanding-remarkable-coordinate-system') }}"
                                   class="btn btn-primary btn-sm">
                                    Read Article
                                </a>
                            </div>
                        </div>
                    </div>
                </article>

            </div>
        </div>
    </section>
@endsection
