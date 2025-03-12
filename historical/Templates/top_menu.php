<?php include_once("analyticstracking.php") ?>
<header id="header_ua" class="region region-header-ua l-arizona-header bg-cochineal-red clearfix" style="z-index:100;clear:right;">
   <div class="ua-redbar-v1">
      <nav role="navigation" class="redbar navbar-static-top">
         <div class="container">
            <div class="row" style="height:59px !IMPORTANT;min-height:59px !IMPORTANT;max-height:59px !IMPORTANT;padding-right:10px;">
               <div class="navbar-header" style="white-space:nowrap;width:100%;">
                  <a href="http://www.arizona.edu/" class="redbar-brand">
                     <p>The University of Arizona</p>
                  </a>
            
               <div id="social" class="pull-right" style="z-index:500;position:relative;top:-20px;padding-right:10px;text-align:right;padding:0;padding-right:5px">
                  <button type="button" title="Twitter" id="twitter" data-url="http://www.twitter.com/uarizonapts" style="float:right;" class="btn btn-xs ua-brand-twitter social"></button>&nbsp;
                  <button type="button" title="Facebook" id="facebook" style="float:right;" data-url="https://www.facebook.com/uarizonapts"  class="btn btn-xs  ua-brand-facebook social"></button>&nbsp;
                  <button type="button"  title="Instagram" id="insta" style="float:right;"  data-url="https://www.instagram.com/uarizonapts/"  class="btn btn-xs ua-brand-instagram social"></button>
               </div></div>
            </div>
         </div>
      </nav>
   </div>
</header>
<br /><br clear="all" />
<div id="l_page">
<!--Sub BAnner Message-->
	<div style="padding: 20px; border: 0; background-color: #E2E9EB;">
    		<div class="row">
        		<div class="col-md-12 corona-box" style="text-align: center; font-size: 20px;">Students, headed out for Spring Break? Look into our <a href="/permits/temp_vehicle_storage_permit/">Holiday Vehicle Storage Parking Permit</a> and/or request a <a href="/about/tus-lyft-codes">Lyft Code</a>.</div>
    		</div>
	</div>
   <div id="header_site">
      <div class="container">
         <div class="row" >
		 
		 
		 
		 <div class="col-lg-4 col-md-6 col-sm-12">
            <div id="site_identifier"><a href="https://parking.arizona.edu/" title="UA Parking & Transportation logo"><img src="/images/template/UA-PTS-logo.png" alt="UA Parking & Transportation logo"></a></div>
          </div> 
		  
		  
 <div class="col-lg-8 col-md-6 col-sm-12">
		   <div class="row">
				 <div class="col-lg-12 col-md-12 col-sm-12">
		  


		  <div id="utility_links" style="padding-top:10px;margin-bottom:15px;" >
               <ul>
                  <?php
                     if (@$_SERVER['HTTPS'] && (@sizeof($_SESSION['entity']) || @sizeof($_SESSION['eds_data'])))
                     {
                     	?>
                  <li>Logged in as  <span style="font-weight:500;color:#AB0520;"><?php echo $_SESSION['eds_data']['givenname_fn'] . ' ' . $_SESSION['eds_data']['sn']; ?></span>
                     &nbsp;&nbsp;&nbsp;-&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a onclick="document.location.href='/index.php?logout=1'"  title="Click here to log out"  style="cursor:pointer;" />Logout</a>
                  </li>
                  <?php
                     }
                     ?>
                  <li ><a href="/about/comments/">Contact</a></li>
     
                  <li><a href="https://arizona.aimsparking.com/">My Account</a></li>
               </ul>
            </div>
			
			
			
			</div>
			</div>
			
			<div class="row search-bar">
			
			
			<div class="col-lg-3 col-md-0 col-sm-0 filter-box" style="margin:0;padding:0;">
			
			</div>
			<div class="col-lg-5 col-md-6 col-sm-12 filter-box" style="align:right;padding-right:5px;margin-right:0;">
					<div class="input-group">
						<span class="input-group-addon input-group-addon-no-border">
							<div class="select-menu-label">I am </div>
						</span>
						<label for="uaqs-navigation-select-menu-uaqs-audience-select-menu" style="" class="sr-only">Select your audience</label>
							<select  id="filterSelect" class="form-control select-primary" style="margin-top:1px;height:36px !IMPORTANT;font-size:21px;color:#ab0520" aria-invalid="true">
								<option value="">choose an option</option>
								<option value="https://parking.arizona.edu/filter-student.php" <?php echo @$studentselected; ?> >a student</option>
								<option value="https://parking.arizona.edu/filter-faculty.php" <?php echo @$facultyselected; ?> >an employee</option>
								<option value="https://parking.arizona.edu/filter-visitor.php" <?php echo @$visitorselected; ?> >a parent or visitor</option>
							</select>
						<span class="input-group-btn">
							<button class="btn btn-default js_select_menu_button" role="button" disabled="disabled" id="filterButton" type="button" tabindex="0">Go<span class="sr-only">to the page for that group</span></button>
						</span>
					</div>		
			</div>
			
			
			
            <div id="search" class="col-lg-4 col-md-6 col-sm-12">
       
            </div>
			
			
			
			</div>
         </div>
			
			
			
         </div>

		 
         <div id="topMenuBar" class="row">
            <div class="col-12">
               <nav class="navbar navbar-default">
                  <div class="container-fluid">
                     <ul class="nav nav-tabs">
                        <li  id="home"><a href="/">Home</a></li>
                       <li class="dropdown" id="parking">
                           <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Visitor Parking<span class="caret"></span></a>
                           <ul class="dropdown-menu">
                              <li><a href="/parking/" title="Parking Home" class="sf-depth-2">Parking Home</a></li>
                                             <li class="divider"></li>
                              <li><a href="/parking/garages/visitor-parking" title="Garage Schedules / Visitor Parking">Visitor and Hourly Pay Parking</a></li>
                              <li><a href="/parking/special-events" title="Special Events" class="sf-depth-2">Special Events</a></li>
                              <li><a href="/parking/athletic-events" title="Athletic Event Parking" class="sf-depth-2">Athletic Event Parking</a></li>
							  <li><a href="/parking/reserve-event-parking/" title="Reserver Event Parking With ParkMoblie" class="sf-depth-2">Reserve Event Parking With Park Mobile</a></li>
							  <li><a target="_blank" href="/pdf/maps/Game_Day_Map_Football.pdf" title=""><img src="/images/icons/pdf.gif" width="16" height="16" border="0" align="absmiddle" alt="Viewing this link requires Adobe Acrobat Viewer."/> Football Parking Map</a></li>
				              <li><a target="_blank" href="/pdf/maps/Game_Day_Map_Basketball.pdf" title=""><img src="/images/icons/pdf.gif" width="16" height="16" border="0" align="absmiddle" alt="Viewing this link requires Adobe Acrobat Viewer."/> Basketball Parking Map</a></li>
                                            <li class="divider"></li>
			      <li><a href="/campus-services/electric-vehicle-charging/" title="New Electric Vehicle Chargers on Campus, More Locations Added">Electric Vehicle Chargers</a></li>
                             		    <li class="divider"></li>
			     <li><a href="/parking/garages/garage-heights" title="Garage Heights">Garage Heights</a></li>
			      <li><a href="/parking/garage-reservation/" title="Department Visitor Garage Reservation">Department Visitor Garage Reservation</a></li>
                           </ul>
                        </li>
						<li class="dropdown" id="Permits">
                           <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Permits<span class="caret"></span></a>
                           <ul class="dropdown-menu">
                              <li><a href="/permits/" title="Parking Home" class="sf-depth-2">Permits Home</a></li>
                              <li class="divider"></li>
                               <li><a href="https://arizona.aimsparking.com/" target="_blank" 
                                      title="Account Portal - Permits, Bus passes, Citations & Appeals" class="sf-depth-2">Your Account Portal</a></li>
                             
                               <li><a href="/permits/regulations" title="Parking Permits - Rules and Regulations" class="sf-depth-2">Rules
                                                                                                                                     & Regulations</a></li>
                               <li class="divider"></li>

              
                               <li><a href="/permits/parking-program-changes" title="Parking Program Changes">Parking Program Changes</a></li>
			       <li><a href="/permits/temp_vehicle_storage_permit" title="Temp Vehicle Storage Permit">Temp Vehicle Storage Permit</a></li>
                               <li class="divider"></li>
                               <li><a href="/pdf/maps/campus-parking-map.pdf" target="_blank"
                                      title="UA Parking Map">Parking Map</a></li>

                               <li><a href="/permits/rates-and-refunds" title="Parking Permit Rates and Refunds" class="sf-depth-2">Rates & Refunds</a></li>
           
                               <li class="divider"></li>


                               
                    <li><a href="/permits/waitlists-and-exchanges/"
                                      title="Waitlists & Exchanges">Waitlists & Exchanges</a></li>
                               <li class="divider"></li>
                               <li><a href="/permits/summer-permits" title="Schedule an Appointment">Summer Permits</a></li>
                               <li><a href="/permits/schedule-appointment" title="Schedule an Appointment">Schedule an Appointment</a>
                               </li>
                           </ul>
                        </li>
                        <li class="dropdown" id="bicycle">
                           <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Bicycles<span class="caret"></span></a>
                           <ul class="dropdown-menu">
                              <li><a href="/bicycle/" title="Bicycle Programs Home" class="sf-depth-2" >Bicycle Programs Home</a></li>
                              <li class="divider"></li>
							  <li><a href="/bicycle/lost-bicycle-information" title="Lost Bicycle Information" class="sf-depth-2">Lost Bicycle Information</a></li>
                              <li><a href="/bicycle/registration" title="Registration" class="sf-depth-2">Bicycle Registration</a></li>
                              <li class="divider"></li>
                              <li><a href="/bicycle/valet" title="Bike Valet" class="sf-depth-2">Bike Valet</a></li>
                              <li><a href="/bicycle/bike-repair-station" title="Campus Bicycle Station" class="sf-depth-2">Campus Bike Repair Station</a></li>
			      <li><a href="/bicycle/self-service-bike-repair" title="Campus Bicycle Station" class="sf-depth-2">Self-Service Bike Repair</a></li>
                              <li><a href="/bicycle/lockers-enclosures" title="Bicycle Locker / Enclosure" class="sf-depth-2">Bicycle Locker / Enclosure</a></li>
                              <li><a href="/bicycle/cat-wheels" title="Cat Wheels Bike Sharing Program" class="sf-depth-2">Cat Wheels Bike Sharing Program</a></li>
							  <li><a href="/bicycle/tugo-city-bikeshare" title="Tugo City Bikeshare" class="sf-depth-2">Tugo City Bikeshare</a></li>
                              <li><a href="/bicycle/bike-to-work" title="Employee Bike To Work Program" class="sf-depth-2">Employee Bike To Work Program</a></li>
							  <li><a href="/bicycle/policy/" title="Bicycle & Pedestrian Accommodation Polic" class="sf-depth-2">Bicycle & Pedestrian Accommodation Policy</a></li>	
                              <li><a href="/campus-services/emergency-ride/" title="Emergency Ride Home Program" class="sf-depth-2">Emergency Ride Home Program</a></li>	
							  <li class="divider"></li>
                              <li><a href="/bicycle/bicycle-search-request/" title="Missing Bicycle Search Request" >Missing Bicycle Search Request</a></li>
                          

                              
                              <li class="divider"></li>
                              
                            </ul>
                        </li>
						
						<li class="dropdown" id="cattran">
                           <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Cat Tran<span class="caret"></span></a>
                           <ul class="dropdown-menu">
                              <li><a href="/cattran/" title="Cat Tran Campus Transit Home" class="sf-depth-2" >Cat Tran Campus Transit Home</a></li>
                              <li class="divider"></li>
							  	<li><a href="/cattran/cat-tran-routes" title="Cat Tran Routes" class="sf-depth-2">Cat Tran Routes</a></li>
									<li><a href="https://arizona.transloc.com/" target="_blank" itle="Cat Tran Tracker" class="sf-depth-2">Cat Tran Tracker</a></li>								
									<li><a href="/cattran/how-to-cat-tran" title="How To Cat Tran" class="sf-depth-2">How To Cat Tran</a></li>
									<li><a href="/cattran/park-and-rides-permits" title="Park & Ride" class="sf-depth-2">Park & Ride</a></li>
									<li><a href="/cattran/night-cat" title="Night Cat" class="sf-depth-2">Night Cat</a></li>
									   <li class="divider"></li>
									<li><a href="/campus-services/shuttle/" title="Cat Tran Charter Service" class="sf-depth-2">Cat Tran Charter</a></li>		        
                            </ul>
                        </li>
						
						
                        <li class="dropdown" id="transportation">
                           <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Transit & Rideshare<span class="caret"></span></a>
                           <ul class="dropdown-menu">
                              <li><a href="/transportation/" title="Alternative Transportation Home" class="sf-depth-2">Alternative Transportation Home</a></li>
                              <li class="divider"></li>
                              <li><a href="/transportation/sungo/" title="U-Pass for Tucson Transit" class="sf-depth-2">U-Pass for Tucson Transit</a></li>
                              <li class="divider"></li>
							  <li><a href="/transportation/rideshare-programs/carpool-parking" title="UA Carpool Parking Program">UA Carpool Parking Program</a></li>
							   <li><a href="/transportation/rideshare-programs/ride-amigos/"  title="RideAmigos - Plan your alternative transportation commute">RideAmigos</a></li>
                              <li><a href="/transportation/rideshare-programs/ridesharing-through-pag/" title="Ridesharing Through PAG">Ridesharing Through PAG</a></li>
                             
							   <li class="divider"></li>
							   
							   <li><a href="http://arizona.transloc.com/info/mobile" target="_blank"  title="" class="sf-depth-2">Track the Cat Tran</a></li>
                           </ul>
                        </li>
                        <li class="dropdown" id="campus">
                           <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Services<span class="caret"></span></a>
                           <ul class="dropdown-menu">

                              <li><a href="/campus-services/" title="Campus Services Home" class="sf-depth-2">Campus Services Home</a></li>
                            						    <li class="divider"></li>
														
														<li><a href="/about/department-services-applications" title="Department Forms" class="sf-depth-2">Department Forms</a></li>
						 <li><a href="/parking/garage-reservation/" title="Departmental Visitor Garage Reservation" class="sf-depth-2">Department Visitor Garage Reservation</a></li>
						    <li class="divider"></li>
 
							                                <li><a href="/campus-services/shuttle/" title="Cat Tran Charter" class="sf-depth-2">Cat Tran Charter</a></li>
                              <li><a href="/about/cattran-advertizing/" title="Advertising on Cat Tran" class="sf-depth-2">Advertising on Cat Tran</a></li>
                              <li class="divider"></li>
			      <!--<li><a href="/campus-services/fleet-services/" title="Fleet Services" class="sf-depth-2">Fleet Services</a></li>-->
			      <li><a href="/campus-services/garage-services/" title="Garage Services" class="sf-depth-2">Garage Services</a></li>
			      <li><a href="/campus-services/motor-pool-services/" title="Motor Pool Services" class="sf-depth-2">Motor Pool Services</a></li>
							
                              <li class="divider"></li>
							  <li><a href="/campus-services/electric-vehicle-charging" title="Electric Vehicle Charging" >Electric Vehicle Charging</a></li>
                             
                              <li><a href="/campus-services/motorist-assistance/" title="Motorist Assistance Program">Motorist Assistance Program</a></li>
							 <li><a href="https://driver.chargepoint.com/mapCenter/32.23915216225858/-110.95599776890037/18" title="New Electric Vehicle Chargers on Campus, More Locations Added">Electric Vehicle Chargers</a></li>
							  	 <li><a href="/campus-services/disability-cart-service/" title="Disability Cart Service" class="sf-depth-2">Disability Cart Service</a></li>
                              <li class="divider"></li>
                              <li><a href="/campus-services/emergency-ride/" title="Motorist Assistance Program">Emergency Ride Home</a></li>
                              <li><a href="http://arizona.transloc.com/info/mobile" title="" class="sf-depth-2">Track the Cat Tran</a></li>
                              <li><a href="http://www.zipcar.com/universities/university-of-arizona" rel="nofollow" title="ZipCar" target="_blank" class="sf-depth-2">
                                 <img width="16" height="16" border="0" align="absmiddle" alt="Opens in new window external link" src="/images/icons/external-link-invert.gif" /> ZipCar</a>
                              </li>
                           </ul>
                        </li>
						
						

									   <li class="dropdown" id="phoenix">
                           <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">PHX Bioscience<span class="caret"></span></a>
                           <ul class="dropdown-menu">

<li><a href="/phoenix/" title="UA Phoenix Biomedical Campus Home" class="sf-depth-2">Phoenix Bioscience Campus Home</a></li>
<li class="divider"></li>
<li><a href="/phoenix/parking-permit-rates" title="Parking Permit Rates" class="sf-depth-2">Parking Permit Rates & Map</a></li>
<li><a href="/phoenix/emergency-ride-home" title="Emergency Ride Home" class="sf-depth-2">Emergency Ride Home</a></li>
<li><a href="/phoenix/hourly-pay-parking/" title="Hourly Pay Parking">Hourly Pay Parking</a></li>
<li><a href="/phoenix/department-guest-parking" title="Department Guest Parking" class="sf-depth-2">Department Guest Parking</a></li>	
<li class="divider"></li>
<li><a href="/phoenix/pbc-alternative-transportation" title="Transportation">PBC Alternative Transportation</a></li>	

				
				<li><a  href="/phoenix/pbc-alternative-transportation/pbc-valley-metro" title="Valley Metro">Valley Metro</a></li>


                           </ul>
                        </li>
						
						
						
						
						
						
						
                        <li class="dropdown" id="citations">
                           <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">Citations<span class="caret"></span></a>
                           <ul class="dropdown-menu">
                              <li><a href="/citations/" title="" class="sf-depth-2">Citations Home</a></li>
                              <li class="divider"></li>
                              <li><a href="/citations/citation-information" title="How to Handle Citation" class="sf-depth-2">How to Handle Citation</a></li>
                              <li><a href="https://arizona.aimsparking.com/" title="Pay Citation" class="sf-depth-2">Pay Citation</a></li>
                              <li><a href="https://arizona.aimsparking.com/" title="Appeal Citation" class="sf-depth-2">Appeal Citation</a></li>
			      <li><a href="/citations/citation-fees">Citation Fees</a></li>
              
                           </ul>
                        </li>
                        <li class="dropdown" id="about">
                           <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">About<span class="caret"></span></a>
                           <ul class="dropdown-menu">
                              <li><a href="/about/" title="About Us Home" class="sf-depth-2">About Us Home</a></li>
                              <li class="divider"></li>
                              <li><a href="/about/general-information/mission-statement" title="PTS Mission Statement">PTS Mission Statement</a></li>
                              <li><a href="/about/general-information/directory" title="Staff Directory">Staff Directory</a></li>
                              <li><a href="/about/general-information/office-information" title="Office Information">Office Information</a></li>
                              <li class="divider"></li>
                              <li><a href="/news/" title="News" class="sf-depth-2">News</a></li>
                              <li><a href="/about/jobs/" title="Working at PTS" class="sf-depth-2">Working at PTS</a></li>
                              <li class="divider"></li>
                              <li><a href="/about/comments/" title="Contact Us">Contact Us</a></li>
                              <li><a href="/about/sustainability" title="Sustainability Programs - PTS" class="sf-depth-2">Sustainability Programs</a></li>

                           </ul>
                        </li>
                     </ul>
                  </div>
               </nav>
            </div>
         </div>
      </div>
   </div>
</div>

			 		 <style>
					 .message-row {
						 padding-top:15px;padding-bottom:15px;
					 }
		 .top-message {
			 text-align:center;
			 font-size:24px;
			 font-weight:bold;
			 color:#FF0520;
			 
		 }
		 </style>
		
		 
<script>

$( document ).ready(function() {

	  $('#filterSelect').on('change',function(e)  {
	   var filterUrl=$(this).val();

	   if (filterUrl!="") { 
			window.location.replace(filterUrl);
		  //  $('#filterButton').prop('disabled', false);   
	   } else {	   
		   $('#filterButton').prop('disabled', true);	   
	   }	   
});
      $('#filterButton').on("click",function(e) {	   
	   var filterUrl=$('#filterSelect').val();
	   if (filterUrl!="") {  
			window.location.replace(filterUrl);  
	   } else {	   
		    $('#filterButton').prop('disabled', true);		   
	   }	   
	  });
})
</script>
