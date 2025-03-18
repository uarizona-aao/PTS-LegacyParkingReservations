/***************** * 
 * GLOBAL javascripts
 * 
 * HANDY FUNCTIONS TO CONVERT PHP TO JAVASCRIPT:
 *				http://phpjs.org/functions/
 */


function isInternetExplorer(){
	// Returns 1 if browser is IE, else 0.
	var ua = window.navigator.userAgent;
	var msie = ua.indexOf("MSIE ");
	if (msie > 0 || !!navigator.userAgent.match(/Trident.*rv\:11\./)){
		// If Internet Explorer, return 1
		// alert(parseInt(ua.substring(msie + 5, ua.indexOf(".", msie)))); //return version number
		return 1;
	}else{
		// If another browser, return 0
		return 0;
	}
}

function showOrHide(tid) {
	// open/close an element id (i.e. <div id='thisid' onclick='showOrHide(thisid)'>).
	if(document.getElementById(tid).style.display=='none')
		document.getElementById(tid).style.display='block';
	else
		document.getElementById(tid).style.display='none';
}


function selectText(id_sel) {
	/***
	 * Select text within a <div> tag (or any tag with an id for that matter)
	 * Example call:
	 * <div id="my_div" onclick="selectText('my_div')">Select Me</div>
	 */
	if (document.selection) {
		var divID = document.body.createTextRange();
		divID.moveToElementText(document.getElementById(id_sel));
		divID.select();
	} else {
		var divID = document.createRange();
		divID.setStartBefore(document.getElementById(id_sel));
		divID.setEndAfter(document.getElementById(id_sel)) ;
		window.getSelection().addRange(divID);
	}
}


function openHelp (topic) {
	NewWindow('/help.php?noTopBottom=1&topic=' + topic, 'helpWin', 750, 400);
}

function checkSearch (formy) {
	if (formy.q.value.length<3 || formy.q.value=="Search PTS") {
		alert("Please enter a search term");
		return false;
	}
}

function hideFlash (way) {
	if (document.all) {
		if (way) document.all["flashContainer"].visibility = "hidden";
		else document.all["flashContainer"].visibility = "visible";
	}
	else {
		if (way) document.getElementById("flashContainer").visibility = "hidden";
		else document.getElementById("flashContainer").visibility = "visible";
	}
}

var menuStore = "";
var timer = false;
function menuDrop (menu, dir) {
	if (!menu) menu = menuStore;
	if (document.all) {
		if (dir) {
			if (menuStore) menuUp();
			document.all[menu].style.display = "block";
		}
		else timer = setTimeout("menuUp()", 600);
		menuStore = menu;
	}
}

function menuUp () {
	if (menuStore) document.all[menuStore].style.display = "none";
	menuHold();
}

function menuHold () {
	if (timer) clearTimeout(timer);
}



// Creates a popup window.
var windough=null;
function NewWindow(mypage, my_id, w, h, scroll, pos, menubar, toolbar, location) {
	// EXAMPLE CALL auto-width/height: <a href="notes.php?num=3" onclick="NewWindow(this.href, 1111111); return false;" onfocus="this.blur()">
	// EXAMPLE CALL: <a href="notes.php?num=3" onclick="NewWindow(this.href, 1111111, 660, 200, 1, 0); return false;" onfocus="this.blur()">
	if (my_id || my_id==0)
		my_id = 'nw_' + my_id.toString(); // sometimes my_id is only digits.

	scroll = 'yes';

	if(pos=="center"){
		LeftPosition = (screen.availWidth) ? (screen.availWidth-w)/2 : 0;
		TopPosition  = (screen.availHeight) ? (screen.availHeight-h)/2 : 20;
	}
	if(pos=="default"){
		LeftPosition=0;
		TopPosition=68
	}else if((pos!="center" && pos!="random" && pos!="default") || pos==null){
		LeftPosition=0;
		TopPosition=20
	}

	if (window.screen) {
		// percentage width / height
      if (!w)	w = window.screen.availWidth * 90 / 100;
      if (!h)  h = window.screen.availHeight * 90 / 100;
	} else {
		if (!h)	h = (screen.availHeight - TopPosition - 100).toString();
		if (!w)	w = (screen.availWidth - 350).toString();
	}

	if (!menubar)		menubar = 'no';
	if (!location)		location = 'yes';
	if (toolbar==0)	toolbar = 'no';
		else				toolbar = 'yes';
	var settings = 'width='+w+', height='+h+', top='+TopPosition+', left='+LeftPosition+', scrollbars='+scroll+', location='+location+', directories=no, status=no, menubar='+menubar+', toolbar='+toolbar+', resizable=yes';

	windough = window.open(mypage, my_id, settings);
	if (window.focus) {windough.focus()}
	if (!windough.closed) {windough.focus()}
}


/*****************************************************************************
		bright_blink function -- Make things flash crazy colors!
/******************************************************************************/

// GLOBAL VARS
var bright_inc = 32; // Set the brightness increment and time delay between colors.
var bright_delay = 10;
// Initial color values
var bright_blue = 0;
var bright_green = 0;
var bright_red = 0;

var element_ID_glob = '';
function bright_blink(element_ID) {
	// Initial color values
	// element_ID must be of the form: document.getElementById('my_tag_id')
	if (element_ID)
		element_ID_glob = element_ID;
	else
		return;
	bright_blue = 0;
	bright_green = 0;
	bright_red = 0;
	brighten_green ();
}


/******************************************************************************
			BEGIN Support functions for bright_blink function
******************************************************************************/

/*-------------------------
get_hex_color(r, g, b)
This function returns a color string given red, green, and blue integers
between 0 and 255.
---------------------------*/
function get_hex_color(r, g, b)
{
	var hexstring = "0123456789abcdef";
	var hex_color =
		hexstring . charAt (Math . floor (r / 16))
	+	hexstring . charAt (r % 16)
	+	hexstring . charAt (Math . floor (g / 16))
	+	hexstring . charAt (g % 16)
	+	hexstring . charAt (Math . floor (b / 16))
	+	hexstring . charAt (b % 16);
	return hex_color;
}

/*-------------------------
brighten_red ()
This function causes the background to fade from "#000000" to "#ff0000".
It then calls the brighten_green () function.
---------------------------*/
function brighten_red ()
{
	bright_red += bright_inc;
	if (bright_red >= 256) {
		setTimeout ("brighten_green ();", bright_delay);
		return;
	}
	element_ID_glob.style.background = '#' + get_hex_color(bright_red, 0, 0);
	setTimeout ("brighten_red ();", bright_delay);
}

/*-------------------------
brighten_green ()
This function causes the background to fade from "#ff0000" to "#ffff00".
It then calls the brighten_blue () function.
--------------------------*/
function brighten_green ()
{
	bright_green += bright_inc;
	if (bright_green >= 256)
	{
		setTimeout ("brighten_blue ();", bright_delay);
		return;
	}
	element_ID_glob.style.background = '#' + get_hex_color(255, bright_green, 0);
	setTimeout ("brighten_green ();", bright_delay);
}

/*-------------------------
brighten_blue ()
This function causes the background to fade from "#ffff00" to "#ffffff".
--------------------------*/
function brighten_blue ()
{
	bright_blue += bright_inc;
	if (bright_blue >= 256) {
		element_ID_glob.style.background = "#ffffff";
		return;
	}
	element_ID_glob.style.background = '#' + get_hex_color(255, 255, bright_blue);
	setTimeout ("brighten_blue ();", bright_delay);
}

/******************************************************************************
			END Support functions for bright_blink function
******************************************************************************/

