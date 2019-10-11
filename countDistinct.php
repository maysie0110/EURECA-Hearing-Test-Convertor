<?php
// count the number of distinct patient for each CNC word score
require "./config/config.php";
$conn = mysqli_connect($auth['host'], $auth['user'], $auth['password'], $auth['db']);
if (mysqli_connect_errno()) {
    echo "Failed to connect to MySQL: " . mysqli_connect_error();
    die();
}

$sql = "SELECT DISTINCT COUNT(audiogramResults.patient_id) as count,cncResults.`Words with 3 Phonemes Correct` 
from audiogramResults, cncResults
where audiogramResults.left_measures != '[]' AND audiogramResults.right_measures = '[]' AND audiogramResults.patient_id=cncResults.PatientID and audiogramResults.date=cncResults.TestDate AND
            cncResults.ConditionsID=(SELECT audioConditionList.ConditionsID from audioConditionList where audiogramResults.left_condition=audioConditionList.LeftAidCondition and audiogramResults.right_condition=audioConditionList.RightAidCondition) GROUP BY cncResults.`Words with 3 Phonemes Correct` Order BY cncResults.`Words with 3 Phonemes Correct` DESC";
    
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        echo $row['Words with 3 Phonemes Correct']." ".$row['count']."\n<br>";
    }

?>