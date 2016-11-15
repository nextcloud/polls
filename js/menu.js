$(document).ready(function () {
  var popoverTemplate = ['<div class="popover ">',
        //'<div class="arrow"></div>',
        '<div class="popover-content popovermenu bubble open menu">',
        '</div>',
        '</div>'].join('');

var content = ['<ul>',
               '<li><a tabindex="0" role="button" href="#" class="menuitem action action-details permanent"><span class="icon icon-details"></span><span>Details</span></a></li>',
               '<li><a tabindex="0" role="button" href="#" class="menuitem action action-rename permanent"><span class="icon icon-rename"></span><span>Umbenennen</span></a></li>',
               '<li><a tabindex="0" role="button" href="#" class="menuitem action action-download permanent"><span class="icon icon-download"></span><span>Herunterladen</span></a></li>',
               '<li><a tabindex="0" role="button" href="#" class="menuitem action action-delete permanent""><span class="icon icon-delete"></span><span>Löschen</span></a></li>',
               '</ul>'
              ].join('');

$(function () {
  $('[data-toggle="popover"]').popover()
})
$(function () {
  $('[data-toggle="poll3dot"]').popover({
  //selector: '[rel=poll3dot]',
  trigger: 'click',
  content: content,
  template: popoverTemplate,
  placement: "bottom",
  html: true,
  container: '#app-navigation'
});
});
});
