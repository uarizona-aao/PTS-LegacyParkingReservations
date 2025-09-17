<?php

declare(strict_types=1);

namespace App\Application\Actions\Customer;

use Psr\Http\Message\ResponseInterface as Response;
use App\Infrastructure\Database\database;
use App\Application\Responders\CustomerResponder;
use App\Infrastructure\Database\reservation;
use App\Application\Services\DateValidator;

include_once __DIR__.'/../../../form_functions.php';

class CreateCustomerViewAction extends CustomerAction
{
    private CustomerResponder $customerResponder;
    private DateValidator $dateValidator;

    public function __construct(
        CustomerResponder $customerResponder,
        DateValidator $dateValidator
    ) {
        $this->customerResponder = $customerResponder;
        $this->dateValidator = $dateValidator;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        
        return $this->customerResponder->edit_customer($this->response);
    }
}

class EditCustomerViewAction extends CustomerAction
{
    private CustomerResponder $customerResponder;
    private DateValidator $dateValidator;

    public function __construct(
        CustomerResponder $customerResponder, DateValidator $dateValidator) {
        $this->customerResponder = $customerResponder;
        $this->dateValidator = $dateValidator;
    }
    
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $id = $this->request->getQueryParams()['id'] ?? null;
        
        if (!$id) {
            return $this->response->withStatus(404);
        }

        $res = new reservation();
        $customer = $_SESSION['cuinfo'];
        $res->getRes($id, true);
        $customer = $_SESSION['cuinfo']; // yes, this is necessary. The code is that old and bad. And time does not exist for a proper fix.
        $redDates = isset($resInfo['RESDATE']) ? $resInfo['RESDATE'] : '';
        // $defaultDateStr = isset($res->resinfo['RESDATE']) ? explode(',', $res->resinfo['RESDATE'][0])[0] : '';
        $addDatesStr = isset($res->resinfo['RESDATE']) ? explode(',', $res->resinfo['RESDATE'][0])[0] : '';

        $data = [
            'receipt' => '', // Content for receipt
            'error' => '',
            'mode' => 'create',
            'customer' => $customer,
            'reservation' => [],
            'db_reservation' => [], // this is for $res object if we instantiate it.
            'redDates' => $redDates,
            'defaultDateStr' => '', // nothing here.
            'addDatesStr' => $addDatesStr,
            'maxDatePicks' => 4,
            'unselectDateMsg' => "Please unselect the date you wish to change.",
            'use_default_jquery' => false, // bit for jquery fix.
            'garageOptions' => [],
            'dateValidator' => $this->dateValidator
        ];
        // Various checks from original edit.php
        if (!$res->resinfo) {
            return $this->response->withHeader('Location', '/?msg=resnotfound')
                                ->withStatus(302);
        }
        $tmpAry = [
            'deptno' => $res->resinfo['DEPT_NO_FK'][0], 
            'userid' => $res->userid
        ];

        // Authorization check
        if ($customer['auth'] < 4 && !$res->checkResOwner($customer, $tmpAry)) {
            return $this->response->withHeader('Location', '/?msg=resallowed')
                                ->withStatus(302);
        }

        // Active check
        if (!$res->active) {
            return $this->response->withHeader('Location', '/?msg=notactive')
                                ->withStatus(302);
        }

        // Past date check
        if (strtotime($res->resdate) <= strtotime('today')) {
            return $this->response->withHeader('Location', '/?msg=notactive')
                                ->withStatus(302);
        }

        // PBC check
        if (preg_match('/bio.?med/si', $res->resinfo['GARAGE_NAME'][0])) {
            return $this->response->withHeader('Location', '/?msg=nopbc')
                                ->withStatus(302);
        }

        // Handle POST request for edits
        // TODO: Res is ready, need to compare why the values aren't lining up.
        if ($this->request->getMethod() === 'POST') {
            return $this->handleEditSubmission($res);
        }

        // Setup for edit form display information
        $res->getGuests($id);
        $resInfo = $res->resinfo;
        $resInfo['guestList'] = is_array($res->guestList) ? implode(" | ", $res->guestList) : $res->guestList;
        $resInfo['groupCount'] = $res->groupCount[0];
        $glg = (isset($res->groupCount[0]) && $res->groupCount[0] > 1) ? "group" : "guest";
        if($glg == "group") {
            $data['groupName'] = $resInfo['guestList'];
            $data['groupSize'] = $res->groupCount[0];
        }
        
        // To patch resInfo, have all keys => [single_value] be just key => value if it is an array
        foreach ($resInfo as $key => $value) {
            if (is_array($value) && count($value) == 1) {
                $resInfo[$key] = $value[0];
            }
        }

        $data['reservation'] = $resInfo;
        $data['glg'] = $glg;
        $data['error'] = $_GET['error'] ?? null;
        $data['db_reservation'] = $res;
        $data['cancelUri'] = '/';
        $data['garageOptions'] = garageOptions(getVal($resInfo, 'GARAGE_ID_FK', 0), "9006,USA,10003");
        $data['kfs_valid'] = true;
        return $this->customerResponder->edit($this->response, $data);
    }

    private function handleEditSubmission(reservation $res): Response 
    {
        $id = $this->request->getQueryParams()['id'] ?? null;
        
        if (!$id) {
            return $this->response->withStatus(404);
        }

        $post = $this->request->getParsedBody();
        $edits = [];
        $guests = [];
        $sizeChange = 0;
        
        // Get existing guest info
        $res->getGuests($id);
        
        // Process FRS changes
        if ($res->frs != $post['frs']) {
            $edits['FRS_FK'] = $post['frs'];
        }

        // Process date changes
        $dates = isset($post['dates']) ? explode(",", $post['dates']) : [$post['startDate'] ?? null];
        if ($dates[0] && $res->resdate != date("m/d/Y", strtotime($dates[0]))) {
            $edits['RES_DATE'] = $dates[0];
        }

        // Process time changes
        if ($res->resenter != date("h:i A", strtotime($post['enterTime']))) {
            $edits['ENTER_TIME'] = $post['enterTime'];
        }
        if ($res->resexit != date("h:i A", strtotime($post['exitTime']))) {
            $edits['EXIT_TIME'] = $post['exitTime'];
        }

        // Process garage changes
        if ($res->garageid != $post['garage']) {
            $edits['GARAGE_ID_FK'] = $post['garage'];
        }

        // PROGRESS SO FAR: EDITS is working. Need to debug the groupGuest handlers.
        // Process guest/group changes
        $glg = $post['groupGuest'] ?? null;
        if ($glg == "group") {
            $this->handleGroupEdits($res, $post, $edits, $guests, $sizeChange);
        } elseif ($glg == "guest") {
            $this->handleGuestListEdits($res, $post, $edits, $guests, $sizeChange);
        }

        // Process come and go changes
        if ($res->comego) {
            $edits['COME_AND_GO'] = "1";
            $edits['PRICE'] = ($post['garage'] == 3) ? $_SESSION['G_price_comeandgo_second'] : $_SESSION['G_price_comeandgo'];
        } else {
            $edits['COME_AND_GO'] = "0";
            $edits['PRICE'] = ($post['garage'] == 3) ? $_SESSION['G_price_second'] : $_SESSION['G_price_regular'];
        }
        
        // PBC Lot 10003 pricing override
        if ($post['garage'] == 12) {
            $edits['PRICE'] = $_SESSION['G_price_pbc_10003'];
        }

        // Check garage capacity if changing garages
        if (isset($edits['GARAGE_ID_FK'])) {
            unset($post['spacesOrig']);
            $res->checkGarageMax($edits['GARAGE_ID_FK'], $dates[0], intval($post['spaces']));
            if ($res->error) {
                return $this->response->withHeader('Location', '/?msg=garageMax')
                                    ->withStatus(302);
            }
        }

        // Process the edits
        if (count($edits) || 
            (isset($guests['kill']) && count($guests['kill'])) || 
            (isset($guests['add']) && count($guests['add'])) ||
            (isset($guests['edit']) && $guests['edit']) || 
            (isset($guests['sizeedit']) && $guests['sizeedit'])) {
            $test = $res->editRes([$id], $edits, $guests, $sizeChange);
            if (!$test && $res->error) {
                return $this->response->withHeader('Location', "/edit?id={$res->conf}&error={$res->error}")
                                    ->withStatus(302);
            }
            return $this->response->withHeader('Location', '/?msg=edited')
                                ->withStatus(302);
        }

        return $this->response->withHeader('Location', '/?msg=nochanges')
                            ->withStatus(302);
    }

    private function handleGroupEdits($res, $post, &$edits, &$guests, &$sizeChange)
    {
        if (!isset($res->guestList[0]) || !$res->guestList[0]) {
            $guests['GROUP_SIZE'] = $post['spaces'];
            $guests['add'] = [stripslashes($post['groupName'])];
        } else {
            if (stripslashes($post['groupName']) != $res->guestList[0]) {
                $guests['edit'] = stripslashes($post['groupName']);
                $guests['orig'] = $res->guestList[0];
            }
            if ($res->groupCount[0] != $post['spaces']) {
                $guests['GROUP_SIZE'] = $post['spaces'];
                $guests['sizeedit'] = $post['spaces'];
                $guests['sizeorig'] = $res->groupCount[0];
                $sizeChange = intval($post['spaces']) - $res->groupCount[0];
            }
            // if ($res->addguests != $post['gaddGuests']) {
            //     $edits['GUESTS_OFFCAMPUS'] = $post['gaddGuests'];
            // }
        }
    }

    private function handleGuestListEdits($res, $post, &$edits, &$guests, &$sizeChange)
    {
        $glist = array_unique(explode(" | ", $post['guestList']));
        $guests['totalSize'] = count($glist);
        $guests['kill'] = array_diff($res->guestList, $glist);
        $guests['add'] = array_diff($glist, $res->guestList);
        $guests['GROUP_SIZE'] = 1;
        $sizeChange = count($glist) - count($res->guestList);
        
        if ($res->addguests != $post['laddGuests']) {
            $edits['GUESTS_OFFCAMPUS'] = $post['laddGuests'];
        }
    }
}
