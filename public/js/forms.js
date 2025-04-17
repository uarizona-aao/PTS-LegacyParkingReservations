// JavaScript Document
function resetDate (field) {
	if (field.value=="MM/DD/YYYY") field.value = "";
	else if (field.value=="") field.value = "MM/DD/YYYY";
}

function resetTime (field) {
	if (field.value=="HH:MM AM") field.value = "";
	else if (field.value=="") field.value = "HH:MM AM";
}

function checkDate (field) {
	if (!field.value) field.value = field.defaultValue;
}

function checkTime (field) {
	if (!field.value) field.value = field.defaultValue;
}

var openedCal;
function openCal (field) {
	if (document.getElementById) document.getElementById(field+"Div").style.visibility = "visible";
	else if (document.all) document.all[field+"Div"].style.visibility = "visible";
	openedCal = field;
}

function setDateField (dateStr) {
	if (openedCal=="startDate") {
		document.resForm.startDate.value = dateStr;
		closeCal();
	}
	else {
		if (document.resForm.multiDateBox.options[0].value=="") {
			document.resForm.multiDateBox.options[0].value = dateStr;
			document.resForm.multiDateBox.options[0].text = dateStr;
		}
		else document.resForm.multiDateBox.options[document.resForm.multiDateBox.options.length] = new Option(dateStr, dateStr);
	}
}

function closeCal () {
	if (document.getElementById) document.getElementById(openedCal+"Div").style.visibility = "hidden";
	else if (document.all) document.all[openedCal+"Div"].style.visibility = "hidden";
}

function removeDate (box) {
	sele = box.selectedIndex;
	if (box.options[sele].value=="") return false;
	else if (sele==-1) alert("Please select a guest to remove");
	else box.options[sele] = null;

	if (!box.options.length) box.options[0] = new Option("Enter a Date", "");
}

function addGuest (guestName, resForm) {
	with (resForm) {
		if (!guestName.value) {
			alert("Please enter a guest name");
			return false;
		}
		if (guestName.value.indexOf(";")>-1) {
			alert("Please do not use semi-colons (;) in the guest name");
			return false;
		}
		//if (guestName.value.indexOf(", ")>-1) {
		//	alert("Please do not use commas (,) in the guest name. Enter the name: 'First Last'");
		//	return false;
		//}
		if (guestListBox.options[0].text == "Please Add Guests to Continue") {
			guestListBox.options[0].value = 0;
			guestListBox.options[0].text  = guestName.value;
		} else {
			var i = a1 = a2 = 0;
			var alreadyThere = false;
			while (guestListBox.options[i]) {
				a1 = String(guestListBox.options[i].text);
				a1 = a1.toUpperCase();
				a2 = String(guestName.value);
				a2 = a2.toUpperCase();
				if (a1 == a2) {
					alreadyThere = true;
				}
				i = i + 1;
			}
			if (alreadyThere)
				alert('Each guest name must be different. You may want to use Group instead of Guest List.');
			else
				guestListBox.options[guestListBox.options.length] = new Option(guestName.value, 0);
		}
		guestName.value = "";
		guestName.focus();
	}
}

function removeGuest (resForm) {
	with (resForm) {
		if (guestListBox.options[guestListBox.selectedIndex].text=="Please Add Guests to Continue") return false;
		else if (guestListBox.selectedIndex==-1) alert("Please select a guest to remove");
		else guestListBox.options[guestListBox.selectedIndex] = null;

		if (!guestListBox.options.length) guestListBox.options[0] = new Option("Please Add Guests to Continue", "");
	}
}

function guestGroup (which) {
	if (which=="guest") {
		if (document.getElementById) {
			document.getElementById(which).style.display = "block";
			document.getElementById("group").style.display = "none";
		}
		else if (document.all) {
			document.all[which].style.display = "block";
			document.all["group"].style.display = "none";
		}
	}
	else if (which=="group") {
		if (document.getElementById) {
			document.getElementById(which).style.display = "block";
			document.getElementById("guest").style.display = "none";
		}
		else if (document.all) {
			document.all[which].style.display = "block";
			document.all["guest"].style.display = "none";
		}
	}
}

var frsCheck = false;
var frsRe = /[0-9a-zA-Z]{6,7}/;

function checkFrs(frs, cust) {
    // Validate the FRS format
    if (!frsRe.test(frs)) {
        document.getElementById('frsCheckSpan').innerHTML = '<b style="color:#CC0000;">KFS must be 7 characters.</b>';
        document.getElementById('frsCheckSpan').style.display = 'block';
        frsCheck = false;
        return;
    }

    // Show the loading message
    document.getElementById('frsCheckSpan').innerHTML = '<b><i>Checking...</i></b>';
    document.getElementById('frsCheckSpan').style.display = 'block';

    // Make an AJAX call to the /frscheck route
    fetch(`/frscheck?frs=${encodeURIComponent(frs)}&cust=${encodeURIComponent(cust)}`)
        .then(response => response.json())
        .then(data => {
			data = data.data || data; // Handle the case where data is nested
            if (data.status === 'success') {
                document.getElementById('frsCheckSpan').innerHTML = `<b style="color:green;">${data.message}</b>`;
                frsCheck = true;
            } else {
                document.getElementById('frsCheckSpan').innerHTML = `<b style="color:#CC0000;">${data.message}</b>`;
                frsCheck = false;
            }
        })
        .catch(error => {
            document.getElementById('frsCheckSpan').innerHTML = '<b style="color:#CC0000;">An error occurred while checking the FRS.</b>';
            console.error('Error:', error);
            frsCheck = false;
        });
}

function loadFrs () {
	if (frames["frstest"].location.href.indexOf('frscheck.php?failed')>-1) {
		document.getElementById('frsCheckSpan').innerHTML = '<b style="color:#CC0000;">You do not have access to this FRS</b>';
		document.getElementById('frsCheckDiv').style.display = 'block';
		frsCheck = false;
	}
	else if (frames["frstest"].location.href.indexOf('frscheck.php?passed')>-1) {
		document.getElementById('frsCheckSpan').innerHTML = '<b><i>Checking...</i></b>';
		document.getElementById('frsCheckDiv').style.display = 'none';
		frsCheck = true;
	}
}

function needHelp (topic) {
	if (topic) window.open("/help_gr.php?topic="+topic, "", "width=640,height=480,scrollbars=yes,menubar=yes,toolbar=yes,status=no,location=no,titlebar=no,directories=no,resizable=yes");
}