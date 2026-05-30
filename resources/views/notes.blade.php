<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AI Notes</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        <script type="module" src="{{ asset('js/app.js') }}" defer></script>
    @endif
</head>
<body>
    <main class="shell">
        <header class="topbar">
            <div>
                <p class="eyebrow">AI Notes Workspace</p>
                <h1>Capture, search, and summarize your notes</h1>
            </div>
            <div class="topbar-stats" aria-label="Application capabilities">
                <span>CRUD APIs</span>
                <span>Semantic search</span>
                <span>AI summaries</span>
            </div>
        </header>

        <section class="workspace">
            <aside class="sidebar">
                <form id="note-form" class="composer">
                    <div class="section-head">
                        <p class="eyebrow">Editor</p>
                        <h2>New note</h2>
                    </div>
                    <input type="hidden" id="note-id">
                    <label>
                        Title
                        <input id="title" name="title" maxlength="160" required placeholder="Meeting notes">
                    </label>
                    <label>
                        Tags
                        <input id="tags" name="tags" placeholder="work, ideas, todo">
                    </label>
                    <label>
                        Content
                        <textarea id="content" name="content" rows="9" required placeholder="Write a useful note..."></textarea>
                    </label>
                    <div class="actions">
                        <button type="submit" class="primary" id="save-note">
                            <span class="button-label">Save</span>
                            <span class="button-loader" aria-hidden="true"></span>
                        </button>
                        <button type="button" id="reset-form">Clear</button>
                    </div>
                    <p id="form-success" class="success" aria-live="polite"></p>
                    <p id="form-error" class="error" aria-live="polite"></p>
                </form>
            </aside>

            <section class="notes-area">
                <div class="toolbar">
                    <label class="search">
                        <span>Find notes</span>
                        <input id="search" placeholder="Try: project deadline or budget">
                    </label>
                    <button id="semantic-search" class="primary" type="button">AI Search</button>
                </div>

                <div id="status" class="status">Loading notes...</div>
                <div id="notes" class="notes-grid"></div>
                <div class="pager">
                    <button id="prev-page" type="button">Previous</button>
                    <span id="page-label">Page 1</span>
                    <button id="next-page" type="button">Next</button>
                </div>
            </section>
        </section>
    </main>
</body>
</html>
