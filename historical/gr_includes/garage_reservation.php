<?php
//===================================================================================================================
// File: garage_reservation.php
// All global vars below should start with G_ followed by the name of the variable.
// This file is included in various web files within parking/garage-reservation/

//$_SESSION['default_start_time']	= '';
//$_SESSION['default_end_time']		= '';

//$_SESSION['max_start_time']		= '05:00 AM';
//$_SESSION['max_end_time']			= '11:59 PM';

/***
See: $garageid in gr/reservation_functions.php
GARAGE_ID	GARAGE_NAME
1				Main Gate Garage
2				Park Avenue Garage
3				Second Street Garage
4				Sixth Street Garage
5				Tyndall Avenue Garage
7				Highland Avenue Garage
8				Cherry Avenue Garage
9				Phoenix BioMedical Campus
10				9006 Lot
11				USA Lot
12				Phoenix BioMedical 10003
 */

// garage reservation keywords: $7.00, $8.00, $10.00, $15.00
$_SESSION['G_price_pbc_10003']	= 15; // for pbc lot 10003 only (this lot does not have comego)

$_SESSION['G_price_regular']		= 8; // was 7
$_SESSION['G_price_comeandgo']	= 9; // was 8

$_SESSION['G_price_second']		= 8; // 2'nd st garage
$_SESSION['G_price_comeandgo_second']	= 10; // was 8


?>