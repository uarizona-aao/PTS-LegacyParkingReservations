
// This is just a hack of img_rotator.js, but all the functions and variables are appended with "_2".
// Also, this uses var loopOnce_2 = true;


var r_delay_2 = 20000;
var r_transition_2 = 40;
var r_opacity_inc_2 = .25; // for better performance increase this ratio.

window.addEventListener?window.addEventListener("load",so_init_2,false):window.attachEvent("onload",so_init_2);

var r_d_2 = document;
var r_imgs_2 = new Array();
var zInterval_2 = null;
var current_2=0, pause_2=false;
var loopOnce_2 = true;
var timeoutID_2 = '';

function so_init_2() {
	if(!r_d_2.getElementById || !r_d_2.createElement)
		return;
	r_imgs_2 = r_d_2.getElementById("imageContainer_2").getElementsByTagName("img");
	for(i=1;i<r_imgs_2.length;i++) r_imgs_2[i].xOpacity = 0;
	r_imgs_2[0].style.display = "block";
	r_imgs_2[0].xOpacity = .99;

	timeoutID_2 = setTimeout(so_xfade_2,r_delay_2);
}

function so_xfade_2() {

	if (loopOnce_2 && (current_2 >= (r_imgs_2.length - 1))) {
		clearTimeout(timeoutID_2);
		return;
	}

	cOpacity = r_imgs_2[current_2].xOpacity;
	nIndex = r_imgs_2[current_2+1]?current_2+1:0;

	nOpacity = r_imgs_2[nIndex].xOpacity;

	cOpacity-=r_opacity_inc_2;
	nOpacity+=r_opacity_inc_2;

	r_imgs_2[nIndex].style.display = "block";
	r_imgs_2[current_2].xOpacity = cOpacity;
	r_imgs_2[nIndex].xOpacity = nOpacity;

	setOpacity(r_imgs_2[current_2]);
	setOpacity(r_imgs_2[nIndex]);

	if(cOpacity<=0) {
		r_imgs_2[current_2].style.display = "none";
		current_2 = nIndex;
		setTimeout(so_xfade_2,r_delay_2);
	} else {
		setTimeout(so_xfade_2,r_transition_2);
	}

	function setOpacity(obj) {
		if(obj.xOpacity>.99) {
			obj.xOpacity = .99;
			return;
		}
		obj.style.opacity = obj.xOpacity;
		obj.style.MozOpacity = obj.xOpacity;
		obj.style.filter = "alpha(opacity=" + (obj.xOpacity*100) + ")";
	}

}