<?php
    header('Content-Type: application/json');
    $incoming_data = file_get_contents("php://input");
    $data = json_decode($incoming_data, true);

    include("database_login.php");
    $connection = mysqli_connect($host, $user, $password, $database);

    if ($data && isset($data["Station"])) {
        if ($data["Station"] == "All") {
            $sql = "SELECT Station
                    FROM ubahn";
            $result = mysqli_query($connection, $sql);
            $arr = mysqli_fetch_all($result);
            
        } else {$arr = "ERROR";}
    } else {$arr = "ERROR";}

    mysqli_close($connection);
    echo json_encode($arr);
?>