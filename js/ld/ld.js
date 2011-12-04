if (typeof Ld == 'undefined') Ld = {};

jQuery(function($) {

    $(".h6e-data tbody").each(function() {
        $(this).children('tr').each(function(i) {
            if (i % 2 == 1) $(this).addClass('even');
        });
    })

});

// Sortable Instances

Ld.sortInstancesUrl = null;

jQuery(function($) {

    if ($(".blocks.sortables li.sortable").size() <= 1) {
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

});

// Lang Switcher

jQuery(function($) {

    $("#ld-lang-switcher input.submit").hide();
    $("#ld-lang-switcher select").change(function() { $(this).parent().submit() });

});

// Login Box

jQuery(function($) {

    $('#ld-login-box').each(function() {
        $('.h6e-simple-footer').hide();
        var ah = $(this).outerHeight();
        var ph = $(window).height();
        var mh = (ph - ah) / 3;
        $(this).css('margin-top', mh);
    });

});

// Login Identities

jQuery(function($) {

    var identities = $('#ld-identities a.identity');
    if (identities.size() > 0) {
        $('#ld-auth-login-input, #ld-auth-login-button, #ld-auth-register-link').hide();
        $('#ld-identities, #ld-auth-another-identity-link').show();
        identities.click(function() {
            $('#ld-auth-username').val( $(this).children('span.identity').text() );
            $('#ld-auth-form').submit();
            return false;
        });
    }

    $('#ld-auth-another-identity-link').click(function() {
        $('#ld-auth-login-input, #ld-auth-login-button, #ld-auth-register-link').show();
        $('#ld-identities, #ld-auth-another-identity-link').hide();
        $('#ld-auth-username').focus();
        return false;
    });

});

// Top Menus

jQuery(function($) {

    $(".ld-site-name").mouseenter(function() {
        var left = Math.round( $(this).position().left );
        $(".ld-site-menu").css('left', left + 'px').show();
    }).mouseleave(function() {
        $(".ld-site-menu").hide();
    });

    $(".ld-subsite-name").mouseenter(function() {
        var left = Math.round( $(this).position().left );
        $(".ld-subsite-menu").css('left', left + 'px').show();
    }).mouseleave(function() {
        $(".ld-subsite-menu").hide();
    });

    $(".ld-app-name").mouseenter(function() {
        var left = Math.round( $(this).position().left );
        $(".ld-app-menu").css('left', left + 'px').show();
    }).mouseleave(function() {
        $(".ld-app-menu").hide();
    });

    $(".ld-main-menu, .ld-app-menu").mouseenter(function() {
        $(this).show();
    }).mouseleave(function() {
        $(this).hide();
    });

});
