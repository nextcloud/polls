var g_chosen_datetimes = [];
var g_chosen_texts = [];
var g_chosen_groups = [];
var g_chosen_users = [];
var chosen_type = 'event';
var access_type = '';

$(document).ready(function () {
    // enable / disable date picker
    $('#id_expire_set').click(function(){
        $('#id_expire_date').prop("disabled", !this.checked);
        if (this.checked) {
           $("#id_expire_date").focus();
        }
    });

    var privateRadio = document.getElementById('private');
    var hiddenRadio = document.getElementById('hidden');
    var publicRadio = document.getElementById('public');
    var selectRadio = document.getElementById('select');
    if(privateRadio.checked) access_type = 'registered';
    if(hiddenRadio.checked) access_type = 'hidden';
    if(publicRadio.checked) access_type = 'public';
    if(selectRadio.checked) access_type = 'select';

    var accessValues = document.getElementById('accessValues');
    if(accessValues.value.length > 0) {
        var accessValueArr = accessValues.value.split(';');
        for(var i=0; i<accessValueArr.length; i++) {
            var val = accessValueArr[i];
            var index = val.indexOf('group_');
            if(index == 0) {
                g_chosen_groups.push(val);
            } else {
                index = val.indexOf('user_');
                if(index == 0) {
                    g_chosen_users.push(val);
                }
            }
        }
    }

    var chosenDates = document.getElementById('chosenDates').value;
    var chosen = '';
    if(chosenDates.length > 0) chosen = JSON.parse(chosenDates);
    var text = document.getElementById('text');
    var event = document.getElementById('event');
    if(event.checked) {
        chosen_type = event.value;
        if(chosenDates.length > 0) g_chosen_datetimes = chosen;
        for(var i=0; i<chosen.length; i++) {
            var date = new Date(chosen[i]*1000);
            var year = date.getFullYear();
            var month = date.getMonth();
            var day = date.getDate();
            var newDate = new Date(year, month, day).getTime(); //save timestamp without time of day
            month = '0' + (month+1); //month is 0-11, so +1
            day = '0' + day;
            var dateStr = day.substr(-2) + '.' + month.substr(-2) + '.' + year;
            var hours = date.getHours();
            var minutes = date.getMinutes();
            var ms = (hours * 60 * 60 * 1000) + (minutes * 60 * 1000); //time of day in milliseconds
            hours = '0' + hours;
            minutes = '0' + minutes;
            var timeStr = hours.substr(-2) + ':' + minutes.substr(-2);
            addRowToList(newDate/1000, dateStr, ms/1000);
            addColToList(ms/1000, timeStr, newDate/1000);
        }
    } else {
        chosen_type = text.value;
        if(chosenDates.length > 0) g_chosen_texts = chosen;
        for(var i=0; i<chosen.length; i++) {
            insertText(chosen[i], true);
        }
    }

    var expirepicker = jQuery('#id_expire_date').datetimepicker({
        inline: false,
        onSelectDate: function(date, $i) {
            var year = date.getFullYear();
            var month = date.getMonth();
            var day = date.getDate();
            var newDate = new Date(year, month, day).getTime()/1000;
            document.getElementById('expireTs').value = newDate;
        },
        timepicker: false,
        format: 'd.m.Y'
    });

    var datepicker = jQuery('#datetimepicker').datetimepicker({
        inline:true,
        step: 15,
        todayButton: true,
        onSelectDate: function(date, $i) {
            var year = date.getFullYear();
            var month = date.getMonth();
            var day = date.getDate();
            var newDate = new Date(year, month, day).getTime(); //save timestamp without time of day
            month = '0' + (month+1); //month is 0-11, so +1
            day = '0' + day;
            var dateStr = day.substr(-2) + '.' + month.substr(-2) + '.' + year;
            addRowToList(newDate/1000, dateStr);
        },
        onSelectTime: function(date, $i) {
            var hours = date.getHours();
            var minutes = date.getMinutes();
            var ms = (hours * 60 * 60 * 1000) + (minutes * 60 * 1000); //time of day in milliseconds
            hours = '0' + hours;
            minutes = '0' + minutes;
            var timeStr = hours.substr(-2) + ':' + minutes.substr(-2);
            addColToList(ms/1000, timeStr);
        }
    });

    $(document).on('click', '.date-row', function(e) {
        var tr = $(this).parent();
        var dateId = parseInt(tr.attr('id'));
        var index = tr.index();
        var cells = tr[0].cells; //convert jQuery object to DOM
        for(var i=1; i<cells.length; i++) {
            var cell = cells[i];
            var delIndex = g_chosen_datetimes.indexOf(dateId + parseInt(cell.id));
            if(delIndex > -1) g_chosen_datetimes.splice(delIndex, 1);
        }
        var table = document.getElementById('selected-dates-table');
        table.deleteRow(index);
    });

    $(document).on('click', '.date-col', function(e) {
        var cellIndex = $(this).index();
        var timeId = parseInt($(this).attr('id'));
        var table = document.getElementById('selected-dates-table');
        var rows = table.rows;
        rows[0].deleteCell(cellIndex);
        for(var i=1; i<rows.length; i++) {
            var row = rows[i];
            var delIndex = g_chosen_datetimes.indexOf(parseInt(row.id) + timeId);
            if(delIndex > -1) g_chosen_datetimes.splice(delIndex, 1);
            row.deleteCell(cellIndex);
        }
    });

    $(document).on('click', '.text-row', function(e) {
        var tr = $(this).parent();
        var rowIndex = tr.index();
        var name = $(this).html();
        var delIndex = g_chosen_texts.indexOf(name);
        if(delIndex > -1) g_chosen_texts.splice(index, 1);
        var table = document.getElementById('selected-texts-table');
        table.deleteRow(rowIndex);
    });

    $(document).on('click', '.icon-close', function(e) {
        selectItem($(this));
    });

    $(document).on('click', '.icon-checkmark', function(e) {
        deselectItem($(this));
    });

    $(document).on('click', '#text-submit', function(e) {
        var text = document.getElementById('text-title');
        if(text.value.length == 0) {
            alert('Please enter a text!');
            return false;
        }
        insertText(text.value);
        text.value = '';
    });

    $(document).on('click', '.cl_user_item', function(e) {
        if($(this).hasClass('selected')) {
            var index = g_chosen_users.indexOf(this.id);
            if(index > -1) g_chosen_users.splice(index, 1);
        } else {
            g_chosen_users.push(this.id);
        }
        $(this).toggleClass('selected');
    });
    
    $(document).on('click', '.cl_group_item', function(e) {
        if($(this).hasClass('selected')) {
            var index = g_chosen_groups.indexOf(this.id);
            if(index > -1) g_chosen_groups.splice(index, 1);
        } else {
            g_chosen_groups.push(this.id);
        }
        $(this).toggleClass('selected');
    });

    $('.toggleable-row').hover(
        function() {
            var td = this.insertCell(-1);
            td.className = 'toggle-all selected-all';
        }, function() {
            $(this).find('td:last-child').remove();
        }
    );

    $(document).on('click', '.toggle-all', function(e) {
        if($(this).attr('class').indexOf('selected-all') > -1) {
            var children = $(this).parent().children('.icon-checkmark');
            for(var i=0; i<children.length; i++) {
                deselectItem($(children[i]));
            }
            $(this).removeClass('selected-all');
            $(this).addClass('selected-none');
        } else {
            var children = $(this).parent().children('.icon-close');
            for(var i=0; i<children.length; i++) {
                selectItem($(children[i]));
            }
            $(this).removeClass('selected-none');
            $(this).addClass('selected-all');
        }
    });

    $('input[type=radio][name=pollType]').change(function() {
        if(this.value == 'event') {
            chosen_type = 'event';
            document.getElementById('text-select-container').style.display = 'none';
            document.getElementById('date-select-container').style.display = 'inline';
        } else {
            chosen_type = 'text';
            document.getElementById('text-select-container').style.display = 'inline';
            document.getElementById('date-select-container').style.display = 'none';
        }
    });

    $('input[type=radio][name=accessType]').change(function() {
        access_type = this.value;
        if(access_type == 'select') {
            $("#access_rights").show();
        } else {
            $("#access_rights").hide();
        }
    });

    $('input[type=checkbox][name=check_expire]').change(function() {
        if(!$(this).is(':checked')) {
            document.getElementById('expireTs').value = '';
        }
    });

    var form = document.finish_poll;
    var submit_finish_poll = document.getElementById('submit_finish_poll');
    if (submit_finish_poll != null) {
        submit_finish_poll.onclick = function() {
            if (g_chosen_datetimes.length === 0 && g_chosen_texts.length == 0) {
                alert(t('polls', 'Nothing selected!\nClick on cells to turn them green.'));
                return false;
            }
            if(chosen_type == 'event') form.elements['chosenDates'].value = JSON.stringify(g_chosen_datetimes);
            else form.elements['chosenDates'].value = JSON.stringify(g_chosen_texts);
            var title = document.getElementById('pollTitle');
            if (title == null || title.value.length == 0) {
                alert(t('polls', 'You must enter at least a title for the new poll.'));
                return false;
            }

            if(access_type == 'select') {
                if(g_chosen_groups.length == 0 && g_chosen_users == 0) {
                    alert(t('polls', 'Please select at least one user or group!'));
                    return false;
                }
                form.elements['accessValues'].value = JSON.stringify({
                    groups: g_chosen_groups,
                    users: g_chosen_users
                });
            }
            form.submit();
        }
    }
});

function selectItem(cell) {
    cell.removeClass('icon-close');
    cell.addClass('icon-checkmark');
    cell.removeClass('date-text-not-selected');
    cell.addClass('date-text-selected');
    if(cell.attr('class').indexOf('is-text') > -1) {
        var id = cell.attr('id');
        g_chosen_texts.push(id.substring(id.indexOf('_') + 1));
    } else {
        var dateId = cell.parent().attr('id'); //timestamp of date
        var timeId = cell.attr('id');
        g_chosen_datetimes.push(parseInt(dateId) + parseInt(timeId));
    }
}

function deselectItem(cell) {
    cell.removeClass('icon-checkmark');
    cell.addClass('icon-close');
    cell.removeClass('date-text-selected');
    cell.addClass('date-text-not-selected');
    if(cell.attr('class').indexOf('is-text') > -1) {
        var id = cell.attr('id');
        var index = g_chosen_texts.indexOf(id.substring(id.indexOf('_') + 1));
        if(index > -1) g_chosen_texts.splice(index, 1);
    } else {
        var dateId = cell.parent().attr('id'); //timestamp of date
        var timeId = cell.attr('id');
        var index = g_chosen_datetimes.indexOf(parseInt(dateId) + parseInt(timeId));
        if(index > -1) g_chosen_datetimes.splice(index, 1);
    }
}

function insertText(text, set) {
    if(typeof set === 'undefined') set = false;
    var table = document.getElementById('selected-texts-table');
    var tr = table.insertRow(-1);
    var td = tr.insertCell(-1);
    td.innerHTML = text;
    td.className = 'text-row';
    td = tr.insertCell(-1);
    if(set) td.className = 'icon-checkmark is-text date-text-selected';
    else td.className = 'icon-close is-text date-text-not-selected';
    td.id = 'text_' + text;
}

function addRowToList(ts, text, timeTs) {
    if(typeof timeTs === 'undefined') timeTs = -1;
    var table = document.getElementById('selected-dates-table');
    var rows = table.rows;
    if(rows.length == 0) {
        var tr = table.insertRow(-1); //start new header
        tr.insertCell(-1);
        tr = table.insertRow(-1); //append new row
        tr.id = ts;
        tr.className = 'toggleable-row';
        var td = tr.insertCell(-1);
        td.className = 'date-row';
        td.innerHTML = text;
        return;
    }
    var curr;
    for(var i=1; i<rows.length; i++) {
        curr = rows[i];
        if(curr.id == ts) return; //already in table, cancel
        if(curr.id > ts) {
            var tr = table.insertRow(i); //insert row at current index
            tr.id = ts;
            tr.className = 'toggleable-row';
            var td = tr.insertCell(-1);
            td.className = 'date-row';
            td.innerHTML = text;
            for(var j=1; j<rows[0].cells.length; j++) {
                var tdId = rows[0].cells[j].id;
                var td = tr.insertCell(-1);
                if(timeTs == tdId) td.className = 'icon-checkmark date-text-selected';
                else td.className = 'icon-close date-text-not-selected';
                td.id = tdId;
                td.innerHTML = '';
            }
            return;
        }
    }
    var tr = table.insertRow(-1); //highest value, append new row
    tr.id = ts;
    tr.className = 'toggleable-row';
    var td = tr.insertCell(-1);
    td.className = 'date-row';
    td.innerHTML = text;
    for(var j=1; j<rows[0].cells.length; j++) {
        var tdId = rows[0].cells[j].id;
        var td = tr.insertCell(-1);
        if(timeTs == tdId) td.className = 'icon-checkmark date-text-selected';
        else td.className = 'icon-close date-text-not-selected';
        td.id = tdId;
        td.innerHTML = '';
    }
    return;
}

function addColToList(ts, text, dateTs) {
    if(typeof dateTs === 'undefined') dateTs = -1;
    var table = document.getElementById('selected-dates-table');
    var rows = table.rows;
    if(rows.length == 0) {
        var tr = table.insertRow(-1);
        tr.insertCell(-1);
    }
    rows = table.rows;

    var tmpRow = rows[0];
    var index = -1;
    var found = false;
    for(var i=0; i<tmpRow.cells.length; i++) {
        var curr = tmpRow.cells[i];
        if(curr.id == ts) return; //already in table, cancel
        if(curr.id > ts) {
            index = i;
            break;
        }
    }

    for(var i=0; i<rows.length; i++) {
        var row = rows[i];
        var cells = row.cells;
        var td = row.insertCell(index);
        //only display time in header row
        if(i==0) {
            td.innerHTML = text;
            td.className = 'date-col';
        } else {
            td.innerHTML = '';
            if(row.id == dateTs) td.className = 'icon-checkmark date-text-selected';
            else td.className = 'icon-close date-text-not-selected';
        }
        td.id = ts;
    }
}
