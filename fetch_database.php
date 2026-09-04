<?php
    header('Content-Type: application/json');
    $incoming_data = file_get_contents("php://input");
    $data = json_decode($incoming_data, true);

    include("database_login.php");
    $connection = mysqli_connect($host, $user, $password, $database);
    $selected_transport = ['sbahn_berlin', 'underground_berlin'];

    $sub_queries = [];
    foreach($selected_transport as $i) {
        $sub_queries[] = "SELECT * FROM $i";
    }
    $union = implode(" UNION ALL ", $sub_queries);

    if ($data && isset($data["Station"])) {
        $sql = "SELECT Station
                FROM ($union) AS Vereinigung
                GROUP BY Station";
        $result = mysqli_query($connection, $sql);
        $arr = mysqli_fetch_all($result);
    } else {$arr = "ERROR";}

    mysqli_close($connection);
    echo json_encode($arr);
?>