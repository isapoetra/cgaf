
(function(){var followAlong=function(el,options){var that=this,i;that.options={duration:'100ms'};if(typeof options=='object'){for(i in options){that.options[i]=options[i];}}
that.element=typeof el=='object'?el:document.getElementById(el);that.element.style.webkitTransitionProperty='-webkit-transform';that.element.style.webkitTransitionTimingFunction='cubic-bezier(0,0,0.25,1)';that.element.style.webkitTransitionDuration=that.element.style.webkitTransitionDuration=that.options.duration;that.element.style.webkitTransform=translateOpen+'0,0'+translateClose;el=that.element;that.x1=that.x2=that.y1=that.y2=0;do{that.x1+=el.offsetLeft;that.y1+=el.offsetTop;}while(el=el.offsetParent);that.x2=that.x1+that.element.offsetWidth;that.y2=that.y1+that.element.offsetHeight;setTimeout(function(){that.follow();},0);window.addEventListener('scroll',that,false);},has3d=('WebKitCSSMatrix'in window&&'m11'in new WebKitCSSMatrix()),translateOpen='translate'+(has3d?'3d(':'('),translateClose=has3d?',0)':')';followAlong.prototype={handleEvent:function(e){if(e.type=='scroll'){this.follow(e);}},follow:function(e){var that=this,scrollX=window.scrollX,scrollY=window.scrollY;if(window.scrollX>that.x1||window.scrollY>that.y1){that.element.className=that.element.className?that.element.className+' float':'float';that.element.style.left=that.x1+'px';that.element.style.top=that.y1+'px';that.element.style.webkitTransform=translateOpen+scrollX+'px,'+scrollY+'px'+translateClose;}else{that.element.style.webkitTransform=translateOpen+'0,0'+translateClose;that.element.className=that.element.className.replace(/(^|\\s)float(\\s|$)/gi,'');that.element.style.left='';that.element.style.top='';}}}
window.followAlong=followAlong;})();;(function($){"use strict";var feature={};feature.fileapi=$("<input type='file'/>").get(0).files!==undefined;feature.formdata=window.FormData!==undefined;$.fn.ajaxSubmit=function(options){if(!this.length){log('ajaxSubmit: skipping submit process - no element selected');return this;}
var method,action,url,$form=this;if(typeof options=='function'){options={success:options};}
method=this.attr('method');action=this.attr('action');url=(typeof action==='string')?$.trim(action):'';url=url||window.location.href||'';if(url){url=(url.match(/^([^#]+)/)||[])[1];}
options=$.extend(true,{url:url,success:$.ajaxSettings.success,type:method||'GET',iframeSrc:/^https/i.test(window.location.href||'')?'javascript:false':'about:blank'},options);var veto={};this.trigger('form-pre-serialize',[this,options,veto]);if(veto.veto){log('ajaxSubmit: submit vetoed via form-pre-serialize trigger');return this;}
if(options.beforeSerialize&&options.beforeSerialize(this,options)===false){log('ajaxSubmit: submit aborted via beforeSerialize callback');return this;}
var traditional=options.traditional;if(traditional===undefined){traditional=$.ajaxSettings.traditional;}
var qx,a=this.formToArray(options.semantic);if(options.data){options.extraData=options.data;qx=$.param(options.data,traditional);}
if(options.beforeSubmit&&options.beforeSubmit(a,this,options)===false){log('ajaxSubmit: submit aborted via beforeSubmit callback');return this;}
this.trigger('form-submit-validate',[a,this,options,veto]);if(veto.veto){log('ajaxSubmit: submit vetoed via form-submit-validate trigger');return this;}
var q=$.param(a,traditional);if(qx){q=(q?(q+'&'+qx):qx);}
if(options.type.toUpperCase()=='GET'){options.url+=(options.url.indexOf('?')>=0?'&':'?')+q;options.data=null;}
else{options.data=q;}
var callbacks=[];if(options.resetForm){callbacks.push(function(){$form.resetForm();});}
if(options.clearForm){callbacks.push(function(){$form.clearForm(options.includeHidden);});}
if(!options.dataType&&options.target){var oldSuccess=options.success||function(){};callbacks.push(function(data){var fn=options.replaceTarget?'replaceWith':'html';$(options.target)[fn](data).each(oldSuccess,arguments);});}
else if(options.success){callbacks.push(options.success);}
options.success=function(data,status,xhr){var context=options.context||options;for(var i=0,max=callbacks.length;i<max;i++){callbacks[i].apply(context,[data,status,xhr||$form,$form]);}};var fileInputs=$('input:file:enabled[value]',this);var hasFileInputs=fileInputs.length>0;var mp='multipart/form-data';var multipart=($form.attr('enctype')==mp||$form.attr('encoding')==mp);var fileAPI=feature.fileapi&&feature.formdata;log("fileAPI :"+fileAPI);var shouldUseFrame=(hasFileInputs||multipart)&&!fileAPI;if(options.iframe!==false&&(options.iframe||shouldUseFrame)){if(options.closeKeepAlive){$.get(options.closeKeepAlive,function(){fileUploadIframe(a);});}
else{fileUploadIframe(a);}}
else if((hasFileInputs||multipart)&&fileAPI){fileUploadXhr(a);}
else{$.ajax(options);}
this.trigger('form-submit-notify',[this,options]);return this;function fileUploadXhr(a){var formdata=new FormData();for(var i=0;i<a.length;i++){formdata.append(a[i].name,a[i].value);}
if(options.extraData){for(var k in options.extraData)
if(options.extraData.hasOwnProperty(k))
formdata.append(k,options.extraData[k]);}
options.data=null;var s=$.extend(true,{},$.ajaxSettings,options,{contentType:false,processData:false,cache:false,type:'POST'});if(options.uploadProgress){s.xhr=function(){var xhr=jQuery.ajaxSettings.xhr();if(xhr.upload){xhr.upload.onprogress=function(event){var percent=0;if(event.lengthComputable)
percent=parseInt((event.position/event.total)*100,10);options.uploadProgress(event,event.position,event.total,percent);}}
return xhr;}}
s.data=null;var beforeSend=s.beforeSend;s.beforeSend=function(xhr,o){o.data=formdata;if(beforeSend)
beforeSend.call(o,xhr,options);};$.ajax(s);}
function fileUploadIframe(a){var form=$form[0],el,i,s,g,id,$io,io,xhr,sub,n,timedOut,timeoutHandle;var useProp=!!$.fn.prop;if(a){if(useProp){for(i=0;i<a.length;i++){el=$(form[a[i].name]);el.prop('disabled',false);}}else{for(i=0;i<a.length;i++){el=$(form[a[i].name]);el.removeAttr('disabled');}}}
if($(':input[name=submit],:input[id=submit]',form).length){alert('Error: Form elements must not have name or id of "submit".');return;}
s=$.extend(true,{},$.ajaxSettings,options);s.context=s.context||s;id='jqFormIO'+(new Date().getTime());if(s.iframeTarget){$io=$(s.iframeTarget);n=$io.attr('name');if(!n)
$io.attr('name',id);else
id=n;}
else{$io=$('<iframe name="'+id+'" src="'+s.iframeSrc+'" />');$io.css({position:'absolute',top:'-1000px',left:'-1000px'});}
io=$io[0];xhr={aborted:0,responseText:null,responseXML:null,status:0,statusText:'n/a',getAllResponseHeaders:function(){},getResponseHeader:function(){},setRequestHeader:function(){},abort:function(status){var e=(status==='timeout'?'timeout':'aborted');log('aborting upload... '+e);this.aborted=1;$io.attr('src',s.iframeSrc);xhr.error=e;if(s.error)
s.error.call(s.context,xhr,e,status);if(g)
$.event.trigger("ajaxError",[xhr,s,e]);if(s.complete)
s.complete.call(s.context,xhr,e);}};g=s.global;if(g&&0===$.active++){$.event.trigger("ajaxStart");}
if(g){$.event.trigger("ajaxSend",[xhr,s]);}
if(s.beforeSend&&s.beforeSend.call(s.context,xhr,s)===false){if(s.global){$.active--;}
return;}
if(xhr.aborted){return;}
sub=form.clk;if(sub){n=sub.name;if(n&&!sub.disabled){s.extraData=s.extraData||{};s.extraData[n]=sub.value;if(sub.type=="image"){s.extraData[n+'.x']=form.clk_x;s.extraData[n+'.y']=form.clk_y;}}}
var CLIENT_TIMEOUT_ABORT=1;var SERVER_ABORT=2;function getDoc(frame){var doc=frame.contentWindow?frame.contentWindow.document:frame.contentDocument?frame.contentDocument:frame.document;return doc;}
var csrf_token=$('meta[name=csrf-token]').attr('content');var csrf_param=$('meta[name=csrf-param]').attr('content');if(csrf_param&&csrf_token){s.extraData=s.extraData||{};s.extraData[csrf_param]=csrf_token;}
function doSubmit(){var t=$form.attr('target'),a=$form.attr('action');form.setAttribute('target',id);if(!method){form.setAttribute('method','POST');}
if(a!=s.url){form.setAttribute('action',s.url);}
if(!s.skipEncodingOverride&&(!method||/post/i.test(method))){$form.attr({encoding:'multipart/form-data',enctype:'multipart/form-data'});}
if(s.timeout){timeoutHandle=setTimeout(function(){timedOut=true;cb(CLIENT_TIMEOUT_ABORT);},s.timeout);}
function checkState(){try{var state=getDoc(io).readyState;log('state = '+state);if(state&&state.toLowerCase()=='uninitialized')
setTimeout(checkState,50);}
catch(e){log('Server abort: ',e,' (',e.name,')');cb(SERVER_ABORT);if(timeoutHandle)
clearTimeout(timeoutHandle);timeoutHandle=undefined;}}
var extraInputs=[];try{if(s.extraData){for(var n in s.extraData){if(s.extraData.hasOwnProperty(n)){extraInputs.push($('<input type="hidden" name="'+n+'">').attr('value',s.extraData[n]).appendTo(form)[0]);}}}
if(!s.iframeTarget){$io.appendTo('body');if(io.attachEvent)
io.attachEvent('onload',cb);else
io.addEventListener('load',cb,false);}
setTimeout(checkState,15);form.submit();}
finally{form.setAttribute('action',a);if(t){form.setAttribute('target',t);}else{$form.removeAttr('target');}
$(extraInputs).remove();}}
if(s.forceSync){doSubmit();}
else{setTimeout(doSubmit,10);}
var data,doc,domCheckCount=50,callbackProcessed;function cb(e){if(xhr.aborted||callbackProcessed){return;}
try{doc=getDoc(io);}
catch(ex){log('cannot access response document: ',ex);e=SERVER_ABORT;}
if(e===CLIENT_TIMEOUT_ABORT&&xhr){xhr.abort('timeout');return;}
else if(e==SERVER_ABORT&&xhr){xhr.abort('server abort');return;}
if(!doc||doc.location.href==s.iframeSrc){if(!timedOut)
return;}
if(io.detachEvent)
io.detachEvent('onload',cb);else
io.removeEventListener('load',cb,false);var status='success',errMsg;try{if(timedOut){throw'timeout';}
var isXml=s.dataType=='xml'||doc.XMLDocument||$.isXMLDoc(doc);log('isXml='+isXml);if(!isXml&&window.opera&&(doc.body===null||!doc.body.innerHTML)){if(--domCheckCount){log('requeing onLoad callback, DOM not available');setTimeout(cb,250);return;}}
var docRoot=doc.body?doc.body:doc.documentElement;xhr.responseText=docRoot?docRoot.innerHTML:null;xhr.responseXML=doc.XMLDocument?doc.XMLDocument:doc;if(isXml)
s.dataType='xml';xhr.getResponseHeader=function(header){var headers={'content-type':s.dataType};return headers[header];};if(docRoot){xhr.status=Number(docRoot.getAttribute('status'))||xhr.status;xhr.statusText=docRoot.getAttribute('statusText')||xhr.statusText;}
var dt=(s.dataType||'').toLowerCase();var scr=/(json|script|text)/.test(dt);if(scr||s.textarea){var ta=doc.getElementsByTagName('textarea')[0];if(ta){xhr.responseText=ta.value;xhr.status=Number(ta.getAttribute('status'))||xhr.status;xhr.statusText=ta.getAttribute('statusText')||xhr.statusText;}
else if(scr){var pre=doc.getElementsByTagName('pre')[0];var b=doc.getElementsByTagName('body')[0];if(pre){xhr.responseText=pre.textContent?pre.textContent:pre.innerText;}
else if(b){xhr.responseText=b.textContent?b.textContent:b.innerText;}}}
else if(dt=='xml'&&!xhr.responseXML&&xhr.responseText){xhr.responseXML=toXml(xhr.responseText);}
try{data=httpData(xhr,dt,s);}
catch(e){status='parsererror';xhr.error=errMsg=(e||status);}}
catch(e){log('error caught: ',e);status='error';xhr.error=errMsg=(e||status);}
if(xhr.aborted){log('upload aborted');status=null;}
if(xhr.status){status=(xhr.status>=200&&xhr.status<300||xhr.status===304)?'success':'error';}
if(status==='success'){if(s.success)
s.success.call(s.context,data,'success',xhr);if(g)
$.event.trigger("ajaxSuccess",[xhr,s]);}
else if(status){if(errMsg===undefined)
errMsg=xhr.statusText;if(s.error)
s.error.call(s.context,xhr,status,errMsg);if(g)
$.event.trigger("ajaxError",[xhr,s,errMsg]);}
if(g)
$.event.trigger("ajaxComplete",[xhr,s]);if(g&&!--$.active){$.event.trigger("ajaxStop");}
if(s.complete)
s.complete.call(s.context,xhr,status);callbackProcessed=true;if(s.timeout)
clearTimeout(timeoutHandle);setTimeout(function(){if(!s.iframeTarget)
$io.remove();xhr.responseXML=null;},100);}
var toXml=$.parseXML||function(s,doc){if(window.ActiveXObject){doc=new ActiveXObject('Microsoft.XMLDOM');doc.async='false';doc.loadXML(s);}
else{doc=(new DOMParser()).parseFromString(s,'text/xml');}
return(doc&&doc.documentElement&&doc.documentElement.nodeName!='parsererror')?doc:null;};var parseJSON=$.parseJSON||function(s){return window['eval']('('+s+')');};var httpData=function(xhr,type,s){var ct=xhr.getResponseHeader('content-type')||'',xml=type==='xml'||!type&&ct.indexOf('xml')>=0,data=xml?xhr.responseXML:xhr.responseText;if(xml&&data.documentElement.nodeName==='parsererror'){if($.error)
$.error('parsererror');}
if(s&&s.dataFilter){data=s.dataFilter(data,type);}
if(typeof data==='string'){if(type==='json'||!type&&ct.indexOf('json')>=0){data=parseJSON(data);}else if(type==="script"||!type&&ct.indexOf("javascript")>=0){$.globalEval(data);}}
return data;};}};$.fn.ajaxForm=function(options){options=options||{};options.delegation=options.delegation&&$.isFunction($.fn.on);if(!options.delegation&&this.length===0){var o={s:this.selector,c:this.context};if(!$.isReady&&o.s){log('DOM not ready, queuing ajaxForm');$(function(){$(o.s,o.c).ajaxForm(options);});return this;}
log('terminating; zero elements found by selector'+($.isReady?'':' (DOM not ready)'));return this;}
if(options.delegation){$(document).off('submit.form-plugin',this.selector,doAjaxSubmit).off('click.form-plugin',this.selector,captureSubmittingElement).on('submit.form-plugin',this.selector,options,doAjaxSubmit).on('click.form-plugin',this.selector,options,captureSubmittingElement);return this;}
return this.ajaxFormUnbind().bind('submit.form-plugin',options,doAjaxSubmit).bind('click.form-plugin',options,captureSubmittingElement);};function doAjaxSubmit(e){var options=e.data;if(!e.isDefaultPrevented()){e.preventDefault();$(this).ajaxSubmit(options);}}
function captureSubmittingElement(e){var target=e.target;var $el=$(target);if(!($el.is(":submit,input:image"))){var t=$el.closest(':submit');if(t.length===0){return;}
target=t[0];}
var form=this;form.clk=target;if(target.type=='image'){if(e.offsetX!==undefined){form.clk_x=e.offsetX;form.clk_y=e.offsetY;}else if(typeof $.fn.offset=='function'){var offset=$el.offset();form.clk_x=e.pageX-offset.left;form.clk_y=e.pageY-offset.top;}else{form.clk_x=e.pageX-target.offsetLeft;form.clk_y=e.pageY-target.offsetTop;}}
setTimeout(function(){form.clk=form.clk_x=form.clk_y=null;},100);}
$.fn.ajaxFormUnbind=function(){return this.unbind('submit.form-plugin click.form-plugin');};$.fn.formToArray=function(semantic){var a=[];if(this.length===0){return a;}
var form=this[0];var els=semantic?form.getElementsByTagName('*'):form.elements;if(!els){return a;}
var i,j,n,v,el,max,jmax;for(i=0,max=els.length;i<max;i++){el=els[i];n=el.name;if(!n){continue;}
if(semantic&&form.clk&&el.type=="image"){if(!el.disabled&&form.clk==el){a.push({name:n,value:$(el).val(),type:el.type});a.push({name:n+'.x',value:form.clk_x},{name:n+'.y',value:form.clk_y});}
continue;}
v=$.fieldValue(el,true);if(v&&v.constructor==Array){for(j=0,jmax=v.length;j<jmax;j++){a.push({name:n,value:v[j]});}}
else if(feature.fileapi&&el.type=='file'&&!el.disabled){var files=el.files;for(j=0;j<files.length;j++){a.push({name:n,value:files[j],type:el.type});}}
else if(v!==null&&typeof v!='undefined'){a.push({name:n,value:v,type:el.type});}}
if(!semantic&&form.clk){var $input=$(form.clk),input=$input[0];n=input.name;if(n&&!input.disabled&&input.type=='image'){a.push({name:n,value:$input.val()});a.push({name:n+'.x',value:form.clk_x},{name:n+'.y',value:form.clk_y});}}
return a;};$.fn.formSerialize=function(semantic){return $.param(this.formToArray(semantic));};$.fn.fieldSerialize=function(successful){var a=[];this.each(function(){var n=this.name;if(!n){return;}
var v=$.fieldValue(this,successful);if(v&&v.constructor==Array){for(var i=0,max=v.length;i<max;i++){a.push({name:n,value:v[i]});}}
else if(v!==null&&typeof v!='undefined'){a.push({name:this.name,value:v});}});return $.param(a);};$.fn.fieldValue=function(successful){for(var val=[],i=0,max=this.length;i<max;i++){var el=this[i];var v=$.fieldValue(el,successful);if(v===null||typeof v=='undefined'||(v.constructor==Array&&!v.length)){continue;}
if(v.constructor==Array)
$.merge(val,v);else
val.push(v);}
return val;};$.fieldValue=function(el,successful){var n=el.name,t=el.type,tag=el.tagName.toLowerCase();if(successful===undefined){successful=true;}
if(successful&&(!n||el.disabled||t=='reset'||t=='button'||(t=='checkbox'||t=='radio')&&!el.checked||(t=='submit'||t=='image')&&el.form&&el.form.clk!=el||tag=='select'&&el.selectedIndex==-1)){return null;}
if(tag=='select'){var index=el.selectedIndex;if(index<0){return null;}
var a=[],ops=el.options;var one=(t=='select-one');var max=(one?index+1:ops.length);for(var i=(one?index:0);i<max;i++){var op=ops[i];if(op.selected){var v=op.value;if(!v){v=(op.attributes&&op.attributes['value']&&!(op.attributes['value'].specified))?op.text:op.value;}
if(one){return v;}
a.push(v);}}
return a;}
return $(el).val();};$.fn.clearForm=function(includeHidden){return this.each(function(){$('input,select,textarea',this).clearFields(includeHidden);});};$.fn.clearFields=$.fn.clearInputs=function(includeHidden){var re=/^(?:color|date|datetime|email|month|number|password|range|search|tel|text|time|url|week)$/i;return this.each(function(){var t=this.type,tag=this.tagName.toLowerCase();if(re.test(t)||tag=='textarea'||(includeHidden&&/hidden/.test(t))){this.value='';}
else if(t=='checkbox'||t=='radio'){this.checked=false;}
else if(tag=='select'){this.selectedIndex=-1;}});};$.fn.resetForm=function(){return this.each(function(){if(typeof this.reset=='function'||(typeof this.reset=='object'&&!this.reset.nodeType)){this.reset();}});};$.fn.enable=function(b){if(b===undefined){b=true;}
return this.each(function(){this.disabled=!b;});};$.fn.selected=function(select){if(select===undefined){select=true;}
return this.each(function(){var t=this.type;if(t=='checkbox'||t=='radio'){this.checked=select;}
else if(this.tagName.toLowerCase()=='option'){var $sel=$(this).parent('select');if(select&&$sel[0]&&$sel[0].type=='select-one'){$sel.find('option').selected(false);}
this.selected=select;}});};$.fn.ajaxSubmit.debug=false;function log(){if(!$.fn.ajaxSubmit.debug)
return;var msg='[jquery.form] '+Array.prototype.join.call(arguments,'');if(window.console&&window.console.log){window.console.log(msg);}
else if(window.opera&&window.opera.postError){window.opera.postError(msg);}}})(jQuery);(function($){$.extend($.fn,{validate:function(options){if(!this.length){if(options&&options.debug&&window.console){console.warn("nothing selected, can't validate, returning nothing");}
return;}
var validator=$.data(this[0],'validator');if(validator){return validator;}
this.attr('novalidate','novalidate');validator=new $.validator(options,this[0]);$.data(this[0],'validator',validator);if(validator.settings.onsubmit){this.validateDelegate(":submit","click",function(ev){if(validator.settings.submitHandler){validator.submitButton=ev.target;}
if($(ev.target).hasClass('cancel')){validator.cancelSubmit=true;}});this.submit(function(event){if(validator.settings.debug){event.preventDefault();}
function handle(){var hidden;if(validator.settings.submitHandler){if(validator.submitButton){hidden=$("<input type='hidden'/>").attr("name",validator.submitButton.name).val(validator.submitButton.value).appendTo(validator.currentForm);}
validator.settings.submitHandler.call(validator,validator.currentForm,event);if(validator.submitButton){hidden.remove();}
return false;}
return true;}
if(validator.cancelSubmit){validator.cancelSubmit=false;return handle();}
if(validator.form()){if(validator.pendingRequest){validator.formSubmitted=true;return false;}
return handle();}else{validator.focusInvalid();return false;}});}
return validator;},valid:function(){if($(this[0]).is('form')){return this.validate().form();}else{var valid=true;var validator=$(this[0].form).validate();this.each(function(){valid&=validator.element(this);});return valid;}},removeAttrs:function(attributes){var result={},$element=this;$.each(attributes.split(/\s/),function(index,value){result[value]=$element.attr(value);$element.removeAttr(value);});return result;},rules:function(command,argument){var element=this[0];if(command){var settings=$.data(element.form,'validator').settings;var staticRules=settings.rules;var existingRules=$.validator.staticRules(element);switch(command){case"add":$.extend(existingRules,$.validator.normalizeRule(argument));staticRules[element.name]=existingRules;if(argument.messages){settings.messages[element.name]=$.extend(settings.messages[element.name],argument.messages);}
break;case"remove":if(!argument){delete staticRules[element.name];return existingRules;}
var filtered={};$.each(argument.split(/\s/),function(index,method){filtered[method]=existingRules[method];delete existingRules[method];});return filtered;}}
var data=$.validator.normalizeRules($.extend({},$.validator.metadataRules(element),$.validator.classRules(element),$.validator.attributeRules(element),$.validator.staticRules(element)),element);if(data.required){var param=data.required;delete data.required;data=$.extend({required:param},data);}
return data;}});$.extend($.expr[":"],{blank:function(a){return!$.trim(""+a.value);},filled:function(a){return!!$.trim(""+a.value);},unchecked:function(a){return!a.checked;}});$.validator=function(options,form){this.settings=$.extend(true,{},$.validator.defaults,options);this.currentForm=form;this.init();};$.validator.format=function(source,params){if(arguments.length===1){return function(){var args=$.makeArray(arguments);args.unshift(source);return $.validator.format.apply(this,args);};}
if(arguments.length>2&&params.constructor!==Array){params=$.makeArray(arguments).slice(1);}
if(params.constructor!==Array){params=[params];}
$.each(params,function(i,n){source=source.replace(new RegExp("\\{"+i+"\\}","g"),n);});return source;};$.extend($.validator,{defaults:{messages:{},groups:{},rules:{},errorClass:"error",validClass:"valid",errorElement:"label",focusInvalid:true,errorContainer:$([]),errorLabelContainer:$([]),onsubmit:true,ignore:":hidden",ignoreTitle:false,onfocusin:function(element,event){this.lastActive=element;if(this.settings.focusCleanup&&!this.blockFocusCleanup){if(this.settings.unhighlight){this.settings.unhighlight.call(this,element,this.settings.errorClass,this.settings.validClass);}
this.addWrapper(this.errorsFor(element)).hide();}},onfocusout:function(element,event){if(!this.checkable(element)&&(element.name in this.submitted||!this.optional(element))){this.element(element);}},onkeyup:function(element,event){if(element.name in this.submitted||element===this.lastElement){this.element(element);}},onclick:function(element,event){if(element.name in this.submitted){this.element(element);}
else if(element.parentNode.name in this.submitted){this.element(element.parentNode);}},highlight:function(element,errorClass,validClass){if(element.type==='radio'){this.findByName(element.name).addClass(errorClass).removeClass(validClass);}else{$(element).addClass(errorClass).removeClass(validClass);}},unhighlight:function(element,errorClass,validClass){if(element.type==='radio'){this.findByName(element.name).removeClass(errorClass).addClass(validClass);}else{$(element).removeClass(errorClass).addClass(validClass);}}},setDefaults:function(settings){$.extend($.validator.defaults,settings);},messages:{required:"This field is required.",remote:"Please fix this field.",email:"Please enter a valid email address.",url:"Please enter a valid URL.",date:"Please enter a valid date.",dateISO:"Please enter a valid date (ISO).",number:"Please enter a valid number.",digits:"Please enter only digits.",creditcard:"Please enter a valid credit card number.",equalTo:"Please enter the same value again.",accept:"Please enter a value with a valid extension.",maxlength:$.validator.format("Please enter no more than {0} characters."),minlength:$.validator.format("Please enter at least {0} characters."),rangelength:$.validator.format("Please enter a value between {0} and {1} characters long."),range:$.validator.format("Please enter a value between {0} and {1}."),max:$.validator.format("Please enter a value less than or equal to {0}."),min:$.validator.format("Please enter a value greater than or equal to {0}.")},autoCreateRanges:false,prototype:{init:function(){this.labelContainer=$(this.settings.errorLabelContainer);this.errorContext=this.labelContainer.length&&this.labelContainer||$(this.currentForm);this.containers=$(this.settings.errorContainer).add(this.settings.errorLabelContainer);this.submitted={};this.valueCache={};this.pendingRequest=0;this.pending={};this.invalid={};this.reset();var groups=(this.groups={});$.each(this.settings.groups,function(key,value){$.each(value.split(/\s/),function(index,name){groups[name]=key;});});var rules=this.settings.rules;$.each(rules,function(key,value){rules[key]=$.validator.normalizeRule(value);});function delegate(event){var validator=$.data(this[0].form,"validator"),eventType="on"+event.type.replace(/^validate/,"");if(validator.settings[eventType]){validator.settings[eventType].call(validator,this[0],event);}}
$(this.currentForm).validateDelegate("[type='text'], [type='password'], [type='file'], select, textarea, "+"[type='number'], [type='search'] ,[type='tel'], [type='url'], "+"[type='email'], [type='datetime'], [type='date'], [type='month'], "+"[type='week'], [type='time'], [type='datetime-local'], "+"[type='range'], [type='color'] ","focusin focusout keyup",delegate).validateDelegate("[type='radio'], [type='checkbox'], select, option","click",delegate);if(this.settings.invalidHandler){$(this.currentForm).bind("invalid-form.validate",this.settings.invalidHandler);}},form:function(){this.checkForm();$.extend(this.submitted,this.errorMap);this.invalid=$.extend({},this.errorMap);if(!this.valid()){$(this.currentForm).triggerHandler("invalid-form",[this]);}
this.showErrors();return this.valid();},checkForm:function(){this.prepareForm();for(var i=0,elements=(this.currentElements=this.elements());elements[i];i++){this.check(elements[i]);}
return this.valid();},element:function(element){element=this.validationTargetFor(this.clean(element));this.lastElement=element;this.prepareElement(element);this.currentElements=$(element);var result=this.check(element)!==false;if(result){delete this.invalid[element.name];}else{this.invalid[element.name]=true;}
if(!this.numberOfInvalids()){this.toHide=this.toHide.add(this.containers);}
this.showErrors();return result;},showErrors:function(errors){if(errors){$.extend(this.errorMap,errors);this.errorList=[];for(var name in errors){this.errorList.push({message:errors[name],element:this.findByName(name)[0]});}
this.successList=$.grep(this.successList,function(element){return!(element.name in errors);});}
if(this.settings.showErrors){this.settings.showErrors.call(this,this.errorMap,this.errorList);}else{this.defaultShowErrors();}},resetForm:function(){if($.fn.resetForm){$(this.currentForm).resetForm();}
this.submitted={};this.lastElement=null;this.prepareForm();this.hideErrors();this.elements().removeClass(this.settings.errorClass);},numberOfInvalids:function(){return this.objectLength(this.invalid);},objectLength:function(obj){var count=0;for(var i in obj){count++;}
return count;},hideErrors:function(){this.addWrapper(this.toHide).hide();},valid:function(){return this.size()===0;},size:function(){return this.errorList.length;},focusInvalid:function(){if(this.settings.focusInvalid){try{$(this.findLastActive()||this.errorList.length&&this.errorList[0].element||[]).filter(":visible").focus().trigger("focusin");}catch(e){}}},findLastActive:function(){var lastActive=this.lastActive;return lastActive&&$.grep(this.errorList,function(n){return n.element.name===lastActive.name;}).length===1&&lastActive;},elements:function(){var validator=this,rulesCache={};return $(this.currentForm).find("input, select, textarea").not(":submit, :reset, :image, [disabled]").not(this.settings.ignore).filter(function(){if(!this.name&&validator.settings.debug&&window.console){console.error("%o has no name assigned",this);}
if(this.name in rulesCache||!validator.objectLength($(this).rules())){return false;}
rulesCache[this.name]=true;return true;});},clean:function(selector){return $(selector)[0];},errors:function(){var errorClass=this.settings.errorClass.replace(' ','.');return $(this.settings.errorElement+"."+errorClass,this.errorContext);},reset:function(){this.successList=[];this.errorList=[];this.errorMap={};this.toShow=$([]);this.toHide=$([]);this.currentElements=$([]);},prepareForm:function(){this.reset();this.toHide=this.errors().add(this.containers);},prepareElement:function(element){this.reset();this.toHide=this.errorsFor(element);},elementValue:function(element){var val=$(element).val();if(typeof val==='string'){return val.replace(/\r/g,"");}
return val;},check:function(element){element=this.validationTargetFor(this.clean(element));var rules=$(element).rules();var dependencyMismatch=false;var val=this.elementValue(element);var result;for(var method in rules){var rule={method:method,parameters:rules[method]};try{result=$.validator.methods[method].call(this,val,element,rule.parameters);if(result==="dependency-mismatch"){dependencyMismatch=true;continue;}
dependencyMismatch=false;if(result==="pending"){this.toHide=this.toHide.not(this.errorsFor(element));return;}
if(!result){this.formatAndAdd(element,rule);return false;}}catch(e){if(this.settings.debug&&window.console){console.log("exception occured when checking element "+element.id+", check the '"+rule.method+"' method",e);}
throw e;}}
if(dependencyMismatch){return;}
if(this.objectLength(rules)){this.successList.push(element);}
return true;},customMetaMessage:function(element,method){if(!$.metadata){return;}
var meta=this.settings.meta?$(element).metadata()[this.settings.meta]:$(element).metadata();return meta&&meta.messages&&meta.messages[method];},customMessage:function(name,method){var m=this.settings.messages[name];return m&&(m.constructor===String?m:m[method]);},findDefined:function(){for(var i=0;i<arguments.length;i++){if(arguments[i]!==undefined){return arguments[i];}}
return undefined;},defaultMessage:function(element,method){return this.findDefined(this.customMessage(element.name,method),this.customMetaMessage(element,method),!this.settings.ignoreTitle&&element.title||undefined,$.validator.messages[method],"<strong>Warning: No message defined for "+element.name+"</strong>");},formatAndAdd:function(element,rule){var message=this.defaultMessage(element,rule.method),theregex=/\$?\{(\d+)\}/g;if(typeof message==="function"){message=message.call(this,rule.parameters,element);}else if(theregex.test(message)){message=$.validator.format(message.replace(theregex,'{$1}'),rule.parameters);}
this.errorList.push({message:message,element:element});this.errorMap[element.name]=message;this.submitted[element.name]=message;},addWrapper:function(toToggle){if(this.settings.wrapper){toToggle=toToggle.add(toToggle.parent(this.settings.wrapper));}
return toToggle;},defaultShowErrors:function(){var i,elements;for(i=0;this.errorList[i];i++){var error=this.errorList[i];if(this.settings.highlight){this.settings.highlight.call(this,error.element,this.settings.errorClass,this.settings.validClass);}
this.showLabel(error.element,error.message);}
if(this.errorList.length){this.toShow=this.toShow.add(this.containers);}
if(this.settings.success){for(i=0;this.successList[i];i++){this.showLabel(this.successList[i]);}}
if(this.settings.unhighlight){for(i=0,elements=this.validElements();elements[i];i++){this.settings.unhighlight.call(this,elements[i],this.settings.errorClass,this.settings.validClass);}}
this.toHide=this.toHide.not(this.toShow);this.hideErrors();this.addWrapper(this.toShow).show();},validElements:function(){return this.currentElements.not(this.invalidElements());},invalidElements:function(){return $(this.errorList).map(function(){return this.element;});},showLabel:function(element,message){var label=this.errorsFor(element);if(label.length){label.removeClass(this.settings.validClass).addClass(this.settings.errorClass);if(label.attr("generated")){label.html(message);}}else{label=$("<"+this.settings.errorElement+"/>").attr({"for":this.idOrName(element),generated:true}).addClass(this.settings.errorClass).html(message||"");if(this.settings.wrapper){label=label.hide().show().wrap("<"+this.settings.wrapper+"/>").parent();}
if(!this.labelContainer.append(label).length){if(this.settings.errorPlacement){this.settings.errorPlacement(label,$(element));}else{label.insertAfter(element);}}}
if(!message&&this.settings.success){label.text("");if(typeof this.settings.success==="string"){label.addClass(this.settings.success);}else{this.settings.success(label);}}
this.toShow=this.toShow.add(label);},errorsFor:function(element){var name=this.idOrName(element);return this.errors().filter(function(){return $(this).attr('for')===name;});},idOrName:function(element){return this.groups[element.name]||(this.checkable(element)?element.name:element.id||element.name);},validationTargetFor:function(element){if(this.checkable(element)){element=this.findByName(element.name).not(this.settings.ignore)[0];}
return element;},checkable:function(element){return(/radio|checkbox/i).test(element.type);},findByName:function(name){var form=this.currentForm;return $(document.getElementsByName(name)).map(function(index,element){return element.form===form&&element.name===name&&element||null;});},getLength:function(value,element){switch(element.nodeName.toLowerCase()){case'select':return $("option:selected",element).length;case'input':if(this.checkable(element)){return this.findByName(element.name).filter(':checked').length;}}
return value.length;},depend:function(param,element){return this.dependTypes[typeof param]?this.dependTypes[typeof param](param,element):true;},dependTypes:{"boolean":function(param,element){return param;},"string":function(param,element){return!!$(param,element.form).length;},"function":function(param,element){return param(element);}},optional:function(element){var val=this.elementValue(element);return!$.validator.methods.required.call(this,val,element)&&"dependency-mismatch";},startRequest:function(element){if(!this.pending[element.name]){this.pendingRequest++;this.pending[element.name]=true;}},stopRequest:function(element,valid){this.pendingRequest--;if(this.pendingRequest<0){this.pendingRequest=0;}
delete this.pending[element.name];if(valid&&this.pendingRequest===0&&this.formSubmitted&&this.form()){$(this.currentForm).submit();this.formSubmitted=false;}else if(!valid&&this.pendingRequest===0&&this.formSubmitted){$(this.currentForm).triggerHandler("invalid-form",[this]);this.formSubmitted=false;}},previousValue:function(element){return $.data(element,"previousValue")||$.data(element,"previousValue",{old:null,valid:true,message:this.defaultMessage(element,"remote")});}},classRuleSettings:{required:{required:true},email:{email:true},url:{url:true},date:{date:true},dateISO:{dateISO:true},number:{number:true},digits:{digits:true},creditcard:{creditcard:true}},addClassRules:function(className,rules){if(className.constructor===String){this.classRuleSettings[className]=rules;}else{$.extend(this.classRuleSettings,className);}},classRules:function(element){var rules={};var classes=$(element).attr('class');if(classes){$.each(classes.split(' '),function(){if(this in $.validator.classRuleSettings){$.extend(rules,$.validator.classRuleSettings[this]);}});}
return rules;},attributeRules:function(element){var rules={};var $element=$(element);for(var method in $.validator.methods){var value;if(method==='required'){value=$element.get(0).getAttribute(method);if(value===""){value=true;}
value=!!value;}else{value=$element.attr(method);}
if(value){rules[method]=value;}else if($element[0].getAttribute("type")===method){rules[method]=true;}}
if(rules.maxlength&&/-1|2147483647|524288/.test(rules.maxlength)){delete rules.maxlength;}
return rules;},metadataRules:function(element){if(!$.metadata){return{};}
var meta=$.data(element.form,'validator').settings.meta;return meta?$(element).metadata()[meta]:$(element).metadata();},staticRules:function(element){var rules={};var validator=$.data(element.form,'validator');if(validator.settings.rules){rules=$.validator.normalizeRule(validator.settings.rules[element.name])||{};}
return rules;},normalizeRules:function(rules,element){$.each(rules,function(prop,val){if(val===false){delete rules[prop];return;}
if(val.param||val.depends){var keepRule=true;switch(typeof val.depends){case"string":keepRule=!!$(val.depends,element.form).length;break;case"function":keepRule=val.depends.call(element,element);break;}
if(keepRule){rules[prop]=val.param!==undefined?val.param:true;}else{delete rules[prop];}}});$.each(rules,function(rule,parameter){rules[rule]=$.isFunction(parameter)?parameter(element):parameter;});$.each(['minlength','maxlength','min','max'],function(){if(rules[this]){rules[this]=Number(rules[this]);}});$.each(['rangelength','range'],function(){if(rules[this]){rules[this]=[Number(rules[this][0]),Number(rules[this][1])];}});if($.validator.autoCreateRanges){if(rules.min&&rules.max){rules.range=[rules.min,rules.max];delete rules.min;delete rules.max;}
if(rules.minlength&&rules.maxlength){rules.rangelength=[rules.minlength,rules.maxlength];delete rules.minlength;delete rules.maxlength;}}
if(rules.messages){delete rules.messages;}
return rules;},normalizeRule:function(data){if(typeof data==="string"){var transformed={};$.each(data.split(/\s/),function(){transformed[this]=true;});data=transformed;}
return data;},addMethod:function(name,method,message){$.validator.methods[name]=method;$.validator.messages[name]=message!==undefined?message:$.validator.messages[name];if(method.length<3){$.validator.addClassRules(name,$.validator.normalizeRule(name));}},methods:{required:function(value,element,param){if(!this.depend(param,element)){return"dependency-mismatch";}
if(element.nodeName.toLowerCase()==="select"){var val=$(element).val();return val&&val.length>0;}
if(this.checkable(element)){return this.getLength(value,element)>0;}
return $.trim(value).length>0;},remote:function(value,element,param){if(this.optional(element)){return"dependency-mismatch";}
var previous=this.previousValue(element);if(!this.settings.messages[element.name]){this.settings.messages[element.name]={};}
previous.originalMessage=this.settings.messages[element.name].remote;this.settings.messages[element.name].remote=previous.message;param=typeof param==="string"&&{url:param}||param;if(this.pending[element.name]){return"pending";}
if(previous.old===value){return previous.valid;}
previous.old=value;var validator=this;this.startRequest(element);var data={};data[element.name]=value;$.ajax($.extend(true,{url:param,mode:"abort",port:"validate"+element.name,dataType:"json",data:data,success:function(response){validator.settings.messages[element.name].remote=previous.originalMessage;var valid=response===true;if(valid){var submitted=validator.formSubmitted;validator.prepareElement(element);validator.formSubmitted=submitted;validator.successList.push(element);validator.showErrors();}else{var errors={};var message=response||validator.defaultMessage(element,"remote");errors[element.name]=previous.message=$.isFunction(message)?message(value):message;validator.showErrors(errors);}
previous.valid=valid;validator.stopRequest(element,valid);}},param));return"pending";},minlength:function(value,element,param){var length=$.isArray(value)?value.length:this.getLength($.trim(value),element);return this.optional(element)||length>=param;},maxlength:function(value,element,param){var length=$.isArray(value)?value.length:this.getLength($.trim(value),element);return this.optional(element)||length<=param;},rangelength:function(value,element,param){var length=$.isArray(value)?value.length:this.getLength($.trim(value),element);return this.optional(element)||(length>=param[0]&&length<=param[1]);},min:function(value,element,param){return this.optional(element)||value>=param;},max:function(value,element,param){return this.optional(element)||value<=param;},range:function(value,element,param){return this.optional(element)||(value>=param[0]&&value<=param[1]);},email:function(value,element){return this.optional(element)||/^((([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+(\.([a-z]|\d|[!#\$%&'\*\+\-\/=\?\^_`{\|}~]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])+)*)|((\x22)((((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(([\x01-\x08\x0b\x0c\x0e-\x1f\x7f]|\x21|[\x23-\x5b]|[\x5d-\x7e]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(\\([\x01-\x09\x0b\x0c\x0d-\x7f]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF]))))*(((\x20|\x09)*(\x0d\x0a))?(\x20|\x09)+)?(\x22)))@((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))$/i.test(value);},url:function(value,element){return this.optional(element)||/^(https?|ftp):\/\/(((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:)*@)?(((\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5])\.(\d|[1-9]\d|1\d\d|2[0-4]\d|25[0-5]))|((([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|\d|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.)+(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])*([a-z]|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])))\.?)(:\d*)?)(\/((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)+(\/(([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)*)*)?)?(\?((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|[\uE000-\uF8FF]|\/|\?)*)?(\#((([a-z]|\d|-|\.|_|~|[\u00A0-\uD7FF\uF900-\uFDCF\uFDF0-\uFFEF])|(%[\da-f]{2})|[!\$&'\(\)\*\+,;=]|:|@)|\/|\?)*)?$/i.test(value);},date:function(value,element){return this.optional(element)||!/Invalid|NaN/.test(new Date(value));},dateISO:function(value,element){return this.optional(element)||/^\d{4}[\/\-]\d{1,2}[\/\-]\d{1,2}$/.test(value);},number:function(value,element){return this.optional(element)||/^-?(?:\d+|\d{1,3}(?:,\d{3})+)?(?:\.\d+)?$/.test(value);},digits:function(value,element){return this.optional(element)||/^\d+$/.test(value);},creditcard:function(value,element){if(this.optional(element)){return"dependency-mismatch";}
if(/[^0-9 \-]+/.test(value)){return false;}
var nCheck=0,nDigit=0,bEven=false;value=value.replace(/\D/g,"");for(var n=value.length-1;n>=0;n--){var cDigit=value.charAt(n);nDigit=parseInt(cDigit,10);if(bEven){if((nDigit*=2)>9){nDigit-=9;}}
nCheck+=nDigit;bEven=!bEven;}
return(nCheck%10)===0;},accept:function(value,element,param){param=typeof param==="string"?param.replace(/,/g,'|'):"png|jpe?g|gif";return this.optional(element)||value.match(new RegExp(".("+param+")$","i"));},equalTo:function(value,element,param){var target=$(param).unbind(".validate-equalTo").bind("blur.validate-equalTo",function(){$(element).valid();});return value===target.val();}}});$.format=$.validator.format;}(jQuery));(function($){var pendingRequests={};if($.ajaxPrefilter){$.ajaxPrefilter(function(settings,_,xhr){var port=settings.port;if(settings.mode==="abort"){if(pendingRequests[port]){pendingRequests[port].abort();}
pendingRequests[port]=xhr;}});}else{var ajax=$.ajax;$.ajax=function(settings){var mode=("mode"in settings?settings:$.ajaxSettings).mode,port=("port"in settings?settings:$.ajaxSettings).port;if(mode==="abort"){if(pendingRequests[port]){pendingRequests[port].abort();}
return(pendingRequests[port]=ajax.apply(this,arguments));}
return ajax.apply(this,arguments);};}}(jQuery));(function($){if(!jQuery.event.special.focusin&&!jQuery.event.special.focusout&&document.addEventListener){$.each({focus:'focusin',blur:'focusout'},function(original,fix){$.event.special[fix]={setup:function(){this.addEventListener(original,handler,true);},teardown:function(){this.removeEventListener(original,handler,true);},handler:function(e){var args=arguments;args[0]=$.event.fix(e);args[0].type=fix;return $.event.handle.apply(this,args);}};function handler(e){e=$.event.fix(e);e.type=fix;return $.event.handle.call(this,e);}});}
$.extend($.fn,{validateDelegate:function(delegate,type,handler){return this.bind(type,function(event){var target=$(event.target);if(target.is(delegate)){return handler.apply(target,arguments);}});}});}(jQuery));(function($){$.fn.passwordStrength=function(username){score=0;password=$(this).val();if(password.length<4)
return 0;if(password.toLowerCase()==username.toLowerCase())
return 0;score+=password.length*4;score+=(checkRepetition(1,password).length-password.length)*1;score+=(checkRepetition(2,password).length-password.length)*1;score+=(checkRepetition(3,password).length-password.length)*1;score+=(checkRepetition(4,password).length-password.length)*1;if(password.match(/(.*[0-9].*[0-9].*[0-9])/))
score+=5;if(password.match(/(.*[!,@,#,$,%,^,&,*,?,_,~].*[!,@,#,$,%,^,&,*,?,_,~])/))
score+=5;if(password.match(/([a-z].*[A-Z])|([A-Z].*[a-z])/))
score+=10;if(password.match(/([a-zA-Z])/)&&password.match(/([0-9])/))
score+=15;if(password.match(/([!,@,#,$,%,^,&,*,?,_,~])/)&&password.match(/([0-9])/))
score+=15;if(password.match(/([!,@,#,$,%,^,&,*,?,_,~])/)&&password.match(/([a-zA-Z])/))
score+=15;if(password.match(/^\w+$/)||password.match(/^\d+$/))
score-=10;return score};function checkRepetition(pLen,str){res="";for(var i=0;i<str.length;i++){repeated=true;for(var j=0;j<pLen&&j
+i+pLen<str.length;j++)
repeated=repeated&&str.charAt(j
+i)==str.charAt(j
+i+pLen);if(j<pLen)
repeated=false;if(repeated){i+=pLen-1;repeated=false;}else
res+=str.charAt(i);}
return res;}
$.fn.gform=function(config){var config=this.config=$.extend({ajaxmode:true,onprepare:null,data:{__t:Math.random(),__data:"json"}},config||{});var me=this;var form=$(this);var eMsg=$(this).find("#error-message");if(eMsg.length>0)
eMsg.bind("click",function(){$(this).slideUp();});if($.isFunction(config.onprepare)){config.onprepare.apply(form);}
form.find('[data-input="date"]').each(function(){var dateconfig=$.extend({changeYear:true,dateFormat:me.config.dateFormat||(cgaf.getConfig("locale")==="id"?"dd/mm/yy":"mm/dd/yy"),regional:cgaf.getConfig("locale"),showOn:"button",defaultDate:null,buttonImage:"/assets/images/calendar_down.png"},me.config.dateconfig||{});if($(this).attr("minyear")!==undefined)
config.minDate=new Date(parseInt($(this).attr("minyear")),1,1);$(this).datepicker(dateconfig)});form.find('[data-input="autocomplete"]').each(function(){$this=$(this);$this.autocomplete({minLength:2,source:function(request,response){var term=request.term;if($this.cache){if(term in $this.cache){response($this.cache[term]);return;}}
lastXhr=$.getJSON($this.attr('srclookup'),request,function(data,status,xhr){$this.cache=$this.cache||{};$this.cache[term]=data;if(xhr===lastXhr){response(data);}});}});});form.find('[data-input="datetime"]').each(function(){$(this).timepicker();});form.find('[data-input="daterange"]').each(function(){if($(this).attr('data-time')){$(this).datetimepicker({timeOnly:false,timeFormat:'hh:mm:ss',dateFormat:cgaf.getConfig('dateInputFormat')});}else{$(this).datepicker({dateFormat:cgaf.getConfig('dateInputFormat')});}});var jax=form.attr("useajax")===undefined?config.ajaxmode:form.attr("useajax");var ajaxm=config.ajaxmode||typeof jax=="undefined"||jax==true;var validconfig=$.extend({debug:true,submitHandler:function(frm,e){if(ajaxm){e.preventDefault();var ajaxconfig=$.extend({dataType:"json",success:function(data,status,xhr){if(typeof data=="undefined"||data==null){$.showErrorMessage("Error  while processing request",$(form));return false}
if(!data._result)
for(k in data){var f=$(form).find("#"
+k);if(f.length>0)
f.val(data[k])}
else if(data._redirect)
location.href=data._redirect;else if(data.content)
$.fancybox({content:data.content});if(data.message)
$.showErrorMessage(data.message);return false},error:function(XMLHttpRequest,textStatus,errorThrown){if(textStatus==="parsererror"){try{var json=eval("("
+XMLHttpRequest.responseText
+")");}catch(e){$.showErrorMessage("Invalid JSON Data <br/>"
+XMLHttpRequest.responseText);return false;}
return this.success(json);}
return false;}},config);form.ajaxSubmit(ajaxconfig);}else{frm.submit();return false;}
return false;}},config||{});$(this).validate(validconfig);return false;};})(jQuery);;(function(factory){if(typeof define==='function'&&define.amd){define(factory);}else{window.purl=factory();}})(function(){var tag2attr={a:'href',img:'src',form:'action',base:'href',script:'src',iframe:'src',link:'href',embed:'src',object:'data'},key=['source','protocol','authority','userInfo','user','password','host','port','relative','path','directory','file','query','fragment'],aliases={'anchor':'fragment'},parser={strict:/^(?:([^:\/?#]+):)?(?:\/\/((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?))?((((?:[^?#\/]*\/)*)([^?#]*))(?:\?([^#]*))?(?:#(.*))?)/,loose:/^(?:(?![^:@]+:[^:@\/]*@)([^:\/?#.]+):)?(?:\/\/)?((?:(([^:@]*):?([^:@]*))?@)?([^:\/?#]*)(?::(\d*))?)(((\/(?:[^?#](?![^?#\/]*\.[^?#\/.]+(?:[?#]|$)))*\/?)?([^?#\/]*))(?:\?([^#]*))?(?:#(.*))?)/},isint=/^[0-9]+$/,querystring_parser=/(?:^|&|;)([^&=;]*)=?([^&;]*)/g,fragment_parser=/(?:^|&|;)([^&=;]*)=?([^&;]*)/g;function parseUri(url,strictMode){var str=decodeURI(url),res=parser[strictMode||false?'strict':'loose'].exec(str),uri={attr:{},param:{},seg:{}},i=14;while(i--){uri.attr[key[i]]=res[i]||'';}
uri.param['query']=parseString(uri.attr['query']);uri.param['fragment']=parseString(uri.attr['fragment']);uri.seg['path']=uri.attr.path.replace(/^\/+|\/+$/g,'').split('/');uri.seg['fragment']=uri.attr.fragment.replace(/^\/+|\/+$/g,'').split('/');uri.attr['base']=uri.attr.host?(uri.attr.protocol?uri.attr.protocol+'://'+uri.attr.host:uri.attr.host)+(uri.attr.port?':'+uri.attr.port:''):'';return uri;}
function getAttrName(elm){var tn=elm.tagName;if(typeof tn!=='undefined')return tag2attr[tn.toLowerCase()];return tn;}
function promote(parent,key){if(parent[key].length===0)return parent[key]={};var t={};for(var i in parent[key])t[i]=parent[key][i];parent[key]=t;return t;}
function parse(parts,parent,key,val){var part=parts.shift();if(!part){if(isArray(parent[key])){parent[key].push(val);}else if('object'==typeof parent[key]){parent[key]=val;}else if('undefined'==typeof parent[key]){parent[key]=val;}else{parent[key]=[parent[key],val];}}else{var obj=parent[key]=parent[key]||[];if(']'==part){if(isArray(obj)){if(''!==val)obj.push(val);}else if('object'==typeof obj){obj[keys(obj).length]=val;}else{obj=parent[key]=[parent[key],val];}}else if(~part.indexOf(']')){part=part.substr(0,part.length-1);if(!isint.test(part)&&isArray(obj))obj=promote(parent,key);parse(parts,obj,part,val);}else{if(!isint.test(part)&&isArray(obj))obj=promote(parent,key);parse(parts,obj,part,val);}}}
function merge(parent,key,val){if(~key.indexOf(']')){var parts=key.split('[');parse(parts,parent,'base',val);}else{if(!isint.test(key)&&isArray(parent.base)){var t={};for(var k in parent.base)t[k]=parent.base[k];parent.base=t;}
if(key!==''){set(parent.base,key,val);}}
return parent;}
function parseString(str){return reduce(String(str).split(/&|;/),function(ret,pair){try{pair=decodeURIComponent(pair.replace(/\+/g,' '));}catch(e){}
var eql=pair.indexOf('='),brace=lastBraceInKey(pair),key=pair.substr(0,brace||eql),val=pair.substr(brace||eql,pair.length);val=val.substr(val.indexOf('=')+1,val.length);if(key===''){key=pair;val='';}
return merge(ret,key,val);},{base:{}}).base;}
function set(obj,key,val){var v=obj[key];if(typeof v==='undefined'){obj[key]=val;}else if(isArray(v)){v.push(val);}else{obj[key]=[v,val];}}
function lastBraceInKey(str){var len=str.length,brace,c;for(var i=0;i<len;++i){c=str[i];if(']'==c)brace=false;if('['==c)brace=true;if('='==c&&!brace)return i;}}
function reduce(obj,accumulator){var i=0,l=obj.length>>0,curr=arguments[2];while(i<l){if(i in obj)curr=accumulator.call(undefined,curr,obj[i],i,obj);++i;}
return curr;}
function isArray(vArg){return Object.prototype.toString.call(vArg)==="[object Array]";}
function keys(obj){var key_array=[];for(var prop in obj){if(obj.hasOwnProperty(prop))key_array.push(prop);}
return key_array;}
function purl(url,strictMode){if(arguments.length===1&&url===true){strictMode=true;url=undefined;}
strictMode=strictMode||false;url=url||window.location.toString();return{data:parseUri(url,strictMode),attr:function(attr){attr=aliases[attr]||attr;return typeof attr!=='undefined'?this.data.attr[attr]:this.data.attr;},param:function(param){return typeof param!=='undefined'?this.data.param.query[param]:this.data.param.query;},fparam:function(param){return typeof param!=='undefined'?this.data.param.fragment[param]:this.data.param.fragment;},segment:function(seg){if(typeof seg==='undefined'){return this.data.seg.path;}else{seg=seg<0?this.data.seg.path.length+seg:seg-1;return this.data.seg.path[seg];}},fsegment:function(seg){if(typeof seg==='undefined'){return this.data.seg.fragment;}else{seg=seg<0?this.data.seg.fragment.length+seg:seg-1;return this.data.seg.fragment[seg];}},setSegment:function(param){if(typeof(param)==='string'){this.data.seg.path=param.split('/');}else if(typeof(param)==='object'){this.data.seg.path=param;}
return this;},addSegment:function(segment){if(typeof(segment)!=='object'){var paths=segment.toString().split('/')
for(var i in paths){this.data.seg.path.push(paths[i]);}}else{for(var i in segment){this.data.seg.path.push(segment[i]);}}
return this;},addPath:function(path){return this.addSegment(path);},setParam:function(param){if(typeof(param)==='string'){var tmp=[];param.replace(querystring_parser,function($0,$1,$2){if($1){tmp[$1]=$2;}});for(var i in tmp){this.data.param.query[i]=tmp[i];}}else if(typeof(param)==='object'){for(var i in param){this.data.param.query[i]=param[i];}}
return this;},toString:function(){var param=[];for(var i in this.data.param.query){param.push(i+'='+this.data.param.query[i]);}
var path=[];for(var i in this.data.seg.path){if(this.data.seg.path[i].toString()!==''){path.push(this.data.seg.path[i]);}}
return this.data.attr['base']
+(path.length>0||param.length>0?'/':'')
+path.join('/')
+(path.length>0?'/':'')
+(param.length>0?'?'+param.join('&'):'');}};}
purl.jQuery=function($){if($!=null){$.fn.url=function(strictMode){var url='';if(this.length){url=$(this).attr(getAttrName(this[0]))||'';}
return purl(url,strictMode);};$.url=purl;}};purl.jQuery(window.jQuery);return purl;});if(!Function.prototype.bind){Function.prototype.bind=function(oThis){if(typeof this!=="function"){throw new TypeError("Function.prototype.bind - what is trying to be bound is not callable");}
var aArgs=Array.prototype.slice.call(arguments,1),fToBind=this,fNOP=function(){},fBound=function(){return fToBind.apply(this instanceof fNOP&&oThis?this:oThis,aArgs.concat(Array.prototype.slice.call(arguments)));};fNOP.prototype=this.prototype;fBound.prototype=new fNOP();return fBound;};}
function bind(scope,fn){return function(){fn.apply(scope,arguments);};}
(function($){function log(){if(!$.fn.ajaxSubmit.debug)
return;var msg="[jquery.form] "+Array.prototype.join.call(arguments,"");if(window.console&&window.console.log)
window.console.log(msg);else if(window.opera&&window.opera.windpostError)
window.opera.postError(msg);}
window.log=log;$.openOverlay=function(options){if(typeof(options)==='string'){options={url:options};}
var m=$('#cgaf-modal');var opts=$.extend(options,options||{title:'test',backdrop:true,show:false});if(m.length==0){m=$('<div id="cgaf-modal" class="modal fade"/>').appendTo('body');$('<div class="modal-dialog"><div class="modal-content">'
+'<div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>'
+'<h4></h4></div>'
+'<div class="modal-body"></div>'
+'<div class="modal-footer"></div>'
+'</div></div>').appendTo(m);}
var b=m.find('.modal-body').empty();var modal=new $.fn.modal.Constructor(m,opts);opts.title=opts.title||'&nbsp;';m.find('.modal-header h4').html(opts.title);if(opts.url){b.addClass('loading');var url=cgaf.url(opts.url,{__uimode:'dialog'}).toString();b.load(url,function(data,s,xhr){if(opts.callback){opts.callback.call(modal,b);}
b.removeClass('loading');});}else if(opts.contents){var content=opts.contents;if(typeof(content)==='object'){var tmp='<ul>';for(var i in content){if(typeof(content[i])==='string'){tmp+='<li>'+content[i]+'</li>';}else if(typeof(content[i])==='object'){for(var j in content[i]){tmp+='<li>';tmp+=content[i][j];tmp+='</li>';}}}
tmp+='</ul>';content=tmp;}
b.html(content);}
modal.show();};$.showErrorMessage=function(msg){$.openOverlay({title:'Error',contents:msg});};$.fn.openOverlay=$.openOverlay;$.filterProperties=function(r,o){var retval={};for(var s in r){var value=o[s];if(typeof value!=="undefined"&&value!==null)
retval[s]=value;}
return retval;};function Q(){var _finish=[],_queue=[];this._started=false;return{onFinish:function(callback){_finish.push(callback);this._started=false;},add:function(callback){_queue.push(callback);},next:function(){if(_queue.length===0){$(_finish).each(function(){this.call();});return;}
this._started=true;callback=_queue.shift();callback.call(this);},start:function(){this.next();}};}
function CGAFEvent(){var _events=[];return{Event:function(e,s,a){var propagationStopped=false;this.EventName=e;this.Source=s;this.args=a;this.stopPropagation=function(){propagationStopped=true;};this.propagationStoped=function(){return propagationStopped;};},bind:function(event,callback){_events.push({name:event,callback:callback});},trigger:function(event,data){for(var e in _events){var ev=_events[e];if(ev.name===event)
ev.callback(data);}},unbind:function(event,data){for(var e in _events){var ev=_events[e];try{if(ev.name===event&&ev.callback==data){_events.splice(e,1);break;}}catch(e){console.log(e);}}}};}
var CGAF=function(){var _queue=[],pluginLoader=new Q,cgafconfig={},_queueRunning=false,loadedJQ={"jquery.mousewheel":""},loadedCSS={},_event=new CGAFEvent,_module={};function CGAF(options){if(window.cgaf)
return window.cgaf;return new CGAF.fn.init(options);}
CGAF.fn=CGAF.prototype={defaults:{ismobile:false},init:function(options){var me=this;cgafconfig=$.extend(this.defaults,options||{});var lo=$("#loading");if(lo.length===0)
lo=$('<div id="loading" class="loading"><span>loading...</span></div>').appendTo("body").hide();lo.bind("ajaxSend",function(){$(this).show();}).bind("ajaxComplete",function(){me.defaultUIHandler();$(this).hide();});this.Events={CGAF_READY:"cgaf-ready",STATUS_CHANGED:"status-changed"};},getModuleInfo:function(m){if(typeof m==="object")
m=m._m;for(var key in _module){var obj=_module[key];if(isNaN(m)){if(obj.mod_dir.toLowerCase()==m.toLowerCase())
return obj;}else if(key.toString().toLowerCase()==m.toString().toLowerCase())
return obj;}},getJSAsync:function(url,config){var callback=null;if(typeof(url)==='string')
url=[url];if(typeof(config)==='function')
callback=config;var loader=function(src,handler){var script=document.createElement("script");script.src=src;script.onload=script.onreadystatechange=function(){script.onreadystatechange=script.onload=null;handler();};var head=document.getElementsByTagName("head")[0];(head||document.body).appendChild(script);};(function(){if(url.length!=0){loader(url.shift(),arguments.callee);}else{callback&&callback();}})();},getJS:function(scripts,onComplete,id){var i=1;var ii=typeof scripts!=="string"?scripts.length:1;function onScriptLoaded(data,response){if(i++==ii)
if(typeof onComplete!=="undefined")
onComplete();}
function onScriptError(){cgaf.log(arguments);i++;}
if(typeof scripts==="string")
try{this.getJSAsync(scripts,onScriptLoaded);}catch(e){onScriptError(e);}
else if(typeof scripts==="object")
for(var s in scripts){if(typeof scripts[s]!=="string")
continue;try{this.getJSAsync(scripts,onScriptLoaded);}catch(e){onScriptError(e);}}
else
cgaf.log(typeof scripts);},ui:{},rescheduleQueue:function(){if(_queueRunning)
return;if(_queue.length==0)
return;_queueRunning=true;var q=_queue.shift();var old=q.data.callback||function(){};var me=this;q.data.callback=function(){try{old.apply(this,arguments);}catch(e){}
_queueRunning=false;me.rescheduleQueue();};q.callback.call(this,q.data);},isJQPluginLoaded:function(js){return loadedJQ.hasOwnProperty(js);},loadStyleSheet:function(url){if(Object.keys(loadedCSS).length==0)
$("head > link").each(function(key,item){var href=$(item).attr("href");loadedCSS[href]=true;});if(loadedCSS.hasOwnProperty(url))
return;loadedCSS[url]=true;console.log(url);$("head").append($('<link rel="stylesheet" type="text/css" />').attr("href",url));},isURL:function(textval){var urlregex=new RegExp("^(http|https|ftp)\://([a-zA-Z0-9\.\-]+(\:[a-zA-Z0-9\.&amp;%\$\-]+)*@)*((25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9])\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[1-9]|0)\.(25[0-5]|2[0-4][0-9]|[0-1]{1}[0-9]{2}|[1-9]{1}[0-9]{1}|[0-9])|([a-zA-Z0-9\-]+\.)*[a-zA-Z0-9\-]+\.(com|edu|gov|int|mil|net|org|biz|arpa|info|name|pro|aero|coop|museum|[a-zA-Z]{2}))(\:[0-9]+)*(/($|[a-zA-Z0-9\.\,\?\'\\\+&amp;%\$#\=~_\-]+))*$");return urlregex.test(textval);},loadJQPlugin:function(jq,callback){if(typeof jq==="object"){var self=this;pluginLoader.onFinish(callback);$.each(jq,function(k,item){pluginLoader.add(function(){self.loadJQPlugin(item,function(){pluginLoader.next();});});});pluginLoader.start();return;}
if(this.isJQPluginLoaded(jq)){this.trigger("onpluginloaded",jq);if($.isFunction(callback))
callback.call(this);return;}
version=this.getConfig("jq.version","latest");var url=this.getConfig("jqurl",this.getConfig("asseturl",""))
+"js/jQuery/plugins/"+jq+".js";loadedJQ[jq]=url;this.require(url,callback);},require:function(ns,callback){this.getJS(ns,function(){if(typeof callback!=="undefined")
callback.call(this,arguments);});},getJSON:function(url,data,callback,err){var me=this;data=$.extend({__data:"json"},data||{});return jQuery.get(url,data,function(d){var j;try{j=(new Function("return "+d))();}catch(e){if(err){err.call(this,e,d);}else{cgaf.log(e.toString()+":"+d);me.showError("Invalid JSON Data<br/>"+d);}
return false;}
if(callback)
callback.call(this,j);},"text");},getJSONApp:function(data,callback){var appurl=cgaf.getConfig("appurl");return jQuery.get(appurl,data,function(d){if(typeof callback!=="undefined")
callback.call(this,(new Function("return "+d))());},"text");},ErrorType:{Notice:"error-type-notice",Warning:"error-type-warning",Error:"error-type-error"},setSysMessage:function(msg){var sm=$("#sysmessage");if(sm){sm.find('.alert-content').html(msg);sm.show('slow');}},defaultUIHandler:function(){$("a[rel=__overlay]").each(function(){if(!$(this).attr("__overlay")){$(this).attr("__overlay",1);$(this).click(function(e){e.preventDefault();$.openOverlay($(this).attr("href"));});}});$("a[data-target]").each(function(){var href=$(this).attr('href');if(href&&(href.substring(0,window.location.protocol.length)==window.location.protocol||href[0]==='/')&&$($(this).attr('data-target')).length>0){if(!$(this).data('data-target')){$(this).click(function(e){e.preventDefault();$($(this).attr('data-target')).load($(this).attr('href'));});}
$(this).data('data-target',true);}});$("a[rel=__confirm]").each(function(){if(!$(this).attr("__confirm")){$(this).attr("__confirm",1);$(this).click(function(e){e.preventDefault();var title=$(this).attr("ctitle");if(!title)
title=$(this).attr("title");var me=$(this);cgaf.confirm(title,function(){var url=$.url(me.attr("href"));url.param("__confirm",1);$.openOverlay(url.toString());});});}});},setReady:function(){this.defaultUIHandler();this.trigger(this.Events.CGAF_READY);},trigger:function(){_event.trigger.apply(this,arguments);},bind:function(){_event.bind.apply(this,arguments);},Windows:function(){return{open:function(url){return $.openOverlay(url);}};},Plugins:function(){return{select:function(){}};},setStatus:function(msg){cgaf.trigger(cgaf.Events.STATUS_CHANGED,msg);},setConfig:function(name,value){if(typeof value==="undefined"){var self=this;$.each(name,function(k,v){self.setConfig(k,v);});}else
cgafconfig[name]=value;},confirm:function(msg,callback){var dlf=$("#confirm-dialog");if(dlf.length===0)
dlf=$('<div id="confirm-dialog"></div>').appendTo("body");dlf.html(msg);dlf.dialog({modal:true,buttons:{Confirm:function(){if(callback)
callback.call(this);$(this).dialog("close");},Cancel:function(){$(this).dialog("close");}}});dlf.dialog("open");},dateFromISO:function(date,format){date=new Date(date);return date.format(format);},showError:function(msg){$.showErrorMessage(msg);},getConfig:function(name,def){return typeof cgafconfig[name]==="undefined"?def:cgafconfig[name];},log:function(args,etype){window.log(arguments);},url:function(uri,data){var r=new $.url(uri);r.setParam(data);return r;},Queue:function(id,callback,data){_queue.push({id:id,data:data,callback:callback});this.rescheduleQueue();},toJSON:function(o){if(typeof o==="string")
try{return(new Function("return "+o))();}catch(e){return null;}
cgaf.log($(this));return o;},Socket:function(){}};CGAF.fn.init.prototype=CGAF.fn;return CGAF;}();window.cgaf=new CGAF;})(jQuery);