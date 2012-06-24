(function($) {

  $.fn.passwordStrength = function(username) {
    score = 0;
    password = $(this).val();
    if (password.length < 4)
      return 0;
    if (password.toLowerCase() == username.toLowerCase())
      return 0;
    score += password.length * 4;
    score += (checkRepetition(1, password).length - password.length) * 1;
    score += (checkRepetition(2, password).length - password.length) * 1;
    score += (checkRepetition(3, password).length - password.length) * 1;
    score += (checkRepetition(4, password).length - password.length) * 1;
    if (password.match(/(.*[0-9].*[0-9].*[0-9])/))
      score += 5;
    if (password.match(/(.*[!,@,#,$,%,^,&,*,?,_,~].*[!,@,#,$,%,^,&,*,?,_,~])/))
      score += 5;
    if (password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/))
      score += 10;
    if (password.match(/([a-zA-Z])/)
        && password.match(/([0-9])/))
      score += 15;
    if (password.match(/([!,@,#,$,%,^,&,*,?,_,~])/)
        && password.match(/([0-9])/))
      score += 15;
    if (password.match(/([!,@,#,$,%,^,&,*,?,_,~])/)
        && password.match(/([a-zA-Z])/))
      score += 15;
    if (password.match(/^\w+$/)
        || password.match(/^\d+$/))
      score -= 10;
    return score
  };
  function checkRepetition(pLen, str) {
    res = "";
    for ( var i = 0; i < str.length; i++) {
      repeated = true;
      for ( var j = 0; j < pLen
          && j
              + i + pLen < str.length; j++)
        repeated = repeated
            && str.charAt(j
                + i) == str.charAt(j
                + i + pLen);
      if (j < pLen)
        repeated = false;
      if (repeated) {
        i += pLen - 1;
        repeated = false;
      } else
        res += str.charAt(i);
    }
    return res;
  }
  $.fn.gform =
      function(config) {
        var config = this.config = $.extend({
          ajaxmode : true,
          onprepare : null,
          data : {
            __t : Math.random(),
            __data : "json"
          }
        }, config
            || {});
        var me = this;
        var form = $(this);
        var eMsg = $(this).find("#error-message");
        if (eMsg.length > 0)
          eMsg.bind("click", function() {
            $(this).slideUp();
          });
        if ($.isFunction(config.onprepare)) {
          config.onprepare.apply(form);
        }
        form.find('[data-input="date"]').each(
            function() {
              var dateconfig =
                  $.extend({
                    changeYear : true,
                    dateFormat : me.config.dateFormat
                        || (cgaf.getConfig("locale") === "id" ? "dd/mm/yy"
                            : "mm/dd/yy"),
                    regional : cgaf.getConfig("locale"),
                    showOn : "button",
                    defaultDate : null,
                    buttonImage : "/assets/images/calendar_down.png"
                  }, me.config.dateconfig
                      || {});
              if ($(this).attr("minyear") !== undefined)
                config.minDate =
                    new Date(parseInt($(this).attr("minyear")), 1, 1);
              $(this).datepicker(dateconfig)
            });
        form.find('[data-input="autocomplete"]').each(
            function() {
              $this = $(this);
              $this.autocomplete({
                minLength : 2,
                source : function(request, response) {
                  var term = request.term;
                  if ($this.cache) {
                    if (term in $this.cache) {
                      response($this.cache[term]);
                      return;
                    }
                  }
                  lastXhr =
                      $.getJSON($this.attr('srclookup'), request,
                          function(data, status, xhr) {
                            $this.cache = $this.cache
                                || {};
                            $this.cache[term] = data;
                            if (xhr === lastXhr) {
                              response(data);
                            }
                          });
                }
              });
            });
        form.find('[data-input="datetime"]').each(function() {
          $(this).timepicker();
        });
        form.find('[data-input="daterange"]').each(function() {
          //console.log(this);
          if ($(this).attr('data-time')) {
            $(this).datetimepicker({
              timeOnly : false,
              timeFormat:'hh:mm:ss',
              dateFormat : cgaf.getConfig('dateInputFormat')
            });
          } else {
            $(this).datepicker({
              dateFormat : cgaf.getConfig('dateInputFormat')
            });
          }
        });
        var jax =
            form.attr("useajax") === undefined ? config.ajaxmode : form
                .attr("useajax");
        var ajaxm = config.ajaxmode
            || typeof jax == "undefined" || jax == true;
        var validconfig =
            $
                .extend(
                    {
                      debug : true,
                      submitHandler : function(frm, e) {
                        if (ajaxm) {
                          e.preventDefault();
                          var ajaxconfig =
                              $
                                  .extend(
                                      {
                                        dataType : "json",
                                        success : function(data, status, xhr) {
                                          if (typeof data == "undefined"
                                              || data == null) {
                                            $
                                                .showErrorMessage(
                                                    "Error  while processing request",
                                                    $(form));
                                            return false
                                          }
                                          if (!data._result)
                                            for (k in data) {
                                              var f = $(form).find("#"
                                                  + k);
                                              if (f.length > 0)
                                                f.val(data[k])
                                            }
                                          else if (data._redirect)
                                            location.href = data._redirect;
                                          else if (data.content)
                                            $.fancybox({
                                              content : data.content
                                            });
                                          if (data.message)
                                            $.showErrorMessage(data.message);
                                          return false
                                        },
                                        error : function(XMLHttpRequest, textStatus, errorThrown) {
                                          if (textStatus === "parsererror") {
                                            try {
                                              var json =
                                                  eval("("
                                                      + XMLHttpRequest.responseText
                                                      + ")");
                                            } catch (e) {
                                              $
                                                  .showErrorMessage("Invalid JSON Data <br/>"
                                                      + XMLHttpRequest.responseText);
                                              return false;
                                            }
                                            return this.success(json);
                                          }
                                          return false;
                                        }
                                      }, config);
                          form.ajaxSubmit(ajaxconfig);
                        } else {
                          frm.submit();
                          return false;
                        }
                        return false;
                      }
                    }, config
                        || {});
        $(this).validate(validconfig);
        return false;
      };
})(jQuery);