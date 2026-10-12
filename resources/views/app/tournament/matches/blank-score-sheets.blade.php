<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blank Score Sheets</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 6mm;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            color: #111827;
            font-family: Arial, sans-serif;
            font-size: 9px;
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

        .toolbar a { background: #6b7280; }

        .score-sheet {
            height: 92mm;
            min-height: 92mm;
            overflow: hidden;
            padding: 4mm;
            margin-bottom: 2mm;
            border: 1px solid #111827;
            page-break-inside: avoid;
        }

        .score-sheet:nth-of-type(3n) { page-break-after: always; }

        .heading { text-align: center; }
        .heading h1 { margin: 0; font-size: 15px; letter-spacing: .5px; }
        .heading h2 { margin: 1px 0; font-size: 12px; }
        .heading p { margin: 1px 0 3px; font-size: 9px; }

        .match-meta,
        .team-info,
        .officials {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 3px 10px;
            margin-bottom: 5px;
        }

        .match-meta {
            grid-template-columns: repeat(4, 1fr);
            padding: 4px;
            border: 1px solid #9ca3af;
            text-align: center;
        }

        .team-box { padding: 4px; border: 1px solid #9ca3af; }
        .line { display: inline-block; min-width: 60%; border-bottom: 1px solid #374151; }
        .serve-timeout { margin-top: 3px; font-size: 9px; }

        .serve-box,
        .final-score-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 14px;
            height: 14px;
            border: 1px solid #374151;
        }

        .final-score-box {
            width: 32px;
            height: 22px;
            margin: 0 4px;
            vertical-align: middle;
        }

        .score-title { margin: 4px 0; font-weight: 700; text-align: center; }

        .tracker-row {
            display: grid;
            grid-template-columns: 75px 1fr;
            align-items: center;
            gap: 5px;
            margin: 4px 0 3px;
        }

        .tracker-label { font-size: 9px; font-weight: 700; }
        .tracker-boxes { display: grid; grid-template-columns: repeat(22, 1fr); gap: 2px; }

        .number-box {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 15px;
            height: 15px;
            border: 1px solid #374151;
            font-size: 8px;
            line-height: 1;
        }

        .final-score { margin-top: 6px; text-align: center; font-weight: 700; }

        .footer-signatures {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
            margin-top: 9px;
            text-align: center;
        }

        .signature-line {
            padding-top: 12px;
            border-top: 1px solid #374151;
            font-size: 9px;
        }

        @media print {
            .toolbar { display: none; }
            .score-sheet { margin-bottom: 0; }
        }
    </style>
</head>

<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Print / Save as PDF</button>
        <a href="{{ route('dashboard') }}">Back to Dashboard</a>
        <div style="margin-top: 6px; color: #4b5563;">Print at 100% scale to fit three blank score sheets per A4 page.</div>
    </div>

    @for ($sheet = 1; $sheet <= 3; $sheet++)
        <section class="score-sheet">
            <div class="heading">
                <h1>DinKin App</h1>
                <h2>Blank Score Sheet</h2>
                <p>Date: <span class="line"></span> · Category: <span class="line"></span></p>
            </div>

            <div class="match-meta">
                <div><strong>Round</strong><br>&nbsp;</div>
                <div><strong>Match</strong><br>&nbsp;</div>
                <div><strong>Schedule</strong><br>&nbsp;</div>
                <div><strong>Court</strong><br>&nbsp;</div>
            </div>

            <div class="team-info">
                @for ($team = 1; $team <= 2; $team++)
                    <div class="team-box">
                        <strong>Team {{ $team }}: <span class="line"></span></strong><br>
                        Player 1: <span class="line"></span><br>
                        Player 2: <span class="line"></span>
                        <div class="serve-timeout">
                            Serve: <span class="serve-box"></span> 1st&nbsp; <span class="serve-box"></span> 2nd
                            &nbsp; Timeouts: ☐ ☐ ☐
                        </div>
                    </div>
                @endfor
            </div>

            <div class="score-title">Score Tracking — mark one numbered box for each score</div>
            @for ($team = 1; $team <= 2; $team++)
                <div class="tracker-row">
                    <div class="tracker-label">Team {{ $team }} Score</div>
                    <div class="tracker-boxes">
                        @for ($point = 0; $point <= 21; $point++)
                            <span class="number-box">{{ $point }}</span>
                        @endfor
                    </div>
                </div>
            @endfor

            <div class="officials">
                <div>Referee Name: <span class="line"></span></div>
            </div>

            <div class="final-score">
                Final Score: Team 1 <span class="final-score-box"></span>
                Team 2 <span class="final-score-box"></span>
            </div>

            <div class="footer-signatures">
                <div class="signature-line">Team 1 Signature</div>
                <div class="signature-line">Team 2 Signature</div>
            </div>
        </section>
    @endfor
</body>

</html>
