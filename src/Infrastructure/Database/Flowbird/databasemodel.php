<?php
namespace App\Infrastructure\Database\Flowbird;
use App\Infrastructure\Database\database;

class DatabaseModel
{

    protected function executeSql($sql) {

        if (!isset($dbConn)) {
            $dbConn = new database();
        }
        if (!$dbConn->connID) {
            $dbConn->connect();
        }
        $st = oci_parse($dbConn->connID, "ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
        oci_execute($st);
        $st=oci_parse($dbConn -> connID,$sql);
        $r=oci_execute($st);
        $toReturn=true;
        if (!$r) {
            $toReturn=false;
        }
        return $toReturn;
    }
    protected function getRecordObject($query)
    {
        if (!isset($dbConn)) {
            $dbConn = new database();
        }
        if (!$dbConn->connID)
            $dbConn->connect();

        $st = oci_parse($dbConn->connID, "ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
        oci_execute($st);
        $st = oci_parse($dbConn->connID, $query);
        oci_execute($st);
        return oci_fetch_object($st);
    }

    protected function doLookupT($data)
    {
        if (!$data) {
            $returnPackage['status'] = "norecords";
        } else {
            $returnPackage['status'] = "recordsfound";
            $returnPackage['data'] = $data;
        }
        return json_encode($returnPackage);
    }

    protected function doLookup($recordSql)
    {
        $resultData = $this->getRecords($recordSql);
        $returnPackage=new \stdClass();
        if ($resultData->rowCount < 1) {
            $returnPackage->status = "norecords";
            $returnPackage->data=null;
        } else {
            $returnPackage->status = "recordsfound";
            $returnPackage->data = $resultData->records;
        }
        return $returnPackage;
        //echo json_encode($returnPackage);
    }
    protected function lookupObject($recordSql)
    {
        $resultData = $this->getRecordObject($recordSql);
        $returnPackage=new \stdClass();
        if ($resultData===false) {
            $returnPackage->status = "norecords";
            $returnPackage->data=null;
        } else {
            $returnPackage->status = "recordsfound";
            $returnPackage->data = $resultData;
        }
        return $returnPackage;
    }

    // bicycle queries
    public function insertUpdate($sql,$data) {
        if (!isset($dbConn)) {
            $dbConn = new database();

        }
        if (!$dbConn->connID) {
            $dbConn->connect();
        }
        $st = oci_parse($dbConn->connID, "ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");

        oci_execute($st);
        $appData = $data;
        $packagea = json_encode($appData);
        $dpackage = json_decode($packagea, True);
        $result = $dbConn->sQuery($sql, $dpackage);
        $id=false;
        if ($result) {
            if (isset($dbConn->results[0]['ID'])) {
                $id = $dbConn->results[0]['ID'];
            }
        }
        return $id;
    }

    protected function getRecords($query, $dFormat="array")
    {
        if (!isset($dbConn)) {
            $dbConn = new database();
        }
        $dbConn->sQuery($query);
        $returnPackage=new \stdClass();
        $returnPackage->rowCount = $dbConn->rows;
/*        if ($dFormat == "array") {
            $records = $this->recordsToArray($dbConn->results, $returnPackage->rowCount);
        } else {
            $records = $this->recordsToJSON($dbConn->results);
        }*/
        $returnPackage->records = $dbConn->results;
        return $returnPackage;
    }

    protected function recordsToArray($records, $rows)
    {
        $tb = json_decode(json_encode($records), True);
        $newRecords = array();
        for ($i = 0; $i < $rows; $i++) {
            $newArray = array();
            foreach ($tb as $field) {
                $newArray[] = $field[$i];
            }
            $newRecords[] = $newArray;
        }
        return $newRecords;
    }

    protected function recordsToJSON($records)
    {

        $tb = $records;
        $newRecords=new \stdClass();
        foreach ($tb as $key => $value) {
            $newRecords->$key = $value[0];
        }
        return $newRecords;
    }
    protected function sendEmailWithCC($fromName,$fromEmail,$toEmail,$text, $subject)
    {
        $from = "From: ".$fromName." <".$fromEmail.">\r\nCc: Commuter Programs <PTS-CommuterPrograms@arizona.edu>\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=ISO-8859-1\r\n";
        mail($toEmail, $subject, $text, $from);
    }

    protected function sendEmail($fromName,$fromEmail,$toEmail,$text, $subject)
    {
        $from = "From: ".$fromName." <".$fromEmail.">\r\nMIME-Version: 1.0\r\nContent-Type: text/html; charset=ISO-8859-1\r\n";
        mail($toEmail, $subject, $text, $from);
    }


    protected function getDatasetArray($strQuery, $noDateSet = 0)
    {
        if (!isset($dbConn)) {
            $dbConn = new database();
        }
        if (!$dbConn->connID)
            $dbConn->connect();
        $dataSet = array();
        if ($noDateSet == 1) {
// 	 $st = oci_parse($dbConn -> connID, "ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
            $st = oci_parse($dbConn->connID, "ALTER SESSION SET NLS_DATE_FORMAT = 'MM/DD/YYYY'");
        } else {
//  $st = oci_parse($dbConn -> connID, "ALTER SESSION SET NLS_DATE_FORMAT = 'MM/DD/YYYY'");
            $st = oci_parse($dbConn->connID, "ALTER SESSION SET NLS_DATE_FORMAT = 'YYYY-MM-DD HH24:MI:SS'");
        }
        oci_execute($st);
        $st = oci_parse($dbConn->connID, $strQuery);
        oci_execute($st);

        while (($row = oci_fetch_array($st, OCI_ASSOC)) != false) {
// Use the uppercase column names for the associative array indices
            array_push($dataSet, (object)$row);
        }
        return $dataSet;
    }
}
