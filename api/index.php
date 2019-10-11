<?php
// required headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: access");
// //header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Credentials: true");
// header('Content-Type: application/json');

// Associative array of routes with general info about each route.
// Really used to help document our API
$routes = [
    ['route' => "words", 'type' => 'GET', 'params' => ['side' => 'string', 'id' => 'int']],
    ['route' => "phonemes", 'type' => 'GET', 'params' => ['side' => 'string', 'id' => 'int']],
];

// Initialize route to nothing.
$route = false;
// See if a route choice is in either the POST or GET array.
if (array_key_exists('route', $_GET)) {
    $route = $_GET['route'];
} else {
    $route = false;
}
$side = $_GET['side'];
$id = $_GET['id'];
$dataPoints = array();
// Choose which route to run
switch ($route) {
    case 'words':getScores($dataPoints, $_GET, 'words');
        break;
    case 'phonemes':getScores($dataPoints, $_GET, 'phonemes');
        break;
        //default: show_routes($route);
}

/**
 * Dumps the routes out to the browser simply to help programmer see what is available.
 * Params:
 *     $route [string] : a route name passed in if there wasnt a route to match.
 * Returns:
 *     prints response (json)
 */
// function show_routes($inroute=null)
// {
//     global $routes;
//     $scheme = $_SERVER['REQUEST_SCHEME'];   // gets http or https
//     $host = $_SERVER['HTTP_HOST'];          // gets domain name (or ip address)
//     $script = $_SERVER['PHP_SELF'];         // gets name of 'this' file
//     $prefix = "{$scheme}://{$host}{$script}";
//     $prefix = str_replace('index.php','',$prefix);
//     $response = [];
//     $response['route'] = $inroute;
//     $i = 0;
//     foreach ($routes as $r) {
//         $temp = [];
//         foreach ($r as $k => $v) {
//             if ($k == 'route') {
//                 $v = $prefix.$v;
//             }
//             $temp[$k] = $v;
//         }
//         $response[] = $temp;
//     }
// if($inroute){
//     echo build_response($response,false,"Error: Route:{$inroute} does not exist!");
// }else{
//     echo build_response($response);
// }
//     exit;
// }

function getScores($dataPoints, $params, $cncScoreType)
{

    require "../config/config.php";
    $conn = mysqli_connect($auth['host'], $auth['user'], $auth['password'], $auth['db']);
    if (mysqli_connect_errno()) {
        echo "Failed to connect to MySQL: " . mysqli_connect_error();
        die();
    }
    $side = $params['side'];
    $id = $params['id'];

    if ($side == 'left') {
        $non_measure = 'right';
    } elseif ($side == 'right') {
        $non_measure = 'left';
    }
    if ($cncScoreType == 'words') {
        $sql = "SELECT DISTINCT audiogramResults.patient_id, audiogramResults.date, audiogramResults.left_measures,audiogramResults.right_measures, audiogramResults.right_condition,audiogramResults.left_condition,cncResults.ConditionsID,cncResults.`Phonemes Correct`,cncResults.`Words with 3 Phonemes Correct` from audiogramResults, cncResults
            where audiogramResults.{$side}_measures != '[]' AND audiogramResults.{$non_measure}_measures = '[]' AND audiogramResults.patient_id=cncResults.PatientID and audiogramResults.date=cncResults.TestDate AND
            `cncResults`.`Words with 3 Phonemes Correct` = '{$id}' and cncResults.ConditionsID=(SELECT audioConditionList.ConditionsID from audioConditionList where audiogramResults.left_condition=audioConditionList.LeftAidCondition and audiogramResults.right_condition=audioConditionList.RightAidCondition) ORDER BY cncResults.`Words with 3 Phonemes Correct` ASC";
        if ($side == 'both') {
            $sql = "SELECT DISTINCT audiogramResults.patient_id, audiogramResults.date, audiogramResults.left_measures,audiogramResults.right_measures, audiogramResults.right_condition,audiogramResults.left_condition,cncResults.ConditionsID,cncResults.`Phonemes Correct`,cncResults.`Words with 3 Phonemes Correct` from audiogramResults, cncResults
            where (audiogramResults.left_measures != '[]' or audiogramResults.right_measures != '[]') AND audiogramResults.patient_id=cncResults.PatientID and audiogramResults.date=cncResults.TestDate AND
            `cncResults`.`Words with 3 Phonemes Correct` = '{$id}' and cncResults.ConditionsID=(SELECT audioConditionList.ConditionsID from audioConditionList where audiogramResults.left_condition=audioConditionList.LeftAidCondition and audiogramResults.right_condition=audioConditionList.RightAidCondition) ORDER BY cncResults.`Words with 3 Phonemes Correct` ASC";
        }
    } elseif ($cncScoreType == 'phonemes') {
        $sql = "SELECT DISTINCT audiogramResults.patient_id, audiogramResults.date, audiogramResults.left_measures,audiogramResults.right_measures, audiogramResults.right_condition,audiogramResults.left_condition,cncResults.ConditionsID,cncResults.`Phonemes Correct`,cncResults.`Words with 3 Phonemes Correct` from audiogramResults, cncResults
        where audiogramResults.{$side}_measures != '[]' AND audiogramResults.{$non_measure}_measures = '[]' AND audiogramResults.patient_id=cncResults.PatientID and audiogramResults.date=cncResults.TestDate AND
        `cncResults`.`Phonemes Correct` = '{$id}' and cncResults.ConditionsID=(SELECT audioConditionList.ConditionsID from audioConditionList where audiogramResults.left_condition=audioConditionList.LeftAidCondition and audiogramResults.right_condition=audioConditionList.RightAidCondition) ORDER BY cncResults.`Words with 3 Phonemes Correct` ASC";
        if ($side == 'both') {
            $sql = "SELECT DISTINCT audiogramResults.patient_id, audiogramResults.date, audiogramResults.left_measures,audiogramResults.right_measures, audiogramResults.right_condition,audiogramResults.left_condition,cncResults.ConditionsID,cncResults.`Phonemes Correct`,cncResults.`Words with 3 Phonemes Correct` from audiogramResults, cncResults
            where (audiogramResults.left_measures != '[]' or audiogramResults.right_measures != '[]') AND audiogramResults.patient_id=cncResults.PatientID and audiogramResults.date=cncResults.TestDate AND
            `cncResults`.`Phonemes Correct` = '{$id}' and cncResults.ConditionsID=(SELECT audioConditionList.ConditionsID from audioConditionList where audiogramResults.left_condition=audioConditionList.LeftAidCondition and audiogramResults.right_condition=audioConditionList.RightAidCondition) ORDER BY cncResults.`Words with 3 Phonemes Correct` ASC";

        }
    }
    global $dataPoints;
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        // output data of each row
        $count = 1;
        while ($row = $result->fetch_assoc()) {
            
            $leftmeasure = json_decode($row['left_measures'], true);
            $rightmeasure = json_decode($row['right_measures'], true);
            if ($leftmeasure) {
                $Points = array();
                foreach ($leftmeasure as $value) {
                    $arr = json_decode($value, true);
                    $frequency = $arr['attrs']['audioValues']['frequency'];
                    $decibels = $arr['attrs']['audioValues']['decibels'];
                    $Points[] = array("x" => $frequency, "y" => $decibels);
                    //print_r($Points);
                    //array_push($dataPoints, array("x" => $frequency, "y" => $decibels));
                }
                asort($Points);
                array_push($dataPoints, $Points);
            }
            if ($rightmeasure) {
                $Points = array();
                foreach ($rightmeasure as $value) {
                    $arr = json_decode($value, true);
                    $frequency = $arr['attrs']['audioValues']['frequency'];
                    $decibels = $arr['attrs']['audioValues']['decibels'];
                    $Points[] = array("x" => $frequency, "y" => $decibels);
                    //array_push($dataPoints, array("x" => $frequency, "y" => $decibels));
                }
                // Print("Presoret");
                // var_dump($Points);
                asort($Points);
                // Print("After sort");
                // var_dump($Points);
                array_push($dataPoints, $Points);
            } 
        }
        // die();
    }
}
?>
<!-- graph score relations for each score -->
<!DOCTYPE html>
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <title></title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="stylesheet" href="">


        <script>
        window.onload = function () {
        var chart = new CanvasJS.Chart("chartContainer", {
            height: 1000,
            animationEnabled: true,
            zoomEnabled: true,
            title:{
                text: <?php echo "\"Score Relation $route on $side with score $id\""; ?>
            },
            axisX: {
                title:"Frequency"
            },
            axisY:{
                maximum: 150,
                interval: 20,
                title: "Decibels"
            },
            legend:{
                cursor: "pointer",
                itemclick: function (e) {
                if (typeof (e.dataSeries.visible) === "undefined" || e.dataSeries.visible) {
                        e.dataSeries.visible = false;
                } else {
                        e.dataSeries.visible = true;
                }
                e.chart.render();
                }
            },
            data: [ <?php $string = "";
foreach ($dataPoints as $i => $item) {
    $string .= "
                {
                    type: \"line\",
                    name: \"Patient $i\",
                    markerType: \"square\",
                    showInLegend: false,
                    dataPoints: ";
    $string .= json_encode($dataPoints[$i]);
    $string .= "},";
}
$string = substr($string, 0, -1);
echo ($string);?>]

        });
            chart.render();
        }
        </script>
    </head>
    <body>
        <div id="chartContainer" style="height: 370px; width: 100%;"></div>
        <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
    </body>
</html>
