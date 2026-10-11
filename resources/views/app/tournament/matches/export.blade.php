<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $tournament->name }} - {{ $team_category }} - {{ $team_bracket }}</title>
</head>
<body>
    <table>
        <thead>
            <tr>
                <th colspan="11">{{ $tournament->name }}</th>
            </tr>
            <tr>
                <th colspan="11">Category: {{ $team_category }} | Bracket: {{ $team_bracket }}</th>
            </tr>
            <tr>
                <th>Schedule No.</th>
                <th>Court</th>
                <th>Round</th>
                <th>Match</th>
                <th>Team 1 No.</th>
                <th>Team 1</th>
                <th>Team 1 Score</th>
                <th>Team 2 No.</th>
                <th>Team 2</th>
                <th>Team 2 Score</th>
                <th>Winner</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($matches as $match)
                <tr>
                    <td>{{ $match->schedule_no ?? '-' }}</td>
                    <td>{{ $match->court_no ? 'Court '.$match->court_no : '-' }}</td>
                    <td>{{ $match->round_no }}</td>
                    <td>{{ $match->match_no }}</td>
                    <td>{{ $match->team1_no }}</td>
                    <td>{{ $match->team1_name }}</td>
                    <td>{{ $match->team1_score ?? '-' }}</td>
                    <td>{{ $match->team2_no }}</td>
                    <td>{{ $match->team2_name }}</td>
                    <td>{{ $match->team2_score ?? '-' }}</td>
                    <td>{{ $match->winner_name ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
