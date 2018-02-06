/** global: Clipboard */
var newUserOptions = [];
var newUserAnswers = [];

var maxVotes = 0;
var valuesChanged = false;

var tzOffset = new Date().getTimezoneOffset();

// HTML template for new comment (handlebars.js)
var tmpl_comment_new = Handlebars.compile('<li class="comment flex-column"> ' +
	'<div class="authorRow user-cell flex-row"> ' +
	'<div class="avatar missing" title="{{userId}}"></div> ' +
	'<div class="author">{{displayName}}</div>' +
	'<div class="date has-tooltip live-relative-timestamp datespan" data-timestamp="{{timeStamp}}" title="{{date}}">{{relativeNow}}</div>' +
	'</div>' +
	'<div class="message wordwrap comment-content">{{comment}}</div>' +
	'</li>');

$.fn.switchClass = function (a, b) {
	this.removeClass(a);
	this.addClass(b);
	return this;
};

function updateCommentsCount() {
	$('#comment-counter').removeClass('no-comments');
	$('#comment-counter').text(parseInt($('#comment-counter').text()) +1);
}

function updateBest() {
	maxVotes = 0;
	$('.counter').each(function () {
		var yes = parseInt($(this).find('.yes').text());
		var no = parseInt($(this).find('.no').text());
		if(yes - no > maxVotes) {
			maxVotes = yes - no;
		}
	});

	$('.vote').each(function () {
		var yes = parseInt($(this).find('.yes').text());
		var no = parseInt($(this).find('.no').text());
		$(this).toggleClass('winner', yes - no === maxVotes);
	});
}

function updateCounters() {
	$('.result-cell.yes').each(function () {
		$(this).text($('#voteid_'+ $(this).attr('data-voteId') + '.poll-cell.yes').length);
	});
	$('.result-cell.no').each(function () {
		$(this).text($('#voteid_'+ $(this).attr('data-voteId') + '.poll-cell.no').length);
	});
	updateBest();
}

function updateAvatar(obj) {
	// Temporary hack - Check if we have Nextcloud or ownCloud with an anomymous user
	if (!document.getElementById('nextcloud') && OC.currentUser === '') {
		$(obj).imageplaceholder(obj.title);
	} else {
		$(obj).avatar(obj.title, 32);
	}
}

function switchSidebar() {
	if ($('#app-content').hasClass('with-app-sidebar')) {
		OC.Apps.hideAppSidebar();
	} else {
		OC.Apps.showAppSidebar();
	}
}

$(document).ready(function () {
	var clipboard = new Clipboard('.copy-link');
	clipboard.on('success', function(e) {
		var $input = $(e.trigger);
		$input.tooltip('hide')
			.attr('data-original-title', t('core', 'Copied!'))
			.tooltip('fixTitle')
			.tooltip({placement: 'bottom', trigger: 'manual'})
			.tooltip('show');
		_.delay(function() {
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
			actionMsg = t('core', 'Press ⌘-C to copy.');
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
			if (OC.Share.Social.Collection.size() == 0) {
				$input.attr('data-original-title', t('core', 'Copy'))
					.tooltip('fixTitle');
			} else {
				$input.tooltip("destroy");
			}
		}, 3000);
	});
	// count how many times in each date
	updateBest();

	// Temporary hack - Check if we have Nextcloud or ownCloud with an anonymous user
	var hideAvatars = false;
	if (!document.getElementById('nextcloud')) {
		if (OC.currentUser === '') {
			hideAvatars = true;
		}
	}

	$('.delete-poll').click(function () {
		deletePoll(this);
	});

	$('#switchDetails').click(function () {
		switchSidebar();
	});

	$('#closeDetails').click(function () {
		OC.Apps.hideAppSidebar();
	});

	$('.avatar').each(function (i, obj) {
		updateAvatar(obj);
	});

	$('.vote.time').each(function () {
		var extendedDate = new Date($(this).attr('data-value-utc').replace(/ /g,'T')+'Z'); //Fix display in Safari and IE

		$(this).find('.month').text(extendedDate.toLocaleString(window.navigator.language, {month: 'short'}));
		$(this).find('.day').text(extendedDate.toLocaleString(window.navigator.language, {day: 'numeric'}));
		$(this).find('.dayow').text(extendedDate.toLocaleString(window.navigator.language, {weekday: 'short'}));
		$(this).find('.time').text(extendedDate.toLocaleTimeString(window.navigator.language, {hour: 'numeric', minute:'2-digit', timeZoneName:'short'}));

	});

	$('#submit_finish_vote').click(function () {
		var form = document.finish_vote;
		var ac = document.getElementById('user_name');
		if (ac !== null) {
			if (ac.value.length >= 3) {
				form.elements.userId.value = ac.value;
			} else {
				alert(t('polls', 'You are not registered.\nPlease enter your name to vote\n(at least 3 characters).'));
				return;
			}
		}
		var check_notif = document.getElementById('check_notif');
		var newUserOptions = [], newUserAnswers = [];
		$('.poll-cell.active').each(function () {
			if($(this).hasClass('no')) {
				newUserAnswers.push('no');
			} else if ($(this).hasClass('yes')) {
				newUserAnswers.push('yes');
			} else if($(this).hasClass('maybe')) {
				newUserAnswers.push('maybe');
			} else {
				newUserAnswers.push('no');
			}
			if (isNaN($(this).attr('data-value'))) {
				newUserOptions.push($(this).attr('data-value'));
			} else {
				newUserOptions.push(parseInt($(this).attr('data-value')));
			}
		});
		form.elements.options.value = JSON.stringify(newUserOptions);
		form.elements.answers.value = JSON.stringify(newUserAnswers);
		form.elements.receiveNotifications.value = (check_notif && check_notif.checked) ? 'true' : 'false';
		form.elements.changed.value = valuesChanged ? 'true' : 'false';
		form.submit();
	});

	$('#submit_send_comment').click(function (e) {
		e.preventDefault();
		var form = document.send_comment;
		var ac = document.getElementById('user_name_comm');
		if (ac !== null) {
			if(ac.value.length >= 3) {
				form.elements.userId.value = ac.value;
			} else {
				alert(t('polls', 'You are not registered.\nPlease enter your name to vote\n(at least 3 characters).'));
				return;
			}
		}
		var comment = document.getElementById('commentBox');
		if(comment.textContent.trim().length <= 0) {
			alert(t('polls', 'Please add some text to your comment before submitting it.'));
			return;
		}
		var data = {
			pollId: form.elements.pollId.value,
			userId: form.elements.userId.value,
			commentBox: comment.textContent.trim()
		};
		$('.new-comment .icon-loading-small').show();

		$.post(form.action, data, function (data) {
			$('#no-comments').after(tmpl_comment_new(data));

			if (!$('#no-comments').hasClass('hidden')) {
				$('#no-comments').addClass('hidden');
			}

			$('.new-comment .message').text('').focus();
			$('.new-comment .icon-loading-small').hide();

			$('.avatar.missing').each(function (i, obj) {
				// oC hack
				if (!hideAvatars) {
					$(obj).avatar(obj.title, 32);
				} else {
					$(obj).imageplaceholder(obj.title);
				}
				$(obj).removeClass('missing');
			});

			updateCommentsCount();
		}).error(function () {
			alert(t('polls', 'An error occurred, your comment was not posted.'));
			$('.new-comment .icon-loading-small').hide();
		});
	});

	$('.share input').click(function () {
		$(this).select();
	});

	$('.has-tooltip').tooltip();
	$('.has-tooltip-bottom').tooltip({placement:'bottom'});
	updateCounters();

});

$('#commentBox').keyup(function () {
	var $message = $('#commentBox');
	if(!$message.text().trim().length) {
		$message.empty();
	}
});

$(document).on('click', '.toggle-cell, .poll-cell.active', function () {
	valuesChanged = true;
	var $nextClass = '';
	var $toggleAllClasses = '';

	if ($(this).hasClass('yes')) {
		$nextClass = 'no';
		$toggleAllClasses= 'yes';
	} else if($(this).hasClass('no')) {
		$nextClass = 'maybe';
		$toggleAllClasses= 'no';
	} else if($(this).hasClass('maybe')) {
		$nextClass = 'yes';
		$toggleAllClasses= 'maybe';
	} else {
		$nextClass = 'yes';
		$toggleAllClasses= 'maybe';
	}

	$(this).removeClass('yes no maybe unvoted');
	$(this).addClass($nextClass);

	if ($(this).hasClass('toggle-cell')) {
		$('.poll-cell.active').removeClass('yes no maybe unvoted');
		$('.poll-cell.active').addClass($toggleAllClasses);
	}
	updateCounters();
});
