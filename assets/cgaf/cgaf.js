/**
**/


/*!
 * jQuery Form Plugin
 * version: 3.03 (08-MAR-2012)
 * @requires jQuery v1.3.2 or later
 *
 * Examples and documentation at: http://malsup.com/jquery/form/
 * Project repository: https://github.com/malsup/form
 * Dual licensed under the MIT and GPL licenses:
 *    http://malsup.github.com/mit-license.txt
 *    http://malsup.github.com/gpl-license-v2.txt
 */
/*global ActiveXObject alert */
;(function($) {
"use strict";

/*
    Usage Note:
    -----------
    Do not use both ajaxSubmit and ajaxForm on the same form.  These
    functions are mutually exclusive.  Use ajaxSubmit if you want
    to bind your own submit handler to the form.  For example,

    $(document).ready(function() {
        $('#myForm').bind('submit', function(e) {
            e.preventDefault(); // <-- important
            $(this).ajaxSubmit({
                target: '#output'
            });
        });
    });

    Use ajaxForm when you want the plugin to manage all the event binding
    for you.  For example,

    $(document).ready(function() {
        $('#myForm').ajaxForm({
            target: '#output'
        });
    });
    
    You can also use ajaxForm with delegation (requires jQuery v1.7+), so the
    form does not have to exist when you invoke ajaxForm:

    $('#myForm').ajaxForm({
        delegation: true,
        target: '#output'
    });
    
    When using ajaxForm, the ajaxSubmit function will be invoked for you
    at the appropriate time.
*/

/**
 * Feature detection
 */
var feature = {};
feature.fileapi = $("<input type='file'/>").get(0).files !== undefined;
feature.formdata = window.FormData !== undefined;

/**
 * ajaxSubmit() provides a mechanism for immediately submitting
 * an HTML form using AJAX.
 */
$.fn.ajaxSubmit = function(options) {
    /*jshint scripturl:true */

    // fast fail if nothing selected (http://dev.jquery.com/ticket/2752)
    if (!this.length) {
        log('ajaxSubmit: skipping submit process - no element selected');
        return this;
    }
    
    var method, action, url, $form = this;

    if (typeof options == 'function') {
        options = { success: options };
    }

    method = this.attr('method');
    action = this.attr('action');
    url = (typeof action === 'string') ? $.trim(action) : '';
    url = url || window.location.href || '';
    if (url) {
        // clean url (don't include hash vaue)
        url = (url.match(/^([^#]+)/)||[])[1];
    }

    options = $.extend(true, {
        url:  url,
        success: $.ajaxSettings.success,
        type: method || 'GET',
        iframeSrc: /^https/i.test(window.location.href || '') ? 'javascript:false' : 'about:blank'
    }, options);

    // hook for manipulating the form data before it is extracted;
    // convenient for use with rich editors like tinyMCE or FCKEditor
    var veto = {};
    this.trigger('form-pre-serialize', [this, options, veto]);
    if (veto.veto) {
        log('ajaxSubmit: submit vetoed via form-pre-serialize trigger');
        return this;
    }

    // provide opportunity to alter form data before it is serialized
    if (options.beforeSerialize && options.beforeSerialize(this, options) === false) {
        log('ajaxSubmit: submit aborted via beforeSerialize callback');
        return this;
    }

    var traditional = options.traditional;
    if ( traditional === undefined ) {
        traditional = $.ajaxSettings.traditional;
    }
    
    var qx, a = this.formToArray(options.semantic);
    if (options.data) {
        options.extraData = options.data;
        qx = $.param(options.data, traditional);
    }

    // give pre-submit callback an opportunity to abort the submit
    if (options.beforeSubmit && options.beforeSubmit(a, this, options) === false) {
        log('ajaxSubmit: submit aborted via beforeSubmit callback');
        return this;
    }

    // fire vetoable 'validate' event
    this.trigger('form-submit-validate', [a, this, options, veto]);
    if (veto.veto) {
        log('ajaxSubmit: submit vetoed via form-submit-validate trigger');
        return this;
    }

    var q = $.param(a, traditional);
    if (qx) {
        q = ( q ? (q + '&' + qx) : qx );
    }    
    if (options.type.toUpperCase() == 'GET') {
        options.url += (options.url.indexOf('?') >= 0 ? '&' : '?') + q;
        options.data = null;  // data is null for 'get'
    }
    else {
        options.data = q; // data is the query string for 'post'
    }

    var callbacks = [];
    if (options.resetForm) {
        callbacks.push(function() { $form.resetForm(); });
    }
    if (options.clearForm) {
        callbacks.push(function() { $form.clearForm(options.includeHidden); });
    }

    // perform a load on the target only if dataType is not provided
    if (!options.dataType && options.target) {
        var oldSuccess = options.success || function(){};
        callbacks.push(function(data) {
            var fn = options.replaceTarget ? 'replaceWith' : 'html';
            $(options.target)[fn](data).each(oldSuccess, arguments);
        });
    }
    else if (options.success) {
        callbacks.push(options.success);
    }

    options.success = function(data, status, xhr) { // jQuery 1.4+ passes xhr as 3rd arg
        var context = options.context || options;    // jQuery 1.4+ supports scope context 
        for (var i=0, max=callbacks.length; i < max; i++) {
            callbacks[i].apply(context, [data, status, xhr || $form, $form]);
        }
    };

    // are there files to upload?
    var fileInputs = $('input:file:enabled[value]', this); // [value] (issue #113)
    var hasFileInputs = fileInputs.length > 0;
    var mp = 'multipart/form-data';
    var multipart = ($form.attr('enctype') == mp || $form.attr('encoding') == mp);

    var fileAPI = feature.fileapi && feature.formdata;
    log("fileAPI :" + fileAPI);
    var shouldUseFrame = (hasFileInputs || multipart) && !fileAPI;

    // options.iframe allows user to force iframe mode
    // 06-NOV-09: now defaulting to iframe mode if file input is detected
    if (options.iframe !== false && (options.iframe || shouldUseFrame)) {
        // hack to fix Safari hang (thanks to Tim Molendijk for this)
        // see:  http://groups.google.com/group/jquery-dev/browse_thread/thread/36395b7ab510dd5d
        if (options.closeKeepAlive) {
            $.get(options.closeKeepAlive, function() {
                fileUploadIframe(a);
            });
        }
          else {
            fileUploadIframe(a);
          }
    }
    else if ((hasFileInputs || multipart) && fileAPI) {
        fileUploadXhr(a);
    }
    else {
        $.ajax(options);
    }

     // fire 'notify' event
     this.trigger('form-submit-notify', [this, options]);
     return this;

     // XMLHttpRequest Level 2 file uploads (big hat tip to francois2metz)
    function fileUploadXhr(a) {
        var formdata = new FormData();

        for (var i=0; i < a.length; i++) {
            formdata.append(a[i].name, a[i].value);
        }

        if (options.extraData) {
            for (var k in options.extraData)
                if (options.extraData.hasOwnProperty(k))
                    formdata.append(k, options.extraData[k]);
        }

        options.data = null;

        var s = $.extend(true, {}, $.ajaxSettings, options, {
            contentType: false,
            processData: false,
            cache: false,
            type: 'POST'
        });

		if (options.uploadProgress) {
			// workaround because jqXHR does not expose upload property
			s.xhr = function() {
				var xhr = jQuery.ajaxSettings.xhr();
				if (xhr.upload) {
					xhr.upload.onprogress = function(event) {
						var percent = 0;
						if (event.lengthComputable)
							percent = parseInt((event.position / event.total) * 100, 10);
						options.uploadProgress(event, event.position, event.total, percent);
					}
				}
				return xhr;
			}
		}

      	s.data = null;
      	var beforeSend = s.beforeSend;
      	s.beforeSend = function(xhr, o) {
          	o.data = formdata;
            if(beforeSend)
                beforeSend.call(o, xhr, options);
      	};
      	$.ajax(s);
   	 }

    // private function for handling file uploads (hat tip to YAHOO!)
    function fileUploadIframe(a) {
        var form = $form[0], el, i, s, g, id, $io, io, xhr, sub, n, timedOut, timeoutHandle;
        var useProp = !!$.fn.prop;

        if (a) {
            if ( useProp ) {
                // ensure that every serialized input is still enabled
                for (i=0; i < a.length; i++) {
                    el = $(form[a[i].name]);
                    el.prop('disabled', false);
                }
            } else {
                for (i=0; i < a.length; i++) {
                    el = $(form[a[i].name]);
                    el.removeAttr('disabled');
                }
            }
        }

        if ($(':input[name=submit],:input[id=submit]', form).length) {
            // if there is an input with a name or id of 'submit' then we won't be
            // able to invoke the submit fn on the form (at least not x-browser)
            alert('Error: Form elements must not have name or id of "submit".');
            return;
        }
        
        s = $.extend(true, {}, $.ajaxSettings, options);
        s.context = s.context || s;
        id = 'jqFormIO' + (new Date().getTime());
        if (s.iframeTarget) {
            $io = $(s.iframeTarget);
            n = $io.attr('name');
            if (!n)
                 $io.attr('name', id);
            else
                id = n;
        }
        else {
            $io = $('<iframe name="' + id + '" src="'+ s.iframeSrc +'" />');
            $io.css({ position: 'absolute', top: '-1000px', left: '-1000px' });
        }
        io = $io[0];


        xhr = { // mock object
            aborted: 0,
            responseText: null,
            responseXML: null,
            status: 0,
            statusText: 'n/a',
            getAllResponseHeaders: function() {},
            getResponseHeader: function() {},
            setRequestHeader: function() {},
            abort: function(status) {
                var e = (status === 'timeout' ? 'timeout' : 'aborted');
                log('aborting upload... ' + e);
                this.aborted = 1;
                $io.attr('src', s.iframeSrc); // abort op in progress
                xhr.error = e;
                if (s.error)
                    s.error.call(s.context, xhr, e, status);
                if (g)
                    $.event.trigger("ajaxError", [xhr, s, e]);
                if (s.complete)
                    s.complete.call(s.context, xhr, e);
            }
        };

        g = s.global;
        // trigger ajax global events so that activity/block indicators work like normal
        if (g && 0 === $.active++) {
            $.event.trigger("ajaxStart");
        }
        if (g) {
            $.event.trigger("ajaxSend", [xhr, s]);
        }

        if (s.beforeSend && s.beforeSend.call(s.context, xhr, s) === false) {
            if (s.global) {
                $.active--;
            }
            return;
        }
        if (xhr.aborted) {
            return;
        }

        // add submitting element to data if we know it
        sub = form.clk;
        if (sub) {
            n = sub.name;
            if (n && !sub.disabled) {
                s.extraData = s.extraData || {};
                s.extraData[n] = sub.value;
                if (sub.type == "image") {
                    s.extraData[n+'.x'] = form.clk_x;
                    s.extraData[n+'.y'] = form.clk_y;
                }
            }
        }
        
        var CLIENT_TIMEOUT_ABORT = 1;
        var SERVER_ABORT = 2;

        function getDoc(frame) {
            var doc = frame.contentWindow ? frame.contentWindow.document : frame.contentDocument ? frame.contentDocument : frame.document;
            return doc;
        }
        
        // Rails CSRF hack (thanks to Yvan Barthelemy)
        var csrf_token = $('meta[name=csrf-token]').attr('content');
        var csrf_param = $('meta[name=csrf-param]').attr('content');
        if (csrf_param && csrf_token) {
            s.extraData = s.extraData || {};
            s.extraData[csrf_param] = csrf_token;
        }

        // take a breath so that pending repaints get some cpu time before the upload starts
        function doSubmit() {
            // make sure form attrs are set
            var t = $form.attr('target'), a = $form.attr('action');

            // update form attrs in IE friendly way
            form.setAttribute('target',id);
            if (!method) {
                form.setAttribute('method', 'POST');
            }
            if (a != s.url) {
                form.setAttribute('action', s.url);
            }

            // ie borks in some cases when setting encoding
            if (! s.skipEncodingOverride && (!method || /post/i.test(method))) {
                $form.attr({
                    encoding: 'multipart/form-data',
                    enctype:  'multipart/form-data'
                });
            }

            // support timout
            if (s.timeout) {
                timeoutHandle = setTimeout(function() { timedOut = true; cb(CLIENT_TIMEOUT_ABORT); }, s.timeout);
            }
            
            // look for server aborts
            function checkState() {
                try {
                    var state = getDoc(io).readyState;
                    log('state = ' + state);
                    if (state && state.toLowerCase() == 'uninitialized')
                        setTimeout(checkState,50);
                }
                catch(e) {
                    log('Server abort: ' , e, ' (', e.name, ')');
                    cb(SERVER_ABORT);
                    if (timeoutHandle)
                        clearTimeout(timeoutHandle);
                    timeoutHandle = undefined;
                }
            }

            // add "extra" data to form if provided in options
            var extraInputs = [];
            try {
                if (s.extraData) {
                    for (var n in s.extraData) {
                        if (s.extraData.hasOwnProperty(n)) {
                            extraInputs.push(
                                $('<input type="hidden" name="'+n+'">').attr('value',s.extraData[n])
                                    .appendTo(form)[0]);
                        }
                    }
                }

                if (!s.iframeTarget) {
                    // add iframe to doc and submit the form
                    $io.appendTo('body');
                    if (io.attachEvent)
                        io.attachEvent('onload', cb);
                    else
                        io.addEventListener('load', cb, false);
                }
                setTimeout(checkState,15);
                form.submit();
            }
            finally {
                // reset attrs and remove "extra" input elements
                form.setAttribute('action',a);
                if(t) {
                    form.setAttribute('target', t);
                } else {
                    $form.removeAttr('target');
                }
                $(extraInputs).remove();
            }
        }

        if (s.forceSync) {
            doSubmit();
        }
        else {
            setTimeout(doSubmit, 10); // this lets dom updates render
        }

        var data, doc, domCheckCount = 50, callbackProcessed;

        function cb(e) {
            if (xhr.aborted || callbackProcessed) {
                return;
            }
            try {
                doc = getDoc(io);
            }
            catch(ex) {
                log('cannot access response document: ', ex);
                e = SERVER_ABORT;
            }
            if (e === CLIENT_TIMEOUT_ABORT && xhr) {
                xhr.abort('timeout');
                return;
            }
            else if (e == SERVER_ABORT && xhr) {
                xhr.abort('server abort');
                return;
            }

            if (!doc || doc.location.href == s.iframeSrc) {
                // response not received yet
                if (!timedOut)
                    return;
            }
            if (io.detachEvent)
                io.detachEvent('onload', cb);
            else    
                io.removeEventListener('load', cb, false);

            var status = 'success', errMsg;
            try {
                if (timedOut) {
                    throw 'timeout';
                }

                var isXml = s.dataType == 'xml' || doc.XMLDocument || $.isXMLDoc(doc);
                log('isXml='+isXml);
                if (!isXml && window.opera && (doc.body === null || !doc.body.innerHTML)) {
                    if (--domCheckCount) {
                        // in some browsers (Opera) the iframe DOM is not always traversable when
                        // the onload callback fires, so we loop a bit to accommodate
                        log('requeing onLoad callback, DOM not available');
                        setTimeout(cb, 250);
                        return;
                    }
                    // let this fall through because server response could be an empty document
                    //log('Could not access iframe DOM after mutiple tries.');
                    //throw 'DOMException: not available';
                }

                //log('response detected');
                var docRoot = doc.body ? doc.body : doc.documentElement;
                xhr.responseText = docRoot ? docRoot.innerHTML : null;
                xhr.responseXML = doc.XMLDocument ? doc.XMLDocument : doc;
                if (isXml)
                    s.dataType = 'xml';
                xhr.getResponseHeader = function(header){
                    var headers = {'content-type': s.dataType};
                    return headers[header];
                };
                // support for XHR 'status' & 'statusText' emulation :
                if (docRoot) {
                    xhr.status = Number( docRoot.getAttribute('status') ) || xhr.status;
                    xhr.statusText = docRoot.getAttribute('statusText') || xhr.statusText;
                }

                var dt = (s.dataType || '').toLowerCase();
                var scr = /(json|script|text)/.test(dt);
                if (scr || s.textarea) {
                    // see if user embedded response in textarea
                    var ta = doc.getElementsByTagName('textarea')[0];
                    if (ta) {
                        xhr.responseText = ta.value;
                        // support for XHR 'status' & 'statusText' emulation :
                        xhr.status = Number( ta.getAttribute('status') ) || xhr.status;
                        xhr.statusText = ta.getAttribute('statusText') || xhr.statusText;
                    }
                    else if (scr) {
                        // account for browsers injecting pre around json response
                        var pre = doc.getElementsByTagName('pre')[0];
                        var b = doc.getElementsByTagName('body')[0];
                        if (pre) {
                            xhr.responseText = pre.textContent ? pre.textContent : pre.innerText;
                        }
                        else if (b) {
                            xhr.responseText = b.textContent ? b.textContent : b.innerText;
                        }
                    }
                }
                else if (dt == 'xml' && !xhr.responseXML && xhr.responseText) {
                    xhr.responseXML = toXml(xhr.responseText);
                }

                try {
                    data = httpData(xhr, dt, s);
                }
                catch (e) {
                    status = 'parsererror';
                    xhr.error = errMsg = (e || status);
                }
            }
            catch (e) {
                log('error caught: ',e);
                status = 'error';
                xhr.error = errMsg = (e || status);
            }

            if (xhr.aborted) {
                log('upload aborted');
                status = null;
            }

            if (xhr.status) { // we've set xhr.status
                status = (xhr.status >= 200 && xhr.status < 300 || xhr.status === 304) ? 'success' : 'error';
            }

            // ordering of these callbacks/triggers is odd, but that's how $.ajax does it
            if (status === 'success') {
                if (s.success)
                    s.success.call(s.context, data, 'success', xhr);
                if (g)
                    $.event.trigger("ajaxSuccess", [xhr, s]);
            }
            else if (status) {
                if (errMsg === undefined)
                    errMsg = xhr.statusText;
                if (s.error)
                    s.error.call(s.context, xhr, status, errMsg);
                if (g)
                    $.event.trigger("ajaxError", [xhr, s, errMsg]);
            }

            if (g)
                $.event.trigger("ajaxComplete", [xhr, s]);

            if (g && ! --$.active) {
                $.event.trigger("ajaxStop");
            }

            if (s.complete)
                s.complete.call(s.context, xhr, status);

            callbackProcessed = true;
            if (s.timeout)
                clearTimeout(timeoutHandle);

            // clean up
            setTimeout(function() {
                if (!s.iframeTarget)
                    $io.remove();
                xhr.responseXML = null;
            }, 100);
        }

        var toXml = $.parseXML || function(s, doc) { // use parseXML if available (jQuery 1.5+)
            if (window.ActiveXObject) {
                doc = new ActiveXObject('Microsoft.XMLDOM');
                doc.async = 'false';
                doc.loadXML(s);
            }
            else {
                doc = (new DOMParser()).parseFromString(s, 'text/xml');
            }
            return (doc && doc.documentElement && doc.documentElement.nodeName != 'parsererror') ? doc : null;
        };
        var parseJSON = $.parseJSON || function(s) {
            /*jslint evil:true */
            return window['eval']('(' + s + ')');
        };

        var httpData = function( xhr, type, s ) { // mostly lifted from jq1.4.4

            var ct = xhr.getResponseHeader('content-type') || '',
                xml = type === 'xml' || !type && ct.indexOf('xml') >= 0,
                data = xml ? xhr.responseXML : xhr.responseText;

            if (xml && data.documentElement.nodeName === 'parsererror') {
                if ($.error)
                    $.error('parsererror');
            }
            if (s && s.dataFilter) {
                data = s.dataFilter(data, type);
            }
            if (typeof data === 'string') {
                if (type === 'json' || !type && ct.indexOf('json') >= 0) {
                    data = parseJSON(data);
                } else if (type === "script" || !type && ct.indexOf("javascript") >= 0) {
                    $.globalEval(data);
                }
            }
            return data;
        };
    }
};

/**
 * ajaxForm() provides a mechanism for fully automating form submission.
 *
 * The advantages of using this method instead of ajaxSubmit() are:
 *
 * 1: This method will include coordinates for <input type="image" /> elements (if the element
 *    is used to submit the form).
 * 2. This method will include the submit element's name/value data (for the element that was
 *    used to submit the form).
 * 3. This method binds the submit() method to the form for you.
 *
 * The options argument for ajaxForm works exactly as it does for ajaxSubmit.  ajaxForm merely
 * passes the options argument along after properly binding events for submit elements and
 * the form itself.
 */
$.fn.ajaxForm = function(options) {
    options = options || {};
    options.delegation = options.delegation && $.isFunction($.fn.on);
    
    // in jQuery 1.3+ we can fix mistakes with the ready state
    if (!options.delegation && this.length === 0) {
        var o = { s: this.selector, c: this.context };
        if (!$.isReady && o.s) {
            log('DOM not ready, queuing ajaxForm');
            $(function() {
                $(o.s,o.c).ajaxForm(options);
            });
            return this;
        }
        // is your DOM ready?  http://docs.jquery.com/Tutorials:Introducing_$(document).ready()
        log('terminating; zero elements found by selector' + ($.isReady ? '' : ' (DOM not ready)'));
        return this;
    }

    if ( options.delegation ) {
        $(document)
            .off('submit.form-plugin', this.selector, doAjaxSubmit)
            .off('click.form-plugin', this.selector, captureSubmittingElement)
            .on('submit.form-plugin', this.selector, options, doAjaxSubmit)
            .on('click.form-plugin', this.selector, options, captureSubmittingElement);
        return this;
    }

    return this.ajaxFormUnbind()
        .bind('submit.form-plugin', options, doAjaxSubmit)
        .bind('click.form-plugin', options, captureSubmittingElement);
};

// private event handlers    
function doAjaxSubmit(e) {
    /*jshint validthis:true */
    var options = e.data;
    if (!e.isDefaultPrevented()) { // if event has been canceled, don't proceed
        e.preventDefault();
        $(this).ajaxSubmit(options);
    }
}
    
function captureSubmittingElement(e) {
    /*jshint validthis:true */
    var target = e.target;
    var $el = $(target);
    if (!($el.is(":submit,input:image"))) {
        // is this a child element of the submit el?  (ex: a span within a button)
        var t = $el.closest(':submit');
        if (t.length === 0) {
            return;
        }
        target = t[0];
    }
    var form = this;
    form.clk = target;
    if (target.type == 'image') {
        if (e.offsetX !== undefined) {
            form.clk_x = e.offsetX;
            form.clk_y = e.offsetY;
        } else if (typeof $.fn.offset == 'function') {
            var offset = $el.offset();
            form.clk_x = e.pageX - offset.left;
            form.clk_y = e.pageY - offset.top;
        } else {
            form.clk_x = e.pageX - target.offsetLeft;
            form.clk_y = e.pageY - target.offsetTop;
        }
    }
    // clear form vars
    setTimeout(function() { form.clk = form.clk_x = form.clk_y = null; }, 100);
}


// ajaxFormUnbind unbinds the event handlers that were bound by ajaxForm
$.fn.ajaxFormUnbind = function() {
    return this.unbind('submit.form-plugin click.form-plugin');
};

/**
 * formToArray() gathers form element data into an array of objects that can
 * be passed to any of the following ajax functions: $.get, $.post, or load.
 * Each object in the array has both a 'name' and 'value' property.  An example of
 * an array for a simple login form might be:
 *
 * [ { name: 'username', value: 'jresig' }, { name: 'password', value: 'secret' } ]
 *
 * It is this array that is passed to pre-submit callback functions provided to the
 * ajaxSubmit() and ajaxForm() methods.
 */
$.fn.formToArray = function(semantic) {
    var a = [];
    if (this.length === 0) {
        return a;
    }

    var form = this[0];
    var els = semantic ? form.getElementsByTagName('*') : form.elements;
    if (!els) {
        return a;
    }

    var i,j,n,v,el,max,jmax;
    for(i=0, max=els.length; i < max; i++) {
        el = els[i];
        n = el.name;
        if (!n) {
            continue;
        }

        if (semantic && form.clk && el.type == "image") {
            // handle image inputs on the fly when semantic == true
            if(!el.disabled && form.clk == el) {
                a.push({name: n, value: $(el).val(), type: el.type });
                a.push({name: n+'.x', value: form.clk_x}, {name: n+'.y', value: form.clk_y});
            }
            continue;
        }

        v = $.fieldValue(el, true);
        if (v && v.constructor == Array) {
            for(j=0, jmax=v.length; j < jmax; j++) {
                a.push({name: n, value: v[j]});
            }
        }
        else if (feature.fileapi && el.type == 'file' && !el.disabled) {
            var files = el.files;
            for (j=0; j < files.length; j++) {
                a.push({name: n, value: files[j], type: el.type});
            }
        }
        else if (v !== null && typeof v != 'undefined') {
            a.push({name: n, value: v, type: el.type});
        }
    }

    if (!semantic && form.clk) {
        // input type=='image' are not found in elements array! handle it here
        var $input = $(form.clk), input = $input[0];
        n = input.name;
        if (n && !input.disabled && input.type == 'image') {
            a.push({name: n, value: $input.val()});
            a.push({name: n+'.x', value: form.clk_x}, {name: n+'.y', value: form.clk_y});
        }
    }
    return a;
};

/**
 * Serializes form data into a 'submittable' string. This method will return a string
 * in the format: name1=value1&amp;name2=value2
 */
$.fn.formSerialize = function(semantic) {
    //hand off to jQuery.param for proper encoding
    return $.param(this.formToArray(semantic));
};

/**
 * Serializes all field elements in the jQuery object into a query string.
 * This method will return a string in the format: name1=value1&amp;name2=value2
 */
$.fn.fieldSerialize = function(successful) {
    var a = [];
    this.each(function() {
        var n = this.name;
        if (!n) {
            return;
        }
        var v = $.fieldValue(this, successful);
        if (v && v.constructor == Array) {
            for (var i=0,max=v.length; i < max; i++) {
                a.push({name: n, value: v[i]});
            }
        }
        else if (v !== null && typeof v != 'undefined') {
            a.push({name: this.name, value: v});
        }
    });
    //hand off to jQuery.param for proper encoding
    return $.param(a);
};

/**
 * Returns the value(s) of the element in the matched set.  For example, consider the following form:
 *
 *  <form><fieldset>
 *      <input name="A" type="text" />
 *      <input name="A" type="text" />
 *      <input name="B" type="checkbox" value="B1" />
 *      <input name="B" type="checkbox" value="B2"/>
 *      <input name="C" type="radio" value="C1" />
 *      <input name="C" type="radio" value="C2" />
 *  </fieldset></form>
 *
 *  var v = $(':text').fieldValue();
 *  // if no values are entered into the text inputs
 *  v == ['','']
 *  // if values entered into the text inputs are 'foo' and 'bar'
 *  v == ['foo','bar']
 *
 *  var v = $(':checkbox').fieldValue();
 *  // if neither checkbox is checked
 *  v === undefined
 *  // if both checkboxes are checked
 *  v == ['B1', 'B2']
 *
 *  var v = $(':radio').fieldValue();
 *  // if neither radio is checked
 *  v === undefined
 *  // if first radio is checked
 *  v == ['C1']
 *
 * The successful argument controls whether or not the field element must be 'successful'
 * (per http://www.w3.org/TR/html4/interact/forms.html#successful-controls).
 * The default value of the successful argument is true.  If this value is false the value(s)
 * for each element is returned.
 *
 * Note: This method *always* returns an array.  If no valid value can be determined the
 *    array will be empty, otherwise it will contain one or more values.
 */
$.fn.fieldValue = function(successful) {
    for (var val=[], i=0, max=this.length; i < max; i++) {
        var el = this[i];
        var v = $.fieldValue(el, successful);
        if (v === null || typeof v == 'undefined' || (v.constructor == Array && !v.length)) {
            continue;
        }
        if (v.constructor == Array)
            $.merge(val, v);
        else
            val.push(v);
    }
    return val;
};

/**
 * Returns the value of the field element.
 */
$.fieldValue = function(el, successful) {
    var n = el.name, t = el.type, tag = el.tagName.toLowerCase();
    if (successful === undefined) {
        successful = true;
    }

    if (successful && (!n || el.disabled || t == 'reset' || t == 'button' ||
        (t == 'checkbox' || t == 'radio') && !el.checked ||
        (t == 'submit' || t == 'image') && el.form && el.form.clk != el ||
        tag == 'select' && el.selectedIndex == -1)) {
            return null;
    }

    if (tag == 'select') {
        var index = el.selectedIndex;
        if (index < 0) {
            return null;
        }
        var a = [], ops = el.options;
        var one = (t == 'select-one');
        var max = (one ? index+1 : ops.length);
        for(var i=(one ? index : 0); i < max; i++) {
            var op = ops[i];
            if (op.selected) {
                var v = op.value;
                if (!v) { // extra pain for IE...
                    v = (op.attributes && op.attributes['value'] && !(op.attributes['value'].specified)) ? op.text : op.value;
                }
                if (one) {
                    return v;
                }
                a.push(v);
            }
        }
        return a;
    }
    return $(el).val();
};

/**
 * Clears the form data.  Takes the following actions on the form's input fields:
 *  - input text fields will have their 'value' property set to the empty string
 *  - select elements will have their 'selectedIndex' property set to -1
 *  - checkbox and radio inputs will have their 'checked' property set to false
 *  - inputs of type submit, button, reset, and hidden will *not* be effected
 *  - button elements will *not* be effected
 */
$.fn.clearForm = function(includeHidden) {
    return this.each(function() {
        $('input,select,textarea', this).clearFields(includeHidden);
    });
};

/**
 * Clears the selected form elements.
 */
$.fn.clearFields = $.fn.clearInputs = function(includeHidden) {
    var re = /^(?:color|date|datetime|email|month|number|password|range|search|tel|text|time|url|week)$/i; // 'hidden' is not in this list
    return this.each(function() {
        var t = this.type, tag = this.tagName.toLowerCase();
        if (re.test(t) || tag == 'textarea' || (includeHidden && /hidden/.test(t)) ) {
            this.value = '';
        }
        else if (t == 'checkbox' || t == 'radio') {
            this.checked = false;
        }
        else if (tag == 'select') {
            this.selectedIndex = -1;
        }
    });
};

/**
 * Resets the form data.  Causes all form elements to be reset to their original value.
 */
$.fn.resetForm = function() {
    return this.each(function() {
        // guard against an input with the name of 'reset'
        // note that IE reports the reset function as an 'object'
        if (typeof this.reset == 'function' || (typeof this.reset == 'object' && !this.reset.nodeType)) {
            this.reset();
        }
    });
};

/**
 * Enables or disables any matching elements.
 */
$.fn.enable = function(b) {
    if (b === undefined) {
        b = true;
    }
    return this.each(function() {
        this.disabled = !b;
    });
};

/**
 * Checks/unchecks any matching checkboxes or radio buttons and
 * selects/deselects and matching option elements.
 */
$.fn.selected = function(select) {
    if (select === undefined) {
        select = true;
    }
    return this.each(function() {
        var t = this.type;
        if (t == 'checkbox' || t == 'radio') {
            this.checked = select;
        }
        else if (this.tagName.toLowerCase() == 'option') {
            var $sel = $(this).parent('select');
            if (select && $sel[0] && $sel[0].type == 'select-one') {
                // deselect all other options
                $sel.find('option').selected(false);
            }
            this.selected = select;
        }
    });
};

// expose debug var
$.fn.ajaxSubmit.debug = false;

// helper fn for console logging
function log() {
    if (!$.fn.ajaxSubmit.debug) 
        return;
    var msg = '[jquery.form] ' + Array.prototype.join.call(arguments,'');
    if (window.console && window.console.log) {
        window.console.log(msg);
    }
    else if (window.opera && window.opera.postError) {
        window.opera.postError(msg);
    }
}

})(jQuery);
/**
 * jQuery Validation Plugin 2.0.0pre
 *
 * http://bassistance.de/jquery-plugins/jquery-plugin-validation/
 * http://docs.jquery.com/Plugins/Validation
 *
 * Copyright (c) 2012 Jörn Zaefferer
 *
 * Dual licensed under the MIT and GPL licenses:
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 */

(function($) {

$.extend($.fn, {
	// http://docs.jquery.com/Plugins/Validation/validate
	validate: function( options ) {

		// if nothing is selected, return nothing; can't chain anyway
		if (!this.length) {
			if (options && options.debug && window.console) {
				console.warn( "nothing selected, can't validate, returning nothing" );
			}
			return;
		}

		// check if a validator for this form was already created
		var validator = $.data(this[0], 'validator');
		if ( validator ) {
			return validator;
		}

		// Add novalidate tag if HTML5.
		this.attr('novalidate', 'novalidate');

		validator = new $.validator( options, this[0] );
		$.data(this[0], 'validator', validator);

		if ( validator.settings.onsubmit ) {

			this.validateDelegate( ":submit", "click", function(ev) {
				if ( validator.settings.submitHandler ) {
					validator.submitButton = ev.target;
				}
				// allow suppressing validation by adding a cancel class to the submit button
				if ( $(ev.target).hasClass('cancel') ) {
					validator.cancelSubmit = true;
				}
			});

			// validate the form on submit
			this.submit( function( event ) {
				if ( validator.settings.debug ) {
					// prevent form submit to be able to see console output
					event.preventDefault();
				}
				function handle() {
					var hidden;
					if ( validator.settings.submitHandler ) {
						if (validator.submitButton) {
							// insert a hidden input as a replacement for the missing submit button
							hidden = $("<input type='hidden'/>").attr("name", validator.submitButton.name).val(validator.submitButton.value).appendTo(validator.currentForm);
						}
						validator.settings.submitHandler.call( validator, validator.currentForm, event );
						if (validator.submitButton) {
							// and clean up afterwards; thanks to no-block-scope, hidden can be referenced
							hidden.remove();
						}
						return false;
					}
					return true;
				}

				// prevent submit for invalid forms or custom submit handlers
				if ( validator.cancelSubmit ) {
					validator.cancelSubmit = false;
					return handle();
				}
				if ( validator.form() ) {
					if ( validator.pendingRequest ) {
						validator.formSubmitted = true;
						return false;
					}
					return handle();
				} else {
					validator.focusInvalid();
					return false;
				}
			});
		}

		return validator;
	},
	// http://docs.jquery.com/Plugins/Validation/valid
	valid: function() {
		if ( $(this[0]).is('form')) {
			return this.validate().form();
		} else {
			var valid = true;
			var validator = $(this[0].form).validate();
			this.each(function() {
				valid &= validator.element(this);
			});
			return valid;
		}
	},
	// attributes: space seperated list of attributes to retrieve and remove
	removeAttrs: function(attributes) {
		var result = {},
			$element = this;
		$.each(attributes.split(/\s/), function(index, value) {
			result[value] = $element.attr(value);
			$element.removeAttr(value);
		});
		return result;
	},
	// http://docs.jquery.com/Plugins/Validation/rules
	rules: function(command, argument) {
		var element = this[0];

		if (command) {
			var settings = $.data(element.form, 'validator').settings;
			var staticRules = settings.rules;
			var existingRules = $.validator.staticRules(element);
			switch(command) {
			case "add":
				$.extend(existingRules, $.validator.normalizeRule(argument));
				staticRules[element.name] = existingRules;
				if (argument.messages) {
					settings.messages[element.name] = $.extend( settings.messages[element.name], argument.messages );
				}
				break;
			case "remove":
				if (!argument) {
					delete staticRules[element.name];
					return existingRules;
				}
				var filtered = {};
				$.each(argument.split(/\s/), function(index, method) {
					filtered[method] = existingRules[method];
					delete existingRules[method];
				});
				return filtered;
			}
		}

		var data = $.validator.normalizeRules(
		$.extend(
			{},
			$.validator.metadataRules(element),
			$.validator.classRules(element),
			$.validator.attributeRules(element),
			$.validator.staticRules(element)
		), element);

		// make sure required is at front
		if (data.required) {
			var param = data.required;
			delete data.required;
			data = $.extend({required: param}, data);
		}

		return data;
	}
});

// Custom selectors
$.extend($.expr[":"], {
	// http://docs.jquery.com/Plugins/Validation/blank
	blank: function(a) {return !$.trim("" + a.value);},
	// http://docs.jquery.com/Plugins/Validation/filled
	filled: function(a) {return !!$.trim("" + a.value);},
	// http://docs.jquery.com/Plugins/Validation/unchecked
	unchecked: function(a) {return !a.checked;}
});

// constructor for validator
$.validator = function( options, form ) {
	this.settings = $.extend( true, {}, $.validator.defaults, options );
	this.currentForm = form;
	this.init();
};

$.validator.format = function(source, params) {
	if ( arguments.length === 1 ) {
		return function() {
			var args = $.makeArray(arguments);
			args.unshift(source);
			return $.validator.format.apply( this, args );
		};
	}
	if ( arguments.length > 2 && params.constructor !== Array  ) {
		params = $.makeArray(arguments).slice(1);
	}
	if ( params.constructor !== Array ) {
		params = [ params ];
	}
	$.each(params, function(i, n) {
		source = source.replace(new RegExp("\\{" + i + "\\}", "g"), n);
	});
	return source;
};

$.extend($.validator, {

	defaults: {
		messages: {},
		groups: {},
		rules: {},
		errorClass: "error",
		validClass: "valid",
		errorElement: "label",
		focusInvalid: true,
		errorContainer: $( [] ),
		errorLabelContainer: $( [] ),
		onsubmit: true,
		ignore: ":hidden",
		ignoreTitle: false,
		onfocusin: function(element, event) {
			this.lastActive = element;

			// hide error label and remove error class on focus if enabled
			if ( this.settings.focusCleanup && !this.blockFocusCleanup ) {
				if ( this.settings.unhighlight ) {
					this.settings.unhighlight.call( this, element, this.settings.errorClass, this.settings.validClass );
				}
				this.addWrapper(this.errorsFor(element)).hide();
			}
		},
		onfocusout: function(element, event) {
			if ( !this.checkable(element) && (element.name in this.submitted || !this.optional(element)) ) {
				this.element(element);
			}
		},
		onkeyup: function(element, event) {
			if ( element.name in this.submitted || element === this.lastElement ) {
				this.element(element);
			}
		},
		onclick: function(element, event) {
			// click on selects, radiobuttons and checkboxes
			if ( element.name in this.submitted ) {
				this.element(element);
			}
			// or option elements, check parent select in that case
			else if (element.parentNode.name in this.submitted) {
				this.element(element.parentNode);
			}
		},
		highlight: function(element, errorClass, validClass) {
			if (element.type === 'radio') {
				this.findByName(element.name).addClass(errorClass).removeClass(validClass);
			} else {
				$(element).addClass(errorClass).removeClass(validClass);
			}
		},
		unhighlight: function(element, errorClass, validClass) {
			if (element.type === 'radio') {
				this.findByName(element.name).removeClass(errorClass).addClass(validClass);
			} else {
				$(element).removeClass(errorClass).addClass(validClass);
			}
		}
	},

	// http://docs.jquery.com/Plugins/Validation/Validator/setDefaults
	setDefaults: function(settings) {
		$.extend( $.validator.defaults, settings );
	},

	messages: {
		required: "This field is required.",
		remote: "Please fix this field.",
		email: "Please enter a valid email address.",
		url: "Please enter a valid URL.",
		date: "Please enter a valid date.",
		dateISO: "Please enter a valid date (ISO).",
		number: "Please enter a valid number.",
		digits: "Please enter only digits.",
		creditcard: "Please enter a valid credit card number.",
		equalTo: "Please enter the same value again.",
		accept: "Please enter a value with a valid extension.",
		maxlength: $.validator.format("Please enter no more than {0} characters."),
		minlength: $.validator.format("Please enter at least {0} characters."),
		rangelength: $.validator.format("Please enter a value between {0} and {1} characters long."),
		range: $.validator.format("Please enter a value between {0} and {1}."),
		max: $.validator.format("Please enter a value less than or equal to {0}."),
		min: $.validator.format("Please enter a value greater than or equal to {0}.")
	},

	autoCreateRanges: false,

	prototype: {

		init: function() {
			this.labelContainer = $(this.settings.errorLabelContainer);
			this.errorContext = this.labelContainer.length && this.labelContainer || $(this.currentForm);
			this.containers = $(this.settings.errorContainer).add( this.settings.errorLabelContainer );
			this.submitted = {};
			this.valueCache = {};
			this.pendingRequest = 0;
			this.pending = {};
			this.invalid = {};
			this.reset();

			var groups = (this.groups = {});
			$.each(this.settings.groups, function(key, value) {
				$.each(value.split(/\s/), function(index, name) {
					groups[name] = key;
				});
			});
			var rules = this.settings.rules;
			$.each(rules, function(key, value) {
				rules[key] = $.validator.normalizeRule(value);
			});

			function delegate(event) {
				var validator = $.data(this[0].form, "validator"),
					eventType = "on" + event.type.replace(/^validate/, "");
				if (validator.settings[eventType]) {
					validator.settings[eventType].call(validator, this[0], event);
				}
			}
			$(this.currentForm)
				.validateDelegate("[type='text'], [type='password'], [type='file'], select, textarea, " +
					"[type='number'], [type='search'] ,[type='tel'], [type='url'], " +
					"[type='email'], [type='datetime'], [type='date'], [type='month'], " +
					"[type='week'], [type='time'], [type='datetime-local'], " +
					"[type='range'], [type='color'] ",
					"focusin focusout keyup", delegate)
				.validateDelegate("[type='radio'], [type='checkbox'], select, option", "click", delegate);

			if (this.settings.invalidHandler) {
				$(this.currentForm).bind("invalid-form.validate", this.settings.invalidHandler);
			}
		},

		// http://docs.jquery.com/Plugins/Validation/Validator/form
		form: function() {
			this.checkForm();
			$.extend(this.submitted, this.errorMap);
			this.invalid = $.extend({}, this.errorMap);
			if (!this.valid()) {
				$(this.currentForm).triggerHandler("invalid-form", [this]);
			}
			this.showErrors();
			return this.valid();
		},

		checkForm: function() {
			this.prepareForm();
			for ( var i = 0, elements = (this.currentElements = this.elements()); elements[i]; i++ ) {
				this.check( elements[i] );
			}
			return this.valid();
		},

		// http://docs.jquery.com/Plugins/Validation/Validator/element
		element: function( element ) {
			element = this.validationTargetFor( this.clean( element ) );
			this.lastElement = element;
			this.prepareElement( element );
			this.currentElements = $(element);
			var result = this.check( element ) !== false;
			if (result) {
				delete this.invalid[element.name];
			} else {
				this.invalid[element.name] = true;
			}
			if ( !this.numberOfInvalids() ) {
				// Hide error containers on last error
				this.toHide = this.toHide.add( this.containers );
			}
			this.showErrors();
			return result;
		},

		// http://docs.jquery.com/Plugins/Validation/Validator/showErrors
		showErrors: function(errors) {
			if(errors) {
				// add items to error list and map
				$.extend( this.errorMap, errors );
				this.errorList = [];
				for ( var name in errors ) {
					this.errorList.push({
						message: errors[name],
						element: this.findByName(name)[0]
					});
				}
				// remove items from success list
				this.successList = $.grep( this.successList, function(element) {
					return !(element.name in errors);
				});
			}
			if (this.settings.showErrors) {
				this.settings.showErrors.call( this, this.errorMap, this.errorList );
			} else {
				this.defaultShowErrors();
			}
		},

		// http://docs.jquery.com/Plugins/Validation/Validator/resetForm
		resetForm: function() {
			if ( $.fn.resetForm ) {
				$( this.currentForm ).resetForm();
			}
			this.submitted = {};
			this.lastElement = null;
			this.prepareForm();
			this.hideErrors();
			this.elements().removeClass( this.settings.errorClass );
		},

		numberOfInvalids: function() {
			return this.objectLength(this.invalid);
		},

		objectLength: function( obj ) {
			var count = 0;
			for ( var i in obj ) {
				count++;
			}
			return count;
		},

		hideErrors: function() {
			this.addWrapper( this.toHide ).hide();
		},

		valid: function() {
			return this.size() === 0;
		},

		size: function() {
			return this.errorList.length;
		},

		focusInvalid: function() {
			if( this.settings.focusInvalid ) {
				try {
					$(this.findLastActive() || this.errorList.length && this.errorList[0].element || [])
					.filter(":visible")
					.focus()
					// manually trigger focusin event; without it, focusin handler isn't called, findLastActive won't have anything to find
					.trigger("focusin");
				} catch(e) {
					// ignore IE throwing errors when focusing hidden elements
				}
			}
		},

		findLastActive: function() {
			var lastActive = this.lastActive;
			return lastActive && $.grep(this.errorList, function(n) {
				return n.element.name === lastActive.name;
			}).length === 1 && lastActive;
		},

		elements: function() {
			var validator = this,
				rulesCache = {};

			// select all valid inputs inside the form (no submit or reset buttons)
			return $(this.currentForm)
			.find("input, select, textarea")
			.not(":submit, :reset, :image, [disabled]")
			.not( this.settings.ignore )
			.filter(function() {
				if ( !this.name && validator.settings.debug && window.console ) {
					console.error( "%o has no name assigned", this);
				}

				// select only the first element for each name, and only those with rules specified
				if ( this.name in rulesCache || !validator.objectLength($(this).rules()) ) {
					return false;
				}

				rulesCache[this.name] = true;
				return true;
			});
		},

		clean: function( selector ) {
			return $( selector )[0];
		},

		errors: function() {
			var errorClass = this.settings.errorClass.replace(' ', '.');
			return $( this.settings.errorElement + "." + errorClass, this.errorContext );
		},

		reset: function() {
			this.successList = [];
			this.errorList = [];
			this.errorMap = {};
			this.toShow = $([]);
			this.toHide = $([]);
			this.currentElements = $([]);
		},

		prepareForm: function() {
			this.reset();
			this.toHide = this.errors().add( this.containers );
		},

		prepareElement: function( element ) {
			this.reset();
			this.toHide = this.errorsFor(element);
		},

		elementValue: function( element ) {
			var val = $(element).val();
			if( typeof val === 'string' ) {
				return val.replace(/\r/g, "");
			}
			return val;
		},

		check: function( element ) {
			element = this.validationTargetFor( this.clean( element ) );

			var rules = $(element).rules();
			var dependencyMismatch = false;
			var val = this.elementValue(element);
			var result;

			for (var method in rules ) {
				var rule = { method: method, parameters: rules[method] };
				try {

					result = $.validator.methods[method].call( this, val, element, rule.parameters );

					// if a method indicates that the field is optional and therefore valid,
					// don't mark it as valid when there are no other rules
					if ( result === "dependency-mismatch" ) {
						dependencyMismatch = true;
						continue;
					}
					dependencyMismatch = false;

					if ( result === "pending" ) {
						this.toHide = this.toHide.not( this.errorsFor(element) );
						return;
					}

					if( !result ) {
						this.formatAndAdd( element, rule );
						return false;
					}
				} catch(e) {
					if ( this.settings.debug && window.console ) {
						console.log("exception occured when checking element " + element.id + ", check the '" + rule.method + "' method", e);
					}
					throw e;
				}
			}
			if (dependencyMismatch) {
				return;
			}
			if ( this.objectLength(rules) ) {
				this.successList.push(element);
			}
			return true;
		},

		// return the custom message for the given element and validation method
		// specified in the element's "messages" metadata
		customMetaMessage: function(element, method) {
			if (!$.metadata) {
				return;
			}
			var meta = this.settings.meta ? $(element).metadata()[this.settings.meta] : $(element).metadata();
			return meta && meta.messages && meta.messages[method];
		},

		// return the custom message for the given element name and validation method
		customMessage: function( name, method ) {
			var m = this.settings.messages[name];
			return m && (m.constructor === String ? m : m[method]);
		},

		// return the first defined argument, allowing empty strings
		findDefined: function() {
			for(var i = 0; i < arguments.length; i++) {
				if (arguments[i] !== undefined) {
					return arguments[i];
				}
			}
			return undefined;
		},

		defaultMessage: function( element, method) {
			return this.findDefined(
				this.customMessage( element.name, method ),
				this.customMetaMessage( element, method ),
				// title is never undefined, so handle empty string as undefined
				!this.settings.ignoreTitle && element.title || undefined,
				$.validator.messages[method],
				"<strong>Warning: No message defined for " + element.name + "</strong>"
			);
		},

		formatAndAdd: function( element, rule ) {
			var message = this.defaultMessage( element, rule.method ),
				theregex = /\$?\{(\d+)\}/g;
			if ( typeof message === "function" ) {
				message = message.call(this, rule.parameters, element);
			} else if (theregex.test(message)) {
				message = $.validator.format(message.replace(theregex, '{$1}'), rule.parameters);
			}
			this.errorList.push({
				message: message,
				element: element
			});

			this.errorMap[element.name] = message;
			this.submitted[element.name] = message;
		},

		addWrapper: function(toToggle) {
			if ( this.settings.wrapper ) {
				toToggle = toToggle.add( toToggle.parent( this.settings.wrapper ) );
			}
			return toToggle;
		},

		defaultShowErrors: function() {
			var i, elements;
			for ( i = 0; this.errorList[i]; i++ ) {
				var error = this.errorList[i];
				if ( this.settings.highlight ) {
					this.settings.highlight.call( this, error.element, this.settings.errorClass, this.settings.validClass );
				}
				this.showLabel( error.element, error.message );
			}
			if( this.errorList.length ) {
				this.toShow = this.toShow.add( this.containers );
			}
			if (this.settings.success) {
				for ( i = 0; this.successList[i]; i++ ) {
					this.showLabel( this.successList[i] );
				}
			}
			if (this.settings.unhighlight) {
				for ( i = 0, elements = this.validElements(); elements[i]; i++ ) {
					this.settings.unhighlight.call( this, elements[i], this.settings.errorClass, this.settings.validClass );
				}
			}
			this.toHide = this.toHide.not( this.toShow );
			this.hideErrors();
			this.addWrapper( this.toShow ).show();
		},

		validElements: function() {
			return this.currentElements.not(this.invalidElements());
		},

		invalidElements: function() {
			return $(this.errorList).map(function() {
				return this.element;
			});
		},

		showLabel: function(element, message) {
			var label = this.errorsFor( element );
			if ( label.length ) {
				// refresh error/success class
				label.removeClass( this.settings.validClass ).addClass( this.settings.errorClass );

				// check if we have a generated label, replace the message then
				if ( label.attr("generated") ) {
					label.html(message);
				}
			} else {
				// create label
				label = $("<" + this.settings.errorElement + "/>")
					.attr({"for":  this.idOrName(element), generated: true})
					.addClass(this.settings.errorClass)
					.html(message || "");
				if ( this.settings.wrapper ) {
					// make sure the element is visible, even in IE
					// actually showing the wrapped element is handled elsewhere
					label = label.hide().show().wrap("<" + this.settings.wrapper + "/>").parent();
				}
				if ( !this.labelContainer.append(label).length ) {
					if ( this.settings.errorPlacement ) {
						this.settings.errorPlacement(label, $(element) );
					} else {
					label.insertAfter(element);
					}
				}
			}
			if ( !message && this.settings.success ) {
				label.text("");
				if ( typeof this.settings.success === "string" ) {
					label.addClass( this.settings.success );
				} else {
					this.settings.success( label );
				}
			}
			this.toShow = this.toShow.add(label);
		},

		errorsFor: function(element) {
			var name = this.idOrName(element);
			return this.errors().filter(function() {
				return $(this).attr('for') === name;
			});
		},

		idOrName: function(element) {
			return this.groups[element.name] || (this.checkable(element) ? element.name : element.id || element.name);
		},

		validationTargetFor: function(element) {
			// if radio/checkbox, validate first element in group instead
			if (this.checkable(element)) {
				element = this.findByName( element.name ).not(this.settings.ignore)[0];
			}
			return element;
		},

		checkable: function( element ) {
			return (/radio|checkbox/i).test(element.type);
		},

		findByName: function( name ) {
			// select by name and filter by form for performance over form.find("[name=...]")
			var form = this.currentForm;
			return $(document.getElementsByName(name)).map(function(index, element) {
				return element.form === form && element.name === name && element  || null;
			});
		},

		getLength: function(value, element) {
			switch( element.nodeName.toLowerCase() ) {
			case 'select':
				return $("option:selected", element).length;
			case 'input':
				if( this.checkable( element) ) {
					return this.findByName(element.name).filter(':checked').length;
				}
			}
			return value.length;
		},

		depend: function(param, element) {
			return this.dependTypes[typeof param] ? this.dependTypes[typeof param](param, element) : true;
		},

		dependTypes: {
			"boolean": function(param, element) {
				return param;
			},
			"string": function(param, element) {
				return !!$(param, element.form).length;
			},
			"function": function(param, element) {
				return param(element);
			}
		},

		optional: function(element) {
			var val = this.elementValue(element);
			return !$.validator.methods.required.call(this, val, element) && "dependency-mismatch";
		},

		startRequest: function(element) {
			if (!this.pending[element.name]) {
				this.pendingRequest++;
				this.pending[element.name] = true;
			}
		},

		stopRequest: function(element, valid) {
			this.pendingRequest--;
			// sometimes synchronization fails, make sure pendingRequest is never < 0
			if (this.pendingRequest < 0) {
				this.pendingRequest = 0;
			}
			delete this.pending[element.name];
			if ( valid && this.pendingRequest === 0 && this.formSubmitted && this.form() ) {
				$(this.currentForm).submit();
				this.formSubmitted = false;
			} else if (!valid && this.pendingRequest === 0 && this.formSubmitted) {
				$(this.currentForm).triggerHandler("invalid-form", [this]);
				this.formSubmitted = false;
			}
		},

		previousValue: function(element) {
			return $.data(element, "previousValue") || $.data(element, "previousValue", {
				old: null,
				valid: true,
				message: this.defaultMessage( element, "remote" )
			});
		}

	},

	classRuleSettings: {
		required: {required: true},
		email: {email: true},
		url: {url: true},
		date: {date: true},
		dateISO: {dateISO: true},
		number: {number: true},
		digits: {digits: true},
		creditcard: {creditcard: true}
	},

	addClassRules: function(className, rules) {
		if ( className.constructor === String ) {
			this.classRuleSettings[className] = rules;
		} else {
			$.extend(this.classRuleSettings, className);
		}
	},

	classRules: function(element) {
		var rules = {};
		var classes = $(element).attr('class');
		if ( classes ) {
			$.each(classes.split(' '), function() {
				if (this in $.validator.classRuleSettings) {
					$.extend(rules, $.validator.classRuleSettings[this]);
				}
			});
		}
		return rules;
	},

	attributeRules: function(element) {
		var rules = {};
		var $element = $(element);

		for (var method in $.validator.methods) {
			var value;

			// support for <input required> in both html5 and older browsers
			if (method === 'required') {
				value = $element.get(0).getAttribute(method);
				// Some browsers return an empty string for the required attribute
				// and non-HTML5 browsers might have required="" markup
				if (value === "") {
					value = true;
				}
				// force non-HTML5 browsers to return bool
				value = !!value;
			} else {
				value = $element.attr(method);
			}

			if (value) {
				rules[method] = value;
			} else if ($element[0].getAttribute("type") === method) {
				rules[method] = true;
			}
		}

		// maxlength may be returned as -1, 2147483647 (IE) and 524288 (safari) for text inputs
		if (rules.maxlength && /-1|2147483647|524288/.test(rules.maxlength)) {
			delete rules.maxlength;
		}

		return rules;
	},

	metadataRules: function(element) {
		if (!$.metadata) {
			return {};
		}

		var meta = $.data(element.form, 'validator').settings.meta;
		return meta ?
			$(element).metadata()[meta] :
			$(element).metadata();
	},

	staticRules: function(element) {
		var rules = {};
		var validator = $.data(element.form, 'validator');
		if (validator.settings.rules) {
			rules = $.validator.normalizeRule(validator.settings.rules[element.name]) || {};
		}
		return rules;
	},

	normalizeRules: function(rules, element) {
		// handle dependency check
		$.each(rules, function(prop, val) {
			// ignore rule when param is explicitly false, eg. required:false
			if (val === false) {
				delete rules[prop];
				return;
			}
			if (val.param || val.depends) {
				var keepRule = true;
				switch (typeof val.depends) {
					case "string":
						keepRule = !!$(val.depends, element.form).length;
						break;
					case "function":
						keepRule = val.depends.call(element, element);
						break;
				}
				if (keepRule) {
					rules[prop] = val.param !== undefined ? val.param : true;
				} else {
					delete rules[prop];
				}
			}
		});

		// evaluate parameters
		$.each(rules, function(rule, parameter) {
			rules[rule] = $.isFunction(parameter) ? parameter(element) : parameter;
		});

		// clean number parameters
		$.each(['minlength', 'maxlength', 'min', 'max'], function() {
			if (rules[this]) {
				rules[this] = Number(rules[this]);
			}
		});
		$.each(['rangelength', 'range'], function() {
			if (rules[this]) {
				rules[this] = [Number(rules[this][0]), Number(rules[this][1])];
			}
		});

		if ($.validator.autoCreateRanges) {
			// auto-create ranges
			if (rules.min && rules.max) {
				rules.range = [rules.min, rules.max];
				delete rules.min;
				delete rules.max;
			}
			if (rules.minlength && rules.maxlength) {
				rules.rangelength = [rules.minlength, rules.maxlength];
				delete rules.minlength;
				delete rules.maxlength;
			}
		}

		// To support custom messages in metadata ignore rule methods titled "messages"
		if (rules.messages) {
			delete rules.messages;
		}

		return rules;
	},

	// Converts a simple string to a {string: true} rule, e.g., "required" to {required:true}
	normalizeRule: function(data) {
		if( typeof data === "string" ) {
			var transformed = {};
			$.each(data.split(/\s/), function() {
				transformed[this] = true;
			});
			data = transformed;
		}
		return data;
	},

	// http://docs.jquery.com/Plugins/Validation/Validator/addMethod
	addMethod: function(name, method, message) {
		$.validator.methods[name] = method;
		$.validator.messages[name] = message !== undefined ? message : $.validator.messages[name];
		if (method.length < 3) {
			$.validator.addClassRules(name, $.validator.normalizeRule(name));
		}
	},

	methods: {

		// http://docs.jquery.com/Plugins/Validation/Methods/required
		required: function(value, element, param) {
			// check if dependency is met
			if ( !this.depend(param, element) ) {
				return "dependency-mismatch";
			}
			if ( element.nodeName.toLowerCase() === "select" ) {
				// could be an array for select-multiple or a string, both are fine this way
				var val = $(element).val();
				return val && val.length > 0;
			}
			if ( this.checkable(element) ) {
				return this.getLength(value, element) > 0;
			}
			return $.trim(value).length > 0;
		},

		// http://docs.jquery.com/Plugins/Validation/Methods/remote
		remote: function(value, element, param) {
			if ( this.optional(element) ) {
				return "dependency-mismatch";
			}

			var previous = this.previousValue(element);
			if (!this.settings.messages[element.name] ) {
				this.settings.messages[element.name] = {};
			}
			previous.originalMessage = this.settings.messages[element.name].remote;
			this.settings.messages[element.name].remote = previous.message;

			param = typeof param === "string" && {url:param} || param;

			if ( this.pending[element.name] ) {
				return "pending";
			}
			if ( previous.old === value ) {
				return previous.valid;
			}

			previous.old = value;
			var validator = this;
			this.startRequest(element);
			var data = {};
			data[element.name] = value;
			$.ajax($.extend(true, {
				url: param,
				mode: "abort",
				port: "validate" + element.name,
				dataType: "json",
				data: data,
				success: function(response) {
					validator.settings.messages[element.name].remote = previous.originalMessage;
					var valid = response === true;
					if ( valid ) {
						var submitted = validator.formSubmitted;
						validator.prepareElement(element);
						validator.formSubmitted = submitted;
						validator.successList.push(element);
						validator.showErrors();
					} else {
						var errors = {};
						var message = response || validator.defaultMessage( element, "remote" );
						errors[element.name] = previous.message = $.isFunction(message) ? message(value) : message;
						validator.showErrors(errors);
					}
					previous.valid = valid;
					validator.stopRequest(element, valid);
				}
			}, param));
			return "pending";
		},

		// http://docs.jquery.com/Plugins/Validation/Methods/minlength
		minlength: function(value, element, param) {
			var length = $.isArray( value ) ? value.length : this.getLength($.trim(value), element);
			return this.optional(element) || length >= param;
		},

		// http://docs.jquery.com/Plugins/Validation/Methods/maxlength
		maxlength: function(value, element, param) {
			var length = $.isArray( value ) ? value.length : this.getLength($.trim(value), element);
			return this.optional(element) || length <= param;
		},

		// http://docs.jquery.com/Plugins/Validation/Methods/rangelength
		rangelength: function(value, element, param) {
			var length = $.isArray( value ) ? value.length : this.getLength($.trim(value), element);
			return this.optional(element) || ( length >= param[0] && length <= param[1] );
		},

		// http://docs.jquery.com/Plugins/Validation/Methods/min
		min: function( value, element, param ) {
			return this.optional(element) || value >= param;
		},

		// http://docs.jquery.com/Plugins/Validation/Methods/max
		max: function( value, element, param ) {
			return this.optional(element) || value <= param;
		},

		// http://docs.jquery.com/Plugins/Validation/Methods/range
		range: function( value, element, param ) {
			return this.optional(element) || ( value >= param[0] && value <= param[1] );
		},

		// http://docs.jquery.com/Plugins/Validation/Methods/email
		email: function(value, element) {
			// contributed by Scott Gonzalez: http://projects.scottsplayground.com/email_address_validation/
			return this.optional(element) || /^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))$/i.test(value);
		},

		// http://docs.jquery.com/Plugins/Validation/Methods/url
		url: function(value, element) {
			// contributed by Scott Gonzalez: http://projects.scottsplayground.com/iri/
			return this.optional(element) || /^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(value);
		},

		// http://docs.jquery.com/Plugins/Validation/Methods/date
		date: function(value, element) {
			return this.optional(element) || !/Invalid|NaN/.test(new Date(value));
		},

		// http://docs.jquery.com/Plugins/Validation/Methods/dateISO
		dateISO: function(value, element) {
			return this.optional(element) || /^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}$/.test(value);
		},

		// http://docs.jquery.com/Plugins/Validation/Methods/number
		number: function(value, element) {
			return this.optional(element) || /^-?(?:\d+|\d{1,3}(?:,\d{3})+)?(?:\.\d+)?$/.test(value);
		},

		// http://docs.jquery.com/Plugins/Validation/Methods/digits
		digits: function(value, element) {
			return this.optional(element) || /^\d+$/.test(value);
		},

		// http://docs.jquery.com/Plugins/Validation/Methods/creditcard
		// based on http://en.wikipedia.org/wiki/Luhn
		creditcard: function(value, element) {
			if ( this.optional(element) ) {
				return "dependency-mismatch";
			}
			// accept only spaces, digits and dashes
			if (/[^0-9 \-]+/.test(value)) {
				return false;
			}
			var nCheck = 0,
				nDigit = 0,
				bEven = false;

			value = value.replace(/\D/g, "");

			for (var n = value.length - 1; n >= 0; n--) {
				var cDigit = value.charAt(n);
				nDigit = parseInt(cDigit, 10);
				if (bEven) {
					if ((nDigit *= 2) > 9) {
						nDigit -= 9;
					}
				}
				nCheck += nDigit;
				bEven = !bEven;
			}

			return (nCheck % 10) === 0;
		},

		// http://docs.jquery.com/Plugins/Validation/Methods/accept
		accept: function(value, element, param) {
			param = typeof param === "string" ? param.replace(/,/g, '|') : "png|jpe?g|gif";
			return this.optional(element) || value.match(new RegExp(".(" + param + ")$", "i"));
		},

		// http://docs.jquery.com/Plugins/Validation/Methods/equalTo
		equalTo: function(value, element, param) {
			// bind to the blur event of the target in order to revalidate whenever the target field is updated
			// TODO find a way to bind the event just once, avoiding the unbind-rebind overhead
			var target = $(param).unbind(".validate-equalTo").bind("blur.validate-equalTo", function() {
				$(element).valid();
			});
			return value === target.val();
		}

	}

});

// deprecated, use $.validator.format instead
$.format = $.validator.format;

}(jQuery));

// ajax mode: abort
// usage: $.ajax({ mode: "abort"[, port: "uniqueport"]});
// if mode:"abort" is used, the previous request on that port (port can be undefined) is aborted via XMLHttpRequest.abort()
(function($) {
	var pendingRequests = {};
	// Use a prefilter if available (1.5+)
	if ( $.ajaxPrefilter ) {
		$.ajaxPrefilter(function(settings, _, xhr) {
			var port = settings.port;
			if (settings.mode === "abort") {
				if ( pendingRequests[port] ) {
					pendingRequests[port].abort();
				}
				pendingRequests[port] = xhr;
			}
		});
	} else {
		// Proxy ajax
		var ajax = $.ajax;
		$.ajax = function(settings) {
			var mode = ( "mode" in settings ? settings : $.ajaxSettings ).mode,
				port = ( "port" in settings ? settings : $.ajaxSettings ).port;
			if (mode === "abort") {
				if ( pendingRequests[port] ) {
					pendingRequests[port].abort();
				}
				return (pendingRequests[port] = ajax.apply(this, arguments));
			}
			return ajax.apply(this, arguments);
		};
	}
}(jQuery));

// provides cross-browser focusin and focusout events
// IE has native support, in other browsers, use event caputuring (neither bubbles)

// provides delegate(type: String, delegate: Selector, handler: Callback) plugin for easier event delegation
// handler is only called when $(event.target).is(delegate), in the scope of the jquery-object for event.target
(function($) {
	// only implement if not provided by jQuery core (since 1.4)
	// TODO verify if jQuery 1.4's implementation is compatible with older jQuery special-event APIs
	if (!jQuery.event.special.focusin && !jQuery.event.special.focusout && document.addEventListener) {
		$.each({
			focus: 'focusin',
			blur: 'focusout'
		}, function( original, fix ){
			$.event.special[fix] = {
				setup:function() {
					this.addEventListener( original, handler, true );
				},
				teardown:function() {
					this.removeEventListener( original, handler, true );
				},
				handler: function(e) {
					var args = arguments;
					args[0] = $.event.fix(e);
					args[0].type = fix;
					return $.event.handle.apply(this, args);
				}
			};
			function handler(e) {
				e = $.event.fix(e);
				e.type = fix;
				return $.event.handle.call(this, e);
			}
		});
	}
	$.extend($.fn, {
		validateDelegate: function(delegate, type, handler) {
			return this.bind(type, function(event) {
				var target = $(event.target);
				if (target.is(delegate)) {
					return handler.apply(target, arguments);
				}
			});
		}
	});
}(jQuery));
(function($) {
	function DateRange(el, options) {
		options = $.extend(this.defaults, options);
		this.init(el, options);
	}
	$.extend(DateRange.prototype, {
		setDefaults : function(settings) {
			extendRemove(this._defaults, settings || {});
			return this;
		},
		_newInst : function($input, o) {

		}
	});

	DateRange.prototype = {

		defaults : {

		},
		init : function(el, options) {
			this.options = options;
			this.$el = $(el);
			this.$el.bind({
				blur : function() {
					console.log('lost focus');
				},
				focus : function() {
					console.log('focus');
				}
			})
		}
	}
	$.fn.daterange = function(o) {
		o = o || {};
		var tmp_args = arguments;
		if (typeof o == 'object')
			tmp_args[0] = $.extend(o, {
				timeOnly : true
			});
		return $(this).each(function() {
			$.fn.datepicker.apply($(this), tmp_args);
		});
	}
})(jQuery);(function($){
	function Dockable(el,options){
		options = $.extend(this.defaults,options);
		this.init(el,options)
		return this;
	} 
	Dockable.prototype = {
			defaults : {
				location :'left',
				url:null,
				renderTo:'body'
			},
			location:function(l) {
				
			},
			
			init : function(el,options) {
				this.options  =options;
				if (el) {
					this.$el = $(el);
				}else{
					var tpl = 
							 '<div class="dockable" id="'+ options.id +'">' 
							+'	<div class="handle">'
							+'		<div class="handle-icon-container">'
							+'			<div class="handle-icon">'
							+'				<i class="icon"></i>'
							+'			</div>'
							+'		</div>'
							+'		<div class="delim"></div>'
							+'	</div>'
							+'	<div class="content"></div>'
							+'</div>';
					
					this.$el=$(tpl).appendTo(options.renderTo);
					if (options.id) {
						this.$el.attr('id',options.id);
					}
				}
			
				this.options = $.extend(options || {},this.defaults || {});
				this.$title = this.$el.find('.handle .handle-icon');
				this.$title.click($.proxy(this.toggle,this));
				this.$content = this.$el.find('.content');
				this.$delim = this.$el.find('.handle .delim');
				this.calculatePosition();
				this.reloadContent();
			},
			reloadContent : function() {
				if (this.options.url) {
					this.$content.html('Loading...');
					$(this).addClass('loading');
					this.$content.load(this.options.url,function(){
						$(this).removeClass('loading');
					});
				} 
			},
			width:function() {
				return this.$el.width();
			},
			getPropertyFromAttr : function(prop,def) {
				var p=this.$el.attr('data-dockable-'+prop);
				return p ? p : (def ? def :  this.options[prop]);
			},
			calculatePosition : function() {
				var loc= this.getPropertyFromAttr('location');
				this.$el.addClass(loc);
				switch (loc) {
					case 'top':
						this.$content.remove().prependTo(this.$el);
						this.$delim.remove().prependTo(this.$el.find('.handle'));
						break;
				}
				
			},
			getId:function() {
				return this.$el.attr('id');
			},
			trigger : function(o) {
				if (this.options.on && this.options.on[o]) {
					this.options.on[o].call(this,o);
				}
			},
			hide : function() {
				this.trigger('beforehide');
				this.$el.removeClass('active');
				this.trigger('hide');
			},
			setContent : function(content) {
				if (typeof(content)==='function') {
					content = content.call();
				} 
				this.$content.html(content);
			},
			show:function() {
				this.trigger('beforeshow');
				this.$el.addClass('active');
				this.trigger('show');
			},
			toggle:function() {
				if (this.$el.hasClass('active')) {
					this.hide();
				}else{
					this.show();
				}
				//this.$content.hide();
			}
	}
	//direct creation
	$.dockable = function(options) {
		return new Dockable(null, options);
	};
	$.fn.dockable = function(option,arg) {
		return this.each(function(){
			var $this = $(this), data = $this.data('dockable'), options = typeof option == 'object' && option
			if (!data)
				$this.data('dockable', (data = new Dockable(this, options)))
			if (typeof option == 'string')
				data[option](arg);
			return $this.data('dockable');
		});
	}
})(jQuery);(function ($) {
    var expandableData = new Array;
    var defaultConfig = {
        groups:"all",
        hideAllGroupOnExpand:true,
        hideHeaderOnExpand:false,
        header:null,
        body:null,
        expanded:false,
        style:"expandable",
        floatui:false
    };
    $.fn.expandable = function (options) {
        options = options || {};
        var config = $.extend(defaultConfig, options);
        this.each(function () {
            var cfg = config;
            expandableData.push(new $.expandable(this, cfg));
        });
        return this;
    };
    $.expandable = function (e, config) {
        if (!e)
            return this;
        config = $.extend(defaultConfig, config || {});
        var self = $(e);
        var me = this;
        if (!self.attr("id"))
            self.attr("id", "expandable-" + Math.floor(Math.random() * 16).toString(16).toUpperCase());
        this.options = $.extend({
            body:"#" + self.attr("id") + "-expander-body"
        }, config);
        if (!this.options.body)
            this.options.body = "#" + self.attr("id") + "-expander-body";
        this.expanded = this.options.expanded;
        this.groups = config.groups;
        this.content = null;
        if (config.style)
            self.addClass("expandable");
        self.addClass("rounded");
        if (config.header) {
            this.head = self.find(config.header);
            if (this.head.find("button .button-expander")) {
                tpl = '<a class="button-expander" href="javascript:void(0)"><i class="icon"></i></a>';
                $(tpl).appendTo(this.head);
            }
        } else {
            var header = self.find(".expandable-header");
            if (header.length === 0)
                header = $('<div class="expandable-header"><a class="button-expander" href="javascript:void(0)"><i class="icon"></i></a></div>').prependTo(self);
            config.title = config.title || self.attr("title");
            if (config.title) {
                title = $("<div/>").html(config.title).text();
                tpl = '<div class="expander-content">' + title + "</div>";
                $(tpl).appendTo(header);
            }
            this.head = header;
        }
        this.button = this.head.find(".button-expander");
        var found = false;
        var c=undefined;
        if (self.find(this.options.body).length === 0)
            try {
                c = $("#" + this.options.body);
                found = c.length > 0;
            } catch (e) {
                try {
                    c = $(this.options.body);
                    found = c.length > 0;
                } catch (e) {
                }
            }
        else {
            c = self.find(this.options.body);
            found = c.length > 0;
        }
        if (!found) {
            c = $('<div id="' + this.options.body + '">' + this.options.content + "</div>").appendTo(self);
            c.hide();
        }
        if (this.options.style)
            c.addClass(this.options.style + "-body ui-widget-content");
        if (this.options.floatui)
            c.css({
                position:"absolute",
                top:self.offset().top + this.head.height(),
                left:0,
                right:0,
                bottom:0,
                "z-index":1001
            });
        this.content = c;
        this.self = self;
        this.head.bind("dblclick", function () {
            me.toggle()
        });
        this.button.bind("click", function () {
            me.toggle()
        });
        this.setup(e);
        if (found && !this.options.contentUrl)
            if (this.options.expanded)
                this.expand(true);
            else
                this.collapse(true)
    };
    var $ex = $.expandable;
    $ex.fn = $ex.prototype = {
        expandable:"version 1.0b"
    };
    $ex.fn.extend = $ex.extend = $.extend;
    $ex.fn.extend({
        setup:function (e) {
        },
        setExpanded:function (value) {
            if (value) {
                this.self.addClass("on");
                this.head.addClass("off");
                this.button.addClass("on");
                this.content.addClass("on");
            } else {
                this.self.removeClass("on");
                this.head.removeClass("off");
                this.button.removeClass("on");
                this.content.removeClass("on");
            }
            this.expanded = value;
        },
        collapse:function (force) {
            if (!force && !this.expanded)
                return;
            this.onAction = true;
            if (this.options.hideHeaderOnExpand)
                this.head.find(".expander-content").show();
            this.content.slideUp().hide();
            this.setExpanded(false)
        },
        loading:function () {
            var l = this.head.find(".loading-small");
            if (l.length == 0)
                l = $('<div class="loading-small"></div>').prependTo(this.head);
            return l
        },
        expand:function (force) {
            if (!force && this.expanded)
                return;
            this.onAction = true;
           // var self = this;
            if (this.options.hideAllGroupOnExpand)
                $.each(expandableData, function (k, v) {
                    if (v.groups === self.groups)
                        v.collapse()
                });
            if (this.options.hideHeaderOnExpand)
                this.head.find(".expander-content").hide();
            if (this.content.html() === "")
                if (this.options.contentUrl)
                    this.load(this.options.contentUrl);
                else
                    this.content.html("");
            else {
                this.content.slideDown().show();
                this.setExpanded(true)
            }
        },
        load:function (url) {
            this.loading().show();
            var self = this;
            this.content.load(url, function (response, status, xhr) {
                self.loading().hide();
                self.content.slideDown().show();
                self.setExpanded(true)
            })
        },
        toggle:function () {
            if (!this.expanded)
                this.expand();
            else
                this.collapse();
            this.self.resize();
        }
    });
})(jQuery);(function($) {
	"use strict"
	var popupDialog = function(element, options) {
		options = $.extend(options || {}, $.fn.popupDialog.defaults);
		this.options = options;
		if (element) {
			this.init(element);
		}
		return this;
	}

	popupDialog.prototype = {
		constructor : popupDialog,
		init : function(element, options) {
			if (!this.initialized) {
				this.initialized = true;
				this.$element = $(element);
				this.enabled = true;
				this.$container = $('#popup-dialog');
				if (this.$container.length === 0) {
					this.$container = $('<div id="popup-dialog" class="popup-dialog">' + this.options.template + '</div>').appendTo('body').hide();
				}
				var me = this;
				this.$container.find('.close').click(function() {
					me.hide();
					$(me).trigger('close');
				});
			} else {
				this.$element = $(element);
			}
			this.calculatePosition();
		},
		setContent : function(o) {
			this.content = o;
			this.calculatePosition();
		},
		getContent : function(el) {
			if (!this.$element) {
				return '';
			}
			if (this.options.contentEl) {
				if (typeof (this.options.contentEl) === 'string') {
					this.options.contentEl = $(this.options.contentEl).show();
				}
				$(this.$element).data('data-popup', this.options.contentEl);
			} else if (this.content) {
				var content = this.content;
				if (typeof this.content == 'function') {
					content = this.content.content.call(this);
				}
				$(this.$element).data('data-popup', content);
			} else if (this.options.url) {
				var dc = $(this.$element).data('data-popup');
				if (!dc) {
					var me = this;
					$.ajax({
						url : this.options.url,
						success : function(data) {
							$(me.$element).data('data-popup', data);
							me.calculatePosition();
						},
						error : function() {
							$(me.$element).data('data-popup', 'error');
							me.calculatePosition();
						}
					});
					return '<span>Loading...</span>';
				}
			} else {
				return '<span></span>';
			}
			return $(this.$element).data('data-popup');
		},

		calculatePosition : function() {
			var content, placement, inside, tp, pos, actualWidth, actualHeight;
			content = this.getContent();
			if (this.options.title) {
				this.$container.find('.title')[$.type(this.options.title) == 'object' ? 'append' : 'html'](this.options.title).show();
			} else {
				this.$container.find('.title').hide();
			}
			this.$container.find('.content').empty().html(content);
			var arrow = this.$container.find('.arrow');
			placement = typeof this.options.placement == 'function' ? this.options.placement.call(this, this.$container, this.$element[0]) : this.options.placement;
			inside = /in/.test(placement);

			//calculate based on Content;
			content = $(content);
			actualWidth = content.width() ? content.width() : this.options.defaultWidth;
			actualHeight = content.height() ? content.height() : this.options.defaultHeight;
			//makesure container has valid width
			this.$container.css({
				width : actualWidth + 'px',
				height : actualHeight + 'px'
			});
			pos = this.$element.offset();
			pos.height = this.$element[0].offsetHeight;
			pos.width = this.$element[0].offsetWidth;
			this.updatePosition(placement, pos, actualWidth, actualHeight);
		},
		updatePosition : function(placement, pos, actualWidth, actualHeight) {
			var inside = /in/.test(placement);
			var tp = {
				width : actualWidth,
				height : actualHeight
			};
			switch (inside ? placement.split(' ')[1] : placement) {
				case 'bottom':
					tp.top = pos.top + pos.height, tp.left = pos.left + pos.width / 2 - actualWidth / 2
					break;
				case 'top':
					tp.top = pos.top - actualHeight, tp.left = pos.left + pos.width / 2 - actualWidth / 2;
					break
				case 'left':
					tp.top = pos.top + pos.height / 2 - actualHeight / 2;
					tp.left = pos.left - actualWidth;
					break
				case 'right':
					tp.top = pos.top + pos.height / 2 - actualHeight / 2;
					tp.left = pos.left + pos.width;

					break
			}
			//Reupdate Position
			this.$container.addClass(placement).css(tp).addClass('in');
			var inner = this.$container.find('.inner');
			if (this.$container.find('.title').is(':visible')) {
				//inner.find('.content').height(actualHeight - (this.$container.find('.title').height() + 30));
			}
			inner.css({
				width : actualWidth,
				height : actualHeight
			});
		},
		setOptions : function(o) {
			if ($.type(o) === 'object') {
				if (o.url && (this.options.url !== o.url)) {
					//force reupdate content from remote
					$(this).data('data-content', null);
				}
				if (o.element) {
					this.init(o.element);
				}
				this.options = $.extend(this.options, o);
			}
		},
		show : function(o) {

			this.calculatePosition();
			this.$element.addClass('active');
			this.$container.show();
		},
		hide : function() {
			this.$element.removeClass('active');
			this.$container.hide();
		},
		toggle : function() {
			if (!this.$container.is(":visible")) {
				this.show();
			} else {
				this.hide();
			}
		},
		setTitle : function(title) {
			this.options.title = title;
			this.update();
		}
	};

	$.fn.popupDialog = function(option, arg) {
		return this.each(function() {
			var $this = $(this), data = $this.data('popupdialog'), options = typeof option == 'object' && option
			if (!data)
				$this.data('popupdialog', (data = new popupDialog(this, options)))
			if (typeof option == 'string')
				data[option](arg);
			return $this.data('popupdialog');
		});
	}
	$.popupDialog = popupDialog;
	$.fn.popupDialog.Constructor = popupDialog;
	$.fn.popupDialog.defaults = {
		placement : 'bottom',
		defaultWidth : '150',
		defaultHeight : '150',
		trigger : 'manual',
		template : '<div class="arrow"></div><div class="inner"><div class="icon icon-remove close"/><h3 class="title"></h3><div class="content"><p></p></div></div>'
	}
})(jQuery);(function () {
    $.fn.scrollbar = function (options) {
        options = $.extend({
            scrollHeight:150
        }, options || {});
        return this.each(function () {
            var $$ = $(this);
            var content = $$.children(0).css({
                position:"relative"
            });
            if (!$$.data("scrollbar")) {
                var sc = $$.find(".scrollbar");
                if (sc.length === 0) {
                    sc = $('<div class="scrollbar"></div>').prependTo($$);
                    s = $("<div></div>").appendTo(sc);
                    sc.css({
                        padding:0,
                        margin:0,
                        backgroundColor:"rgba(118, 118, 118, 1)",
                        cssFloat:"right",
                        width:"6px"
                    });
                    s.css({
                        height:25 + "px",
                        position:"relative",
                        border:"1px solid rgba(118, 118, 118, 1)",
                        borderRadius:"2px",
                        backgroundColor:"red",
                        cursor:"pointer"
                    });
                    s.draggable({
                        containment:"parent",
                        axis:"y",
                        start:function () {
                        },
                        drag:function (event, ui) {
                            recalculate(ui.position.top)
                        },
                        stop:function (event, ui) {
                            recalculate(ui.position.top)
                        }
                    });
                    s.bind("mousewheel", function () {
                        recalculate()
                    })
                }
                function recalculate(top) {
                    sc.css({
                        height:$$.height() + "px"
                    });
                    content.css({
                        top:top * -1
                    })
                }

                recalculate();
                this.recalculate = recalculate;
                $$.data("scrollbar", this)
            } else
                $$.data("scrollbar").recarculate();
            return this
        })
    }
})(jQuery);/* ========================================================
 * bootstrap-tab.js v2.0.2
 * http://twitter.github.com/bootstrap/javascript.html#tabs
 * ========================================================
 * Copyright 2012 Twitter, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ======================================================== */

!function($) {

	"use strict"

	/*
	 * TAB CLASS DEFINITION ====================
	 */

	var Tab = function(element) {
		this.element = $(element)
	}

	Tab.prototype = {

		constructor : Tab

		,
		show : function() {
			var $this = this.element, $ul = $this.closest('ul:not(.dropdown-menu)'), selector = $this.attr('data-target'), previous, $target

			if (!selector) {
				selector = $this.attr('href')
				selector = selector && selector.replace(/.*(?=#[^\s]*$)/, '') //strip for ie7
			}
			if ($this.parent('li').hasClass('active'))
				return

			

			previous = $ul.find('.active a').last()[0]

			$this.trigger({
				type : 'show',
				relatedTarget : previous
			})

			$target = $(selector)

			this.activate($this.parent('li'), $ul)
			this.activate($target, $target.parent(), function() {
				$this.trigger({
					type : 'shown',
					relatedTarget : previous
				})
			});
			var url = $this.attr('data-url');
			if (url) {
				if (!$this.attr('data-loaded')) {
					$target.addClass('loading');
					$.ajax({
						url : url,
						success : function(data) {
							$this.attr('data-loaded', true);
							$target.html(data);
							$target.removeClass('loading');
						}
					});
				}
			}
		}

		,
		activate : function(element, container, callback) {
			var $active = container.find('> .active'), transition = callback && $.support.transition && $active.hasClass('fade')

			function next() {
				$active.removeClass('active').find('> .dropdown-menu > .active').removeClass('active')

				element.addClass('active')

				if (transition) {
					element[0].offsetWidth // reflow for transition
					element.addClass('in')
				} else {
					element.removeClass('fade')
				}

				if (element.parent('.dropdown-menu')) {
					element.closest('li.dropdown').addClass('active')
				}

				callback && callback()
			}

			transition ? $active.one($.support.transition.end, next) : next()

			$active.removeClass('in')
		}
	}

	/*
	 * TAB PLUGIN DEFINITION =====================
	 */

	$.fn.tab = function(option) {
		return this.each(function() {
			var $this = $(this), data = $this.data('tab')
			if (!data)
				$this.data('tab', (data = new Tab(this)))
			if (typeof option == 'string')
				data[option]()
		})
	}

	$.fn.tab.Constructor = Tab

	/*
	 * TAB DATA-API ============
	 */

	$(function() {
		$('body').on('click.tab.data-api', '[data-toggle="tab"], [data-toggle="pill"]', function(e) {
			e.preventDefault()
			$(this).tab('show')
		})
	})

}(window.jQuery);(function () {
    function tooltip(el, config) {
        var config = $.extend({
            title:""
        }, config || {});
        var el = $(el);
        var tip = el.find(".tip");
        var title = config.title ? config.title : el.attr("title");
        if (tip.length === 0)
            tip = $('<div class=".tip">' + title + "</div>").css({
                position:"absolute"
            }).appendTo($(el));
        tip.hide();
        $(el).bind({
            mouseenter:function (e) {
                tip.show();
            },
            mouseleave:function (e) {
                tip.hide();
            },
            mousemove:function (e) {
                var mousex = e.pageX + 20;
                var mousey = e.pageY + 20;
                var tipWidth = tip.width();
                var tipHeight = tip.height();
                var tipVisX = $(window).width() - (mousex + tipWidth);
                var tipVisY = $(window).height() - (mousey + tipHeight);
                if (tipVisX < 20)
                    mousex = e.pageX - tipWidth - 20;
                if (tipVisY < 20)
                    mousey = e.pageY - tipHeight - 20;
                tip.css({
                    top:mousey,
                    left:mousex
                })
            }
        })
    }

    $.fn.tooltip = function (config) {
        $(this).each(function () {
            new tooltip(this, config)
        })
    }
})(jQuery);(function($) {
	var Uploader = function(el,options) {
		
	}
	Uploader.prototype = {
			defaults: {
				allowMulti : true
			}
	}
	$.fn.uploader = function(options) {
		return this.each(function() {
			var $this = $(this), data = $this.data('uploader')
			if (!data)
				$this.data('uploader', (data = new Uploader(this,options)))
			if (typeof option == 'string')
				data[option]()
		}); 
	};
})(jQuery); (function($) {

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
                            $this.cache = $this.cache || {};
                            $this.cache[term] = data;
                            if (xhr === lastXhr) {
                              response(data);
                            }
                          });
                }
              });
            });
        form.find('[data-input="datetime"]').each(function() {

        });
        form.find('[data-input="daterange"]').each(function() {
          //console.log(this);
          $(this).daterange();
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
})(jQuery);(function($) {
	function log() {
		if (!$.fn.ajaxSubmit.debug)
			return;
		var msg = "[jquery.form] " + Array.prototype.join.call(arguments, "");
		if (window.console && window.console.log)
			window.console.log(msg);
		else if (window.opera && window.opera.windpostError)
			window.opera.postError(msg);
	}

	window.log = log;

	$.openOverlay = function(options) {
		if (typeof(options) === 'string') {
			options = {
					url : options
			}
		}
		var m = $('#cgaf-modal');
		var options = $.extend(options, options || {
			title : 'test',
			backdrop : true,
			show : false
		});
		if (m.length == 0) {
			m = $('<div id="cgaf-modal" class="modal fade in"/>').appendTo('body');
			$('<div class="modal-header"><a class="icon-remove close" data-dismiss="modal"></a><h3></h3></div><div class="modal-body"></div> <div class="modal-footer"></div>').appendTo(m);
		}
		var b = m.find('.modal-body').empty();

		var modal = new $.fn.modal.Constructor(m, options);
		if (options.title) {
			m.find('.modal-header h3').html(options.title);
		} else {
			m.find('.modal-header h3').hide();
		}
		if (options.url) {
			b.addClass('loading');
			var url =cgaf.url(options.url,{
				__uimode:'dialog'
			}).toString(); 
			b.load(url, function(data,s,xhr) {
				if (options.callback) {
					options.callback.call(modal,b);
				}
				b.removeClass('loading');
			});
		} else if (options.contents) {
			var content = options.contents;
			if (typeof(content) ==='object') {
				var tmp = '<ul>';
				for (var i in content) {
					if (typeof (content[i]) ==='string') {
						tmp +='<li>'+content[i]+'</li>';
					}else if (typeof(content[i])==='object') {
						
						for (var j in content[i]) {
							tmp += '<li>';
							tmp += content[i][j];
							tmp += '</li>';
						}
					} 
				}
				tmp +='</ul>'
				content =tmp;
			}
			b.html(content);
		}
		modal.show();
	};

	$.showErrorMessage = function(msg) {
		$.openOverlay({
			title : 'Error',
			contents : msg
		});
	};
	
	$.fn.openOverlay = $.openOverlay;
	$.filterProperties = function(r, o) {
		var retval = {};
		for ( var s in r) {
			var value = o[s];
			if (typeof value !== "undefined" && value !== null)
				retval[s] = value;
		}
		return retval;
	};
	function Q() {
		var _finish = [], _queue = [];
		this._started = false;
		return {
			onFinish : function(callback) {
				_finish.push(callback);
				this._started = false;
			},
			add : function(callback) {
				_queue.push(callback);
			},
			next : function() {
				if (_queue.length === 0) {
					$(_finish).each(function() {
						this.call();
					});
					return

				}
				this._started = true;
				callback = _queue.shift();
				callback.call(this);
			},
			start : function() {
				this.next();
			}
		};
	}

	function CGAFEvent() {
		var _events = [];
		return {
			Event : function(e, s, a) {
				var propagationStopped = false;
				this.EventName = e;
				this.Source = s;
				this.args = a;
				this.stopPropagation = function() {
					propagationStopped = true;
				};
				this.propagationStoped = function() {
					return propagationStopped;
				}
			},
			bind : function(event, callback) {
				_events.push({
					name : event,
					callback : callback
				});
			},
			trigger : function(event, data) {
				for ( var e in _events) {
					var ev = _events[e];
					if (ev.name === event)
						ev.callback(data);
				}
			},
			unbind : function(event, data) {
				for ( var e in _events) {
					var ev = _events[e];
					try {
						if (ev.name === event && ev.callback == data) {
							_events.splice(e, 1);
							break;
						}
					} catch (e) {
						console.log(e);
					}
				}
			}
		};
	}

	var CGAF = function() {
		var _queue = [], pluginLoader = new Q, cgafconfig = {}, _queueRunning = false, loadedJQ = {
			"jquery.mousewheel" : ""
		}, loadedCSS = {}, _event = new CGAFEvent, _module = {};

		function CGAF(options) {
			if (window.cgaf)
				return window.cgaf;
			return new CGAF.fn.init(options);
		}

		CGAF.fn = CGAF.prototype = {
			defaults: {
				ismobile:false
			},
			init : function(options) {
				var me = this;
				cgafconfig= $.extend(this.defaults,options || {});
				var lo = $("#loading");
				if (lo.length === 0)
					lo = $('<div id="loading" class="loading"><span>loading...</span></div>').appendTo("body").hide();
				lo.bind("ajaxSend", function() {
					$(this).show();
				}).bind("ajaxComplete", function() {
					me.defaultUIHandler();
					$(this).hide();
				});
				this.Events = {
					CGAF_READY : "cgaf-ready",
					STATUS_CHANGED : "status-changed"
				};
			},
			getModuleInfo : function(m) {
				if (typeof m === "object")
					m = m._m;
				for ( var key in _module) {
					var obj = _module[key];
					if (isNaN(m)) {
						if (obj.mod_dir.toLowerCase() == m.toLowerCase())
							return obj
					} else if (key.toString().toLowerCase() == m.toString().toLowerCase())
						return obj
				}
			},
			getJSAsync : function(url, config) {
				var callback=null;
				if (typeof(url) ==='string') url = [url];
				if (typeof(config) ==='function') callback =config;
				var loader = function(src, handler) {
					var script = document.createElement("script");
					script.src = src;
					script.onload = script.onreadystatechange = function() {
						script.onreadystatechange = script.onload = null;
						handler();
					}
					var head = document.getElementsByTagName("head")[0];
					(head || document.body).appendChild(script);
				};
				(function() {
					if (url.length != 0) {
						loader(url.shift(), arguments.callee);
					} else {
						callback && callback();
					}
				})();

				/*var script = document.createElement("script");
				script.type = "text/javascript";
				var u = cgaf.url(url, config);
				script.src = u.toString();
				document.body.appendChild(script);*/
			},
			getJS : function(scripts, onComplete, id) {
				var i = 1;
				var ii = typeof scripts !== "string" ? scripts.length : 1;

				function onScriptLoaded(data, response) {
					if (i++ == ii)
						if (typeof onComplete !== "undefined")
							onComplete();
				}

				function onScriptError() {
					cgaf.log(arguments);
					i++;
				}

				if (typeof scripts === "string")
					try {
						this.getJSAsync(scripts, onScriptLoaded);
						//$.getScript(scripts, onScriptLoaded)
					} catch (e) {
						onScriptError(e);
					}
				else if (typeof scripts === "object")
					for ( var s in scripts) {
						var sc = scripts[s];
						if (typeof sc !== "string")
							continue;
						try {
							this.getJSAsync(scripts, onScriptLoaded);
							//$.getScript(sc, onScriptLoaded);
						} catch (e) {
							onScriptError(e);
						}
					}
				else
					cgaf.log(typeof scripts);
			},
			ui : {},
			rescheduleQueue : function() {
				if (_queueRunning)
					return;
				if (_queue.length == 0)
					return;
				_queueRunning = true;
				var q = _queue.shift();
				var old = q.data.callback || function() {
				};
				var me = this;
				q.data.callback = function() {
					try {
						old.apply(this, arguments)
					} catch (e) {
					}
					_queueRunning = false;
					me.rescheduleQueue()
				};
				q.callback.call(this, q.data)
			},
			isJQPluginLoaded : function(js) {
				return loadedJQ.hasOwnProperty(js)
			},
			loadStyleSheet : function(url) {
				if (Object.keys(loadedCSS).length == 0)
					$("head > link").each(function(key, item) {
						var href = $(item).attr("href");
						loadedCSS[href] = true;
					});
				if (loadedCSS.hasOwnProperty(url))
					return;
				loadedCSS[url] = true;
				console.log(url);
				$("head").append($('<link rel="stylesheet" type="text/css" />').attr("href", url))
			},
			loadJQPlugin : function(jq, callback) {
				if (typeof jq === "object") {
					var self = this;
					pluginLoader.onFinish(callback);
					$.each(jq, function(k, item) {
						pluginLoader.add(function() {
							self.loadJQPlugin(item, function() {
								pluginLoader.next()
							})
						})
					});
					pluginLoader.start();
					return

				}
				if (this.isJQPluginLoaded(jq)) {
					this.trigger("onpluginloaded", jq);
					if ($.isFunction(callback))
						callback.call(this);
					return

				}
				version = this.getConfig("jq.version", "latest");
				var url = this.getConfig("jqurl", this.getConfig("asseturl", "")) + "js/jQuery/plugins/" + jq + ".js";
				loadedJQ[jq] = url;
				this.require(url, callback)
			},
			require : function(ns, callback) {
				this.getJS(ns, function() {
					if (typeof callback !== "undefined")
						callback.call(this, arguments)
				})
			},
			getJSON : function(url, data, callback, err) {
				var me = this;
				data = $.extend({
					__data : "json"
				}, data || {});
				return jQuery.get(url, data, function(d) {
					var j;
					try {
						j = (new Function("return " + d))()
					} catch (e) {
						if (err) {
							err.call(this, e, d)
						}else{
						  cgaf.log(e.toString() + ":" + d);
	            me.showError("Invalid JSON Data<br/>" + d);
						}
						return false
					}
					if (callback)
						callback.call(this, j)
				}, "text")
			},
			getJSONApp : function(data, callback) {
				var appurl = cgaf.getConfig("appurl");
				return jQuery.get(appurl, data, function(d) {
					if (typeof callback !== "undefined")
						callback.call(this, (new Function("return " + d))())
				}, "text");
			},
			ErrorType : {
				Notice : "error-type-notice",
				Warning : "error-type-warning",
				Error : "error-type-error"
			},
			defaultUIHandler : function() {
				if (!$("#sysmessage").attr("handled"))
					$("#sysmessage").bind("click", function() {
						$(this).hide();
					}).attr("handled", true);
				$("a[rel=__overlay]").each(function() {
					if (!$(this).attr("__overlay")) {
						$(this).attr("__overlay", 1);
						$(this).click(function(e) {
							e.preventDefault();
							$.openOverlay($(this).attr("href"));
						});
					}
				});
				$("a[rel=__confirm]").each(function() {
					if (!$(this).attr("__confirm")) {
						$(this).attr("__confirm", 1);
						$(this).click(function(e) {
							e.preventDefault();
							var title = $(this).attr("ctitle");
							if (!title)
								title = $(this).attr("title");
							var me = $(this);
							cgaf.confirm(title, function() {
								var url = $.url(me.attr("href"));
								url.param("__confirm", 1);
								$.openOverlay(url.toString());
							});
						});
					}
				});
			},
			setReady : function() {
				this.defaultUIHandler();
				this.trigger(this.Events.CGAF_READY);
			},
			trigger : function() {
				_event.trigger.apply(this, arguments);
			},
			bind : function() {
				_event.bind.apply(this, arguments);
			},
			Windows : function() {
				return {
					open : function(url) {
						return $.openOverlay(url);
					}
				};
			},
			Plugins : function() {
				return {
					select : function() {
					}
				};
			},
			setStatus : function(msg) {
				cgaf.trigger(cgaf.Events.STATUS_CHANGED, msg);
			},
			setConfig : function(name, value) {
				if (typeof value === "undefined") {
					var self = this;
					$.each(name, function(k, v) {
						self.setConfig(k, v);
					});
				} else
					cgafconfig[name] = value;
			},
			confirm : function(msg, callback) {
				var dlf = $("#confirm-dialog");
				if (dlf.length === 0)
					dlf = $('<div id="confirm-dialog"></div>').appendTo("body");
				dlf.html(msg);
				dlf.dialog({
					modal : true,
					buttons : {
						Confirm : function() {
							if (callback)
								callback.call(this);
							$(this).dialog("close");
						},
						Cancel : function() {
							$(this).dialog("close")
						}
					}
				});
				dlf.dialog("open")
			},
			dateFromISO : function(date, format) {
				date = new Date(date);
				return date.format(format);
			},
			showError : function(msg) {
				$.showErrorMessage(msg);
			},
			getConfig : function(name, def) {
				return typeof cgafconfig[name] === "undefined" ? def : cgafconfig[name];
			},
			log : function(args, etype) {
				window.log(arguments);
			},
			url : function(uri, data) {
				var r = new $.url(uri);
				r.setParam(data);
				return r;
			},
			Queue : function(id, callback, data) {
				_queue.push({
					id : id,
					data : data,
					callback : callback
				});
				this.rescheduleQueue();
			},
			toJSON : function(o) {
				if (typeof o === "string")
					try {
						return (new Function("return " + o))();
					} catch (e) {
						return null;
					}
				cgaf.log($(this));
				return o;
			},
			Socket : function() {
			}
		};
		CGAF.fn.init.prototype = CGAF.fn;
		return CGAF;
	}();
	// window.Util = Util;
	window.cgaf = new CGAF
})(jQuery);
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

})(jQuery);/**
 * http://github.com/valums/file-uploader
 * 
 * Multiple file upload component with progress-bar, drag-and-drop. © 2010
 * Andrew Valums ( andrew(at)valums.com )
 * 
 * Licensed under GNU GPL 2 or later and GNU LGPL 2 or later, see license.txt.
 */

//
// Helper functions
//
var qq = qq
    || {};

/**
 * Adds all missing properties from second obj to first obj
 */
qq.extend = function(first, second) {
  for ( var prop in second) {
    first[prop] = second[prop];
  }
};

/**
 * Searches for a given element in the array, returns -1 if it is not present.
 * 
 * @param {Number}
 *          [from] The index at which to begin the search
 */
qq.indexOf = function(arr, elt, from) {
  if (arr.indexOf)
    return arr.indexOf(elt, from);

  from = from || 0;
  var len = arr.length;

  if (from < 0)
    from += len;

  for (; from < len; from++) {
    if (from in arr
        && arr[from] === elt) {
      return from;
    }
  }
  return -1;
};

qq.getUniqueId = (function() {
  var id = 0;
  return function() {
    return id++;
  };
})();

//
// Events

qq.attach = function(element, type, fn) {
  if (element.addEventListener) {
    element.addEventListener(type, fn, false);
  } else if (element.attachEvent) {
    element.attachEvent('on'
        + type, fn);
  }
};
qq.detach = function(element, type, fn) {
  if (element.removeEventListener) {
    element.removeEventListener(type, fn, false);
  } else if (element.attachEvent) {
    element.detachEvent('on'
        + type, fn);
  }
};

qq.preventDefault = function(e) {
  if (e.preventDefault) {
    e.preventDefault();
  } else {
    e.returnValue = false;
  }
};

//
// Node manipulations

/**
 * Insert node a before node b.
 */
qq.insertBefore = function(a, b) {
  b.parentNode.insertBefore(a, b);
};
qq.remove = function(element) {
  element.parentNode.removeChild(element);
};

qq.contains = function(parent, descendant) {
  // compareposition returns false in this case
  if (parent == descendant)
    return true;

  if (parent.contains) {
    return parent.contains(descendant);
  } else {
    return !!(descendant.compareDocumentPosition(parent) & 8);
  }
};

/**
 * Creates and returns element from html string Uses innerHTML to create an
 * element
 */
qq.toElement = (function() {
  var div = document.createElement('div');
  return function(html) {
    div.innerHTML = html;
    var element = div.firstChild;
    div.removeChild(element);
    return element;
  };
})();

//
// Node properties and attributes

/**
 * Sets styles for an element. Fixes opacity in IE6-8.
 */
qq.css = function(element, styles) {
  if (styles.opacity != null) {
    if (typeof element.style.opacity != 'string'
        && typeof (element.filters) != 'undefined') {
      styles.filter = 'alpha(opacity='
          + Math.round(100 * styles.opacity) + ')';
    }
  }
  qq.extend(element.style, styles);
};
qq.hasClass = function(element, name) {
  var re = new RegExp('(^| )'
      + name + '( |$)');
  return re.test(element.className);
};
qq.addClass = function(element, name) {
  if (!qq.hasClass(element, name)) {
    element.className += ' '
        + name;
  }
};
qq.removeClass =
    function(element, name) {
      var re = new RegExp('(^| )'
          + name + '( |$)');
      element.className =
          element.className.replace(re, ' ').replace(/^\s+|\s+$/g, "");
    };
qq.setText = function(element, text) {
  element.innerText = text;
  element.textContent = text;
};

//
// Selecting elements

qq.children = function(element) {
  var children = [], child = element.firstChild;

  while (child) {
    if (child.nodeType == 1) {
      children.push(child);
    }
    child = child.nextSibling;
  }

  return children;
};

qq.getByClass = function(element, className) {
  if (element.querySelectorAll) {
    return element.querySelectorAll('.'
        + className);
  }

  var result = [];
  var candidates = element.getElementsByTagName("*");
  var len = candidates.length;

  for ( var i = 0; i < len; i++) {
    if (qq.hasClass(candidates[i], className)) {
      result.push(candidates[i]);
    }
  }
  return result;
};

/**
 * obj2url() takes a json-object as argument and generates a querystring. pretty
 * much like jQuery.param()
 * 
 * how to use:
 * 
 * `qq.obj2url({a:'b',c:'d'},'http://any.url/upload?otherParam=value');`
 * 
 * will result in:
 * 
 * `http://any.url/upload?otherParam=value&a=b&c=d`
 * 
 * @param Object
 *          JSON-Object
 * @param String
 *          current querystring-part
 * @return String encoded querystring
 */
qq.obj2url =
    function(obj, temp, prefixDone) {
      var uristrings = [], prefix = '&', add =
          function(nextObj, i) {
            var nextTemp = temp ? (/\[\]$/.test(temp)) // prevent double-encoding
            ? temp : temp
                + '[' + i + ']' : i;
            if ((nextTemp != 'undefined')
                && (i != 'undefined')) {
              uristrings
                  .push((typeof nextObj === 'object') ? qq.obj2url(nextObj,
                      nextTemp, true)
                      : (Object.prototype.toString.call(nextObj) === '[object Function]') ? encodeURIComponent(nextTemp)
                          + '=' + encodeURIComponent(nextObj())
                          : encodeURIComponent(nextTemp)
                              + '=' + encodeURIComponent(nextObj));
            }
          };

      if (!prefixDone
          && temp) {
        prefix = (/\?/.test(temp)) ? (/\?$/.test(temp)) ? '' : '&' : '?';
        uristrings.push(temp);
        uristrings.push(qq.obj2url(obj));
      } else if ((Object.prototype.toString.call(obj) === '[object Array]')
          && (typeof obj != 'undefined')) {
        // we wont use a for-in-loop on an array (performance)
        for ( var i = 0, len = obj.length; i < len; ++i) {
          add(obj[i], i);
        }
      } else if ((typeof obj != 'undefined')
          && (obj !== null) && (typeof obj === "object")) {
        // for anything else but a scalar, we will use for-in-loop
        for ( var i in obj) {
          add(obj[i], i);
        }
      } else {
        uristrings.push(encodeURIComponent(temp)
            + '=' + encodeURIComponent(obj));
      }

      return uristrings.join(prefix).replace(/^&/, '').replace(/%20/g, '+');
    };

//
//
// Uploader Classes
//
//

var qq = qq
    || {};

/**
 * Creates upload button, validates upload, but doesn't create file list or dd.
 */
qq.FileUploaderBasic =
    function(o) {
      this._options =
          {
            // set to true to see the server response
            debug : false,
            action : '/server/upload',
            params : {},
            button : null,
            multiple : true,
            maxConnections : 3,
            // validation        
            allowedExtensions : [],
            sizeLimit : 0,
            minSizeLimit : 0,
            // events
            // return false to cancel submit
            onSubmit : function(id, fileName) {
            },
            onProgress : function(id, fileName, loaded, total) {
            },
            onComplete : function(id, fileName, responseJSON) {
            },
            onCancel : function(id, fileName) {
            },
            // messages                
            messages : {
              typeError : "{file} has invalid extension. Only {extensions} are allowed.",
              sizeError : "{file} is too large, maximum file size is {sizeLimit}.",
              minSizeError : "{file} is too small, minimum file size is {minSizeLimit}.",
              emptyError : "{file} is empty, please select files again without it.",
              onLeave : "The files are being uploaded, if you leave now the upload will be cancelled."
            },
            showMessage : function(message) {
              alert(message);
            }
          };
      qq.extend(this._options, o);

      // number of files being uploaded
      this._filesInProgress = 0;
      this._handler = this._createUploadHandler();

      if (this._options.button) {
        this._button = this._createUploadButton(this._options.button);
      }

      this._preventLeaveInProgress();
    };

qq.FileUploaderBasic.prototype =
    {
      setParams : function(params) {
        this._options.params = params;
      },
      getInProgress : function() {
        return this._filesInProgress;
      },
      _createUploadButton : function(element) {
        var self = this;

        return new qq.UploadButton({
          element : element,
          multiple : this._options.multiple
              && qq.UploadHandlerXhr.isSupported(),
          onChange : function(input) {
            self._onInputChange(input);
          }
        });
      },
      _createUploadHandler : function() {
        var self = this, handlerClass;

        if (qq.UploadHandlerXhr.isSupported()) {
          handlerClass = 'UploadHandlerXhr';
        } else {
          handlerClass = 'UploadHandlerForm';
        }

        var handler = new qq[handlerClass]({
          debug : this._options.debug,
          action : this._options.action,
          maxConnections : this._options.maxConnections,
          onProgress : function(id, fileName, loaded, total) {
            self._onProgress(id, fileName, loaded, total);
            self._options.onProgress(id, fileName, loaded, total);
          },
          onComplete : function(id, fileName, result) {
            self._onComplete(id, fileName, result);
            self._options.onComplete(id, fileName, result);
          },
          onCancel : function(id, fileName) {
            self._onCancel(id, fileName);
            self._options.onCancel(id, fileName);
          }
        });

        return handler;
      },
      _preventLeaveInProgress : function() {
        var self = this;

        qq.attach(window, 'beforeunload', function(e) {
          if (!self._filesInProgress) {
            return;
          }

          var e = e
              || window.event;
          // for ie, ff
          e.returnValue = self._options.messages.onLeave;
          // for webkit
          return self._options.messages.onLeave;
        });
      },
      _onSubmit : function(id, fileName) {
        this._filesInProgress++;
      },
      _onProgress : function(id, fileName, loaded, total) {
      },
      _onComplete : function(id, fileName, result) {
        this._filesInProgress--;
        if (result.error) {
          this._options.showMessage(result.error);
        }
      },
      _onCancel : function(id, fileName) {
        this._filesInProgress--;
      },
      _onInputChange : function(input) {
        if (this._handler instanceof qq.UploadHandlerXhr) {
          this._uploadFileList(input.files);
        } else {
          if (this._validateFile(input)) {
            this._uploadFile(input);
          }
        }
        this._button.reset();
      },
      _uploadFileList : function(files) {
        for ( var i = 0; i < files.length; i++) {
          if (!this._validateFile(files[i])) {
            return;
          }
        }

        for ( var i = 0; i < files.length; i++) {
          this._uploadFile(files[i]);
        }
      },
      _uploadFile : function(fileContainer) {
        var id = this._handler.add(fileContainer);
        var fileName = this._handler.getName(id);

        if (this._options.onSubmit(id, fileName) !== false) {
          this._onSubmit(id, fileName);
          this._handler.upload(id, this._options.params);
        }
      },
      _validateFile : function(file) {
        var name, size;

        if (file.value) {
          // it is a file input            
          // get input value and remove path to normalize
          name = file.value.replace(/.*(\/|\\)/, "");
        } else {
          // fix missing properties in Safari
          name = file.fileName != null ? file.fileName : file.name;
          size = file.fileSize != null ? file.fileSize : file.size;
        }

        if (!this._isAllowedExtension(name)) {
          this._error('typeError', name);
          return false;

        } else if (size === 0) {
          this._error('emptyError', name);
          return false;

        } else if (size
            && this._options.sizeLimit && size > this._options.sizeLimit) {
          this._error('sizeError', name);
          return false;

        } else if (size
            && size < this._options.minSizeLimit) {
          this._error('minSizeError', name);
          return false;
        }

        return true;
      },
      _error : function(code, fileName) {
        var message = this._options.messages[code];
        function r(name, replacement) {
          message = message.replace(name, replacement);
        }

        r('{file}', this._formatFileName(fileName));
        r('{extensions}', this._options.allowedExtensions.join(', '));
        r('{sizeLimit}', this._formatSize(this._options.sizeLimit));
        r('{minSizeLimit}', this._formatSize(this._options.minSizeLimit));

        this._options.showMessage(message);
      },
      _formatFileName : function(name) {
        if (name.length > 33) {
          name = name.slice(0, 19)
              + '...' + name.slice(-13);
        }
        return name;
      },
      _isAllowedExtension : function(fileName) {
        var ext =
            (-1 !== fileName.indexOf('.')) ? fileName.replace(/.*[.]/, '')
                .toLowerCase() : '';
        var allowed = this._options.allowedExtensions;

        if (!allowed.length) {
          return true;
        }

        for ( var i = 0; i < allowed.length; i++) {
          if (allowed[i].toLowerCase() == ext) {
            return true;
          }
        }

        return false;
      },
      _formatSize : function(bytes) {
        var i = -1;
        do {
          bytes = bytes / 1024;
          i++;
        } while (bytes > 99);

        return Math.max(bytes, 0.1).toFixed(1)
            + [
                'kB', 'MB', 'GB', 'TB', 'PB', 'EB'
            ][i];
      }
    };

/**
 * Class that creates upload widget with drag-and-drop and file list
 * 
 * @inherits qq.FileUploaderBasic
 */
qq.FileUploader =
    function(o) {
      // call parent constructor
      qq.FileUploaderBasic.apply(this, arguments);

      // additional options    
      qq
          .extend(
              this._options,
              {
                element : null,
                // if set, will be used instead of qq-upload-list in template
                listElement : null,

                template : '<div class="qq-uploader">'
                    + '<div class="qq-upload-drop-area"><span>Drop files here to upload</span></div>'
                    + '<div class="qq-upload-button">Upload a file</div>'
                    + '<ul class="qq-upload-list"></ul>' + '</div>',

                // template for one item in file list
                fileTemplate : '<li>'
                    + '<span class="qq-upload-file"></span>'
                    + '<span class="qq-upload-spinner"></span>'
                    + '<span class="qq-upload-size"></span>'
                    + '<a class="qq-upload-cancel" href="#">Cancel</a>'
                    + '<span class="qq-upload-failed-text">Failed</span>'
                    + '</li>',

                classes : {
                  // used to get elements from templates
                  button : 'qq-upload-button',
                  drop : 'qq-upload-drop-area',
                  dropActive : 'qq-upload-drop-area-active',
                  list : 'qq-upload-list',

                  file : 'qq-upload-file',
                  spinner : 'qq-upload-spinner',
                  size : 'qq-upload-size',
                  cancel : 'qq-upload-cancel',

                  // added to list item when upload completes
                  // used in css to hide progress spinner
                  success : 'qq-upload-success',
                  fail : 'qq-upload-fail'
                }
              });
      // overwrite options with user supplied    
      qq.extend(this._options, o);

      this._element = this._options.element;
      this._element.innerHTML = this._options.template;
      this._listElement = this._options.listElement
          || this._find(this._element, 'list');

      this._classes = this._options.classes;

      this._button =
          this._createUploadButton(this._find(this._element, 'button'));

      this._bindCancelEvent();
      this._setupDragDrop();
    };

// inherit from Basic Uploader
qq.extend(qq.FileUploader.prototype, qq.FileUploaderBasic.prototype);

qq.extend(qq.FileUploader.prototype, {
  /**
   * Gets one of the elements listed in this._options.classes
   */
  _find : function(parent, type) {
    var element = qq.getByClass(parent, this._options.classes[type])[0];
    if (!element) {
      throw new Error('element not found '
          + type);
    }

    return element;
  },
  _setupDragDrop : function() {
    var self = this, dropArea = this._find(this._element, 'drop');

    var dz = new qq.UploadDropZone({
      element : dropArea,
      onEnter : function(e) {
        qq.addClass(dropArea, self._classes.dropActive);
        e.stopPropagation();
      },
      onLeave : function(e) {
        e.stopPropagation();
      },
      onLeaveNotDescendants : function(e) {
        qq.removeClass(dropArea, self._classes.dropActive);
      },
      onDrop : function(e) {
        dropArea.style.display = 'none';
        qq.removeClass(dropArea, self._classes.dropActive);
        self._uploadFileList(e.dataTransfer.files);
      }
    });

    dropArea.style.display = 'none';

    qq.attach(document, 'dragenter', function(e) {
      if (!dz._isValidFileDrag(e))
        return;

      dropArea.style.display = 'block';
    });
    qq.attach(document, 'dragleave', function(e) {
      if (!dz._isValidFileDrag(e))
        return;

      var relatedTarget = document.elementFromPoint(e.clientX, e.clientY);
      // only fire when leaving document out
      if (!relatedTarget
          || relatedTarget.nodeName == "HTML") {
        dropArea.style.display = 'none';
      }
    });
  },
  _onSubmit : function(id, fileName) {
    qq.FileUploaderBasic.prototype._onSubmit.apply(this, arguments);
    this._addToList(id, fileName);
  },
  _onProgress : function(id, fileName, loaded, total) {
    qq.FileUploaderBasic.prototype._onProgress.apply(this, arguments);

    var item = this._getItemByFileId(id);
    var size = this._find(item, 'size');
    size.style.display = 'inline';

    var text;
    if (loaded != total) {
      text = Math.round(loaded
          / total * 100)
          + '% from ' + this._formatSize(total);
    } else {
      text = this._formatSize(total);
    }

    qq.setText(size, text);
  },
  _onComplete : function(id, fileName, result) {
    qq.FileUploaderBasic.prototype._onComplete.apply(this, arguments);

    // mark completed
    var item = this._getItemByFileId(id);
    qq.remove(this._find(item, 'cancel'));
    qq.remove(this._find(item, 'spinner'));

    if (result.success) {
      qq.addClass(item, this._classes.success);
    } else {
      qq.addClass(item, this._classes.fail);
    }
  },
  _addToList : function(id, fileName) {
    var item = qq.toElement(this._options.fileTemplate);
    item.qqFileId = id;

    var fileElement = this._find(item, 'file');
    qq.setText(fileElement, this._formatFileName(fileName));
    this._find(item, 'size').style.display = 'none';

    this._listElement.appendChild(item);
  },
  _getItemByFileId : function(id) {
    var item = this._listElement.firstChild;

    // there can't be txt nodes in dynamically created list
    // and we can  use nextSibling
    while (item) {
      if (item.qqFileId == id)
        return item;
      item = item.nextSibling;
    }
  },
  /**
   * delegate click event for cancel link
   */
  _bindCancelEvent : function() {
    var self = this, list = this._listElement;

    qq.attach(list, 'click', function(e) {
      e = e
          || window.event;
      var target = e.target
          || e.srcElement;

      if (qq.hasClass(target, self._classes.cancel)) {
        qq.preventDefault(e);

        var item = target.parentNode;
        self._handler.cancel(item.qqFileId);
        qq.remove(item);
      }
    });
  }
});

qq.UploadDropZone = function(o) {
  this._options = {
    element : null,
    onEnter : function(e) {
    },
    onLeave : function(e) {
    },
    // is not fired when leaving element by hovering descendants   
    onLeaveNotDescendants : function(e) {
    },
    onDrop : function(e) {
    }
  };
  qq.extend(this._options, o);

  this._element = this._options.element;

  this._disableDropOutside();
  this._attachEvents();
};

qq.UploadDropZone.prototype = {
  _disableDropOutside : function(e) {
    // run only once for all instances
    if (!qq.UploadDropZone.dropOutsideDisabled) {

      qq.attach(document, 'dragover', function(e) {
        if (e.dataTransfer) {
          e.dataTransfer.dropEffect = 'none';
          e.preventDefault();
        }
      });

      qq.UploadDropZone.dropOutsideDisabled = true;
    }
  },
  _attachEvents : function() {
    var self = this;

    qq.attach(self._element, 'dragover', function(e) {
      if (!self._isValidFileDrag(e))
        return;

      var effect = e.dataTransfer.effectAllowed;
      if (effect == 'move'
          || effect == 'linkMove') {
        e.dataTransfer.dropEffect = 'move'; // for FF (only move allowed)    
      } else {
        e.dataTransfer.dropEffect = 'copy'; // for Chrome
      }

      e.stopPropagation();
      e.preventDefault();
    });

    qq.attach(self._element, 'dragenter', function(e) {
      if (!self._isValidFileDrag(e))
        return;

      self._options.onEnter(e);
    });

    qq.attach(self._element, 'dragleave', function(e) {
      if (!self._isValidFileDrag(e))
        return;

      self._options.onLeave(e);

      var relatedTarget = document.elementFromPoint(e.clientX, e.clientY);
      // do not fire when moving a mouse over a descendant
      if (qq.contains(this, relatedTarget))
        return;

      self._options.onLeaveNotDescendants(e);
    });

    qq.attach(self._element, 'drop', function(e) {
      if (!self._isValidFileDrag(e))
        return;

      e.preventDefault();
      self._options.onDrop(e);
    });
  },
  _isValidFileDrag : function(e) {
    var dt = e.dataTransfer,
    // do not check dt.types.contains in webkit, because it crashes safari 4            
    isWebkit = navigator.userAgent.indexOf("AppleWebKit") > -1;

    // dt.effectAllowed is none in Safari 5
    // dt.types.contains check is for firefox            
    return dt
        && dt.effectAllowed != 'none' && (dt.files || (!isWebkit
            && dt.types.contains && dt.types.contains('Files')));

  }
};

qq.UploadButton = function(o) {
  this._options = {
    element : null,
    // if set to true adds multiple attribute to file input      
    multiple : false,
    // name attribute of file input
    name : 'file',
    onChange : function(input) {
    },
    hoverClass : 'qq-upload-button-hover',
    focusClass : 'qq-upload-button-focus'
  };

  qq.extend(this._options, o);

  this._element = this._options.element;

  // make button suitable container for input
  qq.css(this._element, {
    position : 'relative',
    overflow : 'hidden',
    // Make sure browse button is in the right side
    // in Internet Explorer
    direction : 'ltr'
  });

  this._input = this._createInput();
};

qq.UploadButton.prototype = {
  /* returns file input element */
  getInput : function() {
    return this._input;
  },
  /* cleans/recreates the file input */
  reset : function() {
    if (this._input.parentNode) {
      qq.remove(this._input);
    }

    qq.removeClass(this._element, this._options.focusClass);
    this._input = this._createInput();
  },
  _createInput : function() {
    var input = document.createElement("input");

    if (this._options.multiple) {
      input.setAttribute("multiple", "multiple");
    }

    input.setAttribute("type", "file");
    input.setAttribute("name", this._options.name);

    qq.css(input, {
      position : 'absolute',
      // in Opera only 'browse' button
      // is clickable and it is located at
      // the right side of the input
      right : 0,
      top : 0,
      fontFamily : 'Arial',
      // 4 persons reported this, the max values that worked for them were 243, 236, 236, 118
      fontSize : '118px',
      margin : 0,
      padding : 0,
      cursor : 'pointer',
      opacity : 0
    });

    this._element.appendChild(input);

    var self = this;
    qq.attach(input, 'change', function() {
      self._options.onChange(input);
    });

    qq.attach(input, 'mouseover', function() {
      qq.addClass(self._element, self._options.hoverClass);
    });
    qq.attach(input, 'mouseout', function() {
      qq.removeClass(self._element, self._options.hoverClass);
    });
    qq.attach(input, 'focus', function() {
      qq.addClass(self._element, self._options.focusClass);
    });
    qq.attach(input, 'blur', function() {
      qq.removeClass(self._element, self._options.focusClass);
    });

    // IE and Opera, unfortunately have 2 tab stops on file input
    // which is unacceptable in our case, disable keyboard access
    if (window.attachEvent) {
      // it is IE or Opera
      input.setAttribute('tabIndex', "-1");
    }

    return input;
  }
};

/**
 * Class for uploading files, uploading itself is handled by child classes
 */
qq.UploadHandlerAbstract = function(o) {
  this._options = {
    debug : false,
    action : '/upload.php',
    // maximum number of concurrent uploads        
    maxConnections : 999,
    onProgress : function(id, fileName, loaded, total) {
    },
    onComplete : function(id, fileName, response) {
    },
    onCancel : function(id, fileName) {
    }
  };
  qq.extend(this._options, o);

  this._queue = [];
  // params for files in queue
  this._params = [];
};
qq.UploadHandlerAbstract.prototype = {
  log : function(str) {
    if (this._options.debug
        && window.console)
      console.log('[uploader] '
          + str);
  },
  /**
   * Adds file or file input to the queue
   * 
   * @returns id
   */
  add : function(file) {
  },
  /**
   * Sends the file identified by id and additional query params to the server
   */
  upload : function(id, params) {
    var len = this._queue.push(id);

    var copy = {};
    qq.extend(copy, params);
    this._params[id] = copy;

    // if too many active uploads, wait...
    if (len <= this._options.maxConnections) {
      this._upload(id, this._params[id]);
    }
  },
  /**
   * Cancels file upload by id
   */
  cancel : function(id) {
    this._cancel(id);
    this._dequeue(id);
  },
  /**
   * Cancells all uploads
   */
  cancelAll : function() {
    for ( var i = 0; i < this._queue.length; i++) {
      this._cancel(this._queue[i]);
    }
    this._queue = [];
  },
  /**
   * Returns name of the file identified by id
   */
  getName : function(id) {
  },
  /**
   * Returns size of the file identified by id
   */
  getSize : function(id) {
  },
  /**
   * Returns id of files being uploaded or waiting for their turn
   */
  getQueue : function() {
    return this._queue;
  },
  /**
   * Actual upload method
   */
  _upload : function(id) {
  },
  /**
   * Actual cancel method
   */
  _cancel : function(id) {
  },
  /**
   * Removes element from queue, starts upload of next
   */
  _dequeue : function(id) {
    var i = qq.indexOf(this._queue, id);
    this._queue.splice(i, 1);

    var max = this._options.maxConnections;

    if (this._queue.length >= max
        && i < max) {
      var nextId = this._queue[max - 1];
      this._upload(nextId, this._params[nextId]);
    }
  }
};

/**
 * Class for uploading files using form and iframe
 * 
 * @inherits qq.UploadHandlerAbstract
 */
qq.UploadHandlerForm = function(o) {
  qq.UploadHandlerAbstract.apply(this, arguments);

  this._inputs = {};
};
// @inherits qq.UploadHandlerAbstract
qq.extend(qq.UploadHandlerForm.prototype, qq.UploadHandlerAbstract.prototype);

qq
    .extend(
        qq.UploadHandlerForm.prototype,
        {
          add : function(fileInput) {
            fileInput.setAttribute('name', 'qqfile');
            var id = 'qq-upload-handler-iframe'
                + qq.getUniqueId();

            this._inputs[id] = fileInput;

            // remove file input from DOM
            if (fileInput.parentNode) {
              qq.remove(fileInput);
            }

            return id;
          },
          getName : function(id) {
            // get input value and remove path to normalize
            return this._inputs[id].value.replace(/.*(\/|\\)/, "");
          },
          _cancel : function(id) {
            this._options.onCancel(id, this.getName(id));

            delete this._inputs[id];

            var iframe = document.getElementById(id);
            if (iframe) {
              // to cancel request set src to something else
              // we use src="javascript:false;" because it doesn't
              // trigger ie6 prompt on https
              iframe.setAttribute('src', 'javascript:false;');

              qq.remove(iframe);
            }
          },
          _upload : function(id, params) {
            var input = this._inputs[id];

            if (!input) {
              throw new Error(
                  'file with passed id was not added, or already uploaded or cancelled');
            }

            var fileName = this.getName(id);

            var iframe = this._createIframe(id);
            var form = this._createForm(iframe, params);
            form.appendChild(input);

            var self = this;
            this._attachLoadEvent(iframe, function() {
              self.log('iframe loaded');

              var response = self._getIframeContentJSON(iframe);

              self._options.onComplete(id, fileName, response);
              self._dequeue(id);

              delete self._inputs[id];
              // timeout added to fix busy state in FF3.6
              setTimeout(function() {
                qq.remove(iframe);
              }, 1);
            });

            form.submit();
            qq.remove(form);

            return id;
          },
          _attachLoadEvent : function(iframe, callback) {
            qq.attach(iframe, 'load', function() {
              // when we remove iframe from dom
              // the request stops, but in IE load
              // event fires
              if (!iframe.parentNode) {
                return;
              }

              // fixing Opera 10.53
              if (iframe.contentDocument
                  && iframe.contentDocument.body
                  && iframe.contentDocument.body.innerHTML == "false") {
                // In Opera event is fired second time
                // when body.innerHTML changed from false
                // to server response approx. after 1 sec
                // when we upload file with iframe
                return;
              }

              callback();
            });
          },
          /**
           * Returns json object received by iframe from server.
           */
          _getIframeContentJSON : function(iframe) {
            // iframe.contentWindow.document - for IE<7
            var doc =
                iframe.contentDocument ? iframe.contentDocument
                    : iframe.contentWindow.document, response;

            this.log("converting iframe's innerHTML to JSON");
            this.log("innerHTML = "
                + doc.body.innerHTML);

            try {
              response = eval("("
                  + doc.body.innerHTML + ")");
            } catch (err) {
              response = {};
            }

            return response;
          },
          /**
           * Creates iframe with unique name
           */
          _createIframe : function(id) {
            // We can't use following code as the name attribute
            // won't be properly registered in IE6, and new window
            // on form submit will open
            // var iframe = document.createElement('iframe');
            // iframe.setAttribute('name', id);

            var iframe = qq.toElement('<iframe src="javascript:false;" name="'
                + id + '" />');
            // src="javascript:false;" removes ie6 prompt on https

            iframe.setAttribute('id', id);

            iframe.style.display = 'none';
            document.body.appendChild(iframe);

            return iframe;
          },
          /**
           * Creates form, that will be submitted to iframe
           */
          _createForm : function(iframe, params) {
            // We can't use the following code in IE6
            // var form = document.createElement('form');
            // form.setAttribute('method', 'post');
            // form.setAttribute('enctype', 'multipart/form-data');
            // Because in this case file won't be attached to request
            var form =
                qq
                    .toElement('<form method="post" enctype="multipart/form-data"></form>');

            var queryString = qq.obj2url(params, this._options.action);

            form.setAttribute('action', queryString);
            form.setAttribute('target', iframe.name);
            form.style.display = 'none';
            document.body.appendChild(form);

            return form;
          }
        });

/**
 * Class for uploading files using xhr
 * 
 * @inherits qq.UploadHandlerAbstract
 */
qq.UploadHandlerXhr = function(o) {
  qq.UploadHandlerAbstract.apply(this, arguments);

  this._files = [];
  this._xhrs = [];

  // current loaded size in bytes for each file 
  this._loaded = [];
};

// static method
qq.UploadHandlerXhr.isSupported =
    function() {
      var input = document.createElement('input');
      input.type = 'file';

      return ('multiple' in input
          && typeof File != "undefined" && typeof (new XMLHttpRequest()).upload != "undefined");
    };

// @inherits qq.UploadHandlerAbstract
qq.extend(qq.UploadHandlerXhr.prototype, qq.UploadHandlerAbstract.prototype)

qq.extend(qq.UploadHandlerXhr.prototype, {
  /**
   * Adds file to the queue Returns id to use with upload, cancel
   */
  add : function(file) {
    if (!(file instanceof File)) {
      throw new Error('Passed obj in not a File (in qq.UploadHandlerXhr)');
    }

    return this._files.push(file) - 1;
  },
  getName : function(id) {
    var file = this._files[id];
    // fix missing name in Safari 4
    return file.fileName != null ? file.fileName : file.name;
  },
  getSize : function(id) {
    var file = this._files[id];
    return file.fileSize != null ? file.fileSize : file.size;
  },
  /**
   * Returns uploaded bytes for file identified by id
   */
  getLoaded : function(id) {
    return this._loaded[id] || 0;
  },
  /**
   * Sends the file identified by id and additional query params to the server
   * 
   * @param {Object}
   *          params name-value string pairs
   */
  _upload : function(id, params) {
    var file = this._files[id], name = this.getName(id), size =
        this.getSize(id);

    this._loaded[id] = 0;

    var xhr = this._xhrs[id] = new XMLHttpRequest();
    var self = this;

    xhr.upload.onprogress = function(e) {
      if (e.lengthComputable) {
        self._loaded[id] = e.loaded;
        self._options.onProgress(id, name, e.loaded, e.total);
      }
    };

    xhr.onreadystatechange = function() {
      if (xhr.readyState == 4) {
        self._onComplete(id, xhr);
      }
    };

    // build query string
    params = params
        || {};
    params['qqfile'] = name;
    var queryString = qq.obj2url(params, this._options.action);

    xhr.open("POST", queryString, true);
    xhr.setRequestHeader("X-Requested-With", "XMLHttpRequest");
    xhr.setRequestHeader("X-File-Name", encodeURIComponent(name));
    xhr.setRequestHeader("Content-Type", "application/octet-stream");
    xhr.send(file);
  },
  _onComplete : function(id, xhr) {
    // the request was aborted/cancelled
    if (!this._files[id])
      return;

    var name = this.getName(id);
    var size = this.getSize(id);

    this._options.onProgress(id, name, size, size);

    if (xhr.status == 200) {
      this.log("xhr - server response received");
      this.log("responseText = "
          + xhr.responseText);

      var response;

      try {
        response = eval("("
            + xhr.responseText + ")");
      } catch (err) {
        response = {};
      }

      this._options.onComplete(id, name, response);

    } else {
      this._options.onComplete(id, name, {});
    }

    this._files[id] = null;
    this._xhrs[id] = null;
    this._dequeue(id);
  },
  _cancel : function(id) {
    this._options.onCancel(id, this.getName(id));

    this._files[id] = null;

    if (this._xhrs[id]) {
      this._xhrs[id].abort();
      this._xhrs[id] = null;
    }
  }
});/**
**/


(function(){var a=function(){function e(a){return Array.isArray?Array.isArray(a):a.constructor.toString().indexOf("Array")!=-1}function d(a,c,d){var e=b[c][d];for(var f=0;f<e.length;f++)e[f].win===a&&e.splice(f,1);b[c][d].length===0&&delete b[c][d]}function c(a,c,d,e){function f(b){for(var c=0;c<b.length;c++)if(b[c].win===a)return!0;return!1}var g=!1;if(c==="*")for(var h in b){if(!b.hasOwnProperty(h))continue;if(h==="*")continue;if(typeof b[h][d]=="object"){g=f(b[h][d]);if(g)break}}else b["*"]&&b["*"][d]&&(g=f(b["*"][d])),!g&&b[c]&&b[c][d]&&(g=f(b[c][d]));if(g)throw"A channel is already bound to the same window which overlaps with origin '"+c+"' and has scope '"+d+"'";typeof b[c]!="object"&&(b[c]={}),typeof b[c][d]!="object"&&(b[c][d]=[]),b[c][d].push({win:a,handler:e})}"use strict";var a=Math.floor(Math.random()*1000001),b={},f={},g=function(a){try{var c=JSON.parse(a.data);if(typeof c!="object"||c===null)throw"malformed"}catch(a){return}var d=a.source,e=a.origin,g,h,i;if(typeof c.method=="string"){var j=c.method.split("::");j.length==2?(g=j[0],i=j[1]):i=c.method}typeof c.id!="undefined"&&(h=c.id);if(typeof i=="string"){var k=!1;if(b[e]&&b[e][g])for(var h=0;h<b[e][g].length;h++)if(b[e][g][h].win===d){b[e][g][h].handler(e,i,c),k=!0;break}if(!k&&b["*"]&&b["*"][g])for(var h=0;h<b["*"][g].length;h++)if(b["*"][g][h].win===d){b["*"][g][h].handler(e,i,c);break}}else typeof h!="undefined"&&f[h]&&f[h](e,i,c)};window.addEventListener?window.addEventListener("message",g,!1):window.attachEvent&&window.attachEvent("onmessage",g);return{build:function(b){var g=function(a){if(b.debugOutput&&window.console&&window.console.log){try{typeof a!="string"&&(a=JSON.stringify(a))}catch(c){}console.log("["+j+"] "+a)}};if(!window.postMessage)throw"jschannel cannot run this browser, no postMessage";if(!window.JSON||!window.JSON.stringify||!window.JSON.parse)throw"jschannel cannot run this browser, no JSON parsing/serialization";if(typeof b!="object")throw"Channel build invoked without a proper object argument";if(!b.window||!b.window.postMessage)throw"Channel.build() called without a valid window argument";if(window===b.window)throw"target window is same as present window -- not allowed";var h=!1;if(typeof b.origin=="string"){var i;b.origin==="*"?h=!0:null!==(i=b.origin.match(/^https?:\/\/(?:[-a-zA-Z0-9_\.])+(?::\d+)?/))&&(b.origin=i[0].toLowerCase(),h=!0)}if(!h)throw"Channel.build() called with an invalid origin";if(typeof b.scope!="undefined"){if(typeof b.scope!="string")throw"scope, when specified, must be a string";if(b.scope.split("::").length>1)throw"scope may not contain double colons: '::'"}var j=function(){var a="",b="abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";for(var c=0;c<5;c++)a+=b.charAt(Math.floor(Math.random()*b.length));return a}(),k={},l={},m={},n=!1,o=[],p=function(a,b,c){var d=!1,e=!1;return{origin:b,invoke:function(b,d){if(!m[a])throw"attempting to invoke a callback of a nonexistent transaction: "+a;var e=!1;for(var f=0;f<c.length;f++)if(b===c[f]){e=!0;break}if(!e)throw"request supports no such callback '"+b+"'";t({id:a,callback:b,params:d})},error:function(b,c){e=!0;if(!m[a])throw"error called for nonexistent message: "+a;delete m[a],t({id:a,error:b,message:c})},complete:function(b){e=!0;if(!m[a])throw"complete called for nonexistent message: "+a;delete m[a],t({id:a,result:b})},delayReturn:function(a){typeof a=="boolean"&&(d=a===!0);return d},completed:function(){return e}}},q=function(a,b,c){return window.setTimeout(function(){if(l[a]){var d="timeout ("+b+"ms) exceeded on method '"+c+"'";(1,l[a].error)("timeout_error",d),delete l[a],delete f[a]}},b)},r=function(a,c,d){if(typeof b.gotMessageObserver=="function")try{b.gotMessageObserver(a,d)}catch(h){g("gotMessageObserver() raised an exception: "+h.toString())}if(d.id&&c){if(k[c]){var i=p(d.id,a,d.callbacks?d.callbacks:[]);m[d.id]={};try{if(d.callbacks&&e(d.callbacks)&&d.callbacks.length>0)for(var j=0;j<d.callbacks.length;j++){var n=d.callbacks[j],o=d.params,q=n.split("/");for(var r=0;r<q.length-1;r++){var s=q[r];typeof o[s]!="object"&&(o[s]={}),o=o[s]}o[q[q.length-1]]=function(){var a=n;return function(b){return i.invoke(a,b)}}()}var t=k[c](i,d.params);!i.delayReturn()&&!i.completed()&&i.complete(t)}catch(h){var u="runtime_error",v=null;typeof h=="string"?v=h:typeof h=="object"&&(h&&e(h)&&h.length==2?(u=h[0],v=h[1]):typeof h.error=="string"&&(u=h.error,h.message?typeof h.message=="string"?v=h.message:h=h.message:v=""));if(v===null)try{v=JSON.stringify(h),typeof v=="undefined"&&(v=h.toString())}catch(w){v=h.toString()}i.error(u,v)}}}else d.id&&d.callback?!l[d.id]||!l[d.id].callbacks||!l[d.id].callbacks[d.callback]?g("ignoring invalid callback, id:"+d.id+" ("+d.callback+")"):l[d.id].callbacks[d.callback](d.params):d.id?l[d.id]?(d.error?(1,l[d.id].error)(d.error,d.message):d.result!==undefined?(1,l[d.id].success)(d.result):(1,l[d.id].success)(),delete l[d.id],delete f[d.id]):g("ignoring invalid response: "+d.id):c&&k[c]&&k[c](null,d.params)};c(b.window,b.origin,typeof b.scope=="string"?b.scope:"",r);var s=function(a){typeof b.scope=="string"&&b.scope.length&&(a=[b.scope,a].join("::"));return a},t=function(a,c){if(!a)throw"postMessage called with null message";var d=n?"post  ":"queue ";g(d+" message: "+JSON.stringify(a));if(!c&&!n)o.push(a);else{if(typeof b.postMessageObserver=="function")try{b.postMessageObserver(b.origin,a)}catch(e){g("postMessageObserver() raised an exception: "+e.toString())}b.window.postMessage(JSON.stringify(a),b.origin)}},u=function(a,c){g("ready msg received");if(n)throw"received ready message while in ready state.  help!";c==="ping"?j+="-R":j+="-L",v.unbind("__ready"),n=!0,g("ready msg accepted."),c==="ping"&&v.notify({method:"__ready",params:"pong"});while(o.length)t(o.pop());typeof b.onReady=="function"&&b.onReady(v)},v={unbind:function(a){if(k[a]){if(delete k[a])return!0;throw"can't delete method: "+a}return!1},bind:function(a,b){if(!a||typeof a!="string")throw"'method' argument to bind must be string";if(!b||typeof b!="function")throw"callback missing from bind params";if(k[a])throw"method '"+a+"' is already bound!";k[a]=b;return this},call:function(b){if(!b)throw"missing arguments to call function";if(!b.method||typeof b.method!="string")throw"'method' argument to call must be string";if(!b.success||typeof b.success!="function")throw"'success' callback missing from call";var c={},d=[],e=function(a,b){if(typeof b=="object")for(var f in b){if(!b.hasOwnProperty(f))continue;var g=a+(a.length?"/":"")+f;typeof b[f]=="function"?(c[g]=b[f],d.push(g),delete b[f]):typeof b[f]=="object"&&e(g,b[f])}};e("",b.params);var g={id:a,method:s(b.method),params:b.params};d.length&&(g.callbacks=d),b.timeout&&q(a,b.timeout,s(b.method)),l[a]={callbacks:c,error:b.error,success:b.success},f[a]=r,a++,t(g)},notify:function(a){if(!a)throw"missing arguments to notify function";if(!a.method||typeof a.method!="string")throw"'method' argument to notify must be string";t({method:s(a.method),params:a.params})},destroy:function(){d(b.window,b.origin,typeof b.scope=="string"?b.scope:""),window.removeEventListener?window.removeEventListener("message",r,!1):window.detachEvent&&window.detachEvent("onmessage",r),n=!1,k={},m={},l={},b.origin=null,o=[],g("channel destroyed"),j=""}};v.bind("__ready",u),setTimeout(function(){},0);return v}}}();WinChan=function(){function j(){var b=window.location,c=window.opener.frames,d=b.protocol+"//"+b.host;for(i=c.length-1;i>=0;i++)try{if(c[i].location.href.indexOf(d)===0&&c[i].name===a)return c[i]}catch(e){}return}function h(a){/^https?:\/\//.test(a)||(a=window.location.href);var b=/^(https?:\/\/[-_a-zA-Z\.0-9:]+)/.exec(a);return b?b[1]:a}function g(){return window.JSON&&window.JSON.stringify&&window.JSON.parse&&window.postMessage}function f(){try{return d.indexOf("Fennec/")!=-1||d.indexOf("Firefox/")!=-1&&d.indexOf("Android")!=-1}catch(a){}return!1}function e(){var a=-1;if(navigator.appName=="Microsoft Internet Explorer"){var b=navigator.userAgent,c=new RegExp("MSIE ([0-9]{1,}[.0-9]{0,})");c.exec(b)!=null&&(a=parseFloat(RegExp.$1))}return a>=8}function c(a,b,c){a.detachEvent?a.detachEvent("on"+b,c):a.removeEventListener&&a.removeEventListener(b,c,!1)}function b(a,b,c){a.attachEvent?a.attachEvent("on"+b,c):a.addEventListener&&a.addEventListener(b,c,!1)}var a="__winchan_relay_frame",k=e();return g()?{open:function(d,e){function p(a){try{var b=JSON.parse(a.data);b.a==="ready"?l.postMessage(n,j):b.a==="error"?e&&(e(b.d),e=null):b.a==="response"&&(c(window,"message",p),c(window,"unload",o),o(),e&&(e(null,b.d),e=null))}catch(a){}}function o(){i&&document.body.removeChild(i),i=undefined,m&&m.close(),m=undefined}if(!e)throw"missing required callback argument";var g;d.url||(g="missing required 'url' parameter"),d.relay_url||(g="missing required 'relay_url' parameter"),g&&setTimeout(function(){e(g)},0);if(!d.window_features||f())d.window_features=undefined;var i,j=h(d.url);if(j!==h(d.relay_url))return setTimeout(function(){e("invalid arguments: origin of url and relay_url must match")},0);var l;k&&(i=document.createElement("iframe"),i.setAttribute("src",d.relay_url),i.style.display="none",i.setAttribute("name",a),document.body.appendChild(i),l=i.contentWindow);var m=window.open(d.url,null,d.window_features);l||(l=m);var n=JSON.stringify({a:"request",d:d.params});b(window,"unload",o),b(window,"message",p);return{close:o,focus:function(){if(m)try{m.focus()}catch(a){}}}}}:{open:function(a,b,c,d){setTimeout(function(){d("unsupported browser")},0)}}}();var b=function(){function l(){return c}function k(){c=g()||h()||i()||j();return!c}function j(){if(!(window.JSON&&window.JSON.stringify&&window.JSON.parse))return"JSON_NOT_SUPPORTED"}function i(){if(!a.postMessage)return"POSTMESSAGE_NOT_SUPPORTED"}function h(){try{var b="localStorage"in a&&a.localStorage!==null;if(b)a.localStorage.setItem("test","true"),a.localStorage.removeItem("test");else return"LOCALSTORAGE_NOT_SUPPORTED"}catch(c){return"LOCALSTORAGE_DISABLED"}}function g(){return f()}function f(){var a=e(),b=a>-1&&a<8;if(b)return"BAD_IE_VERSION"}function e(){var a=-1;if(b.appName=="Microsoft Internet Explorer"){var c=b.userAgent,d=new RegExp("MSIE ([0-9]{1,}[.0-9]{0,})");d.exec(c)!=null&&(a=parseFloat(RegExp.$1))}return a}function d(c,d){b=c,a=d}var a=window,b=navigator,c;return{setTestEnv:d,isSupported:k,getNoSupportReason:l}}();navigator.id||(navigator.id={});if(!navigator.id.request||navigator.id._shimmed){var c="https://browserid.org",d=navigator.userAgent,e=d.indexOf("Fennec/")!=-1||d.indexOf("Firefox/")!=-1&&d.indexOf("Android")!=-1,f=e?undefined:"menubar=0,location=1,resizable=1,scrollbars=1,status=0,dialog=1,width=700,height=375",g,h={login:null,logout:null,ready:null},j=undefined;function k(a){a!==!0;if(j===undefined)j=a;else if(j!=a)throw"you cannot combine the navigator.id.watch() API with navigator.id.getVerifiedEmail() or navigator.id.get()this site should instead use navigator.id.request() and navigator.id.watch()"}var l;function m(){try{if(!l){var b=window.document,d=b.createElement("iframe");d.style.display="none",b.body.appendChild(d),d.src=c+"/communication_iframe",l=a.build({window:d.contentWindow,origin:c,scope:"mozid_ni",onReady:function(){l.call({method:"loaded",success:function(){h.ready&&h.ready()},error:function(){}})}}),l.bind("logout",function(a,b){h.logout&&h.logout()}),l.bind("login",function(a,b){h.login&&h.login(b)})}}catch(e){l=undefined}}function n(a){if(typeof a=="object"){if(a.onlogin&&typeof a.onlogin!="function"||a.onlogout&&typeof a.onlogout!="function"||a.onready&&typeof a.onready!="function")throw"non-function where function expected in parameters to navigator.id.watch()";h.login=a.onlogin||null,h.logout=a.onlogout||null,h.ready=a.onready||null,m(),typeof a.email!="undefined"&&l&&l.notify({method:"loggedInUser",params:a.email})}}function o(a){if(g)try{g.focus()}catch(d){}else{if(!b.isSupported()){var e=b.getNoSupportReason(),i="unsupported_dialog";e==="LOCALSTORAGE_DISABLED"&&(i="cookies_disabled"),g=window.open(c+"/"+i,null,f);return}l&&l.notify({method:"dialog_running"}),g=WinChan.open({url:c+"/sign_in",relay_url:c+"/relay",window_features:f,params:{method:"get",params:a}},function(b,c){l&&(!b&&c&&c.email&&l.notify({method:"loggedInUser",params:c.email}),l.notify({method:"dialog_complete"})),g=undefined;if(!b&&c&&c.assertion)try{h.login&&h.login(c.assertion)}catch(d){}a&&a.onclose&&a.onclose(),delete a.onclose})}}navigator.id={experimental:{request:function(a){k(!1);return o(a)},watch:function(a){k(!1),n(a)}},logout:function(a){m(),l.notify({method:"logout"}),typeof a=="function"&&setTimeout(a,0)},get:function(a,b){b=b||{},k(!0),n({onlogin:function(b){a&&(a(b),a=null)}}),b.onclose=function(){a&&(a(null),a=null),n({})},b&&b.silent?a&&setTimeout(function(){a(null)},0):o(b)},getVerifiedEmail:function(a){k(!0),navigator.id.get(a)},_shimmed:!0}}})()