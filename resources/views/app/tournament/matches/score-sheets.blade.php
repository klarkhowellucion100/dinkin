<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tournament->name }} - Score Sheets</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 6mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #111827;
            font-family: Arial, sans-serif;
            font-size: 8px;
        }

        .toolbar {
            padding: 12px;
            margin-bottom: 12px;
            text-align: center;
            background: #f3f4f6;
        }

        .toolbar button,
        .toolbar a {
            display: inline-block;
            padding: 8px 14px;
            border: 0;
            border-radius: 4px;
            color: #fff;
            background: #1d4ed8;
            cursor: pointer;
            font-size: 12px;
            text-decoration: none;
        }

        .toolbar a {
            background: #6b7280;
        }

        .score-sheet {
            height: 92mm;
            min-height: 92mm;
            overflow: hidden;
            padding: 3mm;
            margin-bottom: 2mm;
            border: 1px solid #111827;
            page-break-inside: avoid;
        }

        .score-sheet:nth-of-type(3n) {
            page-break-after: always;
        }

        .heading {
            text-align: center;
        }

        .heading h1 {
            margin: 0;
            font-size: 12px;
            letter-spacing: .5px;
        }

        .heading h2 {
            margin: 1px 0;
            font-size: 10px;
        }

        .heading p {
            margin: 1px 0 3px;
            font-size: 7px;
        }

        .match-meta,
        .team-info,
        .officials {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2px 8px;
            margin-bottom: 3px;
        }

        .match-meta {
            grid-template-columns: repeat(4, 1fr);
            padding: 2px;
            border: 1px solid #9ca3af;
            text-align: center;
        }

        .team-box {
            padding: 2px;
            border: 1px solid #9ca3af;
        }

        .line {
            display: inline-block;
            min-width: 65%;
            border-bottom: 1px solid #374151;
        }

        .serve-timeout {
            margin-top: 1px;
            font-size: 7px;
        }

        .tracker-title {
            margin: 2px 0 1px;
            font-weight: 700;
            text-align: center;
        }

        .serve-box,
        .final-score-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 9px;
            height: 9px;
            border: 1px solid #374151;
        }

        .final-score-box {
            width: 22px;
            height: 15px;
            margin: 0 2px;
            vertical-align: middle;
        }

        .tracker-row {
            display: grid;
            grid-template-columns: 58px 1fr;
            align-items: center;
            gap: 3px;
            margin-bottom: 1px;
            margin-top: 2px;
        }

        .tracker-label {
            font-size: 7px;
            font-weight: 700;
        }

        .tracker-boxes {
            display: grid;
            grid-template-columns: repeat(22, 1fr);
            gap: 1px;
        }

        .number-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 11px;
            height: 11px;
            border: 1px solid #374151;
            font-size: 6px;
            line-height: 1;
        }

        .score-title {
            margin: 2px 0;
            font-weight: 700;
            text-align: center;
        }

        .footer-signatures {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-top: 5px;
            text-align: center;
        }

        .final-score {
            margin-top: 3px;
            text-align: center;
            font-weight: 700;
        }

        .signature-line {
            padding-top: 8px;
            border-top: 1px solid #374151;
            font-size: 7px;
        }

        @media print {
            .toolbar {
                display: none;
            }

            .score-sheet {
                margin-bottom: 0;
            }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ route('tournament.matches.view', [
            'tournament_id' => Crypt::encryptString($tournament->id),
            'team_bracket' => $team_bracket,
            'team_category' => $team_category,
        ]) }}">Back to Matches</a>
        <div style="margin-top: 6px; color: #4b5563;">Print at 100% scale to fit three score sheets per A4 page.</div>
    </div>

    @forelse ($matches as $match)
        <section class="score-sheet">
            <div class="heading">
                <h1>DinKin App</h1>
                <h2>{{ $tournament->name }}</h2>
                <p>{{ \Illuminate\Support\Carbon::parse($tournament->date)->format('F j, Y') }} · {{ $team_category }} · Bracket {{ $team_bracket }}</p>
            </div>

            <div class="match-meta">
                <div><strong>Round</strong><br>{{ $match->round_no }}</div>
                <div><strong>Match</strong><br>{{ $match->match_no }}</div>
                <div><strong>Schedule</strong><br>{{ $match->schedule_no ?? '-' }}</div>
                <div><strong>Court</strong><br>{{ $match->court_no ?? '-' }}</div>
            </div>

            <div class="team-info">
                <div class="team-box">
                    <strong>Team 1: {{ $match->team1_name }}</strong><br>
                    Player 1: <span class="line"></span><br>
                    Player 2: <span class="line"></span>
                    <div class="serve-timeout">
                        <span>Serve: <span class="serve-box"></span> 1st &nbsp; <span class="serve-box"></span> 2nd</span>
                        <span>Timeouts: ☐ ☐ ☐</span>
                    </div>
                </div>
                <div class="team-box">
                    <strong>Team 2: {{ $match->team2_name }}</strong><br>
                    Player 1: <span class="line"></span><br>
                    Player 2: <span class="line"></span>
                    <div class="serve-timeout">
                        <span>Serve: <span class="serve-box"></span> 1st &nbsp; <span class="serve-box"></span> 2nd</span>
                        <span>Timeouts: ☐ ☐ ☐</span>
                    </div>
                </div>
            </div>

            <div class="score-title">Score Tracking — mark one numbered box for each score</div>
            <div class="tracker-row">
                <div class="tracker-label">Team 1 Score</div>
                <div class="tracker-boxes">
                    @for ($point = 0; $point <= 21; $point++)
                        <span class="number-box">{{ $point }}</span>
                    @endfor
                </div>
            </div>
            <div class="tracker-row">
                <div class="tracker-label">Team 2 Score</div>
                <div class="tracker-boxes">
                    @for ($point = 0; $point <= 21; $point++)
                        <span class="number-box">{{ $point }}</span>
                    @endfor
                </div>
            </div>

            <div class="officials">
                <div>Referee Name: <span class="line"></span></div>
            </div>

            <div class="final-score">
                Final Score: Team 1 <span class="final-score-box"></span>
                Team 2 <span class="final-score-box"></span>
            </div>

            <div class="footer-signatures">
                <div class="signature-line">{{ $match->team1_name }}</div>
                <div class="signature-line">{{ $match->team2_name }}</div>
            </div>
        </section>
    @empty
        <p>No matches are available for this category and bracket.</p>
    @endforelse
</body>

</html>
