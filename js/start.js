
$(document).ready(function () {
/*	 
	 $('.table-body div.column.expiry').each(function(i, obj) {
			if (isDate(obj.dataset.value)) {
				obj.dataset.value = obj.dataset.value.replace(/ /g,"T")+"Z";
				obj.innerText= OC.Util.relativeModifiedDate(obj.dataset.value);
			};
	});
 */  
	$('.table-body .avatardiv').each(function(i, obj) {
		$(obj).avatar(obj.title, 32);
	});
	
	$('.popupmenu').each(function() {
		OC.registerMenu($('#expand_' + $(this).attr('value')), $('#expanddiv_' + $(this).attr('value')) ); 
	});
	
	// $('.cl_delete').click(deletePoll);
	$('.delete_poll').click(deletePoll);

	$('.copy_link').click(function() {
		window.prompt(t('polls','Copy to clipboard: Ctrl+C, Enter'), $(this).data('url'));
	});
	
	// $('.cl_link').click(function() {
		// window.prompt(t('polls','Copy to clipboard: Ctrl+C, Enter'), $(this).data('url'));
	// });
	
	
	
});

function deletePoll() {
	var str = t('polls', 'Do you really want to delete that poll?') + '\n\n' + $(this).attr('data-value');
	if (confirm(str)) {
		var form = document.form_delete_poll;
		var hiddenId = document.createElement("input");
		hiddenId.setAttribute("name", "pollId");
		hiddenId.setAttribute("type", "hidden");
		form.appendChild(hiddenId);
		form.elements['pollId'].value = this.id.split('_')[2];
		form.submit();
	}
}

/* 
			obj.textContent = obj.data-value ;
 */

function isDate(val) {
	var d = new Date(val);
	return !isNaN(d.valueOf());
}

