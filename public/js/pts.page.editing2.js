
var buttonizer=(function(){
		  
function buttonize(btn,callback){
$('<button type="button" />').attr({
    id: btn.id,
    class: btn.class + ' cmsbutton hide',
	 data: btn.data
}).html(btn.label).appendTo(btn.parent).on("click",function() {
	
callback()
})
};


function showButtons(btns) {
		$(".cmsbutton").addClass("hide");
		$('#' + btns.split(' ').join(',#')).removeClass("hide");
	};


return {
	createButton:buttonize,
	show:showButtons
}

})();

var cms=(function(){
	var currentEditor;
	var hasUpdate=false;
	var currentContent="original";
	var editableRegion="";
	var updateContent="";
	var originalContent="";
	var page = document.location.pathname;
	page= (page.charAt(page.length-1)=='/') ? page.substring(0,page.length-1) : page;
	var pagepath=page.substring(page.indexOf('/'),page.length);
	var pagedirectory=page.split('/')[page.split('/').length-1] ;
	var contentFile=pagepath + '/' + pagedirectory + '-include.php';
	var updateFile=pagepath + '/' + pagedirectory + '-include-temp.php';
	
	var init=function(editRegion) {
		cms.makeButtons();
		editableRegion=editRegion;
		currentContent="original"
		originalContent=$('#' + editableRegion).html();
		var updatedContent=_private.checkForUpdate(function(result) {
			
			updatedContent=result;


		if (updatedContent!="false") {
			hasUpdate=true;
			updateContent=updatedContent;
			buttonizer.show("changeView")
		} else {
			hasUpdate=false;
			updateContent="";
			buttonizer.show("editContent")
		}
				}
		);
	}
	
	var _private={
		showEditor:function() {
			var editHTML;
			if (currentContent=="update") {
				editHTML=updateContent;
			} else {
				editHTML=originalContent;
			}
			$('#'+editableRegion).html(editHTML);
			var ht=$('#'+editableRegion).height();
				if (ht>500) {
				ht=500;
				}
		$('#'+editableRegion).empty();
		var config = {height:ht,
			filebrowserBrowseUrl : '/ckeditor/filemanager/dialog.php?type=2&editor=ckeditor&fldr=',
			filebrowserUploadUrl : '/ckeditor/filemanager/dialog.php?type=2&editor=ckeditor&fldr=',
			filebrowserImageBrowseUrl : '/ckeditor/filemanager/dialog.php?type=1&editor=ckeditor&fldr='};
		currentEditor = CKEDITOR.appendTo(editableRegion, config, editHTML );
		buttonizer.show("cancelEdit saveContent")
		},
		saveEditorContent:function() {},
		saveToFile:function(newHTML,saveTarget) {
	
			 $.post("/ajaxstation/savewebpageupdate.php", {newHTML: newHTML,updateFile:saveTarget}, function(result){
							// callback functions
			});
		},
		checkForUpdate:function(callback) {
			 $.post("/ajaxstation/getcontent.php", {filePath:updateFile}, function(result){
				 callback(result);
				//  return result;
			 }
		);
	},
		deleteUpdate:function() {
			
					 $.post("/ajaxstation/deleteupdate.php", {deleteFile:updateFile}, function(result){
							// callback functions
			});
			hasUpdate=false;
			updateContent="false";
			currentContent="update";
			cms.changeView();
			
		},
		switchView:function() {
			if (currentContent=="update") {
				currentContent="original";
				$('#'+editableRegion).html(originalContent);
				$('#changeView').html('View Update');
				$('#editContent').html('Edit');
				if (hasUpdate==true) {
					buttonizer.show("changeView")
				} else {
					buttonizer.show("editContent")
				}
			} else {
				currentContent="update";
				$('#'+editableRegion).html(updateContent)	
				$('#changeView').html('View Original');
				$('#editContent').html('Edit Update');
				buttonizer.show("changeView editContent deleteUpdate confirmUpdate")
			}
		},
		cancelEdit:function() {
			currentEditor.destroy();
			currentEditor = null;
			currentContent=(currentContent=="update") ? "original" : "update";
			cms.changeView();
		},
		saveUpdate:function() {
			hasUpdate=true;
			var editorHTML = currentEditor.getData();
			currentEditor.destroy();
			currentEditor = null;
			updateContent=editorHTML;
			$('#'+editableRegion).html(editorHTML);
			_private.saveToFile(editorHTML,updateFile);
			$("#editContent").html("Edit Update");
			currentContent="original";
			cms.changeView();
		},
		confirmUpdate:function(){
			alert('this will make update perminant');
		},
		buildButtons:function() {
			buttonizer.createButton({
				label: 'View Update',	  
				id: 'changeView',
				class: 'btn btn-xs btn-primary',
				data: updateFile,
				parent: $('.page-heading')
			},
				function() {
					cms.changeView()
				}
						  );
			buttonizer.createButton({
				label: 'Edit',	  
				id: 'editContent',
				class: 'btn btn-xs btn-success',
				data: updateFile,
				parent: $('.page-heading'),
				'data-content-container':'editableContent'
			},
				function() {
					cms.edit()
				}
						  );
			
			buttonizer.createButton({
				label: 'Cancel',	  
				id: 'cancelEdit',
				class: 'btn btn-xs btn-default',
				data: updateFile,
				parent: $('.page-heading'),
				'data-content-container':'originalcontentholder'
			},
				function() {
					cms.cancelEdit()
				}
						  );
			buttonizer.createButton({
				label: 'Save',	  
				id: 'saveContent',
				class: 'btn btn-xs btn-primary',
				data: updateFile,
				parent: $('.page-heading')
			},
				function() {
					cms.save()
				}
						  );
				buttonizer.createButton({
				label: 'Delete',	  
				id: 'deleteUpdate',
				class: 'btn btn-xs btn-default',
				data: updateFile,
				parent: $('.page-heading')
			},
				function() {
					cms.deleteUpdate()
				}
			 );
			buttonizer.createButton({
				label: 'Confirm',	  
				id: 'confirmUpdate2',
				class: 'btn btn-xs btn-warning',
				data: updateFile,
				parent: $('.page-heading')
			},
				function() {
					cms.confirmUpdate()
				}
						  );	
		}
		
	}
	
	var _public= {
		init:init,
		edit:_private.showEditor,
		save:_private.saveUpdate,
		cancelEdit:_private.cancelEdit,
		changeView:_private.switchView,
		makeButtons:_private.buildButtons,
		confirmUpdate:_private.confirmUpdate,
		deleteUpdate:_private.deleteUpdate
	};
	
	return _public;
	
	
})();



cms.init('editableContent');










