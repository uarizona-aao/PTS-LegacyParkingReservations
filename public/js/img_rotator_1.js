var r_delay = 5000;
var r_transition = 40;
var r_opacity_inc = .10; // for better performance increase this ratio.

window.addEventListener?window.addEventListener("load",so_init,false):window.attachEvent("onload",so_init);

var r_d=document;
var r_imgs = new Array();
var zInterval = null;
var current=0, pause=false;
var loopOnce = false;
var timeoutID = '';

function so_init() {
	if(!r_d.getElementById || !r_d.createElement)
		return;
	r_imgs = r_d.getElementById("imageContainer").getElementsByTagName("img");
	for(i=1;i<r_imgs.length;i++) r_imgs[i].xOpacity = 0;
	r_imgs[0].style.display = "block";
	r_imgs[0].xOpacity = .99;

	setTimeout(so_xfade,r_delay);
}

function so_xfade() {

	if (loopOnce && (current >= (r_imgs.length - 1))) {
		clearTimeout(timeoutID);
		return;
	}

	cOpacity = r_imgs[current].xOpacity;
	nIndex = r_imgs[current+1]?current+1:0;

	nOpacity = r_imgs[nIndex].xOpacity;

	cOpacity-=r_opacity_inc;
	nOpacity+=r_opacity_inc;

	r_imgs[nIndex].style.display = "block";
	r_imgs[current].xOpacity = cOpacity;
	r_imgs[nIndex].xOpacity = nOpacity;

	setOpacity(r_imgs[current]);
	setOpacity(r_imgs[nIndex]);

	if(cOpacity<=0) {
		r_imgs[current].style.display = "none";
		current = nIndex;
		setTimeout(so_xfade,r_delay);
	} else {
		setTimeout(so_xfade,r_transition);
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