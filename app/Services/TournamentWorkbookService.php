<?php

namespace App\Services;

use Illuminate\Support\Collection;

class TournamentWorkbookService
{
    public function build(object $tournament, Collection $teams, Collection $matches): string
    {
        $matchRows = $matches->values();
        $matchFirstRow = 2;
        $matchLastRow = max($matchRows->count() + 1, 2);

        return '<?xml version="1.0"?>'."\n"
            .'<?mso-application progid="Excel.Sheet"?>'."\n"
            .'<Workbook xmlns="urn:schemas-microsoft-com:office:spreadsheet" '
            .'xmlns:o="urn:schemas-microsoft-com:office:office" '
            .'xmlns:x="urn:schemas-microsoft-com:office:excel" '
            .'xmlns:ss="urn:schemas-microsoft-com:office:spreadsheet" '
            .'xmlns:html="http://www.w3.org/TR/REC-html40">'
            .'<DocumentProperties xmlns="urn:schemas-microsoft-com:office:office">'
            .$this->element('Author', 'Dinkinapp')
            .$this->element('Title', $tournament->name.' offline tournament backup')
            .'</DocumentProperties>'
            .'<ExcelWorkbook xmlns="urn:schemas-microsoft-com:office:excel">'
            .'<ProtectStructure>False</ProtectStructure><ProtectWindows>False</ProtectWindows>'
            .'<CalculateBeforeSave>True</CalculateBeforeSave>'
            .'</ExcelWorkbook>'
            .'<Styles>'.$this->styles().'</Styles>'
            .$this->instructionsSheet($tournament)
            .$this->matchesSheet($matchRows, $matchFirstRow, $matchLastRow)
            .$this->standingsSheet($teams, $matchFirstRow, $matchLastRow)
            .'</Workbook>';
    }

    private function instructionsSheet(object $tournament): string
    {
        return '<Worksheet ss:Name="Instructions"><Table>'
            .$this->row([$this->textCell('Offline Tournament Backup', 'Title')])
            .$this->row([$this->textCell($tournament->name, 'Subtitle')])
            .$this->row([$this->textCell('Enter scores only in the yellow Team 1 Score and Team 2 Score columns on the Matches sheet.')])
            .$this->row([$this->textCell('The Winner, Status, Games Played, Wins, Losses, Points For, Points Against, Point Differential, and Rank columns are formulas.')])
            .$this->row([$this->textCell('Points For and Points Against follow the application process: they are recorded only for matches won by the team.')])
            .$this->row([$this->textCell('Standings are automatically separated by team category and bracket and work in Excel and LibreOffice Calc.')])
            .'</Table></Worksheet>';
    }

    private function matchesSheet(Collection $matches, int $firstRow, int $lastRow): string
    {
        $headers = ['Match ID', 'Category', 'Bracket', 'Round', 'Match', 'Schedule', 'Court', 'Team 1 ID', 'Team 1 No.', 'Team 1', 'Team 1 Score', 'Team 2 ID', 'Team 2 No.', 'Team 2', 'Team 2 Score', 'Winner ID', 'Winner', 'Status'];
        $xml = '<Worksheet ss:Name="Matches"><Table>'.$this->row(array_map(fn (string $header): string => $this->textCell($header, 'Header'), $headers));

        foreach ($matches as $index => $match) {
            $rowNumber = $firstRow + $index;
            $xml .= '<Row>'
                .$this->numberCell($match->id)
                .$this->textCell($match->team_category)
                .$this->textCell($match->team_bracket)
                .$this->textCell($match->round_no)
                .$this->textCell($match->match_no)
                .$this->numberOrTextCell($match->schedule_no)
                .$this->numberOrTextCell($match->court_no)
                .$this->numberCell($match->team1_id)
                .$this->textCell($match->team1_no)
                .$this->textCell($match->team1_name)
                .$this->numberOrBlankCell($match->team1_score, 'Input')
                .$this->numberCell($match->team2_id)
                .$this->textCell($match->team2_no)
                .$this->textCell($match->team2_name)
                .$this->numberOrBlankCell($match->team2_score, 'Input')
                .$this->formulaCell("=IF(OR(R{$rowNumber}C11=\"\",R{$rowNumber}C15=\"\"),\"\",IF(R{$rowNumber}C11=R{$rowNumber}C15,\"\",IF(R{$rowNumber}C11>R{$rowNumber}C15,R{$rowNumber}C8,R{$rowNumber}C12)))", 'Number')
                .$this->formulaCell("=IF(OR(R{$rowNumber}C11=\"\",R{$rowNumber}C15=\"\"),\"Pending\",IF(R{$rowNumber}C11=R{$rowNumber}C15,\"Tied / Ongoing\",\"Final\"))", 'String')
                .$this->formulaCell("=IF(OR(R{$rowNumber}C11=\"\",R{$rowNumber}C15=\"\"),\"Pending\",IF(R{$rowNumber}C11=R{$rowNumber}C15,\"Tied / Ongoing\",IF(R{$rowNumber}C11>R{$rowNumber}C15,R{$rowNumber}C10,R{$rowNumber}C14)))", 'String')
                .'</Row>';
        }

        return $xml.'</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>1</SplitHorizontal><TopRowBottomPane>1</TopRowBottomPane></WorksheetOptions></Worksheet>';
    }

    private function standingsSheet(Collection $teams, int $matchFirstRow, int $matchLastRow): string
    {
        $headers = ['Team ID', 'Category', 'Bracket', 'Team No.', 'Team', 'Games Played', 'Wins', 'Losses', 'Points For', 'Points Against', 'Point Differential', 'Rank'];
        $standingsLastRow = $teams->count() + 1;
        $xml = '<Worksheet ss:Name="Standings"><Table>'.$this->row(array_map(fn (string $header): string => $this->textCell($header, 'Header'), $headers));

        foreach ($teams->values() as $index => $team) {
            $rowNumber = $index + 2;
            $category = "R{$rowNumber}C2";
            $bracket = "R{$rowNumber}C3";
            $teamId = "R{$rowNumber}C1";
            $matchRange = fn (int $column): string => "Matches!R{$matchFirstRow}C{$column}:R{$matchLastRow}C{$column}";
            $gamesPlayed = "COUNTIFS({$matchRange(2)},{$category},{$matchRange(3)},{$bracket},{$matchRange(8)},{$teamId},{$matchRange(11)},\"<>\",{$matchRange(15)},\"<>\")+COUNTIFS({$matchRange(2)},{$category},{$matchRange(3)},{$bracket},{$matchRange(12)},{$teamId},{$matchRange(11)},\"<>\",{$matchRange(15)},\"<>\")";
            $wins = "COUNTIFS({$matchRange(2)},{$category},{$matchRange(3)},{$bracket},{$matchRange(16)},{$teamId})";
            $losses = "{$gamesPlayed}-{$wins}";
            $pointsFor = "SUMIFS({$matchRange(11)},{$matchRange(2)},{$category},{$matchRange(3)},{$bracket},{$matchRange(8)},{$teamId},{$matchRange(16)},{$teamId})+SUMIFS({$matchRange(15)},{$matchRange(2)},{$category},{$matchRange(3)},{$bracket},{$matchRange(12)},{$teamId},{$matchRange(16)},{$teamId})";
            $pointsAgainst = "SUMIFS({$matchRange(15)},{$matchRange(2)},{$category},{$matchRange(3)},{$bracket},{$matchRange(8)},{$teamId},{$matchRange(16)},{$teamId})+SUMIFS({$matchRange(11)},{$matchRange(2)},{$category},{$matchRange(3)},{$bracket},{$matchRange(12)},{$teamId},{$matchRange(16)},{$teamId})";

            $xml .= '<Row>'
                .$this->numberCell($team->id)
                .$this->textCell($team->team_category)
                .$this->textCell($team->team_bracket)
                .$this->textCell($team->team_no)
                .$this->textCell($team->team_name)
                .$this->formulaCell('='.$gamesPlayed, 'Number')
                .$this->formulaCell('='.$wins, 'Number')
                .$this->formulaCell('='.$losses, 'Number')
                .$this->formulaCell('='.$pointsFor, 'Number')
                .$this->formulaCell('='.$pointsAgainst, 'Number')
                .$this->formulaCell('=RC[-2]-RC[-1]', 'Number')
                .$this->formulaCell("=1+COUNTIFS(R2C2:R{$standingsLastRow}C2,{$category},R2C3:R{$standingsLastRow}C3,{$bracket},R2C7:R{$standingsLastRow}C7,\">\"&RC[-5])+COUNTIFS(R2C2:R{$standingsLastRow}C2,{$category},R2C3:R{$standingsLastRow}C3,{$bracket},R2C7:R{$standingsLastRow}C7,RC[-5],R2C11:R{$standingsLastRow}C11,\">\"&RC[-1])", 'Number')
                .'</Row>';
        }

        return $xml.'</Table><WorksheetOptions xmlns="urn:schemas-microsoft-com:office:excel"><FreezePanes/><FrozenNoSplit/><SplitHorizontal>1</SplitHorizontal><TopRowBottomPane>1</TopRowBottomPane></WorksheetOptions></Worksheet>';
    }

    private function styles(): string
    {
        return '<Style ss:ID="Default" ss:Name="Normal"><Alignment ss:Vertical="Center"/><Font ss:FontName="Calibri" ss:Size="11"/></Style>'
            .'<Style ss:ID="Title"><Font ss:Bold="1" ss:Size="16"/></Style>'
            .'<Style ss:ID="Subtitle"><Font ss:Bold="1" ss:Size="13"/></Style>'
            .'<Style ss:ID="Header"><Font ss:Bold="1"/><Interior ss:Color="#D9EAF7" ss:Pattern="Solid"/><Alignment ss:WrapText="1"/></Style>'
            .'<Style ss:ID="Input"><Interior ss:Color="#FFF2CC" ss:Pattern="Solid"/></Style>';
    }

    private function row(array $cells): string
    {
        return '<Row>'.implode('', $cells).'</Row>';
    }

    private function textCell(mixed $value, ?string $style = null): string
    {
        return $this->cell($value, 'String', $style);
    }

    private function numberCell(mixed $value, ?string $style = null): string
    {
        return $this->cell($value, 'Number', $style);
    }

    private function numberOrTextCell(mixed $value, ?string $style = null): string
    {
        return is_numeric($value) ? $this->numberCell($value, $style) : $this->textCell($value, $style);
    }

    private function numberOrBlankCell(mixed $value, ?string $style = null): string
    {
        return $value === null ? '<Cell ss:StyleID="'.$style.'"/>' : $this->numberCell($value, $style);
    }

    private function formulaCell(string $formula, string $type): string
    {
        return '<Cell ss:Formula="'.$this->escape($formula).'"><Data ss:Type="'.$type.'"></Data></Cell>';
    }

    private function cell(mixed $value, string $type, ?string $style = null): string
    {
        $styleAttribute = $style === null ? '' : ' ss:StyleID="'.$this->escape($style).'"';

        return '<Cell'.$styleAttribute.'><Data ss:Type="'.$type.'">'.$this->escape((string) ($value ?? '')).'</Data></Cell>';
    }

    private function element(string $name, mixed $value): string
    {
        return '<'.$name.'>'.$this->escape((string) $value).'</'.$name.'>';
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }
}
