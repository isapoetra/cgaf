(function($) {
  $.userMessages =
      {
        init : function(mode) {
          var me = this;
          this._mode = mode;
          this.$grid = $('#message-grid');
          this.$grid.find('[name="chkall"]').click(
              function() {
                me.$grid.find('tbody input[type="checkbox"]').attr('checked',
                    $(this).attr('checked') ? true : false);

              });

        },
        del : function() {
          switch (this._mode) {
            case 'inbox':
              this.$grid.find('tbody input[type="checkbox"]').each(
                  function(e, a) {
                    if ($(this).attr('checked')) {
                      $(this).parent().parent().css({
                        textDecoration : 'line-through'
                      });
                    }
                  });
              var me = this;
              cgaf.confirm('Delete?', function() {
                $('#frm-message').find('#action').val('del');
                $('#frm-message').submit();
              });
              break;
          }
        }
      }
})(jQuery);