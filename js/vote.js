var newUserDates = [];
var newUserTypes = [];

var max_votes = 0;
var values_changed = false;

$.fn.switchClass = function(a, b) {
    this.removeClass(a);
    this.addClass(b);
    return this;
}

$(document).ready(function () {
    $('#submit_finish_vote').click(function() {
        var form = document.finish_vote;
        var ac = document.getElementById('user_name');
        if (ac != null) {
            if(ac.value.length >= 3){
                form.elements['userId'].value = ac.value;
            } else {
                alert(t('polls', 'You are not registered.\nPlease enter your name to vote\n(at least 3 characters).'));
                return;
            }
        }
        check_notif = document.getElementById('check_notif');
        var newUserDates = [], newUserTypes = [];
        $(".cl_click").each(function() {
            if($(this).hasClass('poll-cell-active-not')) {
                newUserTypes.push(0);
            } else if ($(this).hasClass('poll-cell-active-is')){
                newUserTypes.push(1);
            } else if($(this).hasClass('poll-cell-active-maybe')){
                newUserTypes.push(2);
            } else {
                newUserTypes.push(-1);
            }
            newUserDates.push(parseInt($(this).attr('id')));
        });
        form.elements['dates'].value = JSON.stringify(newUserDates);
        form.elements['types'].value = JSON.stringify(newUserTypes);
        form.elements['notif'].value = (check_notif && check_notif.checked) ? 'true' : 'false';
        form.elements['changed'].value = values_changed ? 'true' : 'false';
        form.submit();
    });

    $('#submit_send_comment').click(function(e) {
        e.preventDefault();
        var form = document.send_comment;
        var comm = document.getElementById('commentBox');
        if(comm.value.trim().length <= 0) {
            alert(t('polls', 'Please add some text to your comment before submitting it.'));
            return;
        }
        var data = {
            pollId: form.elements['pollId'].value,
            userId: form.elements['userId'].value,
            commentBox: comm.value.trim()
        };
        $('.new-comment .icon-loading-small').show();
        $.post(form.action, data, function(data) {
            $('.comments .comment:first').after('<div class="comment"><div class="comment-header"><span class="comment-date">' + data.date + '</span>' + data.userName + '</div><div class="wordwrap comment-content">' + data.comment + '</div></div>');
            $('.new-comment textarea').val('').focus();
            $('.new-comment .icon-loading-small').hide();
        }).error(function() {
            alert(t('polls', 'An error occurred, your comment was not posted...'));
            $('.new-comment .icon-loading-small').hide();  
        });
    });
    
    $(".share input").click(function() {
        $(this).select();
    });
});

$(document).on('click', '.toggle-all, .cl_click', function(e) {
    values_changed = true;
    var cl = "";
    if($(this).hasClass('poll-cell-active-is')) {
        cl = "not";
    } else if($(this).hasClass('poll-cell-active-not')) {
        cl = "maybe";
    } else if($(this).hasClass('poll-cell-active-maybe')) {
        cl = "is";
    } else {
        cl = "is";
    }
    if($(this).hasClass('toggle-all')) {
        $(".cl_click").attr('class', 'cl_click poll-cell-active-' + cl);
        $(this).attr('class', 'toggle-all poll-cell-active-' + cl);
    } else {
        $(this).attr('class', 'cl_click poll-cell-active-' + cl);
    }
    $('.cl_click').each(function() {
        var yes_c = $('#id_y_' + $(this).attr('id'));
        var no_c = $('#id_n_' + $(this).attr('id'));
        $(yes_c).text(parseInt($(yes_c).attr('data-value')) + ($(this).hasClass('poll-cell-active-is') ? 1 : 0));
        $(no_c).text(parseInt($(no_c).attr('data-value')) + ($(this).hasClass('poll-cell-active-not') ? 1 : 0));
    });
    updateCounts();
});

function updateCounts(){
    max_votes = 0;
    $('td.total').each(function() {
        var yes = parseInt($(this).find('.color_yes').text());
        var no = parseInt($(this).find('.color_no').text());
        if(yes - no > max_votes) {
            max_votes = yes - no;
        }
    });
    var i = 0;
    $('td.total').each(function() {
        var yes = parseInt($(this).find('.color_yes').text());
        var no = parseInt($(this).find('.color_no').text());
        $('#id_total_' + i++).toggleClass('icon-checkmark', yes - no == max_votes);
    });
}
