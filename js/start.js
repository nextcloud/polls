/* global clipboard, navigator */
$(document).ready(function () {
	var clipboard = new Clipboard('.copy-link');
	clipboard.on('success', function (e) {
		var $input = $(e.trigger);
		$input.tooltip('hide')
			.attr('data-original-title', t('core', 'Copied!'))
			.tooltip('fixTitle')
			.tooltip({placement: 'bottom', trigger: 'manual'})
			.tooltip('show');
		_.delay(function () {
			$input.tooltip('hide');
			if (OC.Share.Social.Collection.size() === 0) {
				$input.attr('data-original-title', t('core', 'Copy'))
					.tooltip('fixTitle');
			} else {
				$input.tooltip('destroy');
			}
		}, 3000);
	});
	
	clipboard.on('error', function (e) {
		var $input = $(e.trigger);
		var actionMsg = '';
		if (/iPhone|iPad/i.test(navigator.userAgent)) {
			actionMsg = t('core', 'Not supported!');
		} else if (/Mac/i.test(navigator.userAgent)) {
			actionMsg = t('core', 'Press ?-C to copy.');
		} else {
			actionMsg = t('core', 'Press Ctrl-C to copy.');
		}

		$input.tooltip('hide')
			.attr('data-original-title', actionMsg)
			.tooltip('fixTitle')
			.tooltip({placement: 'bottom', trigger: 'manual'})
			.tooltip('show');
		_.delay(function () {
			$input.tooltip('hide');
			if (OC.Share.Social.Collection.size() === 0) {
				$input.attr('data-original-title', t('core', 'Copy'))
					.tooltip('fixTitle');
			} else {
				$input.tooltip("destroy");
			}
		}, 3000);
	});

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
