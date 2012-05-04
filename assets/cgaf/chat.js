(function($) {
  var Chat = function() {

  };
  Chat.prototype =
      {
        defaults : {
          floatMode : true,
          appId : null
        },
        init : function(configs) {
          if (this.initialize) {
            return;
          }
          this.configs = $.extend(this.defaults, configs
              || {});
          if (!this.configs.appId) {
            this.configs.appId = cgaf.getConfig('appid');
          }
          this.initialize = true;
          this.online = null;
          var $cont = this.container = $('#chat-container');
          if ($cont.length === 0) {
            $cont =
                this.container =
                    $(
                        '<div id="chat-container" class="chat-container'
                            + (this.configs.floatMode ? ' float' : '')
                            + '">'
                            + '    <div class="chat-message-container">'
                            + '      <div class="message">'
                            + '      </div>'
                            + '      <div class="chat-message-action">'
                            + '        <input type="text" id="msg">'
                            + '        <button class="btn" type="button" id="send">Go!</button>'
                            + '      </div>'
                            + '    </div>'
                            + '    <div class="chat-contacts">'
                            + '      <div class="header">'
                            + '        <label class="label label-status label-important ">Offline</label>'
                            + '        <div class="btn-group">'
                            + '          <button class="btn btn-log"><i class="icon icon-list-alt"></i></button>'
                            + '          <button class="btn btn-refresh"><i class="icon icon-refresh"></i></button>'
                            + '        </div>' + '      </div>'
                            + '      <ul class="contact-list"></ul>'
                            + '    </div>' + '    <div class="chat-log"></div>'
                            + '</div>').appendTo(
                        this.configs.floatMode ? 'body' : '#wrapper');
          }
          if (this.configs.floatMode) {
            $cont.hide();
          }
          var $status = $cont.find('.label-status'), $connect =
              $cont.find('#connect'), $disconnect = $cont.find('#disconnect'), $send =
              $cont.find('#send'), $msg = $cont.find('#msg'), $message =
              $cont.find('.message'), me = this;
          this.setOnline(false);

          WebPush.log = function(msg) {
            me.log(msg, 'LOG : ');
          }
          var server = this.server = new WebPush('ws://localhost:8088/');
          this.$log = $cont.find('.chat-log');
          this.$contactList = $cont.find('.contact-list');
          //WebPush events
          $send.addClass('disabled');
          $cont.find('.btn-refresh').click($.proxy(this, 'refreshContactList'));
          server.bind('open', function() {
            me.setOnline(true);
            me.refreshContactList();
          });
          $cont.find('.btn-log').click(function(e) {
            e.preventDefault();
            var $c = me.container.find('.chat-log');
            $c.toggle();
            if ($c.is(':visible')) {
              $(this).addClass('active');
            } else {
              $(this).removeClass('active');
            }
          });
          server.bind('connection_disconnected', function() {
            me.setOnline(false);
          });

          server.bind('close', function() {
            me.setOnline(false);
          });
          server.bind('connection_failed', function() {
            me.setOnline(false);
          });

          server.bind('message', function(msg) {
            var response = JSON.parse(msg);
            me.log(msg, '&lt;');
            var d = new Date();
            switch (response.service) {
              case 'chat':
                switch (response.action) {
                  case 'userinfo':
                    
                    break;
                  case 'contact_list':
                    me.refreshContactList(response.data, true);
                    break;
                  case 'error':
                    me.log(response.message, 'ERROR');
                    break;
                  default:
                    $message.append('<div><span class="label label-info t">'
                        + d.toLocaleTimeString() + '</span>'
                        + '<a class="from" target="__dialog" href="'+response.from+'"><span>' + response.from + '</span></a>'
                        + '<span class="m">'
                        + response.data + '</div></div>').find('.from').click(function(e) {
                          e.preventDefault();
                          me.send({
                            action:'userinfo',
                            userid: $(this).attr('href')
                          });
                        });
                }
            }
          });
          $disconnect.click(function(e) {
            e.preventDefault();
            server.disconnect();
          });
          $connect.click(function(e) {
            e.preventDefault();
            server.connect();
          });
          $status.click(function() {
            if (!me.online) {
              me.server.connect();
            } else {
              me.server.disconnect();
            }
          });
          $send.click(function() {
            if (!me.online)
              return;
            var d = new Date();
            $message.append('<div><span class="label label-important s">'
                + d.toLocaleTimeString() + '</span><span class="m">'
                + $msg.val() + '</div></div>');
            me.send({
              action : 'message',
              data : $msg.val()
            });
            $msg.val('');
          });
          $(window).resize($.proxy(this, 'resetui'));
          this.resetui();
        },
        refreshContactList : function(data, datamode) {
          if (data
              && datamode) {
            this.$contactList.empty();
            for ( var i in data) {
              $('<li>'
                  + data[i].id + '</li>').appendTo(this.$contactList);
            }
          } else {
            this.send({
              service : 'chat',
              action : 'contact_list'
            });

          }
        },
        log : function(data, p) {
          p = p
              || '&gt;';
          this.container.find('.chat-log').append('<div>'
              + p + ':' + JSON.stringify(data) + '</div>');
        },
        send : function(data) {
          if (!data.appId) {
            data.appId = this.configs.appId;
          }
          if (!data.service) {
            data.service = 'chat';
          }
          this.log(data, '&gt;&nbsp;');
          this.server.send(JSON.stringify(data));
        },
        setOnline : function(v) {
          if (this.online !== v) {
            this.online = v;
            var $status = this.container.find('.label-status');
            var $send = this.container.find('#send');
            var $msg = this.container.find('#msg');
            if (v) {
              this.container.find('.btn-refresh').removeClass('disabled');
              $msg.attr('disabled', false);
              $status.removeClass('label-important').addClass('label-success')
                  .html('Online');
              $msg.removeClass('disabled');
              $send.removeClass('disabled');
            } else {
              $msg.attr('disabled', 'disabled');
              this.container.find('.btn-refresh').addClass('disabled');
              $send.addClass('disabled');
              $status.removeClass('label-success').addClass('label-important')
                  .html('Offline');
            }
          }
          //me.resetui();
        },
        resetui : function() {
          if (this.container
              && this.container.is(':visible')) {
            if (!this.configs.floatMode) {
              this.container.closest('body').closest('html').css({
                overflow : 'hidden'
              });
              this.container.parent().css({
                padding : 0
              })
            }
            var oo = this.container.offset();
            this.container.css({
              height : (this.configs.floatMode ? $(window).height()
                  - (oo.top * 2) : $(window).height()
                  - (40 + oo.top))
                  + 'px',
              left : (($(window).width() - this.container.width()) / 2)
                  + 'px'
            });
          }
        },
        showMenu : function(e) {
          if (this.configs.floatMode) {
            this.container.toggle();
          }
          this.resetui();
        }
      };
  $.chat = new Chat();

})(jQuery);