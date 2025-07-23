<?php
namespace App\Infrastructure\Database\Flowbird;
/**
 * FloworderController - processes forbird ticket orderform data
 * PHP Version 1.0.
 *
 * https://parking.arizona.edu/flowbird/ -- FloworderController production
 *
 * @author    David Ross Wallace <davidwallace@arizona.edu>
 * @copyright 2023 - 2023 David Ross Wallace
 */


// include_once '/var/www2/include/phxres/databasemodel.php';

class FlowsettingsModel extends DatabaseModel
{
    private $settings;

    function __construct()
    {
        $this->settings = $this->getSettingsData();
    }

    public function getSettings()
    {
        return $this->settings;

    }
    private function getSettingsData()
    {
 /*       $sql = "
    SELECT 
    SETTINGSID \"ID\",
    SETTINGSID,
    nvl(APPLICATION_MODE,'TEST') \"APPLICATION_MODE\" ,
    nvl(EVENT_ALLOTMENT,200) \"EVENT_ALLOTMENT\",
    nvl(LOT_10002_ALLOTMENT,5) \"LOT_10002_ALLOTMENT\",
    nvl(LOT_10003_ALLOTMENT,20) \"LOT_10003_ALLOTMENT\",
    nvl(AZ_CENTER_EMAIL,'n/a') \"AZ_CENTER_EMAIL\",
    nvl(TEST_NOTIFICATION_EMAIL,'n/a') \"TEST_NOTIFICATION_EMAIL\",
    nvl(PBC_VALIDATION_ISSUER_EMAIL,'n/a') \"PBC_VALIDATION_ISSUER_EMAIL\",
    nvl(PTS_TOGARAGES_FROM_EMAIL,'n/a') \"PTS_TOGARAGES_FROM_EMAIL\",
    nvl(PTS_TOCUSTOMER_FROM_EMAIL,'n/a') \"PTS_TOCUSTOMER_FROM_EMAIL\",
    nvl(VALIDATION_ALLOTMENT,200) \"VALIDATION_ALLOTMENT\",
    nvl(PBC_EVENT_APPROVER_EMAIL,'n/a') \"PBC_EVENT_APPROVER_EMAIL\",
    nvl(PBC_GARAGE_EMAIL,'n/a') \"PBC_GARAGE_EMAIL\",
    nvl(PBC_GARAGE_NAME,'n/a') \"PBC_GARAGE_NAME\",
    nvl(AZ_CENTER_NAME,'n/a') \"AZ_CENTER_NAME\",
    nvl(PTS_FROM_EMAIL_NAME,'n/a') \"PTS_FROM_EMAIL_NAME\",
    nvl(CURRENTTICKETSETID,1) \"CURRENTTICKETSETID\",
    nvl(LOTPRICEPERSPACE,15) \"LOTPRICEPERSPACE\",
    nvl(VALIDATIONPRICEPERSPACE,15) \"VALIDATIONPRICEPERSPACE\", 
    nvl(PBCGARAGEPRICEPERSPACE,15) \"PBCGARAGEPRICEPERSPACE\"
    FROM PHXRESSETTINGS
    where  SETTINGSID=1";
        $settingsInfo = $this->getRecordObject($sql);
        return $settingsInfo;*/

        $sql="select
RESERVATIONNOTIFICAIONEMAIL,
TICKETORDERNOTIFICATIONEMAIL,
INVENTORYALERTEMAIL,
NUMBEROFTICKETSALLOWED,
INVENTORYLOWTHRESHOLD,
FLOWBIRDORDERFORMSTATUS
            from PARKING.FLOWBIRDSETTINGS
                where FLOWBIRDSETTINGSID=1";

        $storedSettings= $this->getRecordObject($sql);

        $settings=new \stdClass();
        $settings->APPLICATION_MODE='test';
        $settings->TEST_NOTIFICATION_EMAIL='davidwallace@arizona.edu';
        $settings->LIVE_NOTIFICATION_EMAIL='davidwallace@arizona.edu';
        $settings->FROM_NOTIFICATION_EMAIL='PTS-Information@email.arizona.edu';
        $settings->FROM_NOTIFICATION_NAME='UofA Parking & Transportation';
        $settings->RESERVATIONNOTIFICAIONEMAIL=$storedSettings->RESERVATIONNOTIFICAIONEMAIL;
        $settings->TICKETORDERNOTIFICATIONEMAIL=$storedSettings->TICKETORDERNOTIFICATIONEMAIL;
        $settings->INVENTORYALERTEMAIL=$storedSettings->INVENTORYALERTEMAIL;
        $settings->NUMBEROFTICKETSALLOWED=$storedSettings->NUMBEROFTICKETSALLOWED;
        $settings->INVENTORYLOWTHRESHOLD=$storedSettings->INVENTORYLOWTHRESHOLD;
        $settings->FLOWBIRDORDERFORMSTATUS=$storedSettings->FLOWBIRDORDERFORMSTATUS;
        return $settings;
    }
}
