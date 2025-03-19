			var currentEditor;
			
			$( document ).ready(function() {
				
		$('#editContent').on('click', function () {
			var contentSource=$(this).attr('data-content-container');
			var data = $("#" + contentSource).html();
			var ht=$("#editableContent").height();
				if (ht>500) {
				ht=500
				}
		$("<div />").attr({id:'originalcontentholder',class:'hide'}).html(data).appendTo("body");
	
		$("#editableContent").empty();
		var config = {height:ht};
		currentEditor = CKEDITOR.appendTo( 'editableContent', config, data );
		$("#editContent,#viewUpdate,#viewOriginal").addClass("hide");
		$("#saveContent,#cancelContent").removeClass("hide");
		
	})



		$('#saveContent').on('click', function () {
			html = currentEditor.getData();
			currentEditor.destroy();
			currentEditor = null;
			$("#originalcontentholder").remove();
			$("#editContent,#viewUpdate").removeClass("hide");
			$("#saveContent,#cancelContent").addClass("hide");
			$("#editableContent").html(html)
			var saveTarget=$(this).attr("data");
				saveUpdates(html,saveTarget);
	})
	
			$('#cancelContent').on('click', function () {
			contentSource=$(this).attr('data-content-container');
			html = $("#" + contentSource).html();
			if (currentEditor) {  
			currentEditor.destroy();
			currentEditor = null;
		}
			$("#editContent").removeClass("hide");
			$("#saveContent,#cancelContent").addClass("hide");
			
			if (contentSource=="updatecontentholder") {
					$("#viewOriginal").removeClass("hide");
			}
	
			$("#editableContent").html(html);
			$("#originalcontentholder").remove();

	})
			
			})	
			
		function	saveUpdates(newHTML,saveTarget) {
			
			    $.post("/ajaxstation/savewebpageupdate.php", {newHTML: newHTML,updateFile:saveTarget}, function(result){
							// callback functions
						//	alert(result);
			});
		}
			
			
	function checkForUpdate (updatedFileName)	{
				 $.post("/ajaxstation/getcontent.php", {filePath:updatedFileName}, function(result){
						var updateHTML=result;
						if (updateHTML!="blank") {
							$("<div />").attr({id:'updatecontentholder',class:'hide'}).html(updateHTML).appendTo("body");
								buttonize({
									label: 'View Update',	  
									id: 'viewUpdate',
									class: 'btn btn-xs btn-warning',
									data: updatedFileName,
									parent: $('.page-heading')
								})
								buttonize({
									label: 'Confirm Update',	  
									id: 'confirmUpdate',
									class: 'btn btn-xs btn-primary hide',
									data: updatedFileName,
									parent: $('.page-heading')
								})
								buttonize({
									label: 'View Original',	  
									id: 'viewOriginal',
									class: 'btn btn-xs btn-primary hide',
									data: updatedFileName,
									parent: $('.page-heading')
								})
								$("#editContent").addClass("hide");
								
									$('#viewUpdate').on('click', function () {
										updateHTML = $("#updatecontentholder").html();
										currentHTML=$("#editableContent").html();
										$("<div />").attr({id:'originalcontentholder',class:'hide'}).html(currentHTML).appendTo("body");
										$("#editableContent").html(updateHTML);
										$("#editContent,#viewOriginal").removeClass("hide");
										$("#saveContent,#viewUpdate,#cancelConent").addClass("hide");
										$("#editContent").attr({"data-content-container":"editableContent"}).html("Edit Update");
										$("#cancelContent").attr({"data-content-container":"updatecontentholder"}).html("Cancel Update Edit");	 
			
								})
								$('#viewOriginal').on('click', function () {
										currentHTML=$("#originalcontentholder").html();
										$("#editableContent").html(currentHTML);
										$("#viewUpdate").removeClass("hide");
										$("#saveContent,#cancelEdit,#viewOriginal").addClass("hide");
										$("#editContent").attr({"data-content-container":"editableContent"}).html("Edit");
										$("#cancelContent").attr({"data-content-container":"originalcontentholder"}).html("Cancel Edit");	 
			
								})
		
					 }
			});
	}
	
			
			
			
			
var page = document.location.pathname;
page= (page.charAt(page.length-1)=='/') ? page.substring(0,page.length-1) : page;
var pagepath=page.substring(page.indexOf('/'),page.length)
var pagedirectory=page.split('/')[page.split('/').length-1] 
var contentFile=pagepath + '/' + pagedirectory + '-include.php'
var updateFile=pagepath + '/' + pagedirectory + '-include-temp.php'




buttonize({
	label: 'Edit',	  
	id: 'editContent',
   class: 'btn btn-xs btn-success',
	data: updateFile,
	parent: $('.page-heading'),
	'data-content-container':'editableContent'
})

buttonize({
	label: 'Cancel',	  
	id: 'cancelContent',
   class: 'btn btn-xs btn-default hide',
	data: updateFile,
	parent: $('.page-heading'),
	'data-content-container':'originalcontentholder'
})

buttonize({
	label: 'Save',	  
	id: 'saveContent',
   class: 'btn btn-xs btn-primary hide',
	data: updateFile,
	parent: $('.page-heading')
})

checkForUpdate(updateFile);


var buttonizer=(function(){
		  
function buttonize(btn){
$('<button type="button" />').attr({
    id: btn.id,
    class: btn.class,
	 data: btn.data
}).html(btn.label).appendTo(btn.parent);
}

return {
	addButton:this.buttonize,
	showButtons:this.showButtons
}


})();