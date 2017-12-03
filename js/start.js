/** global: Clipboard */
$(document).ready(function () {
	new Clipboard('.copy-link');
	$('.alt-tooltip').tooltip();

	$('.delete-poll').click(function () {
		deletePoll(this);
	});

	$('.table-body .avatardiv').each(function (i, obj) {
		$(obj).avatar(obj.title, 32);
	});

	$('.popupmenu').each(function () {
		OC.registerMenu($('#expand_' + $(this).attr('value')), $('#expanddiv_' + $(this).attr('value')) );
	});

	$('.copy_link').click(function () {
		window.prompt(t('polls','Copy to clipboard: Ctrl+C, Enter'), $(this).data('url'));
	});
});
