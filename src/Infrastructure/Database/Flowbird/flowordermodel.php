<?php
namespace App\Infrastructure\Database\Flowbird;
/*
 *
 * Class FloworderModel: Flowbird Orders Database Functions
 * Version 1.0.
 *
 * /var/www2/include/flowbird-include/flowordermodel.php -
 *
 * @author  David Ross Wallace <davidwallace@arizona.edu>
 *
 *  Database functions for Flowbird Ticket Orders:
 *  NOTE: included classes are autoloaded and located here: /var/www2/include/flowbird-include/
 *
 */


class FloworderModel extends DatabaseModel
{

    protected $objOracleApi;

    function __construct()
    {
        // $this->objOracleApi = new OracleapiModel('128.196.6.59', 'ptsor19.ptsaz.arizona.edu', 'parking', 'longitudinal-syringe-9@');
    }

    public function checkForAvailableCodes($package)
    {

        $sql = "  SELECT 
                   count(distinct frt.FBTICKETCODE) \"ticketCount\" 
                    from FLOWBIRDTICKETCODES frt
                    where frt.FBCODESTATUS='ACTIVE'
                    and frt.FBTICKETVALUE=" . $package->FBTICKETVALUE . "
                    and frt.FBTICKETTYPE='" . $package->FBTICKETTYPE . "'  
                    and frt.FBTICKETLOCATION='" . $package->FBTICKETLOCATION . "'";

        $results = $this->getRecordObject($sql);
        return $results->ticketCount;
    }


    public function getTicketCodes($package)
    {
        $sql = "select
                    frt.FBTICKETCODE
                    from FLOWBIRDTICKETCODES frt
                    where frt.FBCODESTATUS='ACTIVE'
                    and frt.FBTICKETVALUE=" . $package->FBTICKETVALUE . "
                    and frt.FBTICKETTYPE='" . $package->FBTICKETTYPE . "'  
                    and frt.FBTICKETLOCATION='" . $package->FBTICKETLOCATION . "'
                    ORDER BY frt.FBTICKETCODE
                    FETCH FIRST $package->FBNUMBEROFTICKETS ROWS ONLY";

        $results = $this->getDatasetArray($sql);
        return $results;
    }

    public function getOrderTicketCodes($orderId)
    {
        $sql = "select
                    frt.FBTICKETCODE, frt.FBTICKETVALUE, frt.FBTICKETTYPE,frt.FBTICKETLOCATION  
                    from FLOWBIRDTICKETCODES frt
                    where frt.FBORDERID=$orderId";

        $results = $this->getDatasetArray($sql);
        //   echo var_dump($results->records);
        //    exit;
        return $results;
    }

    public function createFlowbirdOrder($package)
    {
        $sql = "insert into  PARKING.FLOWBIRDTICKETORDERS
		(
            FBORDERID,
            ORDERTYPE,
            CUSTOMERNAME,
            CUSTOMEREMAIL,
            CUSTOMERPHONE,
            KFSNUMBER,
            DEPARTMENTNAME,
            FBTICKETLOCATION,
            FBTICKETTYPE,
            FBTICKETVALUE,
            FBNUMBEROFTICKETS,
            TOTALORDERCOST,
            ORDERDATE
		)
		values
		(
            PARKING.\"FBORDERID\".NEXTVAL,
            'TICKETORDER',
            '$package->CUSTOMERNAME',
            '$package->CUSTOMEREMAIL',
            '$package->CUSTOMERPHONE',
            '$package->KFSNUMBER',
            '$package->DEPARTMENTNAME',
            '$package->FBTICKETLOCATION',
            '$package->FBTICKETTYPE',
            to_number($package->FBTICKETVALUE),
            to_number($package->FBNUMBEROFTICKETS),
            to_number($package->TOTALORDERCOST),
            SYSDATE
		)";

        $result=$this->executeSql($sql);
        $returnId=false;
        if ($result) {
            $getId = "select max(FBORDERID) \"id\" from PARKING.FLOWBIRDTICKETORDERS";
            $idInfo = $this->getRecordObject($getId);
            $returnId=$idInfo->id;
        }
        return $returnId;
    }

    public function createReservationOrder($package)
    {
        $sql = "insert into  PARKING.FLOWBIRDTICKETORDERS
		(
                FBORDERID,
                ORDERTYPE,
                CUSTOMERNAME,
                CUSTOMEREMAIL,
                CUSTOMERPHONE,
                KFSNUMBER,
                DEPARTMENTNAME,
                FBTICKETLOCATION,
                FBTICKETTYPE,
                FBTICKETVALUE,
                FBNUMBEROFTICKETS,
                TOTALORDERCOST,
                ORDERDATE,
                RESERVATIONDATE,
                RESERVATIONVISITORCOUNT,
                RESERVATIONNUMBER,
                RESERVATIONDATES
		)
		values
		(
                PARKING.\"FBORDERID\".NEXTVAL,
                'RESERVATIONORDER',
                '$package->CUSTOMERNAME',
                '$package->CUSTOMEREMAIL',
                '$package->CUSTOMERPHONE',
                '$package->KFSNUMBER',
                '$package->DEPARTMENTNAME',
                '$package->FBTICKETLOCATION',
                '$package->FBTICKETTYPE',
                to_number($package->FBTICKETVALUE),
                to_number($package->FBNUMBEROFTICKETS),
                to_number($package->TOTALORDERCOST),
                SYSDATE,
                TO_DATE('$package->RESERVATIONDATE','MM/DD/YYYY'),
                $package->RESERVATIONVISITORCOUNT,
                '$package->RESERVATIONNUMBER',
                '".$package->RESERVATIONDATES."'
		)";

        $result=$this->executeSql($sql);
        $returnId=false;
        if ($result) {
            $getId = "select max(FBORDERID) \"id\" from PARKING.FLOWBIRDTICKETORDERS";
            $idInfo = $this->getRecordObject($getId);
            $returnId=$idInfo->id;
        }
        return $returnId;
    }

    public function retrieveOrder($orderId)
    {
        $sql = "select
                    FBORDERID,
                    CUSTOMERNAME,
                    CUSTOMEREMAIL,
                    CUSTOMERPHONE,
                    KFSNUMBER,
                    to_char(ORDERDATE,'MM/DD/YYYY') \"ORDERDATE\",
                    DEPARTMENTNAME,
                    FBTICKETLOCATION,
                    fbl.LOCATIONLABEL \"LOCATIONLABEL\",
                    FBTICKETTYPE,
                    FBTICKETVALUE,
                    FBNUMBEROFTICKETS,
                    TOTALORDERCOST    
                    from PARKING.FLOWBIRDTICKETORDERS
                    inner join PARKING.FLOWBIRDLOCATIONS fbl on 
                    FLOWBIRDTICKETORDERS.FBTICKETLOCATION=fbl.LOCATIONCODE
                    where FBORDERID=$orderId";

        return $this->getRecordObject($sql);
    }

    public function retrieveReservationOrder($orderId)
    {
        $sql = "select
                    FBORDERID,
                    CUSTOMERNAME,
                    CUSTOMEREMAIL,
                    CUSTOMERPHONE,
                    KFSNUMBER,
                    to_char(ORDERDATE,'MM/DD/YYYY') \"ORDERDATE\",
                    DEPARTMENTNAME,
                    FBTICKETLOCATION,
                    fbl.LOCATIONLABEL \"LOCATIONLABEL\",
                    FBTICKETTYPE,
                    FBTICKETVALUE,
                    FBNUMBEROFTICKETS,
                    TOTALORDERCOST,
                    to_char(RESERVATIONDATE,'MM/DD/YYYY') \"RESERVATIONDATE\",
                    RESERVATIONVISITORCOUNT,
                    RESERVATIONNUMBER,
                    RESERVATIONDATES
                    from PARKING.FLOWBIRDTICKETORDERS
                    inner join PARKING.FLOwBIRDLOCATIONS fbl on 
                    FLOWBIRDTICKETORDERS.FBTICKETLOCATION=fbl.LOCATIONCODE
                    where FBORDERID=$orderId";

        return $this->getRecordObject($sql);
    }

    public function reserveTicketCodes($package)
    {

        $codes = json_decode(json_encode($package->ticketCodes), true);

        for ($i = 0; $i < count($codes); $i++) {
            $currentCode = $codes[$i]['FBTICKETCODE'];
            $sql = "update FLOWBIRDTICKETCODES 
                    SET 
                    FBCODESTATUS='SOLD',
                    FBDATESOLD=TO_DATE(SYSDATE,'YYYY-MM-DD HH24:MI:SS'),
                    FBORDERID=to_number($package->orderId)
                    where FBTICKETCODE = '" . $currentCode . "'
                    and FBCODESTATUS='ACTIVE'";

            $this->executeSql($sql);
        }

        return true;

    }


    public function getCodes($package)
    {
        $sql = "select max(VALIDATIONREQUESTID) \"id\" from PARKING.PHXRESVALIDATIONSREQUEST";
        $results = $this->objOracleApi->getQueryResults($sql); // default result set is array
        return $results[0]->ticketCount;
    }

    public function getKFSinfo($kfsNumber)
    {
        $sql = "select KFSNUMBER
                    ,DEPARTMENTNAME
                    ,ACCOUNTNAME
                    FROM KFSNUMBERS
                    where KFSNUMBER='$kfsNumber'";
        $kfsInfoData = $this->getRecordObject($sql);

        $kfsInfo = new \stdClass();
        $kfsInfo->kfsnumber = $kfsNumber;
        if ($kfsInfoData) {
            $kfsInfo->found = true;
            $kfsInfo->DEPARTMENTNAME = $kfsInfoData->DEPARTMENTNAME;
            //    $kfsInfo->accountName = $kfsInfoData->ACCOUNTNAME;
        } else {
            $altKfsInfo = $this->alternateGetDepartment($kfsNumber);
            if ($altKfsInfo->found == true) {
                $kfsInfo->found = true;
                $kfsInfo->DEPARTMENTNAME = $altKfsInfo->DEPARTMENTNAME;
            } else {
                $kfsInfo->found = false;
                $kfsInfo->DEPARTMENTNAME = "";
            }
        }
        return $kfsInfo;
    }

    public function alternateGetDepartment($kfsNumber)
    {

        $sql3 = "select
                    GRD.DEPT_NAME
                    , GFRS.DESCRIPTION
                    FROM GR_DEPARTMENT GRD
                    INNER JOIN GR_FRS GFRS
                    ON GRD.DEPT_NO=GFRS.DEPT_NO_FK
                    where GFRS.FRS='" . $kfsNumber . "'";
        $altkfsInfoData = $this->getRecordObject($sql3);

        $altInfo = new \stdClass();
        $altInfo->found = false;


        if ($altkfsInfoData !== false) {

          $data = new \stdClass();
            $altInfo->found = true;
           $sql2 = "Insert into KFSNUMBERS (KFSNUMBER,DEPARTMENTNAME,ACCOUNTNAME) values ('$kfsNumber','" . $altkfsInfoData->DEPT_NAME . "','" . $altkfsInfoData->DESCRIPTION . "')";
           $this->insertUpdate($sql2, $data);
            $altInfo->DEPARTMENTNAME = $altkfsInfoData->DEPT_NAME;
        }
        return $altInfo;
    }

}
