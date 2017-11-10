var newUserDates = [];
var newUserTypes = [];

var maxVotes = 0;
var valuesChanged = false;

var tzOffset = new Date().getTimezoneOffset();

$.fn.switchClass = function(a, b) {
	this.removeClass(a);
	this.addClass(b);
	return this;
};

function updateCommentsCount(){
	$('#comment-counter').removeClass('no-comments');
	$('#comment-counter').text(parseInt($('#comment-counter').text()) +1);
	
}

function updateBest(){
	maxVotes = 0;
	$('.counter').each(function() {
		var yes = parseInt($(this).find('.yes').text());
		var no = parseInt($(this).find('.no').text());
		if(yes - no > maxVotes) {
			maxVotes = yes - no;
		}
	});
	var i = 0;
	$('.vote').each(function() {
		var yes = parseInt($(this).find('.yes').text());
		var no = parseInt($(this).find('.no').text());
		$(this).toggleClass('winner', yes - no === maxVotes);
	});
}

function updateCounters(){
	$('.result-cell.yes').each(function() {
			$(this).text($('#voteid_'+ $(this).attr('data-voteId') + '.poll-cell.yes').length);
	});
	$('.result-cell.no').each(function() {
			$(this).text($('#voteid_'+ $(this).attr('data-voteId') + '.poll-cell.no').length);
	});
	updateBest();
}

function switchSidebar() {
	if ($('#app-content').hasClass('with-app-sidebar')) {
		OC.Apps.hideAppSidebar();
	} else {
		OC.Apps.showAppSidebar();
	}
}

$(document).ready(function () {
	// count how many times in each date
	new Clipboard('.copy-link');
	updateBest();
    $('.delete-poll').click(function(){
		deletePoll(this);
    });
 
    $('#switchDetails').click(function(){
		switchSidebar();
    });
	
    $('#closeDetails').click(function(){
		OC.Apps.hideAppSidebar();
    });
	
	$('.avatar').each(function (i, obj) {
		$(obj).avatar(obj.title, 32);
	});

	$('.vote.time').each(function() {
        var extendedDate = new Date($(this).attr("data-value-utc").replace(/ /g,"T")+"Z"); //Fix display in Safari and IE

        $(this).find('.month').text(extendedDate.toLocaleString(window.navigator.language, {month: 'short'}));
        $(this).find('.day').text(extendedDate.toLocaleString(window.navigator.language, {day: 'numeric'}));
        $(this).find('.dayow').text(extendedDate.toLocaleString(window.navigator.language, {weekday: 'short'}));
        $(this).find('.time').text(extendedDate.toLocaleTimeString(window.navigator.language, {hour: 'numeric', minute:'2-digit', timeZoneName:'short'}));
        
 	});

	$('#submit_finish_vote').click(function() {
		var form = document.finish_vote;
		var ac = document.getElementById('user_name');
		if (ac !== null) {
			if(ac.value.length >= 3){
				form.elements.userId.value = ac.value;
			} else {
				alert(t('polls', 'You are not registered.\nPlease enter your name to vote\n(at least 3 characters).'));
				return;
			}
		}
		var check_notif = document.getElementById('check_notif');
		var newUserDates = [], newUserTypes = [];
		$(".poll-cell.active").each(function() {
			if($(this).hasClass('no')) {
				newUserTypes.push(0);
			} else if ($(this).hasClass('yes')){
				newUserTypes.push(1);
			} else if($(this).hasClass('maybe')){
				newUserTypes.push(2);
			} else {
				newUserTypes.push(-1);
			}
			if (isNaN($(this).attr('data-value'))) {
				newUserDates.push($(this).attr('data-value'));
			} else {
				newUserDates.push(parseInt($(this).attr('data-value')));
			}
		});
		form.elements.dates.value = JSON.stringify(newUserDates);
		form.elements.types.value = JSON.stringify(newUserTypes);
		form.elements.receiveNotifications.value = (check_notif && check_notif.checked) ? 'true' : 'false';
		form.elements.changed.value = valuesChanged ? 'true' : 'false';
		form.submit();
	});

	$('#submit_send_comment').click(function(e) {
		e.preventDefault();
		var form = document.send_comment;
		var ac = document.getElementById('user_name_comm');
		if (ac !== null) {
			if(ac.value.length >= 3){
				form.elements.userId.value = ac.value;
			} else {
				alert(t('polls', 'You are not registered.\nPlease enter your name to vote\n(at least 3 characters).'));
				return;
			}
		}
		var comment = document.getElementById('commentBox');
		if(comment.value.trim().length <= 0) {
			alert(t('polls', 'Please add some text to your comment before submitting it.'));
			return;
		}
		var data = {
			pollId: form.elements.pollId.value,
			userId: form.elements.userId.value,
			commentBox: comment.value.trim()
		};
		$('.new-comment .icon-loading-small').show();
		$.post(form.action, data, function(data) {
		var newCommentElement = '<li class="comment column"> ' +
								'<div class="authorRow user-cell row"> ' +
								'<div class="avatar avatardiv" title="' + data.userId + '"></div> ' +
								'<div class="author">' + data.userName + '</div>' +
								'<div class="date has-tooltip live-relative-timestamp datespan" data-timestamp="' + Date.now() + '" title="' + data.date + '">' + t('now') + '</div>' + 
								'</div>' + 
								'<div class="message wordwrap comment-content">' + data.comment + '</div>' +
								'</li>';


			$('.comments .comment:first').before(newCommentElement);
			$('.new-comment textarea').val('').focus();
			$('.new-comment .icon-loading-small').hide();
			updateCommentsCount();
		}).error(function() {
			alert(t('polls', 'An error occurred, your comment was not posted.'));
			$('.new-comment .icon-loading-small').hide();
		});
	});

	$(".share input").click(function() {
		$(this).select();
	});
	
	$('.toggle-cell').tooltip();
	$('.time-slot').tooltip();
	$('.avatardiv').tooltip();
	$('.has-tooltip').tooltip();
	updateCounters();

});

$(document).on('click', '.toggle-cell, .poll-cell.active', function() {
	valuesChanged = true;
	var $nextClass = "";
	var $toggleAllClasses = "";
	
	if($(this).hasClass('yes')) {
		$nextClass = "no";
		$toggleAllClasses= "yes";
	} else if($(this).hasClass('no')) {
		$nextClass = "maybe";
		$toggleAllClasses= "no";
	} else if($(this).hasClass('maybe')) {
		$nextClass = "yes";
		$toggleAllClasses= "maybe";
	} else {
		$nextClass = "yes";
		$toggleAllClasses= "maybe";
	}
	
	$(this).removeClass('yes no maybe unvoted');
	$(this).addClass($nextClass);
	
	if($(this).hasClass('toggle-cell')) {
		$(".poll-cell.active").removeClass('yes no maybe unvoted');
		$(".poll-cell.active").addClass($toggleAllClasses);
	}
	updateCounters();
});

