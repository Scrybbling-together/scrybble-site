@extends('layouts.app')

@push('head')
    <?php $title = "Scrybble - Roadmap"; ?>

        <!-- Canonical URL -->
    <link rel="canonical" href="{{ url()->current() }}">

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:title" content="{{ $title }}">
    <meta property="og:description"
          content="See what's coming next for Scrybble. Our transparent roadmap shows current development priorities, planned features, and community-requested improvements for the reMarkable-Obsidian integration.">
    <meta property="og:image" content="{{ asset('img/scrybble-roadmap-og.jpg') }}">
    <meta property="og:site_name" content="Scrybble">

    <!-- Twitter -->
    <meta property="twitter:card" content="summary_large_image">
    <meta property="twitter:url" content="{{ url()->current() }}">
    <meta property="twitter:title" content="{{ $title }}">
    <meta property="twitter:description"
          content="See what's coming next for Scrybble. Our transparent roadmap shows current development priorities, planned features, and community-requested improvements for the reMarkable-Obsidian integration.">
    <meta property="twitter:image" content="{{ asset('img/scrybble-roadmap-twitter.jpg') }}">
@endpush

@section('content')
    <section class="bg-secondary-subtle p-5">
        <div class="container">
            <div class="text-center">
                <h1 class="display-2 fw-bolder">Development Roadmap</h1>
            </div>
        </div>
    </section>

    <section class="p-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="text-center mb-5">
                        <h2 class="mb-4">Current Development Focus</h2>
                        <p class="fs-5">We're committed to transparent development. Here's exactly what we're working on
                            and what's coming next.</p>
                    </div>

                    <!-- Status Legend -->
                    <div class="row mb-5">
                        <div class="col-12">
                            <div class="d-flex flex-wrap gap-3 justify-content-center">
                                <span class="badge bg-success fs-6 p-2">Completed</span>
                                <span class="badge bg-primary fs-6 p-2">In Progress</span>
                                <span class="badge bg-warning text-dark fs-6 p-2">Planned</span>
                                <span class="badge bg-info text-dark fs-6 p-2">Under Consideration</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-body-tertiary p-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <h2 class="text-center mb-5">Current Focus (Q3 2026)</h2>

                    <div class="grid gap-4 mb-4">

                        <x-roadmap-card
                            title="Improving sync"
                            status="In progress"
                            expected="During 2026"
                        >
                            <p>Synchronization from reMarkable to Obsidian has some rough edges that need to be worked
                                out</p>
                            <ul>
                                <li>We need to add support for images</li>
                                <li>When a sync error occurs, this should be communicated correctly in the plugin</li>
                                <li>Support for reMarkable templates (not methods!)</li>
                                <li>Pen rendering improvements</li>
                            </ul>
                            <p><strong>Solution</strong> just a bunch of development work :)</p>
                        </x-roadmap-card>

                        <x-roadmap-card
                            title="Zotero x reMarkable integration"
                            status="In progress"
                            expected="2026"
                        >
                            <p>Many people using the reMarkable use it within a research context, Zotero is the go-to
                                citation management solution. However, like Obsidian it's not integrated well with the
                                reMarkable ecosystem.</p>
                            <p><strong>Solution:</strong> Develop an integration with zotero for reMarkable that becomes
                                a
                                part of the Scrybble offering.</p>
                            <p>Interested in joining the conversation or trying an early version? Development is done in
                                the
                                open, and you can find the project <a
                                    href="https://github.com/Scrybbling-together/zotero2remarkable_bridge">here</a>.</p>
                        </x-roadmap-card>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Next: Upcoming Quarter -->
    <section class="p-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <h2 class="text-center mb-5">Q4 2026 - Next Up</h2>
                    <div class="grid gap-4 mb-4">
                        <x-roadmap-card
                            title="Highlight support for scanned documents"
                            status="Exploring"
                            expected="Q4 2025"
                        >
                            <p><strong>Problem:</strong> Highlights don't work on scanned PDFs without embedded text</p>
                            <p><strong>Solution:</strong> AI and/or OCR</p>
                        </x-roadmap-card>

                        <x-roadmap-card
                            title="Automatic sync functionality"
                            status="Planned"
                            expected="Q4 2026"
                        >
                            <p><strong>Problem:</strong> You have to manually click a file to get it synced or updated
                                within Obsidian</p>
                            <p><strong>Goal:</strong> Find documents and annotations quickly</p>
                            <ul class="mb-0">
                                <li>Select files and or folders which should automatically sync</li>
                            </ul>
                        </x-roadmap-card>

                        <x-roadmap-card
                            title="Two-way sync"
                            status="Exploring"
                            expected="Late 2026"
                        >
                            <p><strong>Problem:</strong> At the moment, the Obsidian plugin only allows you to sync from reMarkable to Obsidian</p>
                            <p><strong>Goal:</strong> Allow you to sync back Obsidian files directly to reMarkable so you can annotate and highlight them on-device</p>
                            <pi>Sometimes you just need to think with your hands!</pi>
                        </x-roadmap-card>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="bg-secondary text-white p-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <h2 class="text-center mb-5">Future Possibilities</h2>
                    <p class="text-center mb-5">Ideas we're exploring - timeline depends on community feedback and
                        technical feasibility.</p>

                    <div class="grid gap-4">
                        <x-roadmap-card
                            title="Convert structured annotations into popular formats"
                            status="Exploring"
                            borderClass="border-0 bg-white bg-opacity-10"
                            headerBg="bg-transparent"
                            headerBorder="border-0"
                            headerTextColor="text-white"
                        >
                            <ul class="text-white mb-0">
                                <li>Drawn diagrams to mermaid diagrams</li>
                                <li>Tables to markdown tables</li>
                                <li>Drawn text to structured Markdown?</li>
                                <li>Other conversions?</li>
                            </ul>
                        </x-roadmap-card>

                        <x-roadmap-card
                            title="reMarkable x Readwise integration"
                            status="Exploring"
                            borderClass="border-0 bg-white bg-opacity-10"
                            headerBg="bg-transparent"
                            headerBorder="border-0"
                            headerTextColor="text-white"
                        >
                            <ul class="text-white mb-0">
                                <li>Your highlights made on your reMarkable synced with Readwise!</li>
                            </ul>
                        </x-roadmap-card>

                        <x-roadmap-card
                            title="Anki x reMarkable integration"
                            status="Exploring"
                            borderClass="border-0 bg-white bg-opacity-10"
                            headerBg="bg-transparent"
                            headerBorder="border-0"
                            headerTextColor="text-white"
                        >
                            <ul class="text-white mb-0">
                                <li>Create anki cards on your tablet</li>
                                <li>Your study, book notes and research notes go directly into your favorite Spaced
                                    Repetition program
                                </li>
                                <li>No more distraction through your phone!</li>
                            </ul>
                        </x-roadmap-card>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Community Input -->
    <section class="p-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="text-center mb-5">
                        <h2 class="mb-4">Shape the Future</h2>
                        <p class="fs-5">This roadmap isn't set in stone. Your feedback directly influences our
                            development priorities.</p>
                    </div>

                    <div class="grid gap-4 mb-5">
                        <div class="card border-outline-secondary g-col-6">
                            <div class="card-body text-center">
                                <h4>Get your voice heard</h4>
                                <p>A particular feature particularly important for you? Contact us!</p>
                            </div>
                            <div class="card-footer">
                                <div class="d-grid d-sm-flex gap-2">
                                    <a href="mailto:{{ config('app.support_email') }}"
                                       class="btn btn-outline-secondary">Send Email</a>
                                    <a href="{{ config('app.discord.invite') }}" class="btn btn-primary">Join
                                        Discord</a>
                                </div>
                            </div>
                        </div>
                        <div class="card border-outline-secondary g-col-6">
                            <div class="card-body text-center">
                                <h4>Suggest Ideas</h4>
                                <p>Have a feature request? We want to hear it.</p>
                            </div>
                            <div class="card-footer">
                                <div class="d-grid d-sm-flex gap-2">
                                    <a href="mailto:{{ config('app.support_email') }}"
                                       class="btn btn-outline-secondary">Send Email</a>
                                    <a href="{{ config('app.discord.invite') }}" class="btn btn-primary">Join
                                        Discord</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3">Previously Completed</h3>
                        <ul>
                            <li class="badge bg-success fs-6 p-2 mb-2">✓ Improved on-device file handling security</li>
                            <li class="badge bg-success fs-6 p-2 mb-2">✓ Encryption at rest</li>
                            <li class="badge bg-success fs-6 p-2 mb-2">✓ Search and sort options for reMarkable
                                notebooks within the Obsidian plugin
                            </li>
                            <li class="badge bg-success fs-6 p-2 mb-2">✓ Type folio & typed text support</li>
                            <li class="badge bg-success fs-6 p-2 mb-2">✓ reMarkable Paper Pro support</li>
                            <li class="badge bg-success fs-6 p-2 mb-2">✓ Improved sync reliability</li>
                            <li class="badge bg-success fs-6 p-2 mb-2">✓ Greatly improved UI within Obsidian</li>
                            <li class="badge bg-success fs-6 p-2 mb-2">✓ Highlight export from device</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
