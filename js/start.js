var edit_access_id = null; // set if called from the summary page

$(document).ready(function () {
    edit_access_id = null;
    // users, groups
    var elem = document.getElementById('select');
    if (elem) elem.onclick = showAccessDialog;
    elem = document.getElementById('button_close_access');
    if (elem) elem.onclick = closeAccessDialog;

    cells = document.getElementsByClassName('cl_group_item');
    for (var i = 0; i < cells.length; i++) {
        cells[i].onclick = groupItemClicked;
    }
    cells = document.getElementsByClassName('cl_user_item');
    for (var i = 0; i < cells.length; i++) {
        cells[i].onclick = userItemClicked;
    }

    var cells = document.getElementsByClassName('cl_delete');
    for (var i = 0; i < cells.length; i++) {
        var cell = cells[i];
        cells[i].onclick = deletePoll;
    }

    // set "show poll url" handler
    cells = document.getElementsByClassName('cl_poll_url');
    for (var i = 0; i < cells.length; i++) {
        var cell = cells[i];
        cells[i].onclick = function(e) {
            // td has inner 'input'; value is poll url
            var cell = e.target;
            var url = cell.getElementsByTagName('input')[0].value;
            window.prompt(t('polls','Copy to clipboard: Ctrl+C, Enter'), url);
        }
    }
});

function deletePoll(e) {
    var tr = this.parentNode.parentNode;
    var titleTd = tr.firstChild;
    //check if first child is whitespace text element
    if(titleTd.nodeName == '#text') titleTd = titleTd.nextSibling;
    var tdElem = titleTd.firstChild;
    //again, whitespace check
    if(tdElem.nodeName == '#text') tdElem = tdElem.nextSibling;
    var str = t('polls', 'Do you really want to delete that poll?') + '\n\n' + tdElem.innerHTML;
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
