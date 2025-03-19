


// Creates a popup window.
var windough=null;
function NewWindow(mypage, my_id, w, h, scroll, pos, menubar, toolbar, location) {
	// EXAMPLE CALL auto-width/height: <a href="notes.php?num=3" onclick="NewWindow(this.href, 1111111); return false" onfocus="this.blur()">
	// EXAMPLE CALL: <a href="notes.php?num=3" onclick="NewWindow(this.href, 1111111, 660, 200, 1, 0); return false" onfocus="this.blur()">
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
      if (!w)	w = window.screen.availWidth * 70 / 100;
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
	//	myWindow.moveBy(1,1); // to ensure window re-focuses
	windough.focus();
}


