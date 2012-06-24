/* Modernizr 2.0.6 (Custom Build) | MIT & BSD
 * Build: http://www.modernizr.com/download/#-csstransforms3d-touch-teststyles-testprop-prefixes
 */
;window.Modernizr=function(a,b,c){function z(a,b){for(var d in a)if(j[a[d]]!==c)return b=="pfx"?a[d]:!0;return!1}function y(a,b){return!!~(""+a).indexOf(b)}function x(a,b){return typeof a===b}function w(a,b){return v(m.join(a+";")+(b||""))}function v(a){j.cssText=a}var d="2.0.6",e={},f=b.documentElement,g=b.head||b.getElementsByTagName("head")[0],h="modernizr",i=b.createElement(h),j=i.style,k,l=Object.prototype.toString,m=" -webkit- -moz- -o- -ms- -khtml- ".split(" "),n={},o={},p={},q=[],r=function(a,c,d,e){var g,i,j,k=b.createElement("div");if(parseInt(d,10))while(d--)j=b.createElement("div"),j.id=e?e[d]:h+(d+1),k.appendChild(j);g=["&shy;","<style>",a,"</style>"].join(""),k.id=h,k.innerHTML+=g,f.appendChild(k),i=c(k,a),k.parentNode.removeChild(k);return!!i},s,t={}.hasOwnProperty,u;!x(t,c)&&!x(t.call,c)?u=function(a,b){return t.call(a,b)}:u=function(a,b){return b in a&&x(a.constructor.prototype[b],c)};var A=function(c,d){var f=c.join(""),g=d.length;r(f,function(c,d){var f=b.styleSheets[b.styleSheets.length-1],h=f.cssRules&&f.cssRules[0]?f.cssRules[0].cssText:f.cssText||"",i=c.childNodes,j={};while(g--)j[i[g].id]=i[g];e.touch="ontouchstart"in a||j.touch.offsetTop===9,e.csstransforms3d=j.csstransforms3d.offsetLeft===9},g,d)}([,["@media (",m.join("touch-enabled),("),h,")","{#touch{top:9px;position:absolute}}"].join(""),["@media (",m.join("transform-3d),("),h,")","{#csstransforms3d{left:9px;position:absolute}}"].join("")],[,"touch","csstransforms3d"]);n.touch=function(){return e.touch},n.csstransforms3d=function(){var a=!!z(["perspectiveProperty","WebkitPerspective","MozPerspective","OPerspective","msPerspective"]);a&&"webkitPerspective"in f.style&&(a=e.csstransforms3d);return a};for(var B in n)u(n,B)&&(s=B.toLowerCase(),e[s]=n[B](),q.push((e[s]?"":"no-")+s));v(""),i=k=null,e._version=d,e._prefixes=m,e.testProp=function(a){return z([a])},e.testStyles=r;return e}(this,this.document);/*
 * jQuery.translate3d.js v0.1
 *
 * Copyright (c) 2011 Richard Scarrott
 * http://www.richardscarrott.co.uk
 *
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 * 
 * // getter
 * $(selector).css('translate3d'); // returns { x: val, y: val, z: val }
 *
 * // setter
 * $(selector).css('translate3d', { x: val, y: val, z: val });
 *
 * // support
 * $.support.transform3d; // returns boolean
 *
 */
 
(function ($) {

	// getSupportedProp mostly taken from http://api.jquery.com/jQuery.cssHooks/
	var getSupportedProp = function (prop) {
		
		var vendorProp,
			supportedProp,
			capProp = prop.charAt(0).toUpperCase() + prop.slice(1),
			prefixes = ['Moz', 'Webkit', 'Khtml', 'O', 'Ms'],
			style = document.documentElement.style;

		if (prop in style) {
			supportedProp = prop;
		}
		else {
			for (var i = 0; i < prefixes.length; i++) {
				vendorProp = prefixes[i] + capProp;	
				if (vendorProp in style) {
					supportedProp = vendorProp;
					break;
				}
			}
		}

		div = null;
		$.support[prop] = supportedProp;
		return supportedProp;
	},
	transform = getSupportedProp('transform');	
	
	if ($.support.transform) {
		$.cssHooks.translate3d = {
			set: function(elem, obj) {

				var obj = $.extend({
					x: 0,
					y: 0,
					z: 0
				}, obj);

				// append px if unit not passed in
				$.each(obj, function (prop, val) {
					obj[prop] = typeof val === 'number' ? val + 'px' : val;
				});

				elem.style[transform] = 'translate3d(' + obj.x + ', ' + obj.y + ', ' + obj.z + ')';
			},

			// properly regex out all three values... prob shouldn't conver to Int here incase the value was percentage etc.
			get: function (elem) {
				var value = elem.style[transform];
				if (value) {
					value = value.split('(')[1].split(',');  // a bit rubbish me thinks...
					return {
						x: parseInt(value[0]),
						y: parseInt(value[1]),
						z: parseInt(value[2].split(')')[0])
					};
				}
			}
		};
	}
	
})(jQuery);/*! 
 * jquery.event.drag - v 2.1.0 
 * Copyright (c) 2010 Three Dub Media - http://threedubmedia.com
 * Open Source MIT License - http://threedubmedia.com/code/license
 */
// Created: 2008-06-04 
// Updated: 2010-09-15
// REQUIRES: jquery 1.4.2+

;(function( $ ){

// add the jquery instance method
$.fn.drag = function( str, arg, opts ){
	// figure out the event type
	var type = typeof str == "string" ? str : "",
	// figure out the event handler...
	fn = $.isFunction( str ) ? str : $.isFunction( arg ) ? arg : null;
	// fix the event type
	if ( type.indexOf("drag") !== 0 ) 
		type = "drag"+ type;
	// were options passed
	opts = ( str == fn ? arg : opts ) || {};
	// trigger or bind event handler
	return fn ? this.bind( type, opts, fn ) : this.trigger( type );
};

// local refs (increase compression)
var $event = $.event, 
$special = $event.special,
// configure the drag special event 
drag = $special.drag = {
	
	// these are the default settings
	defaults: {
		which: 1, // mouse button pressed to start drag sequence
		distance: 0, // distance dragged before dragstart
		not: ':input', // selector to suppress dragging on target elements
		handle: null, // selector to match handle target elements
		relative: false, // true to use "position", false to use "offset"
		drop: true, // false to suppress drop events, true or selector to allow
		click: false // false to suppress click events after dragend (no proxy)
	},
	
	// the key name for stored drag data
	datakey: "dragdata",
		
	// count bound related events
	add: function( obj ){ 
		// read the interaction data
		var data = $.data( this, drag.datakey ),
		// read any passed options 
		opts = obj.data || {};
		// count another realted event
		data.related += 1;
		// extend data options bound with this event
		// don't iterate "opts" in case it is a node 
		$.each( drag.defaults, function( key, def ){
			if ( opts[ key ] !== undefined )
				data[ key ] = opts[ key ];
		});
	},
	
	// forget unbound related events
	remove: function(){
		$.data( this, drag.datakey ).related -= 1;
	},
	
	// configure interaction, capture settings
	setup: function(){
		// check for related events
		if ( $.data( this, drag.datakey ) ) 
			return;
		// initialize the drag data with copied defaults
		var data = $.extend({ related:0 }, drag.defaults );
		// store the interaction data
		$.data( this, drag.datakey, data );
		// bind the mousedown event, which starts drag interactions
		$event.add( this, "touchstart mousedown", drag.init, data );
		// prevent image dragging in IE...
		if ( this.attachEvent ) 
			this.attachEvent("ondragstart", drag.dontstart ); 
	},
	
	// destroy configured interaction
	teardown: function(){
		var data = $.data( this, drag.datakey ) || {};
		// check for related events
		if ( data.related ) 
			return;
		// remove the stored data
		$.removeData( this, drag.datakey );
		// remove the mousedown event
		$event.remove( this, "touchstart mousedown", drag.init );
		// enable text selection
		drag.textselect( true ); 
		// un-prevent image dragging in IE...
		if ( this.detachEvent ) 
			this.detachEvent("ondragstart", drag.dontstart ); 
	},
		
	// initialize the interaction
	init: function( event ){ 
		// sorry, only one touch at a time
		if ( drag.touched ) 
			return;
		// the drag/drop interaction data
		var dd = event.data, results;
		// check the which directive
		if ( event.which != 0 && dd.which > 0 && event.which != dd.which ) 
			return; 
		// check for suppressed selector
		if ( $( event.target ).is( dd.not ) ) 
			return;
		// check for handle selector
		if ( dd.handle && !$( event.target ).closest( dd.handle, event.currentTarget ).length ) 
			return;

		// store/reset some initial attributes
		if ( event.type == "touchstart" ){
			drag.touched = this;
			drag.touchFix( event, dd );
		}
		dd.propagates = 1;
		dd.mousedown = this;
		dd.interactions = [ drag.interaction( this, dd ) ];
		dd.target = event.target;
		dd.pageX = event.pageX;
		dd.pageY = event.pageY;
		dd.dragging = null;
		// handle draginit event... 
		results = drag.hijack( event, "draginit", dd );
		// early cancel
		if ( !dd.propagates )
			return;
		// flatten the result set
		results = drag.flatten( results );
		// insert new interaction elements
		if ( results && results.length ){
			dd.interactions = [];
			$.each( results, function(){
				dd.interactions.push( drag.interaction( this, dd ) );
			});
		}
		// remember how many interactions are propagating
		dd.propagates = dd.interactions.length;
		// locate and init the drop targets
		if ( dd.drop !== false && $special.drop ) 
			$special.drop.handler( event, dd );
		// disable text selection
		drag.textselect( false ); 
		// bind additional events...
		if ( drag.touched )
			$event.add( drag.touched, "touchmove touchend", drag.handler, dd );
		else 
			$event.add( document, "mousemove mouseup", drag.handler, dd );
		// helps prevent text selection or scrolling
		if ( !drag.touched || dd.live )
			return false;
	},	
	
	// fix event properties for touch events
	touchFix: function( event, dd ){
		var orig = event.originalEvent, i = 0;
		// iOS webkit: touchstart, touchmove, touchend
		if ( orig && orig.changedTouches ){ 
			event.pageX = orig.changedTouches[0].pageX;
			event.pageY = orig.changedTouches[0].pageY;	
		}
		//console.log( event.type, event );
	},
	
	// returns an interaction object
	interaction: function( elem, dd ){
		var offset = $( elem )[ dd.relative ? "position" : "offset" ]() || { top:0, left:0 };
		return {
			drag: elem, 
			callback: new drag.callback(), 
			droppable: [],
			offset: offset
		};
	},
	
	// handle drag-releatd DOM events
	handler: function( event ){ 
		// read the data before hijacking anything
		var dd = event.data;
		if ( drag.touched )
			drag.touchFix( event, dd );		
		// handle various events
		switch ( event.type ){
			// mousemove, check distance, start dragging
			case !dd.dragging && 'touchmove': 
				event.preventDefault();
			case !dd.dragging && 'mousemove':
				//  drag tolerance, x� + y� = distance�
				if ( Math.pow(  event.pageX-dd.pageX, 2 ) + Math.pow(  event.pageY-dd.pageY, 2 ) < Math.pow( dd.distance, 2 ) ) 
					break; // distance tolerance not reached
				event.target = dd.target; // force target from "mousedown" event (fix distance issue)
				drag.hijack( event, "dragstart", dd ); // trigger "dragstart"
				if ( dd.propagates ) // "dragstart" not rejected
					dd.dragging = true; // activate interaction
			// mousemove, dragging
			case 'touchmove':
				event.preventDefault();
			case 'mousemove':
				if ( dd.dragging ){
					// trigger "drag"		
					drag.hijack( event, "drag", dd );
					if ( dd.propagates ){
						// manage drop events
						if ( dd.drop !== false && $special.drop )
							$special.drop.handler( event, dd ); // "dropstart", "dropend"							
						break; // "drag" not rejected, stop		
					}
					event.type = "mouseup"; // helps "drop" handler behave
				}
			// mouseup, stop dragging
			case 'touchend': 
			case 'mouseup': 
				if ( drag.touched )
					$event.remove( drag.touched, "touchmove touchend", drag.handler ); // remove touch events
				else 
					$event.remove( document, "mousemove mouseup", drag.handler ); // remove page events	
				if ( dd.dragging ){
					if ( dd.drop !== false && $special.drop ) 
						$special.drop.handler( event, dd ); // "drop"
					drag.hijack( event, "dragend", dd ); // trigger "dragend"	
					}
				drag.textselect( true ); // enable text selection
				// if suppressing click events...
				if ( dd.click === false && dd.dragging ){
					$.data( dd.mousedown, "suppress.click", new Date().getTime() + 5 );
				}
				dd.dragging = drag.touched = false; // deactivate element	
				break;
		}
	},
		
	// re-use event object for custom events
	hijack: function( event, type, dd, x, elem ){
		// not configured
		if ( !dd ) 
			return;
		// remember the original event and type
		var orig = { event:event.originalEvent, type: event.type },
		// is the event drag related or drog related?
		mode = type.indexOf("drop") ? "drag" : "drop",
		// iteration vars
		result, i = x || 0, ia, $elems, callback,
		len = !isNaN( x ) ? x : dd.interactions.length;
		// modify the event type
		event.type = type;
		// remove the original event
		event.originalEvent = null;
		// initialize the results
		dd.results = [];
		// handle each interacted element
		do if ( ia = dd.interactions[ i ] ){
			// validate the interaction
			if ( type !== "dragend" && ia.cancelled )
				continue;
			// set the dragdrop properties on the event object
			callback = drag.properties( event, dd, ia );
			// prepare for more results
			ia.results = [];
			// handle each element
			$( elem || ia[ mode ] || dd.droppable ).each(function( p, subject ){
				// identify drag or drop targets individually
				callback.target = subject;
				// handle the event	
				result = subject ? $event.handle.call( subject, event, callback ) : null;
				// stop the drag interaction for this element
				if ( result === false ){
					if ( mode == "drag" ){
						ia.cancelled = true;
						dd.propagates -= 1;
					}
					if ( type == "drop" ){
						ia[ mode ][p] = null;
					}
				}
				// assign any dropinit elements
				else if ( type == "dropinit" )
					ia.droppable.push( drag.element( result ) || subject );
				// accept a returned proxy element 
				if ( type == "dragstart" )
					ia.proxy = $( drag.element( result ) || ia.drag )[0];
				// remember this result	
				ia.results.push( result );
				// forget the event result, for recycling
				delete event.result;
				// break on cancelled handler
				if ( type !== "dropinit" )
					return result;
			});	
			// flatten the results	
			dd.results[ i ] = drag.flatten( ia.results );	
			// accept a set of valid drop targets
			if ( type == "dropinit" )
				ia.droppable = drag.flatten( ia.droppable );
			// locate drop targets
			if ( type == "dragstart" && !ia.cancelled )
				callback.update(); 
		}
		while ( ++i < len )
		// restore the original event & type
		event.type = orig.type;
		event.originalEvent = orig.event;
		// return all handler results
		return drag.flatten( dd.results );
	},
		
	// extend the callback object with drag/drop properties...
	properties: function( event, dd, ia ){		
		var obj = ia.callback;
		// elements
		obj.drag = ia.drag;
		obj.proxy = ia.proxy || ia.drag;
		// starting mouse position
		obj.startX = dd.pageX;
		obj.startY = dd.pageY;
		// current distance dragged
		obj.deltaX = event.pageX - dd.pageX;
		obj.deltaY = event.pageY - dd.pageY;
		// original element position
		obj.originalX = ia.offset.left;
		obj.originalY = ia.offset.top;
		// adjusted element position
		obj.offsetX = obj.originalX + obj.deltaX; 
		obj.offsetY = obj.originalY + obj.deltaY;
		// assign the drop targets information
		obj.drop = drag.flatten( ( ia.drop || [] ).slice() );
		obj.available = drag.flatten( ( ia.droppable || [] ).slice() );
		return obj;	
	},
	
	// determine is the argument is an element or jquery instance
	element: function( arg ){
		if ( arg && ( arg.jquery || arg.nodeType == 1 ) )
			return arg;
	},
	
	// flatten nested jquery objects and arrays into a single dimension array
	flatten: function( arr ){
		return $.map( arr, function( member ){
			return member && member.jquery ? $.makeArray( member ) : 
				member && member.length ? drag.flatten( member ) : member;
		});
	},
	
	// toggles text selection attributes ON (true) or OFF (false)
	textselect: function( bool ){ 
		$( document )[ bool ? "unbind" : "bind" ]("selectstart", drag.dontstart )
			.css("MozUserSelect", bool ? "" : "none" );
		// .attr("unselectable", bool ? "off" : "on" )
		document.unselectable = bool ? "off" : "on"; 
	},
	
	// suppress "selectstart" and "ondragstart" events
	dontstart: function(){ 
		return false; 
	},
	
	// a callback instance contructor
	callback: function(){}
	
};

// callback methods
drag.callback.prototype = {
	update: function(){
		if ( $special.drop && this.available.length )
			$.each( this.available, function( i ){
				$special.drop.locate( this, i );
			});
	}
};

// patch $.event.handle to allow suppressing clicks
var orighandle = $event.handle;
$event.handle = function( event ){
	if ( $.data( this, "suppress."+ event.type ) - new Date().getTime() > 0 ){
		$.removeData( this, "suppress."+ event.type );
		return;
	}
	return orighandle.apply( this, arguments );
};

// share the same special event configuration with related events...
$special.draginit = $special.dragstart = $special.dragend = drag;

})( jQuery );/*
 * jquery.rs.carousel.js v0.8.6
 *
 * Copyright (c) 2011 Richard Scarrott
 * http://www.richardscarrott.co.uk
 *
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 * Depends:
 *  jquery.js v1.4+
 *  jquery.ui.widget.js v1.8+
 *
 */
 
(function ($, undefined) {

    var _super = $.Widget.prototype,
        horizontal = {
            pos: 'left',
            pos2: 'right',
            dim: 'width'
        },
        vertical = {
            pos: 'top',
            pos2: 'bottom',
            dim: 'height'
        };
    
    $.widget('rs.rscarousel', {

        options: {
            itemsPerPage: 'auto',
            itemsPerTransition: 'auto',
            orientation: 'horizontal',
            baseClass:'rs-carousel',
            itemPadding:10,
            loop: false,
            nextPrevActions: true,
            insertPrevAction: function () {
                return $('<a href="#" class="rs-carousel-action-prev">Prev</a>').appendTo(this);
            },
            insertNextAction: function () {
                return $('<a href="#" class="rs-carousel-action-next">Next</a>').appendTo(this);
            },
            pagination: true,
            insertPagination: function (pagination) {
                return $(pagination).insertAfter($(this).find('.rs-carousel-mask'));
            },
            speed: 'normal',
            easing: 'swing',

            // callbacks
            create: null,
            before: null,
            after: null
        },

        _create: function () {

            this.page = 1;
            this._elements();
            this._defineOrientation();
            this._addMask();
            this._addNextPrevActions();
            this.refresh(false);

            return;
        },

        // caches DOM elements
        _elements: function () {

            var elems = this.elements = {},
                baseClass = '.' + this.options.baseClass;

            elems.mask = this.element.children(baseClass + '-mask');
            elems.runner = this.element.find(baseClass + '-runner').first();
            elems.items = elems.runner.children(baseClass + '-item');
            elems.pagination = undefined;
            elems.nextAction = undefined;
            elems.prevAction = undefined;

            return;
        },

        _addClasses: function () {

            if (!this.oldClass) {
                this.oldClass = this.element[0].className;
            }

            this._removeClasses();

            var baseClass = this.options.baseClass,
                classes = [];

            classes.push(baseClass);
            classes.push(baseClass + '-' + this.options.orientation);
            classes.push(baseClass + '-items-' + this.options.itemsPerPage);

            this.element.addClass(classes.join(' '));

            return;
        },

        // removes rs-carousel* classes
        _removeClasses: function () {

            var self = this,
                widgetClasses = [];

            this.element.removeClass(function (i, classes) {

                $.each(classes.split(' '), function (i, value) {

                    if (value.indexOf(self.widgetBaseClass) !== -1) {
                        widgetClasses.push(value);
                    }

                });

                return widgetClasses.join(' ');

            });

            return;
        },

        // defines obj to hold strings based on orientation for dynamic method calls
        _defineOrientation: function () {

            if (this.options.orientation === 'horizontal') {
                this.isHorizontal = true;
                this.helperStr = horizontal;
            }
            else {
                this.isHorizontal = false;
                this.helperStr = vertical;
            }
            return;
        },

        // adds masking div (aka clipper)
        _addMask: function () {

            var elems = this.elements;

            // already exists in markup
            if (elems.mask.length) {
                return;
            }

            elems.mask = elems.runner
                .wrap('<div class="' + this.options.baseClass + '-mask" />')
                .parent();

            // indicates whether mask was dynamically added or already existed in mark-up
            this.maskAdded = true;

            return;
        },

        // sets runners width
        _setRunnerWidth: function () {

            if (!this.isHorizontal) {
                return;
            }
            
            var self = this;

            this.elements.runner.width(function () {
                return (self._getItemDim() * self.getNoOfItems()) + (self.options.itemPadding * self.getNoOfItems());
            });

            return;
        },

        // sets itemDim to the dimension of first item incl. margin
        _getItemDim: function () {

            // is this ridiculous??
            return this.elements.items
                ['outer' + this.helperStr.dim.charAt(0).toUpperCase() + this.helperStr.dim.slice(1)](true);

        },

        getNoOfItems: function () {
            
            return this.elements.items.length;
             
        },

        // adds next and prev links
        _addNextPrevActions: function () {

            if (!this.options.nextPrevActions) {
                return;
            }

            var self = this,
                elems = this.elements,
                opts = this.options;
                
            this._removeNextPrevActions();

            elems.prevAction = opts.insertPrevAction.apply(this.element[0])
                .bind('click.' + this.widgetName, function (e) {
                    e.preventDefault();
                    self.prev();
                });

            elems.nextAction = opts.insertNextAction.apply(this.element[0])
                .bind('click.' + this.widgetName, function (e) {
                    e.preventDefault();
                    self.next();
                });
            
            return;
        },

        _removeNextPrevActions: function () {
        
            var elems = this.elements;
        
            if (elems.nextAction) {
                elems.nextAction.remove();
                elems.nextAction = undefined;
            }   
            
            if (elems.prevAction) {
                elems.prevAction.remove();
                elems.prevAction = undefined;
            }
            
            return; 
        },

        // adds pagination links and binds associated events
        _addPagination: function () {

            if (!this.options.pagination) {
                return;
            }

            var self = this,
                elems = this.elements,
                opts = this.options,
                baseClass = this.options.baseClass,
                pagination = $('<ol class="' + baseClass + '-pagination" />'),
                links = [],
                noOfPages = this.getNoOfPages(),
                i;
                
            this._removePagination();

            for (i = 1; i <= noOfPages; i++) {
                links[i] = '<li class="' + baseClass + '-pagination-link"><a href="#page-' + i + '">' + i + '</a></li>';
            }

            pagination
                .append(links.join(''))
                .delegate('a', 'click.' + this.widgetName, function (e) {
                    e.preventDefault();
                    self.goToPage(parseInt(this.hash.split('-')[1], 10));
                });
            
            this.elements.pagination = this.options.insertPagination.call(this.element[0], pagination);
            
            return;
        },

        _removePagination: function () {
        
            if (this.elements.pagination) {
                this.elements.pagination.remove();
                this.elements.pagination = undefined;
            }
            
            return;
        },

        // sets array of pages
        _setPages: function () {

            var index = 1,
                page = 0,
                noOfPages = this.getNoOfPages();
                
            this.pages = [];
            
            while (page < noOfPages) {
                
                // if index is greater than total number of items just go to last
                if (index > this.getNoOfItems()) {
                    index = this.getNoOfItems();
                }

                this.pages[page] = index;
                index += this.getItemsPerTransition(); // this.getItemsPerPage(index);
                page++;
            }

            return;
        },

        getPages: function () {
            
            return this.pages;

        },

        // returns noOfPages
        getNoOfPages: function () {

            var itemsPerTransition = this.getItemsPerTransition();

            // #18 - ensure we don't return Infinity
            if (itemsPerTransition <= 0) {
                return 0;
            }

            return Math.ceil((this.getNoOfItems() - this.getItemsPerPage()) / itemsPerTransition) + 1;

        },

        // returns options.itemsPerPage. If not a number it's calculated based on maskdim
        getItemsPerPage: function () {

            // if itemsPerPage of type number don't dynamically calculate
            if (typeof this.options.itemsPerPage === 'number') {
                return this.options.itemsPerPage;
            }
            
            return Math.floor(this._getMaskDim() / this._getItemDim());

        },

        getItemsPerTransition: function () {

            if (typeof this.options.itemsPerTransition === 'number') {
                return this.options.itemsPerTransition;
            }

            return this.getItemsPerPage();
            
        },

        _getMaskDim: function () {
            
            return this.elements.mask[this.helperStr.dim]();

        },

        next: function (animate) {

            var page = this.page + 1;

            if (this.options.loop && page > this.getNoOfPages()) {
                page = 1;
            }
            
            this.goToPage(page, animate);

            return;
        },

        prev: function (animate) {

            var page = this.page - 1;

            if (this.options.loop && page < 1) {
                page = this.getNoOfPages();
            }
            
            this.goToPage(page, animate);

            return;
        },

        // shows specific page (one based)
        goToPage: function (page, animate) {

            if (!this.options.disabled && this._isValid(page)) {
                this.prevPage = this.page;
                this.page = page;
                this._go(animate);
            }
            
            return;
        },

        // returns true if page index is valid, false if not
        _isValid: function (page) {
            
            if (page <= this.getNoOfPages() && page >= 1) {
                return true;
            }
            
            return false;
        },

        // returns valid page index
        _makeValid: function (page) {
                
            if (page < 1) {
                page = 1;
            }
            else if (page > this.getNoOfPages()) {
                page = this.getNoOfPages();
            }

            return page;
        },

        // abstract _slide to easily override within extensions
        _go: function (animate) {
            
            this._slide(animate);

            return;
        },

        _slide: function (animate) {

            var self = this,
                animate = animate === false ? false : true, // undefined should pass as true
                speed = animate ? this.options.speed : 0,
                animateProps = {},
                lastPos = this._getAbsoluteLastPos(),
                pos = this.elements.items
                    .eq(this.pages[this.page - 1] - 1) // arrays and .eq() are zero based, carousel is 1 based
                        .position()[this.helperStr.pos];

            // check pos doesn't go past last posible pos
            if (pos > lastPos) {
                pos = lastPos;
            }

            // might be nice to put animate on event object:
            // $.Event('slide', { animate: animate }) - would require jQuery 1.6+
            this._trigger('before', null, {
                elements: this.elements,
                animate: animate
            });

            animateProps[this.helperStr.pos] = -pos;
            this.elements.runner
                .stop()
                .animate(animateProps, speed, this.options.easing, function () {
                    
                    self._trigger('after', null, {
                        elements: self.elements,
                        animate: animate
                    });

                });
                
            this._updateUi();

            return;
        },

        // gets lastPos to ensure runner doesn't move beyond mask (allowing mask to be any width and the use of margins)
        _getAbsoluteLastPos: function () {
            
            var lastItem = this.elements.items.eq(this.getNoOfItems() - 1);
            
            return lastItem.position()[this.helperStr.pos] + this._getItemDim() -
                    this._getMaskDim() - parseInt(lastItem.css('margin-' + this.helperStr.pos2), 10);

        },

        // updates pagination, next and prev link status classes
        _updateUi: function () {

            if (this.options.pagination) {
                this._updatePagination();
            }

            if (this.options.nextPrevActions) {
                this._updateNextPrevActions();
            }

            return;
        },

        _updatePagination: function () {
            
            var baseClass = this.options.baseClass,
                activeClass = baseClass + '-pagination-link-active';

            this.elements.pagination
                .children('.' + baseClass + '-pagination-link')
                    .removeClass(activeClass)
                    .eq(this.page - 1)
                        .addClass(activeClass);

            return;
        },

        _updateNextPrevActions: function () {
            
            var elems = this.elements,
                page = this.page,
                disabledClass = this.options.baseClass + '-action-disabled';

            elems.nextAction
                .add(elems.prevAction)
                    .removeClass(disabledClass);

            if (!this.options.loop) {
                
                if (page === this.getNoOfPages()) {
                    elems.nextAction.addClass(disabledClass);
                }
                else if (page === 1) {
                    elems.prevAction.addClass(disabledClass);
                }

            }

            return;
        },

        // formalise appending items as continuous adding complexity by inserting
        // cloned items
        add: function (items) {

            this.elements.runner.append(items);
            this.refresh();

            return;
        },

        remove: function (selector) {
            
            if (this.getNoOfItems() > 0) {

                this.elements.items
                    .filter(selector)
                    .remove();

                this.refresh();
            }

            return;
        },

        // handles option updates
        _setOption: function (option, value) {

            var requiresRefresh = [
                'itemsPerPage',
                'itemsPerTransition',
                'orientation'
            ];      

            _super._setOption.apply(this, arguments);

            switch (option) {

            case 'orientation':
            
                this.elements.runner
                    .css(this.helperStr.pos, '')
                    .width('');

                this._defineOrientation();

                break;

            case 'pagination':

                if (value) {
                    this._addPagination();
                    this._updateUi();
                }
                else {
                    this._removePagination();
                }

                break;

            case 'nextPrevActions':

                if (value) {
                    this._addNextPrevActions();
                    this._updateUi();
                }
                else {
                    this._removeNextPrevActions();
                }

                break;

            case 'loop':

                this._updateUi();

                break;
            }

            if ($.inArray(option, requiresRefresh) !== -1) {
                this.refresh();
            }

            return;
        },

        // if no of items is less than items per page we disable carousel
        _checkDisabled: function () {
            
            if (this.getNoOfItems() <= this.getItemsPerPage()) {
                this.elements.runner.css(this.helperStr.pos, '');
                this.disable();
            }
            else {
                this.enable();
            }

            return;
        },

        // refresh carousel
        refresh: function (recache) {

            // assume true (undefined should pass condition)
            if (recache !== false) {
                this._recacheItems();
            }

            this._addClasses();
            this._setPages();
            this._addPagination();
            this._checkDisabled();
            this._setRunnerWidth();
            this.page = this._makeValid(this.page);
            this.goToPage(this.page, false);

            return;
        },

        // re-cache items in case new items have been added,
        // moved to own method so continuous can easily override
        // to avoid clones
        _recacheItems: function () {

            this.elements.items = this.elements.runner
                .children('.' + this.options.baseClass + '-item');

            return;
        },

        // returns carousel to original state
        destroy: function () {

            var elems = this.elements,
                cssProps = {};

            this.element
                .removeClass()
                .addClass(this.oldClass);
            
            if (this.maskAdded) {
                elems.runner
                    .unwrap('.' + this.options.baseClass + '-mask');
            }

            cssProps[this.helperStr.pos] = '';
            cssProps[this.helperStr.dim] = '';
            elems.runner.css(cssProps);
            
            this._removePagination();
            this._removeNextPrevActions();
            
            _super.destroy.apply(this, arguments);

            return;
        },

        getPage: function () {
            
            return this.page;

        },

        getPrevPage: function () {
            
            return this.prevPage;

        },

        // item can be $obj, element or 1 based index
        goToItem: function (index, animate) {

            // assume element or jQuery obj
            if (typeof index !== 'number') {
                index = this.elements.items.index(index) + 1;
            }

            if (index <= this.getNoOfItems()) {
                this.goToPage(Math.ceil(index / this.getItemsPerTransition()), animate);
            }

            return;
        }

    });
    
    $.rs.rscarousel.version = '0.8.6';

})(jQuery);
/*
 * jquery.rs.carousel-autoscroll v0.8.6
 *
 * Copyright (c) 2011 Richard Scarrott
 * http://www.richardscarrott.co.uk
 *
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 * Depends:
 *  jquery.js v1.4+
 *  jquery.ui.widget.js v1.8+
 *  jquery.rs.carousel.js v0.8.6+
 *
 */
 
(function($, undefined) {

    var _super = $.rs.rscarousel.prototype;
    
    $.widget('rs.rscarousel', $.rs.rscarousel, {
    
        options: {
            pause: 8000,
            autoScroll: false
        },
        
        _create: function() {
        
            _super._create.apply(this);
            
            if (!this.options.autoScroll) {
                return;
            }
            
            this._bindAutoScroll();
            this._start();
            
            return;
        },
        
        _bindAutoScroll: function() {
            
            if (this.autoScrollInitiated) {
                return;
            }
            
            this.element
                .bind('mouseenter.' + this.widgetName, $.proxy(this, '_stop'))
                .bind('mouseleave.' + this.widgetName, $.proxy(this, '_start'));
                
            this.autoScrollInitiated = true;
            
            return;
        },
        
        _unbindAutoScroll: function() {
            
            this.element
                .unbind('mouseenter.' + this.widgetName)
                .unbind('mouseleave.' + this.widgetName);
                
            this.autoScrollInitiated = false;
            
            return;
        },
        
        _start: function() {
        
            var self = this;
            
            // ensures interval isn't started twice
            this._stop();
            
            this.interval = setInterval(function() {

                if (self.page === self.getNoOfPages()) {
                    self.goToPage(1);
                }
                else {
                    self.next();
                }
            
            }, this.options.pause);
            
            return;
        },
        
        _stop: function() {
        
            clearInterval(this.interval);
            
            return;     
        },
        
        _setOption: function (option, value) {
        
            _super._setOption.apply(this, arguments);
            
            switch (option) {
                
            case 'autoScroll':
            
                this._stop();
                
                if (value) {
                    this._bindAutoScroll();
                    this._start();
                }
                else {
                    this._unbindAutoScroll();
                }
                
                break;
                    
            }
            
            return;
        },
        
        destroy: function() {
            
            this._stop();
            _super.destroy.apply(this);
            
            return;
        }
        
    });
    
})(jQuery);
/*
 * jquery.rs.carousel-continuous v0.8.6
 *
 * Copyright (c) 2011 Richard Scarrott
 * http://www.richardscarrott.co.uk
 *
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 * Depends:
 *  jquery.js v1.4+
 *  jquery.ui.widget.js v1.8+
 *  jquery.rs.carousel.js v0.8.6+
 *
 */
 
(function($, undefined) {

    var _super = $.rs.rscarousel.prototype;
    
    $.widget('rs.rscarousel', $.rs.rscarousel, {
    
        options: {
            continuous: false
        },
        
        _create: function () {
        
            _super._create.apply(this, arguments);

            if (this.options.continuous) {

                this._setOption('loop', true);
                this._addClonedItems();
                this.goToPage(1, false); // go to page to ensure we ignore clones

            }
            
            return;
        },
        
        // appends and prepends items to provide illusion of continuous scrolling
        _addClonedItems: function () {

            if (this.options.disabled) {
                this._removeClonedItems();
                return;
            }
        
            var elems = this.elements,
                cloneCount = this._getCloneCount(),
                cloneClass = this.options.baseClass + '-item-clone';

            this._removeClonedItems();
        
            elems.clonedBeginning = this.elements.items
                .slice(0, cloneCount)
                    .clone()
                        .addClass(cloneClass);

            elems.clonedEnd = this.elements.items
                .slice(-cloneCount)
                    .clone()
                        .addClass(cloneClass);
            
            elems.clonedBeginning.appendTo(elems.runner);
            elems.clonedEnd.prependTo(elems.runner);
            
            return;
        },

        _removeClonedItems: function () {
        
            var elems = this.elements;
        
            if (elems.clonedBeginning) {
                elems.clonedBeginning.remove();
                elems.clonedBeginning = undefined;
            }
            
            if (elems.clonedEnd) {
                elems.clonedEnd.remove();
                elems.clonedEnd = undefined;
            }
        
        },

        // number of cloned items should equal itemsPerPage or, if greater, itemsPerTransition
        _getCloneCount: function () {

            var visibleItems = Math.ceil(this._getMaskDim() / this._getItemDim()),
                itemsPerTransition = this.getItemsPerTransition();

            return visibleItems >= itemsPerTransition ? visibleItems : itemsPerTransition;
        },

        // needs to be overridden to take into account cloned items
        _setRunnerWidth: function () {

            if (!this.isHorizontal) {
                return;
            }

            var self = this;
            
            if (this.options.continuous) {
                
                this.elements.runner.width(function () {
                    return self._getItemDim() * (self.getNoOfItems() + (self._getCloneCount() * 2));
                });

            }
            else {
                _super._setRunnerWidth.apply(this, arguments);
            }

            return;
        },

        _slide: function (animate) {

            var self = this,
                itemIndex,
                cloneIndex;

            // if first or last page jump to cloned before slide
            if (this.options.continuous) {

                // this criteria means using goToPage(1) when on last page will act as continuous,
                // good thing is it means autoScrolls _start method doesn't have to be overridden
                // anymore, but is it desired?
                if (this.page === 1 && this.prevPage === this.getNoOfPages()) {

                    // jump to clonedEnd
                    this.elements.runner.css(this.helperStr.pos, function () {

                        // get item index of old page in context of clonedEnd
                        itemIndex = self.pages[self.prevPage - 1];

                        cloneIndex = self.elements.items
                            .slice(-self._getCloneCount())
                            .index(self.elements.items.eq(itemIndex - 1));

                        return -self.elements.clonedEnd
                            .eq(cloneIndex)
                                .position()[self.helperStr.pos];
                    });

                }
                else if (this.page === this.getNoOfPages() && this.prevPage === 1) {

                    // jump to clonedBeginning
                    this.elements.runner.css(this.helperStr.pos, function () {
                        return -self.elements.clonedBeginning
                            .first()
                                .position()[self.helperStr.pos];
                    });
                                                
                }

            }

            // continue
            _super._slide.apply(this, arguments);

            return;
        },

        // don't need to take into account itemsPerPage when continuous as there's no absolute last pos
        getNoOfPages: function () {
            
            var itemsPerTransition;

            if (this.options.continuous) {

                itemsPerTransition = this.getItemsPerTransition();

                if (itemsPerTransition <= 0) {
                    return 0;
                }

                return Math.ceil(this.getNoOfItems() / itemsPerTransition);
            }

            return _super.getNoOfPages.apply(this, arguments);
        },

        // not required as cloned items fill space
        _getAbsoluteLastPos: function () {
            
            if (this.options.continuous) {
                return;
            }

            return _super._getAbsoluteLastPos.apply(this, arguments);
        },

        refresh: function() {

            _super.refresh.apply(this, arguments);
            
            if (this.options.continuous) {
                this._addClonedItems();
                this.goToPage(this.page, false);
            }
            
            return;
        },

        // override to avoid clones
        _recacheItems: function () {

            var baseClass = '.' +this.options.baseClass;

            this.elements.items = this.elements.runner
                .children(baseClass + '-item')
                    .not(baseClass + '-item-clone');

            return;
        },

        add: function (items) {

            if (this.elements.items.length) {

                this.elements.items
                    .last()
                        .after(items);

                this.refresh();

                return;
            }
            
            // cloned items won't exist so use add from prototype (appends to runner)
            _super.add.apply(this, arguments);

            return;
        },

        _setOption: function (option, value) {
            
            _super._setOption.apply(this, arguments);
            
            switch (option) {
                
            case 'continuous':

                this._setOption('loop', value);

                if (!value) {
                    this._removeClonedItems();
                }
                
                this.refresh();
                
                break;
            }

            return;
        },
        
        destroy: function() {
            
            this._removeClonedItems();
            
            _super.destroy.apply(this);
            
            return;
        }
        
    });
    
})(jQuery);
/*
 * jquery.rs.carousel-touch v0.8.6
 *
 * Copyright (c) 2011 Richard Scarrott
 * http://www.richardscarrott.co.uk
 *
 * Dual licensed under the MIT and GPL licenses:
 * http://www.opensource.org/licenses/mit-license.php
 * http://www.gnu.org/licenses/gpl.html
 *
 * Depends:
 *  jquery.js v1.4+
 *  jquery.translate3d.js v0.1+ // if passing in translate3d true for hardware acceleration
 *  jquery.event.drag.js v2.1.0+
 *  jquery.ui.widget.js v1.8+
 *  jquery.rs.carousel.js v0.8.6+
 *
 */

(function ($) {

    var _super = $.Widget.prototype;
    
    // custom drag, if supported it uses 'translate3d' instead of 'left / top'
    // for hardware acceleration in iOS et al.
    $.widget('rs.draggable3d', {
    
        options: {
            axis: 'x',
            translate3d: false
        },
        
        _create: function () {

            var self = this;

            this.element
                .bind('dragstart', function (e) {
                    self._mouseStart(e);
                })
                .bind('drag', function (e) {
                    self._mouseDrag(e);
                })
                .bind('dragend', function (e) {
                    self._mouseStop(e);
                });
            
            return;
        },
        
        _getPosStr: function () {
            
            return this.options.axis === 'x' ? 'left' : 'top';
            
        },
        
        _mouseStart: function(e) {
            
            this.mouseStartPos = this.options.axis === 'x' ? e.pageX : e.pageY;
            
            if (this.options.translate3d) {
                this.runnerPos = this.element.css('translate3d')[this.options.axis];
            }
            else {
                this.runnerPos = parseInt(this.element.position()[this._getPosStr()], 10);
            }
            
            this._trigger('start', e);

            return;
        },
        
        _mouseDrag: function(e) {
        
            var page = this.options.axis === 'x' ? e.pageX : e.pageY,
                pos = (page - this.mouseStartPos) + this.runnerPos,
                cssProps = {};
            
            if (this.options.translate3d) {
                cssProps.translate3d = this.options.axis === 'x' ? {x: pos} : {y: pos};
            }
            else {
                cssProps[this._getPosStr()] = pos;
            }
            
            this.element.css(cssProps);
            
            return;
        },
        
        _mouseStop: function (e) {
            
            this._trigger('stop', e);
            
            return;
        },
        
        destroy: function () {
        
            var cssProps = {};
            
            if (this.options.translate3d) {
                cssProps.translate3d = {};
            }
            else {
                cssProps[this._getPosStr()] = '';
            }
            
            this.element.css(cssProps);
            //this._mouseDestroy();
            _super.destroy.apply(this);
            
            return;
        }
        
    });
    
})(jQuery);



// touch extension
(function ($) {
    
    var _super = $.rs.rscarousel.prototype;
        
    $.widget('rs.rscarousel', $.rs.rscarousel, {
    
        options: {
            touch: false,
            translate3d: false,
            sensitivity: 0.8
        },
        
        _create: function () {
            
            _super._create.apply(this);
            
            var self = this;

            if (this.options.touch) {
                
                this.elements.runner
                    .draggable3d({
                        translate3d: this.options.translate3d,
                        axis: this._getAxis(),
                        start: function (e) {
                            e = e.originalEvent.touches ? e.originalEvent.touches[0] : e;
                            self._dragStartHandler(e);
                        },
                        stop: function (e) {
                            e = e.originalEvent.touches ? e.originalEvent.touches[0] : e;
                            self._dragStopHandler(e);
                        }
                    });

            }
                
            // bind CSS transition callback
            if (this.options.translate3d) {
                this.elements.runner.bind('webkitTransitionEnd transitionend oTransitionEnd', function (e) {
                    self._trigger('after', null, {
                        elements: self.elements,
                        animate: animate
                    });
                    e.preventDefault(); // stops page from jumping to top...
                });
            }
            
            return;
        },
        
        _getAxis: function () {
            
            return this.isHorizontal ? 'x' : 'y';
        
        },
        
        _dragStartHandler: function (e) {
        
            // remove transition class to ensure drag doesn't transition
            if (this.options.translate3d) {
                this.elements.runner.removeClass(this.widgetBaseClass + '-runner-transition');
            }
        
            this.startTime = this._getTime();
            
            this.startPos = {
                x: e.pageX,
                y: e.pageY
            };
            
            return;
        },
        
        _dragStopHandler: function (e) {
        
            var time,
                distance,
                speed,
                direction,
                axis = this._getAxis();
                
            // if touch direction changes start date should prob be reset to correctly determine speed...
            this.endTime = this._getTime();
            
            time = this.endTime - this.startTime;
            
            this.endPos = {
                x: e.pageX,
                y: e.pageY
            };
            
            distance = Math.abs(this.startPos[axis] - this.endPos[axis]);
            speed = distance / time;
            direction = this.startPos[axis] > this.endPos[axis] ? 'next' : 'prev';
            
            if (speed > this.options.sensitivity || distance > (this._getItemDim() * this.getItemsPerTransition() / 2)) {
                if ((this.page === this.getNoOfPages() && direction === 'next')
                    || (this.page === 1 && direction === 'prev')) {
                    this.goToPage(this.page);
                }
                else {
                    this[direction]();
                }
            }
            else {
                this.goToPage(this.page); // go back to current page
            }
            
            return;
        },
        
        _getTime: function () {
            
            var date = new Date();
            return date.getTime();
        
        },
        
        // override _slide to work with tanslate3d - TODO: remove duplication
        _slide: function (animate) {

            var self = this,
                animate = animate === false ? false : true, // undefined should pass as true
                speed = animate ? this.options.speed : 0,
                animateProps = {},
                lastPos = this._getAbsoluteLastPos(),
                pos = this.elements.items
                    .eq(this.pages[this.page - 1] - 1) // arrays and .eq() are zero based, carousel is 1 based
                        .position()[this.helperStr.pos];

            // check pos doesn't go past last posible pos
            if (pos > lastPos) {
                pos = lastPos;
            }

            this._trigger('before', null, {
                elements: this.elements,
                animate: animate
            });
            
            if (this.options.translate3d) {
                
                this.elements.runner
                    .addClass(this.widgetBaseClass + '-runner-transition')
                    .css({
                        translate3d: this.isHorizontal ? {x: -pos} : {y: -pos}
                    });
                
            }
            else {
                
                animateProps[this.helperStr.pos] = -pos;
                animateProps.useTranslate3d = true; // what the hell is this...
                this.elements.runner
                    .stop()
                    .animate(animateProps, speed, this.options.easing, function () {
                        
                        self._trigger('after', null, {
                            elements: self.elements,
                            animate: animate
                        });

                    });
            }
                
            this._updateUi();
            
            return;
        },
        
        _setOption: function (option, value) {
        
            _super._setOption.apply(this, arguments);
            
            switch (option) {
                
            case 'orientation':
                this._switchAxis();
                break;
            }
            
            return;
        },
        
        _switchAxis: function () {
        
            this.elements.runner.draggable3d('option', 'axis', this._getAxis());
            
            return;
        },
        
        destroy: function () {
            
            this.elements.runner.draggable3d('destroy');
            _super.destroy.apply(this);
            
            return;
        }
        
    });

})(jQuery);
