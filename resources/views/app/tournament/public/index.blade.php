<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>DinKin' Tournament Results</title>

    {{-- Bootstrap 4 --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

    {{-- Font Awesome --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

    <style>
        :root {
            --primary: #111827;
            --primary-light: #1f2937;
            --accent: #f59e0b;
            --accent-dark: #d97706;
            --success: #16a34a;
            --danger: #dc2626;
            --info: #2563eb;
            --light: #f3f4f6;
            --border: #e5e7eb;
            --text: #111827;
            --muted: #6b7280;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #f4f6f9;
            color: var(--text);
            font-family:
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                Roboto,
                Helvetica,
                Arial,
                sans-serif;
        }

        /* =========================================================
           TOP HEADER
        ========================================================= */

        .sports-header {
            background:
                linear-gradient(135deg,
                    #111827 0%,
                    #1f2937 55%,
                    #111827 100%);
            color: white;
            padding: 28px 0;
            box-shadow: 0 4px 18px rgba(0, 0, 0, .18);
        }

        .brand-wrapper {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .brand-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, .10);
            border: 1px solid rgba(255, 255, 255, .15);
            font-size: 24px;
            color: var(--accent);
        }

        .brand-title {
            font-size: 25px;
            font-weight: 700;
            margin: 0;
        }

        .brand-subtitle {
            color: rgba(255, 255, 255, .65);
            font-size: 13px;
            margin-top: 2px;
        }

        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            background: rgba(22, 163, 74, .15);
            border: 1px solid rgba(34, 197, 94, .30);
            color: #86efac;
            padding: 7px 12px;
            border-radius: 30px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .live-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #22c55e;
            box-shadow: 0 0 0 4px rgba(34, 197, 94, .15);
        }

        /* =========================================================
           MAIN
        ========================================================= */

        .dashboard {
            padding: 25px 0 50px;
        }

        /* =========================================================
           FILTER CARD
        ========================================================= */

        .filter-card {
            background: white;
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: 0 3px 12px rgba(0, 0, 0, .05);
            padding: 20px;
            margin-bottom: 25px;
        }

        .filter-title {
            font-size: 14px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .5px;
            color: var(--muted);
            margin-bottom: 15px;
        }

        .form-label {
            font-size: 12px;
            font-weight: 700;
            color: var(--muted);
            text-transform: uppercase;
            margin-bottom: 6px;
        }

        .form-control {
            height: 44px;
            border-radius: 9px;
            border: 1px solid #d1d5db;
            font-size: 14px;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(245, 158, 11, .12);
        }

        /* =========================================================
           TOURNAMENT INFO
        ========================================================= */

        .tournament-banner {
            background: white;
            border-radius: 14px;
            border: 1px solid var(--border);
            padding: 20px 24px;
            margin-bottom: 25px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, .05);
        }

        .tournament-name {
            font-size: 23px;
            font-weight: 750;
            margin-bottom: 7px;
        }

        .tournament-meta {
            color: var(--muted);
            font-size: 13px;
        }

        .meta-badge {
            display: inline-block;
            background: #f3f4f6;
            border-radius: 6px;
            padding: 5px 9px;
            font-weight: 600;
            color: #374151;
        }

        /* =========================================================
           SECTION
        ========================================================= */

        .dashboard-section {
            background: white;
            border-radius: 14px;
            border: 1px solid var(--border);
            box-shadow: 0 3px 12px rgba(0, 0, 0, .05);
            margin-bottom: 25px;
            overflow: hidden;
        }

        .section-header {
            padding: 17px 20px;
            border-bottom: 1px solid var(--border);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .section-title {
            font-size: 16px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-icon {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
        }

        .section-body {
            padding: 20px;
        }

        /* =========================================================
           PODIUM
        ========================================================= */

        .podium-grid {
            display: grid;
            grid-template-columns: 1fr 1.15fr 1fr;
            gap: 15px;
            align-items: end;
        }

        .podium-card {
            position: relative;
            text-align: center;
            border-radius: 14px;
            padding: 25px 15px;
            border: 1px solid var(--border);
            background: #fafafa;
            min-height: 185px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .podium-card.third-place {
            order: 1;
            min-height: 160px;
            background: linear-gradient(145deg, #fff1e6, #fff);
            border-color: #d99a62;
        }

        .podium-card.runner-up {
            order: 2;
            min-height: 190px;
            background: linear-gradient(145deg, #f1f3f5, #fff);
            border-color: #b9c0c8;
        }

        .podium-card.champion {
            order: 3;
            min-height: 220px;
            background:
                linear-gradient(145deg,
                    #fff7d6,
                    #fff);
            border: 2px solid #f5c542;
            box-shadow: 0 8px 25px rgba(245, 197, 66, .16);
        }

        .podium-medal {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .champion .podium-medal {
            font-size: 42px;
            color: #f59e0b;
        }

        .runner-up .podium-medal {
            color: #9ca3af;
        }

        .third-place .podium-medal {
            color: #b45309;
        }

        .third-place .podium-position {
            color: #9a5a24;
        }

        .podium-position {
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: var(--muted);
        }

        .champion .podium-position {
            color: var(--accent-dark);
        }

        .podium-team {
            font-size: 21px;
            font-weight: 800;
            margin-top: 8px;
        }

        .champion .podium-team {
            font-size: 25px;
        }

        .podium-name {
            color: var(--muted);
            font-size: 13px;
            margin-top: 3px;
        }

        .podium-score {
            margin-top: 12px;
            font-size: 13px;
            font-weight: 700;
        }

        /* =========================================================
           STANDINGS
        ========================================================= */

        .standings-table {
            margin: 0;
        }

        .standings-table thead th {
            background: #f8fafc;
            border-top: none;
            border-bottom: 1px solid var(--border);
            color: var(--muted);
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: .5px;
            font-weight: 700;
            padding: 12px 10px;
            white-space: nowrap;
        }

        .standings-table tbody td {
            padding: 13px 10px;
            vertical-align: middle;
            border-color: #eef0f2;
            font-size: 13px;
        }

        .rank-badge {
            width: 30px;
            height: 30px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #f3f4f6;
            font-weight: 800;
        }

        .rank-1 {
            background: #fef3c7;
            color: #92400e;
        }

        .rank-2 {
            background: #e5e7eb;
            color: #374151;
        }

        .rank-3 {
            background: #fed7aa;
            color: #9a3412;
        }

        .team-number {
            font-weight: 800;
        }

        .team-name-small {
            color: var(--muted);
            font-size: 11px;
            margin-top: 2px;
        }

        .win-count {
            color: var(--success);
            font-weight: 800;
        }

        .loss-count {
            color: var(--danger);
            font-weight: 700;
        }

        .pd-positive {
            color: var(--success);
            font-weight: 800;
        }

        .pd-negative {
            color: var(--danger);
            font-weight: 800;
        }

        /* =========================================================
           MATCH CARDS
        ========================================================= */

        .round-title {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 20px 0 12px;
            font-size: 14px;
            font-weight: 800;
        }

        .round-title:first-child {
            margin-top: 0;
        }

        .round-badge {
            background: #111827;
            color: white;
            padding: 5px 9px;
            border-radius: 6px;
            font-size: 11px;
        }

        .match-card {
            border: 1px solid var(--border);
            border-radius: 10px;
            margin-bottom: 10px;
            overflow: hidden;
            background: white;
        }

        .match-row {
            display: grid;
            grid-template-columns: 65px 1fr 100px 1fr 110px;
            align-items: center;
            min-height: 65px;
        }

        .match-number {
            padding: 12px;
            color: var(--muted);
            font-size: 11px;
            text-align: center;
            border-right: 1px solid var(--border);
        }

        .match-team {
            padding: 10px 15px;
        }

        .match-team-number {
            font-weight: 800;
            font-size: 13px;
        }

        .match-team-name {
            font-size: 11px;
            color: var(--muted);
            margin-top: 2px;
        }

        .match-score {
            text-align: center;
            font-size: 17px;
            font-weight: 800;
            border-left: 1px solid var(--border);
            border-right: 1px solid var(--border);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .match-winner {
            padding: 10px;
            text-align: center;
        }

        .winner-badge {
            background: #dcfce7;
            color: #166534;
            border-radius: 20px;
            padding: 5px 10px;
            font-size: 11px;
            font-weight: 800;
        }

        /* =========================================================
           PLAYOFF
        ========================================================= */

        .playoff-card {
            border: 1px solid var(--border);
            border-radius: 12px;
            margin-bottom: 12px;
            overflow: hidden;
        }

        .playoff-type {
            background: #111827;
            color: white;
            padding: 8px 12px;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: .5px;
        }

        .playoff-body {
            display: grid;
            grid-template-columns: 1fr 110px 1fr;
            align-items: center;
            min-height: 80px;
        }

        .playoff-team {
            padding: 15px;
        }

        .playoff-team.right {
            text-align: right;
        }

        .playoff-score {
            text-align: center;
            font-size: 20px;
            font-weight: 800;
            border-left: 1px solid var(--border);
            border-right: 1px solid var(--border);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .bracket-label {
            display: inline-block;
            margin-top: 4px;
            font-size: 10px;
            background: #f3f4f6;
            color: #6b7280;
            padding: 3px 6px;
            border-radius: 4px;
        }

        /* =========================================================
           LOADING
        ========================================================= */

        .loading-box {
            background: white;
            border-radius: 14px;
            border: 1px solid var(--border);
            padding: 35px;
            text-align: center;
            color: var(--muted);
            margin-bottom: 25px;
        }

        .loading-spinner {
            font-size: 26px;
            margin-bottom: 10px;
        }

        /* =========================================================
           FOOTER
        ========================================================= */

        .dashboard-footer {
            text-align: center;
            color: #9ca3af;
            font-size: 11px;
            padding: 15px 0;
        }

        /* =========================================================
           MOBILE
        ========================================================= */

        @media (max-width: 767px) {

            .sports-header {
                padding: 20px 0;
            }

            .brand-title {
                font-size: 20px;
            }

            .live-indicator {
                margin-top: 12px;
            }

            .podium-grid {
                grid-template-columns: 1fr;
            }

            .podium-card,
            .podium-card.champion {
                min-height: 160px;
            }

            .match-row {
                grid-template-columns: 45px 1fr 75px 1fr;
            }

            .match-winner {
                display: none;
            }

            .match-team {
                padding: 8px;
            }

            .playoff-body {
                grid-template-columns: 1fr 80px 1fr;
            }

            .playoff-team {
                padding: 10px;
            }

            .standings-table {
                min-width: 650px;
            }

            .table-responsive {
                overflow-x: auto;
            }
        }
    </style>
</head>

<body>

    {{-- =========================================================
       HEADER
    ========================================================== --}}

    <header class="sports-header">
        <div class="container">

            <div class="row align-items-center">

                <div class="col-md-8">

                    <div class="brand-wrapper">

                        <div class="brand-icon">
                            <i class="fas fa-trophy"></i>
                        </div>

                        <div>
                            <h1 class="brand-title">
                                Tournament Results
                            </h1>

                            <div class="brand-subtitle">
                                Live standings, matches and playoff results
                            </div>
                        </div>

                    </div>

                </div>

                <div class="col-md-4 text-md-right mt-3 mt-md-0">

                    <span class="live-indicator">
                        <span class="live-dot"></span>
                        Live Results
                    </span>

                    @auth
                        <a href="{{ route('dashboard') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-tachometer-alt mr-1"></i>
                            Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-light btn-sm">
                            <i class="fas fa-sign-in-alt mr-1"></i>
                            Login
                        </a>
                    @endauth

                </div>

            </div>

        </div>
    </header>


    {{-- =========================================================
       DASHBOARD
    ========================================================== --}}

    <main class="dashboard">

        <div class="container">

            {{-- =================================================
               FILTER
            ================================================== --}}

            <div class="filter-card">

                <div class="filter-title">
                    <i class="fas fa-sliders-h mr-1"></i>
                    Tournament Selection
                </div>

                <div class="row">

                    {{-- TOURNAMENT --}}

                    <div class="col-md-4">

                        <div class="form-group mb-md-0">

                            <label for="tournament_id" class="form-label">
                                Tournament
                            </label>

                            <select id="tournament_id" class="form-control">

                                <option value="">
                                    Select Tournament
                                </option>

                                @foreach ($tournaments as $tournament)
                                    <option value="{{ $tournament->id }}">
                                        {{ $tournament->name }}
                                    </option>
                                @endforeach

                            </select>

                        </div>

                    </div>


                    {{-- CATEGORY --}}

                    <div class="col-md-4">

                        <div class="form-group mb-md-0">

                            <label for="team_category" class="form-label">
                                Category
                            </label>

                            <select id="team_category" class="form-control" disabled>

                                <option value="">
                                    Select Category
                                </option>

                            </select>

                        </div>

                    </div>


                    {{-- BRACKET --}}

                    <div class="col-md-4">

                        <div class="form-group mb-0">

                            <label for="team_bracket" class="form-label">
                                Bracket
                            </label>

                            <select id="team_bracket" class="form-control" disabled>

                                <option value="">
                                    Select Bracket
                                </option>

                            </select>

                        </div>

                    </div>

                </div>

            </div>


            {{-- =================================================
               LOADING
            ================================================== --}}

            <div id="loading" class="loading-box" style="display: none;">

                <div class="loading-spinner">
                    <i class="fas fa-circle-notch fa-spin"></i>
                </div>

                <div>
                    Loading tournament results...
                </div>

            </div>


            {{-- =================================================
               TOURNAMENT INFORMATION
            ================================================== --}}

            <div id="tournamentInfo" class="tournament-banner" style="display: none;">

                <div class="tournament-name">
                    <i class="fas fa-trophy text-warning mr-2"></i>
                    <span id="tournamentName"></span>
                </div>

                <div class="tournament-meta">

                    <span class="meta-badge">
                        <i class="fas fa-layer-group mr-1"></i>
                        <span id="displayCategory"></span>
                    </span>

                    <span class="mx-1">
                        /
                    </span>

                    <span class="meta-badge">
                        <i class="fas fa-sitemap mr-1"></i>
                        Bracket <span id="displayBracket"></span>
                    </span>

                    <span id="liveUpdateStatus" class="meta-badge ml-2">
                        Live updates every 5 seconds
                    </span>

                </div>

            </div>


            {{-- =================================================
               PODIUM
            ================================================== --}}

            <div id="podiumSection" style="display: none;">

                <div class="dashboard-section">

                    <div class="section-header">

                        <h3 class="section-title">

                            <span class="section-icon">
                                <i class="fas fa-trophy text-warning"></i>
                            </span>

                            Final Podium

                        </h3>

                    </div>

                    <div class="section-body">

                        <div class="podium-grid">

                            {{-- RUNNER UP --}}

                            <div class="podium-card runner-up">

                                <div class="podium-medal">
                                    <i class="fas fa-medal"></i>
                                </div>

                                <div class="podium-position">
                                    Runner-up
                                </div>

                                <div id="runnerUpTeam" class="podium-team">
                                    —
                                </div>

                                <div id="runnerUpName" class="podium-name">
                                    Final pending
                                </div>

                                <div id="runnerUpScore" class="podium-score">
                                    —
                                </div>

                            </div>


                            {{-- CHAMPION --}}

                            <div class="podium-card champion">

                                <div class="podium-medal">
                                    <i class="fas fa-trophy"></i>
                                </div>

                                <div class="podium-position">
                                    Champion
                                </div>

                                <div id="championTeam" class="podium-team">
                                    —
                                </div>

                                <div id="championName" class="podium-name">
                                    Final pending
                                </div>

                                <div id="championScore" class="podium-score">
                                    —
                                </div>

                            </div>


                            {{-- THIRD PLACE --}}

                            <div class="podium-card third-place">

                                <div class="podium-medal">
                                    <i class="fas fa-medal"></i>
                                </div>

                                <div class="podium-position">
                                    3rd Place
                                </div>

                                <div id="thirdPlaceTeam" class="podium-team">
                                    —
                                </div>

                                <div id="thirdPlaceName" class="podium-name">
                                    3rd Place pending
                                </div>

                                <div id="thirdPlaceScore" class="podium-score">
                                    —
                                </div>

                            </div>

                        </div>

                    </div>

                </div>

            </div>


            {{-- =================================================
               STANDINGS
            ================================================== --}}

            <div id="standingsSection" style="display: none;">

                <div class="dashboard-section">

                    <div class="section-header">

                        <h3 class="section-title">

                            <span class="section-icon">
                                <i class="fas fa-ranking-star"></i>
                            </span>

                            Live Standings

                        </h3>

                        <small class="text-muted">
                            Round Robin
                        </small>

                    </div>

                    <div class="table-responsive">

                        <table class="table standings-table">

                            <thead>

                                <tr>

                                    <th class="text-center">
                                        Rank
                                    </th>

                                    <th>
                                        Team
                                    </th>

                                    <th class="text-center">
                                        GP
                                    </th>

                                    <th class="text-center">
                                        W
                                    </th>

                                    <th class="text-center">
                                        L
                                    </th>

                                    <th class="text-center">
                                        PF
                                    </th>

                                    <th class="text-center">
                                        PA
                                    </th>

                                    <th class="text-center">
                                        PD
                                    </th>

                                </tr>

                            </thead>

                            <tbody id="standingsBody">
                            </tbody>

                        </table>

                    </div>

                </div>

            </div>


            {{-- =================================================
               ROUND ROBIN MATCHES
            ================================================== --}}

            <div id="matchesSection" style="display: none;">

                <div class="dashboard-section">

                    <div class="section-header">

                        <h3 class="section-title">

                            <span class="section-icon">
                                <i class="fas fa-table-tennis-paddle-ball"></i>
                            </span>

                            Round Robin Matches

                        </h3>

                    </div>

                    <div class="section-body">

                        <div id="matchesContainer">
                        </div>

                    </div>

                </div>

            </div>


            {{-- =================================================
               PLAYOFF MATCHES
            ================================================== --}}

            <div id="playoffsSection" style="display: none;">

                <div class="dashboard-section">

                    <div class="section-header">

                        <h3 class="section-title">

                            <span class="section-icon">
                                <i class="fas fa-trophy text-warning"></i>
                            </span>

                            Playoff Matches

                        </h3>

                        <small class="text-muted">
                            Crossover & Finals
                        </small>

                    </div>

                    <div class="section-body">

                        <div id="playoffsContainer">
                        </div>

                    </div>

                </div>

            </div>


            <div class="dashboard-footer">

                Tournament Results Dashboard by <a href="https://www.facebook.com/klarkhowellucion111821"
                    target="_blank">KHDLucion</a>

            </div>

        </div>

    </main>


    {{-- =========================================================
       JAVASCRIPT
    ========================================================== --}}

    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function() {

                const tournamentSelect =
                    document.getElementById('tournament_id');

                const categorySelect =
                    document.getElementById('team_category');

                const bracketSelect =
                    document.getElementById('team_bracket');

                const loading =
                    document.getElementById('loading');

                const liveUpdateStatus =
                    document.getElementById('liveUpdateStatus');

                let activeDataController = null;
                let activeDataKey = null;
                let dataRequestSequence = 0;


                /*
                |--------------------------------------------------------------------------
                | Tournament Changed
                |--------------------------------------------------------------------------
                */

                tournamentSelect.addEventListener(
                    'change',
                    function() {

                        const tournamentId = this.value;

                        resetResults();

                        categorySelect.innerHTML =
                            '<option value="">Select Category</option>';

                        bracketSelect.innerHTML =
                            '<option value="">Select Bracket</option>';

                        categorySelect.disabled = true;
                        bracketSelect.disabled = true;

                        if (!tournamentId) {
                            return;
                        }

                        loadFilters(tournamentId);

                    }
                );


                /*
                |--------------------------------------------------------------------------
                | Category Changed
                |--------------------------------------------------------------------------
                */

                categorySelect.addEventListener(
                    'change',
                    function() {

                        const category = this.value;

                        bracketSelect.innerHTML =
                            '<option value="">Select Bracket</option>';

                        bracketSelect.disabled = true;

                        resetResults();

                        if (!category) {
                            return;
                        }

                        const brackets =
                            window.filterData?.brackets || [];

                        brackets.forEach(
                            function(bracket) {

                                const option =
                                    document.createElement('option');

                                option.value = bracket;

                                option.textContent =
                                    'Bracket ' + bracket;

                                bracketSelect.appendChild(option);

                            }
                        );

                        bracketSelect.disabled =
                            brackets.length === 0;

                    }
                );


                /*
                |--------------------------------------------------------------------------
                | Bracket Changed
                |--------------------------------------------------------------------------
                */

                bracketSelect.addEventListener(
                    'change',
                    function() {

                        const bracket = this.value;

                        if (!bracket) {
                            resetResults();
                            return;
                        }

                        resetResults(false);
                        loadTournamentData();

                    }
                );


                /*
                |--------------------------------------------------------------------------
                | Load Filters
                |--------------------------------------------------------------------------
                */

                async function loadFilters(tournamentId) {

                    try {

                        const response =
                            await fetch(
                                `{{ route('public.tournaments.filters') }}?tournament_id=${encodeURIComponent(tournamentId)}`, {
                                    cache: 'no-store',
                                    headers: {
                                        'Accept': 'application/json'
                                    }
                                }
                            );

                        if (!response.ok) {

                            throw new Error(
                                `HTTP ${response.status}`
                            );

                        }

                        const data =
                            await response.json();

                        if (tournamentSelect.value !== tournamentId) {
                            return;
                        }

                        window.filterData = data;

                        categorySelect.innerHTML =
                            '<option value="">Select Category</option>';

                        data.categories.forEach(
                            function(category) {

                                const option =
                                    document.createElement('option');

                                option.value = category;
                                option.textContent = category;

                                categorySelect.appendChild(option);

                            }
                        );

                        categorySelect.disabled =
                            data.categories.length === 0;

                    } catch (error) {

                        console.error(
                            'Unable to load filters:',
                            error
                        );

                        alert(
                            'Unable to load tournament filters.'
                        );

                    }

                }


                async function refreshFilters() {

                    const tournamentId = tournamentSelect.value;

                    if (!tournamentId) {
                        return;
                    }

                    const selectedCategory = categorySelect.value;
                    const selectedBracket = bracketSelect.value;

                    try {

                        const response = await fetch(
                            `{{ route('public.tournaments.filters') }}?tournament_id=${encodeURIComponent(tournamentId)}`, {
                                cache: 'no-store',
                                headers: {
                                    'Accept': 'application/json'
                                }
                            }
                        );

                        if (!response.ok) {
                            throw new Error(`HTTP ${response.status}`);
                        }

                        const data = await response.json();

                        if (
                            tournamentSelect.value !== tournamentId ||
                            categorySelect.value !== selectedCategory ||
                            bracketSelect.value !== selectedBracket
                        ) {
                            return;
                        }

                        window.filterData = data;

                        categorySelect.innerHTML = '<option value="">Select Category</option>';
                        data.categories.forEach(function(category) {
                            const option = document.createElement('option');
                            option.value = category;
                            option.textContent = category;
                            categorySelect.appendChild(option);
                        });

                        const categoryStillExists = data.categories.includes(selectedCategory);
                        categorySelect.value = categoryStillExists ? selectedCategory : '';
                        categorySelect.disabled = data.categories.length === 0;

                        bracketSelect.innerHTML = '<option value="">Select Bracket</option>';
                        data.brackets.forEach(function(bracket) {
                            const option = document.createElement('option');
                            option.value = bracket;
                            option.textContent = 'Bracket ' + bracket;
                            bracketSelect.appendChild(option);
                        });

                        const bracketStillExists = data.brackets.includes(selectedBracket);
                        bracketSelect.value = categoryStillExists && bracketStillExists ? selectedBracket : '';
                        bracketSelect.disabled = !categoryStillExists || data.brackets.length === 0;

                        if (!categoryStillExists || !bracketStillExists) {
                            resetResults();
                        }

                    } catch (error) {
                        console.error('Unable to refresh tournament filters:', error);
                    }
                }


                /*
                |--------------------------------------------------------------------------
                | Load Tournament Data
                |--------------------------------------------------------------------------
                */

                async function loadTournamentData({ silent = false } = {}) {

                    const tournamentId =
                        tournamentSelect.value;

                    const category =
                        categorySelect.value;

                    const bracket =
                        bracketSelect.value;

                    if (
                        !tournamentId ||
                        !category ||
                        !bracket
                    ) {
                        return;
                    }

                    const requestKey = `${tournamentId}:${category}:${bracket}`;

                    if (activeDataController && activeDataKey === requestKey) {
                        return;
                    }

                    if (activeDataController) {
                        activeDataController.abort();
                    }

                    const requestController = new AbortController();
                    const requestSequence = ++dataRequestSequence;
                    activeDataController = requestController;
                    activeDataKey = requestKey;

                    if (!silent && getComputedStyle(document.getElementById('tournamentInfo')).display === 'none') {
                        loading.style.display = 'block';
                    }

                    try {

                        const url =
                            `{{ route('public.tournaments.data') }}` +
                            `?tournament_id=${encodeURIComponent(tournamentId)}` +
                            `&team_category=${encodeURIComponent(category)}` +
                            `&team_bracket=${encodeURIComponent(bracket)}`;

                        const response =
                            await fetch(
                                url, {
                                    cache: 'no-store',
                                    signal: requestController.signal,
                                    headers: {
                                        'Accept': 'application/json'
                                    }
                                }
                            );

                        if (!response.ok) {

                            const errorText =
                                await response.text();

                            console.error(
                                'Data request failed:',
                                errorText
                            );

                            throw new Error(
                                `HTTP ${response.status}`
                            );

                        }

                        const data =
                            await response.json();

                        if (
                            requestSequence !== dataRequestSequence ||
                            tournamentSelect.value !== tournamentId ||
                            categorySelect.value !== category ||
                            bracketSelect.value !== bracket
                        ) {
                            return;
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | Tournament Information
                        |--------------------------------------------------------------------------
                        */

                        document.getElementById(
                                'tournamentName'
                            ).textContent =
                            data.tournament.name;

                        document.getElementById(
                                'displayCategory'
                            ).textContent =
                            category;

                        document.getElementById(
                                'displayBracket'
                            ).textContent =
                            bracket;

                        document.getElementById(
                                'tournamentInfo'
                            ).style.display =
                            'block';


                        /*
                        |--------------------------------------------------------------------------
                        | Standings
                        |--------------------------------------------------------------------------
                        */

                        displayStandings(
                            data.standings
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Round Robin Matches
                        |--------------------------------------------------------------------------
                        */

                        displayMatches(
                            data.matches
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Playoffs
                        |--------------------------------------------------------------------------
                        */

                        displayPlayoffs(
                            data.playoffs || []
                        );


                        /*
                        |--------------------------------------------------------------------------
                        | Podium
                        |--------------------------------------------------------------------------
                        */

                        displayPodium(
                            data.podium || {}
                        );

                        liveUpdateStatus.textContent =
                            `Live · Updated ${new Date().toLocaleTimeString()}`;

                    } catch (error) {

                        if (error.name === 'AbortError') {
                            return;
                        }

                        console.error(
                            'Unable to load tournament data:',
                            error
                        );

                        liveUpdateStatus.textContent = 'Live update unavailable; retrying…';

                        if (!silent) {
                            alert('Unable to load tournament data.');
                        }

                    } finally {

                        if (requestSequence === dataRequestSequence) {
                            loading.style.display = 'none';
                            activeDataController = null;
                            activeDataKey = null;
                        }

                    }

                }


                /*
                |--------------------------------------------------------------------------
                | Display Standings
                |--------------------------------------------------------------------------
                */

                function displayStandings(standings) {

                    const section =
                        document.getElementById(
                            'standingsSection'
                        );

                    const tbody =
                        document.getElementById(
                            'standingsBody'
                        );

                    tbody.innerHTML = '';

                    if (
                        !standings ||
                        standings.length === 0
                    ) {

                        section.style.display = 'none';

                        return;

                    }

                    section.style.display = 'block';

                    standings.forEach(
                        function(standing) {

                            const row =
                                document.createElement('tr');

                            let rankClass = '';

                            if (standing.rank === 1) {
                                rankClass = 'rank-1';
                            } else if (standing.rank === 2) {
                                rankClass = 'rank-2';
                            } else if (standing.rank === 3) {
                                rankClass = 'rank-3';
                            }

                            let pdClass = '';

                            if (
                                standing.point_differential > 0
                            ) {
                                pdClass = 'pd-positive';
                            } else if (
                                standing.point_differential < 0
                            ) {
                                pdClass = 'pd-negative';
                            }

                            row.innerHTML = `

                                <td class="text-center">

                                    <span class="rank-badge ${rankClass}">
                                        ${standing.rank}
                                    </span>

                                </td>

                                <td>

                                    <div class="team-number">
                                        ${standing.team_no}
                                    </div>

                                    <div class="team-name-small">
                                        ${standing.team_name}
                                    </div>

                                </td>

                                <td class="text-center">
                                    ${standing.games_played}
                                </td>

                                <td class="text-center">
                                    <span class="win-count">
                                        ${standing.wins}
                                    </span>
                                </td>

                                <td class="text-center">
                                    <span class="loss-count">
                                        ${standing.losses}
                                    </span>
                                </td>

                                <td class="text-center">
                                    ${standing.points_for}
                                </td>

                                <td class="text-center">
                                    ${standing.points_against}
                                </td>

                                <td class="text-center">

                                    <span class="${pdClass}">
                                        ${standing.point_differential > 0 ? '+' : ''}
                                        ${standing.point_differential}
                                    </span>

                                </td>

                            `;

                            tbody.appendChild(row);

                        }
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Display Round Robin Matches
                |--------------------------------------------------------------------------
                */

                function displayMatches(matches) {

                    const section =
                        document.getElementById(
                            'matchesSection'
                        );

                    const container =
                        document.getElementById(
                            'matchesContainer'
                        );

                    container.innerHTML = '';

                    if (
                        !matches ||
                        matches.length === 0
                    ) {

                        section.style.display = 'none';

                        return;

                    }

                    section.style.display = 'block';

                    const rounds = {};

                    matches.forEach(
                        function(match) {

                            if (!rounds[match.round_no]) {
                                rounds[match.round_no] = [];
                            }

                            rounds[match.round_no].push(match);

                        }
                    );

                    Object.keys(rounds).forEach(
                        function(roundNo) {

                            let html = `

                                <div class="round-title">

                                    <span class="round-badge">
                                        ROUND ${roundNo}
                                    </span>

                                    <span>
                                        Round Robin
                                    </span>

                                </div>

                            `;

                            rounds[roundNo].forEach(
                                function(match) {

                                    const score =
                                        match.team1_score !== null &&
                                        match.team2_score !== null ?
                                        `${match.team1_score} - ${match.team2_score}` :
                                        'VS';

                                    html += `

                                        <div class="match-card">

                                            <div class="match-row">

                                                <div class="match-number">

                                                    Match
                                                    <strong>
                                                        ${match.match_no}
                                                    </strong>

                                                </div>

                                                <div class="match-team">

                                                    <div class="match-team-number">
                                                        ${match.team1_no}
                                                    </div>

                                                    <div class="match-team-name">
                                                        ${match.team1_name}
                                                    </div>

                                                </div>

                                                <div class="match-score">
                                                    ${score}
                                                </div>

                                                <div class="match-team">

                                                    <div class="match-team-number">
                                                        ${match.team2_no}
                                                    </div>

                                                    <div class="match-team-name">
                                                        ${match.team2_name}
                                                    </div>

                                                </div>

                                                <div class="match-winner">

                                                    ${
                                                        match.winner_no
                                                            ? `
                                                                                                        <span class="winner-badge">
                                                                                                            <i class="fas fa-check mr-1"></i>
                                                                                                            ${match.winner_no}
                                                                                                        </span>
                                                                                                      `
                                                            : `
                                                                                                        <span class="text-muted">
                                                                                                            Pending
                                                                                                        </span>
                                                                                                      `
                                                    }

                                                </div>

                                            </div>

                                        </div>

                                    `;

                                }
                            );

                            container.innerHTML += html;

                        }
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Display Playoffs
                |--------------------------------------------------------------------------
                */

                function displayPlayoffs(playoffs) {

                    const section =
                        document.getElementById(
                            'playoffsSection'
                        );

                    const container =
                        document.getElementById(
                            'playoffsContainer'
                        );

                    container.innerHTML = '';

                    if (
                        !playoffs ||
                        playoffs.length === 0
                    ) {

                        section.style.display = 'none';

                        return;

                    }

                    section.style.display = 'block';

                    playoffs.forEach(
                        function(match) {

                            let matchName =
                                match.match_type;

                            if (match.match_type === 'ROUND') {
                                matchName = 'Knockout Round';
                            } else if (match.match_type === 'SF') {
                                matchName = 'Semifinal';
                            } else if (match.match_type === 'SF1') {
                                matchName = 'Semifinal 1';
                            } else if (
                                match.match_type === 'SF2'
                            ) {
                                matchName = 'Semifinal 2';
                            } else if (
                                match.match_type === '3RD'
                            ) {
                                matchName = '3rd Place';
                            } else if (
                                match.match_type === 'FINAL'
                            ) {
                                matchName = 'Final';
                            }

                            const score =
                                match.team1_score !== null &&
                                match.team2_score !== null ?
                                `${match.team1_score} - ${match.team2_score}` :
                                'VS';

                            container.innerHTML += `

                                <div class="playoff-card">

                                    <div class="playoff-type">

                                        <i class="fas fa-trophy mr-1"></i>

                                        ${matchName}

                                        ${
                                            match.match_no
                                                ? `
                                                                                            <span class="float-right">
                                                                                                Match ${match.match_no}
                                                                                            </span>
                                                                                          `
                                                : ''
                                        }

                                    </div>

                                    <div class="playoff-body">

                                        <div class="playoff-team">

                                            <div class="team-number">
                                                ${match.team1_no || 'TBD'}
                                            </div>

                                            <div class="team-name-small">
                                                ${match.team1_name || 'Team pending'}
                                            </div>

                                            ${
                                                match.team1_bracket
                                                    ? `
                                                                                                <span class="bracket-label">
                                                                                                    Bracket ${match.team1_bracket}
                                                                                                </span>
                                                                                              `
                                                    : ''
                                            }

                                        </div>


                                        <div class="playoff-score">
                                            ${score}
                                        </div>


                                        <div class="playoff-team right">

                                            <div class="team-number">
                                                ${match.team2_no || 'TBD'}
                                            </div>

                                            <div class="team-name-small">
                                                ${match.team2_name || 'Team pending'}
                                            </div>

                                            ${
                                                match.team2_bracket
                                                    ? `
                                                                                                <span class="bracket-label">
                                                                                                    Bracket ${match.team2_bracket}
                                                                                                </span>
                                                                                              `
                                                    : ''
                                            }

                                        </div>

                                    </div>

                                    ${
                                        match.winner_no
                                            ? `
                                                                                        <div class="text-center py-2 border-top">

                                                                                            <span class="winner-badge">

                                                                                                <i class="fas fa-crown mr-1"></i>

                                                                                                Winner:
                                                                                                ${match.winner_no}

                                                                                            </span>

                                                                                        </div>
                                                                                      `
                                            : ''
                                    }

                                </div>

                            `;

                        }
                    );

                }


                /*
                |--------------------------------------------------------------------------
                | Display Podium
                |--------------------------------------------------------------------------
                */

                function displayPodium(podium) {

                    const section =
                        document.getElementById(
                            'podiumSection'
                        );

                    const champion =
                        podium.champion;

                    const runnerUp =
                        podium.runner_up;

                    const thirdPlace =
                        podium.third_place;


                    if (
                        !champion &&
                        !runnerUp &&
                        !thirdPlace
                    ) {

                        section.style.display = 'none';

                        return;

                    }

                    section.style.display = 'block';


                    /*
                    |--------------------------------------------------------------------------
                    | Champion
                    |--------------------------------------------------------------------------
                    */

                    document.getElementById(
                            'championTeam'
                        ).textContent =
                        champion ?
                        champion.team_no :
                        '—';

                    document.getElementById(
                            'championName'
                        ).textContent =
                        champion ?
                        champion.team_name :
                        'Final pending';

                    document.getElementById(
                            'championScore'
                        ).textContent =
                        champion ?
                        `Final Score: ${champion.score}` :
                        '—';


                    /*
                    |--------------------------------------------------------------------------
                    | Runner-up
                    |--------------------------------------------------------------------------
                    */

                    document.getElementById(
                            'runnerUpTeam'
                        ).textContent =
                        runnerUp ?
                        runnerUp.team_no :
                        '—';

                    document.getElementById(
                            'runnerUpName'
                        ).textContent =
                        runnerUp ?
                        runnerUp.team_name :
                        'Final pending';

                    document.getElementById(
                            'runnerUpScore'
                        ).textContent =
                        runnerUp ?
                        `Final Score: ${runnerUp.score}` :
                        '—';


                    /*
                    |--------------------------------------------------------------------------
                    | Third Place
                    |--------------------------------------------------------------------------
                    */

                    document.getElementById(
                            'thirdPlaceTeam'
                        ).textContent =
                        thirdPlace ?
                        thirdPlace.team_no :
                        '—';

                    document.getElementById(
                            'thirdPlaceName'
                        ).textContent =
                        thirdPlace ?
                        thirdPlace.team_name :
                        '3rd Place pending';

                    document.getElementById(
                            'thirdPlaceScore'
                        ).textContent =
                        thirdPlace ?
                        (thirdPlace.automatic ?
                            (thirdPlace.previous_match_score ?
                                `Awarded automatically · Previous match ${thirdPlace.previous_match_score}` :
                                'Awarded automatically (bye round)') :
                            `3rd Place Score: ${thirdPlace.score}`) :
                        '—';

                }


                /*
                |--------------------------------------------------------------------------
                | Reset Results
                |--------------------------------------------------------------------------
                */

                function resetResults(
                    hideLoading = true
                ) {

                    if (activeDataController) {
                        activeDataController.abort();
                    }

                    activeDataController = null;
                    activeDataKey = null;
                    dataRequestSequence++;

                    document.getElementById(
                        'tournamentInfo'
                    ).style.display = 'none';

                    document.getElementById(
                        'standingsSection'
                    ).style.display = 'none';

                    document.getElementById(
                        'matchesSection'
                    ).style.display = 'none';

                    document.getElementById(
                        'playoffsSection'
                    ).style.display = 'none';

                    document.getElementById(
                        'podiumSection'
                    ).style.display = 'none';

                    if (hideLoading) {
                        loading.style.display = 'none';
                    }

                }


                setInterval(function() {
                    if (!document.hidden) {
                        loadTournamentData({ silent: true });
                    }
                }, 5000);

                setInterval(function() {
                    if (!document.hidden) {
                        refreshFilters();
                    }
                }, 30000);

                document.addEventListener('visibilitychange', function() {
                    if (!document.hidden) {
                        loadTournamentData({ silent: true });
                        refreshFilters();
                    }
                });

            }
        );
    </script>

</body>

</html>
