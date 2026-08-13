<?php
    session_start();

    // Daten vom Java-Script entgegennehmen und verarbeiten
    header('Content-Type: application/json');
    $incoming_data = file_get_contents("php://input");
    $data_guess = json_decode($incoming_data, true);

    // Datenbank Login-Daten und Verbindungsaufbau
    include("database_login.php");
    $connection = mysqli_connect($host, $user, $password, $database);

    // Random Ziel-Station generieren oder gespeicherte aus Session-Cookies lesen
    if (!isset($_SESSION["ziel_data"])) {
        $sql = "SELECT * 
                FROM ubahn 
                ORDER BY RAND() 
                LIMIT 1";
        $result = mysqli_query($connection, $sql);
        $ziel_row = mysqli_fetch_assoc($result);
        $_SESSION["ziel_data"] = $ziel_row;
    } else {
        $ziel_row = $_SESSION["ziel_data"];
    }

    // Guess von Nutzer entgegennehmen
    if ($data_guess && isset($data_guess["Station"])) {
        if ($ziel_row["Station"] == $data_guess["Station"]) { // Richtige Station vom Nutzer erraten
            $_SESSION["ziel_data"] = NULL; // Ziel-Station zurücksetzen
        }
    } else {
        http_response_code(400);
        $data_to_send = [
            "ERROR" => True
        ];
        throw new Error("Daten fehlerhaft oder nicht übermittelt");
    }
    
    // Informationen zum Guess abfragen
    $guessed_name = $data_guess["Station"];
    $sql = "SELECT *
            FROM ubahn
            WHERE Station = '$guessed_name'
            LIMIT 1";
    $result = mysqli_query($connection, $sql);
    $guess_row = mysqli_fetch_assoc($result);

    if ($guess_row["Station"] != $ziel_row["Station"]) {
        // Berechnung des Richtungswinkel von Guess zum Ziel
        $latitude_guess = deg2rad($guess_row["Latitude"]);
        $longitude_guess = deg2rad($guess_row["Longitude"]);
        $latitude_ziel = deg2rad($ziel_row["Latitude"]);
        $longitude_ziel = deg2rad($ziel_row["Longitude"]);
        $Längengrad = $longitude_ziel - $longitude_guess;

        $y = sin($Längengrad) * cos($latitude_ziel);
        $x = cos($latitude_guess) * sin($latitude_ziel) - sin($latitude_guess) * cos($latitude_ziel) * cos($Längengrad);

        $erg = atan2($y, $x);
        $winkel = rad2deg($erg);

        if ($winkel < 0) { 
            $winkel += 360;
        }
        $winkel = round($winkel, 1);

        // Himmelsrichtung aus Winkel ableiten
        $direction = match (true) {
            $winkel > 337.5 || $winkel <= 22.5 => "Norden",
            $winkel > 22.5 && $winkel <= 67.5 => "Nord-Osten",
            $winkel > 67.5 && $winkel <= 112.5 => "Osten",
            $winkel > 112.5 && $winkel <= 157.5 => "Süd-Osten",
            $winkel > 157.5 && $winkel <= 202.5 => "Süden",
            $winkel > 202.5 && $winkel <= 247.5 => "Süd-Westen",
            $winkel > 247.5 && $winkel <= 292.5 => "Westen",
            $winkel > 292.5 && $winkel <= 337.5 => "Nord-Westen",
            default => "",
        };
    } else {$direction = "";}

    // An Station verkehrende Linien
    $guess_line_arr = array_map('trim', explode(",", $guess_row["Linie"]));
    $ziel_line_arr = array_map('trim', explode(",", $ziel_row["Linie"]));
    if ($guess_line_arr == $ziel_line_arr) {
        $correct_lines = "Green";
    } elseif (!empty(array_intersect($ziel_line_arr, $guess_line_arr))) {
        $correct_lines = "Yellow";
    } else {$correct_lines = "Red";}

    // Korrekter Bezirk
    if ($guess_row["Bezirk"] == $ziel_row["Bezirk"]) {
        $correct_district = "Green";
    } else {$correct_district = "Red";}
    
    // Stationsnamen-Länge
    $guess_len = strlen(preg_replace("/[^A-Za-zäöüÄÖÜß]/", "", $guess_row["Station"]));
    $ziel_len = strlen(preg_replace("/[^A-Za-zäöüÄÖÜß]/", "", $ziel_row["Station"]));
    if ($guess_len == $ziel_len) {
        $correct_word_count = "Green";
    } else if (abs($guess_len - $ziel_len) <= 2) {
        $correct_word_count = "Yellow";
    } else {$correct_word_count = "Red";}

    // Schließen der Datenbankverbindung
    mysqli_close($connection);

    // Vorbereiten und zurücksenden der Daten
    $data_to_send = [
        "ERROR" => False,
        "guess" => $guess_row["Station"],
        "direction" => $direction,
        "correct_lines" => $correct_lines,
        "lines" => $guess_row["Linie"],
        "correct_district" => $correct_district,
        "district" => $guess_row["Bezirk"],
        "correct_len" => $correct_word_count
    ];
    echo json_encode($data_to_send);
?>