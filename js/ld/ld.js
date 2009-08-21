Ld = {};

Ld.sortInstancesUrl = null;

Ld.init = function($)
{
    this.dataTables($);
    this.themesPanel($);
    this.sortableBlocks($);
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
        items: 'li.sortable', cursor: 'crosshair',
        update: function() {
            $.post(Ld.sortInstancesUrl, $(".blocks").sortable('serialize'));
        }
    });
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

	$("#ld-lang-switcher select").change(function() {
		// $.cookie('ld-lang', $(this).val());
		$(this).parent().submit();
	});
}
