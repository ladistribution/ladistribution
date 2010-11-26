(function($) {
  $(document).ready(function() {

    var options = {
        onSubmit: function(hsb, hex, rgb, el) {
            $(el).ColorPickerHide();
        },
        onBeforeShow: function () {
            $(this).ColorPickerSetColor(this.value);
        },
        onChange: function (hsb, hex, rgb) {
            var el = $(this).data('colorpicker').el;
            el = $(el);
            el.val('' + hex);
            el.css('background', '#' + hex);
            var id = el.attr('id');
            if (rules[id]) {
                var rule = rules[id];
                $(rule.selector).css(rule.property, '#' + hex + '');
            }
        }
    }

    var rules = {
        'ld-colors-background' : {'property' : 'background-color',
            'selector' : 'body, .ld-layout, .h6e-layout, body.ld-layout', },
        'ld-colors-border' : {'property' : 'border-color',
            'selector' : '.h6e-simple-footer'},
        'ld-colors-title' : {'property' : 'color',
            'selector' : '.h6e-main-content h2, .h6e-main-content h2 a'},
        'ld-colors-text' : {'property' : 'color',
            'selector' : '.h6e-bread-crumbs, .h6e-bread-crumbs a, .h6e-simple-footer, .h6e-simple-footer a'},

        'ld-colors-background-2' : {'property' : 'background-color',
            'selector' : 'ul.blocks.mini li, .h6e-top-bar-inner, .h6e-super-bar-inner'},
        'ld-colors-border-2' : {'property' : 'border-color',
            'selector' : 'ul.blocks.mini li, .h6e-top-bar-inner, .h6e-super-bar-inner'},
        'ld-colors-text-2' : {'property' : 'color',
            'selector' : '.h6e-top-bar, .h6e-top-bar a, .h6e-super-bar ul.instances li, .h6e-super-bar ul.instances li a'},

        'ld-colors-background-3' : {'property' : 'background-color',
            'selector' : '.ld-instance-menu li a, .h6e-tabs li a, .ld-panel-content, ul.blocks.mini li'},
        'ld-colors-border-3' : {'property' : 'border-color',
            'selector' : '.ld-instance-menu li a, .h6e-tabs li a, .ld-panel-content, ul.blocks.mini li'},
        'ld-colors-title-3' : {'property' : 'color',
            'selector' : '.ld-panel-content h3'},
        'ld-colors-text-3' : { 'property' : 'color',
            'selector' : '.ld-instance-menu li a, .h6e-tabs li a, .ld-panel-content, .ld-panel-content a, ul.blocks.mini li, ul.blocks.mini li a'}
    }

    $('.appearance-colors-form input.color').ColorPicker(options);

    function enableDisableColors() {
        var el = $(this);
        var inputs = el.parents('tr').find('input.color');
        if (el.val() == 1) {
            inputs.attr('disabled', 'disabled').css('opacity', 0.25);
        } else {
            inputs.removeAttr('disabled').css('opacity', 1);
        }
    };

    $('.appearance-colors-form input:radio:checked').each(enableDisableColors);

    $('.appearance-colors-form input:radio').change(enableDisableColors);

  });
})(jQuery);