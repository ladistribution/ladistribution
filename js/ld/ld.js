Ld = {};

Ld.sortInstancesUrl = null;

Ld.init = function($)
{
    this.dataTables($);
    this.themesPanel($);
    this.sortableBlocks($);
    this.sortableUserRoles($);
    // this.instanceMenu($);
    this.langSwitcher($);
}

Ld.dataTables = function($)
{
    $(".h6e-data tbody").each(function() {
        $(this).children('tr').each(function(i) {
            if (i % 2 == 1) $(this).addClass('even');
        });
    })
}

Ld.sortableBlocks = function($)
{
    if ($(".blocks.sortables .sortable").size() <= 1) {
        return;
    }
    $(".blocks.sortables").sortable({
        items: 'li.sortable',
        update: function() {
            $.post(Ld.sortInstancesUrl, $(".blocks").sortable('serialize'));
        }
    });
    $(".blocks.sortables li.sortable").css('cursor', 'move');
    $(".blocks a").mousedown(function(e){
        e.stopPropagation();
    });
}

Ld.instanceMenu = function($)
{
    $(".ld-instance-menu li a").click(function(e) {
        $(".ld-instance-menu li").removeClass('current');
        $(this).parent().addClass('current');
        $(".ld-panel-content").load(
            $(this).attr('href') + " .ld-panel-content > *", null,
            function() { Ld.dataTables($);Ld.themesPanel($); }
        );
        return false;
    });
}

Ld.themesPanel = function($)
{
    var width = 0;
    $("#available-themes .themes .theme").each(function() {
        width += 175;
    });
    $("#available-themes .themes").width(width);
    
    $("#available-themes input[name='referer']").attr('value', window.location);

    // $("#available-themes input.submit").hide();
    // $("#available-themes input[name=theme]:radio").change(function(e) {
    //     var value = $('#available-themes input:radio[name=theme]:checked').val();
    //     $.post($("#available-themes").attr('action'), {theme: value});
    // });
}

Ld.langSwitcher = function($)
{
    $("#ld-lang-switcher input.submit").hide();
    $("#ld-lang-switcher select").change(function() { $(this).parent().submit() });
}

Ld.userRoles = {}

Ld.saveUserRoles = null;

Ld.sortableUserRoles = function($)
{
    $(".ld-group.sortable").sortable({
        items: '.ld-user', connectWith: '.ld-group', placeholder: 'ld-user empty',
        update: function() {
            var role = $(this).attr('id').replace('group_', '');
            var users = $(this).sortable('toArray');
            for ( var i = 0, length = users.length ; i < length ; i++ ) {
                var username = users[i].replace('user_', '');
                var key = 'userRoles[' + username + ']';
                Ld.userRoles[key] = role;
                var key2 = 'userOrder[' + username+ ']';
                Ld.userRoles[key2] = i;
            }
            if (Ld.saveUserRoles) {
                clearTimeout(Ld.saveUserRoles);
            }
            Ld.saveUserRoles = setTimeout(function() { $.post(Ld.setRolesUrl, Ld.userRoles) }, 333);
        }
    });

    $(".ld-group input[type=checkbox]").change(function() {
        if ( $(this).is(':checked') ) {
            $(this).parent().addClass('selected');
        } else {
            $(this).parent().removeClass('selected');
        }
        if ( $(this).is(':disabled') ) {
            $(this).parent().addClass('disabled');
        }
    }).change();

    $(".ld-group.sortable .ld-user").css('cursor', 'move');
}
