var newUserDates = [];
var newUserTypes = [];

var max_votes = 0;
var values_changed = false;

var tzOffset = new Date().getTimezoneOffset();

$.fn.switchClass = function(a, b) {
    this.removeClass(a);
    this.addClass(b);
    return this;
}

$(document).ready(function () {
    // count how many times in each date
    var arr_dates = [];  // will be like: [21.02] => 3
    var arr_years = [];  // [1992] => 6
    var prev = '';
    var dateStr = '';

    $('.poll.avatardiv').each(function(i, obj) {
        $(obj).avatar(obj.title, 32);
    });

    
    $('.hidden-dates').each(function(i, obj) {
        var exDt = new Date(obj.value.replace(/ /g,"T")+"Z"); //Fix display in Safari and IE, still NaN on Firefox on iPad
        var day = ('0' + exDt.getDate()).substr(-2);
        var month = ('0' + (exDt.getMonth()+1)).substr(-2);
        var day_month = day + '.' + month;
        var year = exDt.getFullYear();

        if(typeof arr_dates[day_month] != 'undefined') {
            arr_dates[day_month] += 1;
        } else {
            arr_dates[day_month] = 1;
        }
        if(typeof arr_years[year] != 'undefined') {
            arr_years[year] += 1;
        } else {
            arr_years[year] = 1;
        }
        var c = (prev != (year + day_month) ? ' bordered' : '');
        prev = (year + day_month);
        var ch_obj = ('0' + (exDt.getHours())).substr(-2) + ':' + ('0' + exDt.getMinutes()).substr(-2)
        dateStr += '<th class="time-slot" value="' + obj.value + '"> ' + 
        '<div class="month">' + exDt.toLocaleString(window.navigator.language, {month: 'short'}) + 
                            // ' \'' + exDt.toLocaleString(window.navigator.language, {year: '2-digit'}) + 
                            '</div>' + 
        '<div class="day">'   + exDt.toLocaleString(window.navigator.language, {day: 'numeric'}) + '</div>' +
        '<div class="dayow">' + exDt.toLocaleString(window.navigator.language, {weekday: 'short'}) + '</div>' + 
        '<div class="time">'  + ('0' + (exDt.getHours())).substr(-2) + ':' + ('0' + exDt.getMinutes()).substr(-2) + '</div>' + 
        '</th>';
    });

    var for_string_dates = '';
    for(var k in arr_dates) {
        for_string_dates += '<th colspan="' + arr_dates[k] + '" class="bordered">' + k + '</th>';
    }

    var for_string_years = '';
    for(var k in arr_years) {
        for_string_years += '<th colspan="' + arr_years[k] + '" class="bordered">' + k + '</th>';
    }

    $('#time-slots-header').append(dateStr);

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
            if($(this).hasClass('no')) {
                newUserTypes.push(0);
            } else if ($(this).hasClass('yes')){
                newUserTypes.push(1);
            } else if($(this).hasClass('maybe')){
                newUserTypes.push(2);
            } else {
                newUserTypes.push(-1);
            }
            var userDate = $(this).attr('id');
            if(isNaN($(this).attr('id')) ) {
                newUserDates.push($(this).attr('id'));
            } else { 
                newUserDates.push(parseInt($(this).attr('id')));
            }
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
        var ac = document.getElementById('user_name_comm');
        if (ac != null) {
            if(ac.value.length >= 3){
                form.elements['userId'].value = ac.value;
            } else {
                alert(t('polls', 'You are not registered.\nPlease enter your name to vote\n(at least 3 characters).'));
                return;
            }
        }
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
    var $cl = "";
    var $toggle = "";
    if($(this).hasClass('yes')) {
        $cl = "no";
        $toggle= "yes";
    } else if($(this).hasClass('no')) {
        $cl = "maybe";
        $toggle= "no";
    } else if($(this).hasClass('maybe')) {
        $cl = "yes";
        $toggle= "maybe";
    } else {
        $cl = "yes";
        $toggle= "maybe";
    }
    if($(this).hasClass('toggle-all')) {
        $(".cl_click").attr('class', 'cl_click poll-cell active ' + $toggle);
        $(this).attr('class', 'toggle-all toggle ' + $cl);
    } else {
        $(this).attr('class', 'cl_click poll-cell active ' + $cl);
    }
    $('.cl_click').each(function() {
        var yes_c = $('#id_y_' + $(this).attr('id'));
        var no_c = $('#id_n_' + $(this).attr('id'));
        $(yes_c).text(parseInt($(yes_c).attr('data-value')) + ($(this).hasClass('yes') ? 1 : 0));
        $(no_c).text(parseInt($(no_c).attr('data-value')) + ($(this).hasClass('no') ? 1 : 0));
    });
    updateCounts();
});

function updateCounts(){
    max_votes = 0;
    $('td.total').each(function() {
        var yes = parseInt($(this).find('.yes').text());
        var no = parseInt($(this).find('.no').text());
        if(yes - no > max_votes) {
            max_votes = yes - no;
        }
    });
    var i = 0;
    $('td.total').each(function() {
        var yes = parseInt($(this).find('.yes').text());
        var no = parseInt($(this).find('.no').text());
        $('#id_total_' + i++).toggleClass('icon-checkmark', yes - no == max_votes);
    });
}
