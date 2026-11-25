<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $tournament->name }} - Timed Schedule</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111; margin: 24px; }
        h1, p { text-align: center; margin: 0 0 8px; }
        p { color: #555; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #333; padding: 7px 9px; text-align: left; }
        th { background: #eee; }
        .time, .court { white-space: nowrap; }
        @media print { body { margin: 0; } .no-print { display: none; } }
    </style>
</head>
<body>
    <button class="no-print" type="button" onclick="window.print()">Print</button>
    <h1>{{ $tournament->name }}</h1>
    <p>Timed schedule · {{ $tournament->date }} · {{ $tournament->venue }}</p>
    <p>{{ $tournament->court_count }} court(s) · {{ $tournament->match_duration_minutes }} minutes per game</p>

    <table>
        <thead>
            <tr>
                <th>No.</th>
                <th>Time Range</th>
                <th>Category</th>
                <th>Bracket</th>
                <th>Court</th>
                <th>Team 1</th>
                <th>Team 2</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($courtSchedule as $match)
                <tr>
                    <td>{{ $match->schedule_no }}</td>
                    <td class="time">{{ $match->schedule_time }}</td>
                    <td>{{ $match->team_category }}</td>
                    <td>{{ $match->team_bracket }}</td>
                    <td class="court">{{ $match->court_no }}</td>
                    <td>{{ $match->team1_no }} {{ $match->team1_name }}</td>
                    <td>{{ $match->team2_no }} {{ $match->team2_name }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No scheduled matches.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
