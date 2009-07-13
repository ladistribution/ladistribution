(function($) {
    $(document).ready(function() {
        $(".h6e-data tbody").each(function() {
            $(this).children('tr').each(function(i) {
                if (i % 2 == 1) $(this).addClass('even');
            });
        })
    });
})(jQuery);