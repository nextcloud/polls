$(document).ready(function () {
    
    $('.table-body div.column.created').each(function(i, obj) {
            if (isDate(obj.dataset.value)) {
                obj.dataset.value = obj.dataset.value.replace(/ /g,"T")+"Z";
                obj.innerText = OC.Util.relativeModifiedDate(obj.dataset.value);
            };
    });

    $('.table-body div.column.expiry').each(function(i, obj) {
            if (isDate(obj.dataset.value)) {
                obj.dataset.value = obj.dataset.value.replace(/ /g,"T")+"Z";
                obj.innerText= OC.Util.relativeModifiedDate(obj.dataset.value);
            };
    });

    $('.table-body .avatardiv').each(function(i, obj) {
        $(obj).avatar(obj.title, 32);
    });

    
    $('.cl_delete').click(deletePoll);

    $('.cl_link').click(function() {
        window.prompt(t('polls','Copy to clipboard: Ctrl+C, Enter'), $(this).data('url'));
    });
    
    
    
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

/* 
            obj.textContent = obj.data-value ;
 */

function isDate(val) {
    var d = new Date(val);
    return !isNaN(d.valueOf());
}