var newUserDates = [];
var newUserTypes = [];

var max_votes = 0;
var values_changed = false;

$(document).ready(function () {
    var cells = [];
    cells = document.getElementsByClassName('cl_click');
    // loop over 'user' cells
    for (var i = 0; i < cells.length; i++){
        // fill arrays (if this is edit)
        if (cells[i].className.indexOf('poll-cell-active-not') >= 0){
            newUserTypes.push(0);
            newUserDates.push(cells[i].id);
        } else if (cells[i].className.indexOf('poll-cell-active-is') >= 0){
            newUserTypes.push(1);
            newUserDates.push(cells[i].id);
        } else if(cells[i].className.indexOf('poll-cell-active-maybe') >= 0){
            newUserTypes.push(2);
            newUserDates.push(cells[i].id);
        } else {
            newUserTypes.push(-1);
            newUserDates.push(cells[i].id);
        }
    }

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
        form.elements['dates'].value = JSON.stringify(newUserDates);
        form.elements['types'].value = JSON.stringify(newUserTypes);
        form.elements['notif'].value = (check_notif && check_notif.checked) ? 'true' : 'false';
        form.elements['changed'].value = values_changed ? 'true' : 'false';
        form.submit();
    });

    $('#submit_send_comment').click(function() {
        var form = document.send_comment;
        var comm = document.getElementById('commentBox');
        if(comm.value.length <= 0) {
            alert(t('polls', 'Please add some text to your comment before submitting it.'));
            return;
        }
        form.submit();
    });
});

$(document).on('click', '.toggle-all', function(e) {
    if($(this).attr('class').indexOf('selected-all') > -1) {
        var selected = $(this).parent().children('.poll-cell-active-is');
        var maybes = $(this).parent().children('.poll-cell-active-maybe');
        for(var i=0; i<selected.length; i++) {
            var curr = $(selected[i]);
            curr.switchClass('poll-cell-active-is', 'poll-cell-active-not');
            deselectItem(curr);
        }
        for(var i=0; i<maybes.length; i++) {
            var curr = $(maybes[i]);
            curr.switchClass('poll-cell-active-maybe', 'poll-cell-active-not');
            deselectItem(curr);
        }
        $(this).removeClass('selected-all');
        $(this).addClass('selected-none');
    } else if($(this).attr('class').indexOf('selected-none') > -1) {
        var selected = $(this).parent().children('.poll-cell-active-is');
        var unselected = $(this).parent().children('.poll-cell-active-not');
        for(var i=0; i<selected.length; i++) {
            var curr = $(selected[i]);
            curr.switchClass('poll-cell-active-is', 'poll-cell-active-maybe');
            maybeItem(curr);
        }
        for(var i=0; i<unselected.length; i++) {
            var curr = $(unselected[i]);
            curr.switchClass('poll-cell-active-not', 'poll-cell-active-maybe');
            maybeItem(curr);
        }
        $(this).removeClass('selected-none');
        $(this).addClass('selected-maybe');
    } else {
        var maybes = $(this).parent().children('.poll-cell-active-maybe');
        var unselected = $(this).parent().children('.poll-cell-active-not');
        var notselected = $(this).parent().children('.poll-cell-active-un');
        for(var i=0; i<maybes.length; i++) {
            var curr = $(maybes[i]);
            curr.switchClass('poll-cell-active-maybe', 'poll-cell-active-is');
            selectItem(curr);
        }
        for(var i=0; i<unselected.length; i++) {
            var curr = $(unselected[i]);
            curr.switchClass('poll-cell-active-not', 'poll-cell-active-is');
            selectItem(curr, 'poll-cell-active-not');
        }
        for(var i=0; i<notselected.length; i++) {
            var curr = $(notselected[i]);
            curr.switchClass('poll-cell-active-un', 'poll-cell-active-is');
            selectItem(curr);
        }
        $(this).removeClass('selected-maybe');
        $(this).addClass('selected-all');
    }
});

$(document).on('click', '.poll-cell-active-un', function(e) {
    maybeItem($(this));
});

$(document).on('click', '.poll-cell-active-not', function(e) {
    maybeItem($(this));
});

$(document).on('click', '.poll-cell-active-maybe', function(e) {
    selectItem($(this));
});

$(document).on('click', '.poll-cell-active-is', function(e) {
    deselectItem($(this));
});

function selectItem(cell, cl='') {
    values_changed = true;
    var ts = cell.attr('id');
    var index = newUserDates.indexOf(ts);
    if(index > -1) {
        newUserDates.splice(index, 1);
        newUserTypes.splice(index, 1);
    }
    newUserDates.push(ts);
    newUserTypes.push(1);
    if(cl.indexOf('poll-cell-active-not') > -1) {
        var total_no = document.getElementById('id_n_' + ts);
        total_no.innerHTML = parseInt(total_no.innerHTML) - 1;
    }
    var total_yes = document.getElementById('id_y_' + ts);
    total_yes.innerHTML = parseInt(total_yes.innerHTML) + 1;
    cell.switchClass('poll-cell-active-maybe', 'poll-cell-active-is');
    findNewMaxCount();
    updateStrongCounts();
}

function deselectItem(cell) {
    values_changed = true;
    var ts = cell.attr('id');
    var index = newUserDates.indexOf(ts);
    if(index > -1) {
        newUserDates.splice(index, 1);
        newUserTypes.splice(index, 1);
    }
    newUserDates.push(ts);
    newUserTypes.push(0);
    var total_yes = document.getElementById('id_y_' + ts);
    var total_no = document.getElementById('id_n_' + ts);
    total_yes.innerHTML = parseInt(total_yes.innerHTML) - 1;
    total_no.innerHTML = parseInt(total_no.innerHTML) + 1;
    cell.switchClass('poll-cell-active-is', 'poll-cell-active-not');
    findNewMaxCount();
    updateStrongCounts();
}

function maybeItem(cell) {
    values_changed = true;
    var ts = cell.attr('id');
    var index = newUserDates.indexOf(ts);
    if(index > -1) {
        newUserDates.splice(index, 1);
        newUserTypes.splice(index, 1);
    }
    newUserDates.push(ts);
    newUserTypes.push(2);
    var total_no = document.getElementById('id_n_' + ts);
    total_no.innerHTML = parseInt(total_no.innerHTML) - 1;
    cell.switchClass('poll-cell-active-not', 'poll-cell-active-maybe');
    cell.switchClass('poll-cell-active-un', 'poll-cell-active-maybe');
    findNewMaxCount();
    updateStrongCounts();
}

function findNewMaxCount(){
    var cell_tot_y = document.getElementsByClassName('cl_total_y');
    var cell_tot_n = document.getElementsByClassName('cl_total_n');
    max_votes = 0;
    for(var i=0; i<cell_tot_y.length; i++) {
        var currYes = parseInt(cell_tot_y[i].innerHTML);
        var currNo = parseInt(cell_tot_n[i].innerHTML);
        var curr = currYes - currNo;
        if(curr > max_votes) max_votes = curr;
    }
}

function updateStrongCounts(){
    var cell_tot_y = document.getElementsByClassName('cl_total_y');
    var cell_tot_n = document.getElementsByClassName('cl_total_n');

    for(var i=0; i<cell_tot_y.length; i++) {
        var cell_win = document.getElementById('id_total_' + i);
        var curr = parseInt(cell_tot_y[i].innerHTML) - parseInt(cell_tot_n[i].innerHTML);
        if(curr < max_votes) {
            cell_win.className = 'win_row';
                cell_tot_y[i].style.fontWeight = 'normal';
        }
        else {
            cell_tot_y[i].style.fontWeight = 'bold';
            cell_win.className = 'win_row icon-checkmark';
        }
    }
}
