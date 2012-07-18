/*
* jQuery timepicker addon
* By: Trent Richardson [http://trentrichardson.com]
* Version 1.0.0
* Last Modified: 02/05/2012
*
* Copyright 2012 Trent Richardson
* Dual licensed under the MIT and GPL licenses.
* http://trentrichardson.com/Impromptu/GPL-LICENSE.txt
* http://trentrichardson.com/Impromptu/MIT-LICENSE.txt
*
* HERES THE CSS:
* .ui-timepicker-div .ui-widget-header { margin-bottom: 8px; }
* .ui-timepicker-div dl { text-align: left; }
* .ui-timepicker-div dl dt { height: 25px; margin-bottom: -25px; }
* .ui-timepicker-div dl dd { margin: 0 10px 10px 65px; }
* .ui-timepicker-div td { font-size: 90%; }
* .ui-tpicker-grid-label { background: none; border: none; margin: 0; padding: 0; }
*/

(function($) {

// Prevent "Uncaught RangeError: Maximum call stack size exceeded"
$.ui.timepicker = $.ui.timepicker || {};
if ($.ui.timepicker.version) {
	return;
}

$.extend($.ui, { timepicker: { version: "1.0.0" } });

/* Time picker manager.
   Use the singleton instance of this class, $.timepicker, to interact with the time picker.
   Settings for (groups of) time pickers are maintained in an instance object,
   allowing multiple different settings on the same page. */

function Timepicker() {
	this.regional = []; // Available regional settings, indexed by language code
	this.regional[''] = { // Default regional settings
		currentText: 'Now',
		closeText: 'Done',
		ampm: false,
		amNames: ['AM', 'A'],
		pmNames: ['PM', 'P'],
		timeFormat: 'hh:mm tt',
		timeSuffix: '',
		timeOnlyTitle: 'Choose Time',
		timeText: 'Time',
		hourText: 'Hour',
		minuteText: 'Minute',
		secondText: 'Second',
		millisecText: 'Millisecond',
		timezoneText: 'Time Zone'
	};
	this._defaults = { // Global defaults for all the datetime picker instances
		showButtonPanel: true,
		timeOnly: false,
		showHour: true,
		showMinute: true,
		showSecond: false,
		showMillisec: false,
		showTimezone: false,
		showTime: true,
		stepHour: 1,
		stepMinute: 1,
		stepSecond: 1,
		stepMillisec: 1,
		hour: 0,
		minute: 0,
		second: 0,
		millisec: 0,
		timezone: '+0000',
		hourMin: 0,
		minuteMin: 0,
		secondMin: 0,
		millisecMin: 0,
		hourMax: 23,
		minuteMax: 59,
		secondMax: 59,
		millisecMax: 999,
		minDateTime: null,
		maxDateTime: null,
		onSelect: null,
		hourGrid: 0,
		minuteGrid: 0,
		secondGrid: 0,
		millisecGrid: 0,
		alwaysSetTime: true,
		separator: ' ',
		altFieldTimeOnly: true,
		showTimepicker: true,
		timezoneIso8609: false,
		timezoneList: null,
		addSliderAccess: false,
		sliderAccessArgs: null
	};
	$.extend(this._defaults, this.regional['']);
};

$.extend(Timepicker.prototype, {
	$input: null,
	$altInput: null,
	$timeObj: null,
	inst: null,
	hour_slider: null,
	minute_slider: null,
	second_slider: null,
	millisec_slider: null,
	timezone_select: null,
	hour: 0,
	minute: 0,
	second: 0,
	millisec: 0,
	timezone: '+0000',
	hourMinOriginal: null,
	minuteMinOriginal: null,
	secondMinOriginal: null,
	millisecMinOriginal: null,
	hourMaxOriginal: null,
	minuteMaxOriginal: null,
	secondMaxOriginal: null,
	millisecMaxOriginal: null,
	ampm: '',
	formattedDate: '',
	formattedTime: '',
	formattedDateTime: '',
	timezoneList: null,

	/* Override the default settings for all instances of the time picker.
	   @param  settings  object - the new settings to use as defaults (anonymous object)
	   @return the manager object */
	setDefaults: function(settings) {
		extendRemove(this._defaults, settings || {});
		return this;
	},

	//########################################################################
	// Create a new Timepicker instance
	//########################################################################
	_newInst: function($input, o) {
		var tp_inst = new Timepicker(),
			inlineSettings = {};

		for (var attrName in this._defaults) {
			var attrValue = $input.attr('time:' + attrName);
			if (attrValue) {
				try {
					inlineSettings[attrName] = eval(attrValue);
				} catch (err) {
					inlineSettings[attrName] = attrValue;
				}
			}
		}
		tp_inst._defaults = $.extend({}, this._defaults, inlineSettings, o, {
			beforeShow: function(input, dp_inst) {
				if ($.isFunction(o.beforeShow))
					return o.beforeShow(input, dp_inst, tp_inst);
			},
			onChangeMonthYear: function(year, month, dp_inst) {
				// Update the time as well : this prevents the time from disappearing from the $input field.
				tp_inst._updateDateTime(dp_inst);
				if ($.isFunction(o.onChangeMonthYear))
					o.onChangeMonthYear.call($input[0], year, month, dp_inst, tp_inst);
			},
			onClose: function(dateText, dp_inst) {
				if (tp_inst.timeDefined === true && $input.val() != '')
					tp_inst._updateDateTime(dp_inst);
				if ($.isFunction(o.onClose))
					o.onClose.call($input[0], dateText, dp_inst, tp_inst);
			},
			timepicker: tp_inst // add timepicker as a property of datepicker: $.datepicker._get(dp_inst, 'timepicker');
		});
		tp_inst.amNames = $.map(tp_inst._defaults.amNames, function(val) { return val.toUpperCase(); });
		tp_inst.pmNames = $.map(tp_inst._defaults.pmNames, function(val) { return val.toUpperCase(); });

		if (tp_inst._defaults.timezoneList === null) {
			var timezoneList = [];
			for (var i = -11; i <= 12; i++)
				timezoneList.push((i >= 0 ? '+' : '-') + ('0' + Math.abs(i).toString()).slice(-2) + '00');
			if (tp_inst._defaults.timezoneIso8609)
				timezoneList = $.map(timezoneList, function(val) {
					return val == '+0000' ? 'Z' : (val.substring(0, 3) + ':' + val.substring(3));
				});
			tp_inst._defaults.timezoneList = timezoneList;
		}

		tp_inst.hour = tp_inst._defaults.hour;
		tp_inst.minute = tp_inst._defaults.minute;
		tp_inst.second = tp_inst._defaults.second;
		tp_inst.millisec = tp_inst._defaults.millisec;
		tp_inst.ampm = '';
		tp_inst.$input = $input;

		if (o.altField)
			tp_inst.$altInput = $(o.altField)
				.css({ cursor: 'pointer' })
				.focus(function(){ $input.trigger("focus"); });

		if(tp_inst._defaults.minDate==0 || tp_inst._defaults.minDateTime==0)
		{
			tp_inst._defaults.minDate=new Date();
		}
		if(tp_inst._defaults.maxDate==0 || tp_inst._defaults.maxDateTime==0)
		{
			tp_inst._defaults.maxDate=new Date();
		}

		// datepicker needs minDate/maxDate, timepicker needs minDateTime/maxDateTime..
		if(tp_inst._defaults.minDate !== undefined && tp_inst._defaults.minDate instanceof Date)
			tp_inst._defaults.minDateTime = new Date(tp_inst._defaults.minDate.getTime());
		if(tp_inst._defaults.minDateTime !== undefined && tp_inst._defaults.minDateTime instanceof Date)
			tp_inst._defaults.minDate = new Date(tp_inst._defaults.minDateTime.getTime());
		if(tp_inst._defaults.maxDate !== undefined && tp_inst._defaults.maxDate instanceof Date)
			tp_inst._defaults.maxDateTime = new Date(tp_inst._defaults.maxDate.getTime());
		if(tp_inst._defaults.maxDateTime !== undefined && tp_inst._defaults.maxDateTime instanceof Date)
			tp_inst._defaults.maxDate = new Date(tp_inst._defaults.maxDateTime.getTime());
		return tp_inst;
	},

	//########################################################################
	// add our sliders to the calendar
	//########################################################################
	_addTimePicker: function(dp_inst) {
		var currDT = (this.$altInput && this._defaults.altFieldTimeOnly) ?
				this.$input.val() + ' ' + this.$altInput.val() :
				this.$input.val();

		this.timeDefined = this._parseTime(currDT);
		this._limitMinMaxDateTime(dp_inst, false);
		this._injectTimePicker();
	},

	//########################################################################
	// parse the time string from input value or _setTime
	//########################################################################
	_parseTime: function(timeString, withDate) {
		var regstr = this._defaults.timeFormat.toString()
				.replace(/h{1,2}/ig, '(\\d?\\d)')
				.replace(/m{1,2}/ig, '(\\d?\\d)')
				.replace(/s{1,2}/ig, '(\\d?\\d)')
				.replace(/l{1}/ig, '(\\d?\\d?\\d)')
				.replace(/t{1,2}/ig, this._getPatternAmpm())
				.replace(/z{1}/ig, '(z|[-+]\\d\\d:?\\d\\d)?')
				.replace(/\s/g, '\\s?') + this._defaults.timeSuffix + '$',
			order = this._getFormatPositions(),
			ampm = '',
			treg;

		if (!this.inst) this.inst = $.datepicker._getInst(this.$input[0]);

		if (withDate || !this._defaults.timeOnly) {
			// the time should come after x number of characters and a space.
			// x = at least the length of text specified by the date format
			var dp_dateFormat = $.datepicker._get(this.inst, 'dateFormat');
			// escape special regex characters in the seperator
			var specials = new RegExp("[.*+?|()\\[\\]{}\\\\]", "g");
			regstr = '^.{' + dp_dateFormat.length + ',}?' + this._defaults.separator.replace(specials, "\\$&") + regstr;
		}

		treg = timeString.match(new RegExp(regstr, 'i'));

		if (treg) {
			if (order.t !== -1) {
				if (treg[order.t] === undefined || treg[order.t].length === 0) {
					ampm = '';
					this.ampm = '';
				} else {
					ampm = $.inArray(treg[order.t].toUpperCase(), this.amNames) !== -1 ? 'AM' : 'PM';
					this.ampm = this._defaults[ampm == 'AM' ? 'amNames' : 'pmNames'][0];
				}
			}

			if (order.h !== -1) {
				if (ampm == 'AM' && treg[order.h] == '12')
					this.hour = 0; // 12am = 0 hour
				else if (ampm == 'PM' && treg[order.h] != '12')
					this.hour = (parseFloat(treg[order.h]) + 12).toFixed(0); // 12pm = 12 hour, any other pm = hour + 12
				else this.hour = Number(treg[order.h]);
			}

			if (order.m !== -1) this.minute = Number(treg[order.m]);
			if (order.s !== -1) this.second = Number(treg[order.s]);
			if (order.l !== -1) this.millisec = Number(treg[order.l]);
			if (order.z !== -1 && treg[order.z] !== undefined) {
				var tz = treg[order.z].toUpperCase();
				switch (tz.length) {
				case 1:	// Z
					tz = this._defaults.timezoneIso8609 ? 'Z' : '+0000';
					break;
				case 5:	// +hhmm
					if (this._defaults.timezoneIso8609)
						tz = tz.substring(1) == '0000'
						   ? 'Z'
						   : tz.substring(0, 3) + ':' + tz.substring(3);
					break;
				case 6:	// +hh:mm
					if (!this._defaults.timezoneIso8609)
						tz = tz == 'Z' || tz.substring(1) == '00:00'
						   ? '+0000'
						   : tz.replace(/:/, '');
					else if (tz.substring(1) == '00:00')
						tz = 'Z';
					break;
				}
				this.timezone = tz;
			}

			return true;

		}
		return false;
	},

	//########################################################################
	// pattern for standard and localized AM/PM markers
	//########################################################################
	_getPatternAmpm: function() {
		var markers = [],
			o = this._defaults;
		if (o.amNames)
			$.merge(markers, o.amNames);
		if (o.pmNames)
			$.merge(markers, o.pmNames);
		markers = $.map(markers, function(val) { return val.replace(/[.*+?|()\[\]{}\\]/g, '\\$&'); });
		return '(' + markers.join('|') + ')?';
	},

	//########################################################################
	// figure out position of time elements.. cause js cant do named captures
	//########################################################################
	_getFormatPositions: function() {
		var finds = this._defaults.timeFormat.toLowerCase().match(/(h{1,2}|m{1,2}|s{1,2}|l{1}|t{1,2}|z)/g),
			orders = { h: -1, m: -1, s: -1, l: -1, t: -1, z: -1 };

		if (finds)
			for (var i = 0; i < finds.length; i++)
				if (orders[finds[i].toString().charAt(0)] == -1)
					orders[finds[i].toString().charAt(0)] = i + 1;

		return orders;
	},

	//########################################################################
	// generate and inject html for timepicker into ui datepicker
	//########################################################################
	_injectTimePicker: function() {
		var $dp = this.inst.dpDiv,
			o = this._defaults,
			tp_inst = this,
			// Added by Peter Medeiros:
			// - Figure out what the hour/minute/second max should be based on the step values.
			// - Example: if stepMinute is 15, then minMax is 45.
			hourMax = parseInt((o.hourMax - ((o.hourMax - o.hourMin) % o.stepHour)) ,10),
			minMax  = parseInt((o.minuteMax - ((o.minuteMax - o.minuteMin) % o.stepMinute)) ,10),
			secMax  = parseInt((o.secondMax - ((o.secondMax - o.secondMin) % o.stepSecond)) ,10),
			millisecMax  = parseInt((o.millisecMax - ((o.millisecMax - o.millisecMin) % o.stepMillisec)) ,10),
			dp_id = this.inst.id.toString().replace(/([^A-Za-z0-9_])/g, '');

		// Prevent displaying twice
		//if ($dp.find("div#ui-timepicker-div-"+ dp_id).length === 0) {
		if ($dp.find("div#ui-timepicker-div-"+ dp_id).length === 0 && o.showTimepicker) {
			var noDisplay = ' style="display:none;"',
				html =	'<div class="ui-timepicker-div" id="ui-timepicker-div-' + dp_id + '"><dl>' +
						'<dt class="ui_tpicker_time_label" id="ui_tpicker_time_label_' + dp_id + '"' +
						((o.showTime) ? '' : noDisplay) + '>' + o.timeText + '</dt>' +
						'<dd class="ui_tpicker_time" id="ui_tpicker_time_' + dp_id + '"' +
						((o.showTime) ? '' : noDisplay) + '></dd>' +
						'<dt class="ui_tpicker_hour_label" id="ui_tpicker_hour_label_' + dp_id + '"' +
						((o.showHour) ? '' : noDisplay) + '>' + o.hourText + '</dt>',
				hourGridSize = 0,
				minuteGridSize = 0,
				secondGridSize = 0,
				millisecGridSize = 0,
				size = null;

 			// Hours
			html += '<dd class="ui_tpicker_hour"><div id="ui_tpicker_hour_' + dp_id + '"' +
						((o.showHour) ? '' : noDisplay) + '></div>';
			if (o.showHour && o.hourGrid > 0) {
				html += '<div style="padding-left: 1px"><table class="ui-tpicker-grid-label"><tr>';

				for (var h = o.hourMin; h <= hourMax; h += parseInt(o.hourGrid,10)) {
					hourGridSize++;
					var tmph = (o.ampm && h > 12) ? h-12 : h;
					if (tmph < 10) tmph = '0' + tmph;
					if (o.ampm) {
						if (h == 0) tmph = 12 +'a';
						else if (h < 12) tmph += 'a';
						else tmph += 'p';
					}
					html += '<td>' + tmph + '</td>';
				}

				html += '</tr></table></div>';
			}
			html += '</dd>';

			// Minutes
			html += '<dt class="ui_tpicker_minute_label" id="ui_tpicker_minute_label_' + dp_id + '"' +
					((o.showMinute) ? '' : noDisplay) + '>' + o.minuteText + '</dt>'+
					'<dd class="ui_tpicker_minute"><div id="ui_tpicker_minute_' + dp_id + '"' +
							((o.showMinute) ? '' : noDisplay) + '></div>';

			if (o.showMinute && o.minuteGrid > 0) {
				html += '<div style="padding-left: 1px"><table class="ui-tpicker-grid-label"><tr>';

				for (var m = o.minuteMin; m <= minMax; m += parseInt(o.minuteGrid,10)) {
					minuteGridSize++;
					html += '<td>' + ((m < 10) ? '0' : '') + m + '</td>';
				}

				html += '</tr></table></div>';
			}
			html += '</dd>';

			// Seconds
			html += '<dt class="ui_tpicker_second_label" id="ui_tpicker_second_label_' + dp_id + '"' +
					((o.showSecond) ? '' : noDisplay) + '>' + o.secondText + '</dt>'+
					'<dd class="ui_tpicker_second"><div id="ui_tpicker_second_' + dp_id + '"'+
							((o.showSecond) ? '' : noDisplay) + '></div>';

			if (o.showSecond && o.secondGrid > 0) {
				html += '<div style="padding-left: 1px"><table><tr>';

				for (var s = o.secondMin; s <= secMax; s += parseInt(o.secondGrid,10)) {
					secondGridSize++;
					html += '<td>' + ((s < 10) ? '0' : '') + s + '</td>';
				}

				html += '</tr></table></div>';
			}
			html += '</dd>';

			// Milliseconds
			html += '<dt class="ui_tpicker_millisec_label" id="ui_tpicker_millisec_label_' + dp_id + '"' +
					((o.showMillisec) ? '' : noDisplay) + '>' + o.millisecText + '</dt>'+
					'<dd class="ui_tpicker_millisec"><div id="ui_tpicker_millisec_' + dp_id + '"'+
							((o.showMillisec) ? '' : noDisplay) + '></div>';

			if (o.showMillisec && o.millisecGrid > 0) {
				html += '<div style="padding-left: 1px"><table><tr>';

				for (var l = o.millisecMin; l <= millisecMax; l += parseInt(o.millisecGrid,10)) {
					millisecGridSize++;
					html += '<td>' + ((l < 10) ? '0' : '') + l + '</td>';
				}

				html += '</tr></table></div>';
			}
			html += '</dd>';

			// Timezone
			html += '<dt class="ui_tpicker_timezone_label" id="ui_tpicker_timezone_label_' + dp_id + '"' +
					((o.showTimezone) ? '' : noDisplay) + '>' + o.timezoneText + '</dt>';
			html += '<dd class="ui_tpicker_timezone" id="ui_tpicker_timezone_' + dp_id + '"'	+
							((o.showTimezone) ? '' : noDisplay) + '></dd>';

			html += '</dl></div>';
			$tp = $(html);

				// if we only want time picker...
			if (o.timeOnly === true) {
				$tp.prepend(
					'<div class="ui-widget-header ui-helper-clearfix ui-corner-all">' +
						'<div class="ui-datepicker-title">' + o.timeOnlyTitle + '</div>' +
					'</div>');
				$dp.find('.ui-datepicker-header, .ui-datepicker-calendar').hide();
			}

			this.hour_slider = $tp.find('#ui_tpicker_hour_'+ dp_id).slider({
				orientation: "horizontal",
				value: this.hour,
				min: o.hourMin,
				max: hourMax,
				step: o.stepHour,
				slide: function(event, ui) {
					tp_inst.hour_slider.slider( "option", "value", ui.value);
					tp_inst._onTimeChange();
				}
			});


			// Updated by Peter Medeiros:
			// - Pass in Event and UI instance into slide function
			this.minute_slider = $tp.find('#ui_tpicker_minute_'+ dp_id).slider({
				orientation: "horizontal",
				value: this.minute,
				min: o.minuteMin,
				max: minMax,
				step: o.stepMinute,
				slide: function(event, ui) {
					tp_inst.minute_slider.slider( "option", "value", ui.value);
					tp_inst._onTimeChange();
				}
			});

			this.second_slider = $tp.find('#ui_tpicker_second_'+ dp_id).slider({
				orientation: "horizontal",
				value: this.second,
				min: o.secondMin,
				max: secMax,
				step: o.stepSecond,
				slide: function(event, ui) {
					tp_inst.second_slider.slider( "option", "value", ui.value);
					tp_inst._onTimeChange();
				}
			});

			this.millisec_slider = $tp.find('#ui_tpicker_millisec_'+ dp_id).slider({
				orientation: "horizontal",
				value: this.millisec,
				min: o.millisecMin,
				max: millisecMax,
				step: o.stepMillisec,
				slide: function(event, ui) {
					tp_inst.millisec_slider.slider( "option", "value", ui.value);
					tp_inst._onTimeChange();
				}
			});

			this.timezone_select = $tp.find('#ui_tpicker_timezone_'+ dp_id).append('<select></select>').find("select");
			$.fn.append.apply(this.timezone_select,
				$.map(o.timezoneList, function(val, idx) {
					return $("<option />")
						.val(typeof val == "object" ? val.value : val)
						.text(typeof val == "object" ? val.label : val);
				})
			);
			this.timezone_select.val((typeof this.timezone != "undefined" && this.timezone != null && this.timezone != "") ? this.timezone : o.timezone);
			this.timezone_select.change(function() {
				tp_inst._onTimeChange();
			});

			// Add grid functionality
			if (o.showHour && o.hourGrid > 0) {
				size = 100 * hourGridSize * o.hourGrid / (hourMax - o.hourMin);

				$tp.find(".ui_tpicker_hour table").css({
					width: size + "%",
					marginLeft: (size / (-2 * hourGridSize)) + "%",
					borderCollapse: 'collapse'
				}).find("td").each( function(index) {
					$(this).click(function() {
						var h = $(this).html();
						if(o.ampm)	{
							var ap = h.substring(2).toLowerCase(),
								aph = parseInt(h.substring(0,2), 10);
							if (ap == 'a') {
								if (aph == 12) h = 0;
								else h = aph;
							} else if (aph == 12) h = 12;
							else h = aph + 12;
						}
						tp_inst.hour_slider.slider("option", "value", h);
						tp_inst._onTimeChange();
						tp_inst._onSelectHandler();
					}).css({
						cursor: 'pointer',
						width: (100 / hourGridSize) + '%',
						textAlign: 'center',
						overflow: 'hidden'
					});
				});
			}

			if (o.showMinute && o.minuteGrid > 0) {
				size = 100 * minuteGridSize * o.minuteGrid / (minMax - o.minuteMin);
				$tp.find(".ui_tpicker_minute table").css({
					width: size + "%",
					marginLeft: (size / (-2 * minuteGridSize)) + "%",
					borderCollapse: 'collapse'
				}).find("td").each(function(index) {
					$(this).click(function() {
						tp_inst.minute_slider.slider("option", "value", $(this).html());
						tp_inst._onTimeChange();
						tp_inst._onSelectHandler();
					}).css({
						cursor: 'pointer',
						width: (100 / minuteGridSize) + '%',
						textAlign: 'center',
						overflow: 'hidden'
					});
				});
			}

			if (o.showSecond && o.secondGrid > 0) {
				$tp.find(".ui_tpicker_second table").css({
					width: size + "%",
					marginLeft: (size / (-2 * secondGridSize)) + "%",
					borderCollapse: 'collapse'
				}).find("td").each(function(index) {
					$(this).click(function() {
						tp_inst.second_slider.slider("option", "value", $(this).html());
						tp_inst._onTimeChange();
						tp_inst._onSelectHandler();
					}).css({
						cursor: 'pointer',
						width: (100 / secondGridSize) + '%',
						textAlign: 'center',
						overflow: 'hidden'
					});
				});
			}

			if (o.showMillisec && o.millisecGrid > 0) {
				$tp.find(".ui_tpicker_millisec table").css({
					width: size + "%",
					marginLeft: (size / (-2 * millisecGridSize)) + "%",
					borderCollapse: 'collapse'
				}).find("td").each(function(index) {
					$(this).click(function() {
						tp_inst.millisec_slider.slider("option", "value", $(this).html());
						tp_inst._onTimeChange();
						tp_inst._onSelectHandler();
					}).css({
						cursor: 'pointer',
						width: (100 / millisecGridSize) + '%',
						textAlign: 'center',
						overflow: 'hidden'
					});
				});
			}

			var $buttonPanel = $dp.find('.ui-datepicker-buttonpane');
			if ($buttonPanel.length) $buttonPanel.before($tp);
			else $dp.append($tp);

			this.$timeObj = $tp.find('#ui_tpicker_time_'+ dp_id);

			if (this.inst !== null) {
				var timeDefined = this.timeDefined;
				this._onTimeChange();
				this.timeDefined = timeDefined;
			}

			//Emulate datepicker onSelect behavior. Call on slidestop.
			var onSelectDelegate = function() {
				tp_inst._onSelectHandler();
			};
			this.hour_slider.bind('slidestop',onSelectDelegate);
			this.minute_slider.bind('slidestop',onSelectDelegate);
			this.second_slider.bind('slidestop',onSelectDelegate);
			this.millisec_slider.bind('slidestop',onSelectDelegate);

			// slideAccess integration: http://trentrichardson.com/2011/11/11/jquery-ui-sliders-and-touch-accessibility/
			if (this._defaults.addSliderAccess){
				var sliderAccessArgs = this._defaults.sliderAccessArgs;
				setTimeout(function(){ // fix for inline mode
					if($tp.find('.ui-slider-access').length == 0){
						$tp.find('.ui-slider:visible').sliderAccess(sliderAccessArgs);

						// fix any grids since sliders are shorter
						var sliderAccessWidth = $tp.find('.ui-slider-access:eq(0)').outerWidth(true);
						if(sliderAccessWidth){
							$tp.find('table:visible').each(function(){
								var $g = $(this),
									oldWidth = $g.outerWidth(),
									oldMarginLeft = $g.css('marginLeft').toString().replace('%',''),
									newWidth = oldWidth - sliderAccessWidth,
									newMarginLeft = ((oldMarginLeft * newWidth)/oldWidth) + '%';

								$g.css({ width: newWidth, marginLeft: newMarginLeft });
							});
						}
					}
				},0);
			}
			// end slideAccess integration

		}
	},

	//########################################################################
	// This function tries to limit the ability to go outside the
	// min/max date range
	//########################################################################
	_limitMinMaxDateTime: function(dp_inst, adjustSliders){
		var o = this._defaults,
			dp_date = new Date(dp_inst.selectedYear, dp_inst.selectedMonth, dp_inst.selectedDay);

		if(!this._defaults.showTimepicker) return; // No time so nothing to check here

		if($.datepicker._get(dp_inst, 'minDateTime') !== null && $.datepicker._get(dp_inst, 'minDateTime') !== undefined && dp_date){
			var minDateTime = $.datepicker._get(dp_inst, 'minDateTime'),
				minDateTimeDate = new Date(minDateTime.getFullYear(), minDateTime.getMonth(), minDateTime.getDate(), 0, 0, 0, 0);

			if(this.hourMinOriginal === null || this.minuteMinOriginal === null || this.secondMinOriginal === null || this.millisecMinOriginal === null){
				this.hourMinOriginal = o.hourMin;
				this.minuteMinOriginal = o.minuteMin;
				this.secondMinOriginal = o.secondMin;
				this.millisecMinOriginal = o.millisecMin;
			}

			if(dp_inst.settings.timeOnly || minDateTimeDate.getTime() == dp_date.getTime()) {
				this._defaults.hourMin = minDateTime.getHours();
				if (this.hour <= this._defaults.hourMin) {
					this.hour = this._defaults.hourMin;
					this._defaults.minuteMin = minDateTime.getMinutes();
					if (this.minute <= this._defaults.minuteMin) {
						this.minute = this._defaults.minuteMin;
						this._defaults.secondMin = minDateTime.getSeconds();
					} else if (this.second <= this._defaults.secondMin){
						this.second = this._defaults.secondMin;
						this._defaults.millisecMin = minDateTime.getMilliseconds();
					} else {
						if(this.millisec < this._defaults.millisecMin)
							this.millisec = this._defaults.millisecMin;
						this._defaults.millisecMin = this.millisecMinOriginal;
					}
				} else {
					this._defaults.minuteMin = this.minuteMinOriginal;
					this._defaults.secondMin = this.secondMinOriginal;
					this._defaults.millisecMin = this.millisecMinOriginal;
				}
			}else{
				this._defaults.hourMin = this.hourMinOriginal;
				this._defaults.minuteMin = this.minuteMinOriginal;
				this._defaults.secondMin = this.secondMinOriginal;
				this._defaults.millisecMin = this.millisecMinOriginal;
			}
		}

		if($.datepicker._get(dp_inst, 'maxDateTime') !== null && $.datepicker._get(dp_inst, 'maxDateTime') !== undefined && dp_date){
			var maxDateTime = $.datepicker._get(dp_inst, 'maxDateTime'),
				maxDateTimeDate = new Date(maxDateTime.getFullYear(), maxDateTime.getMonth(), maxDateTime.getDate(), 0, 0, 0, 0);

			if(this.hourMaxOriginal === null || this.minuteMaxOriginal === null || this.secondMaxOriginal === null){
				this.hourMaxOriginal = o.hourMax;
				this.minuteMaxOriginal = o.minuteMax;
				this.secondMaxOriginal = o.secondMax;
				this.millisecMaxOriginal = o.millisecMax;
			}

			if(dp_inst.settings.timeOnly || maxDateTimeDate.getTime() == dp_date.getTime()){
				this._defaults.hourMax = maxDateTime.getHours();
				if (this.hour >= this._defaults.hourMax) {
					this.hour = this._defaults.hourMax;
					this._defaults.minuteMax = maxDateTime.getMinutes();
					if (this.minute >= this._defaults.minuteMax) {
						this.minute = this._defaults.minuteMax;
						this._defaults.secondMax = maxDateTime.getSeconds();
					} else if (this.second >= this._defaults.secondMax) {
						this.second = this._defaults.secondMax;
						this._defaults.millisecMax = maxDateTime.getMilliseconds();
					} else {
						if(this.millisec > this._defaults.millisecMax) this.millisec = this._defaults.millisecMax;
						this._defaults.millisecMax = this.millisecMaxOriginal;
					}
				} else {
					this._defaults.minuteMax = this.minuteMaxOriginal;
					this._defaults.secondMax = this.secondMaxOriginal;
					this._defaults.millisecMax = this.millisecMaxOriginal;
				}
			}else{
				this._defaults.hourMax = this.hourMaxOriginal;
				this._defaults.minuteMax = this.minuteMaxOriginal;
				this._defaults.secondMax = this.secondMaxOriginal;
				this._defaults.millisecMax = this.millisecMaxOriginal;
			}
		}

		if(adjustSliders !== undefined && adjustSliders === true){
			var hourMax = parseInt((this._defaults.hourMax - ((this._defaults.hourMax - this._defaults.hourMin) % this._defaults.stepHour)) ,10),
                minMax  = parseInt((this._defaults.minuteMax - ((this._defaults.minuteMax - this._defaults.minuteMin) % this._defaults.stepMinute)) ,10),
                secMax  = parseInt((this._defaults.secondMax - ((this._defaults.secondMax - this._defaults.secondMin) % this._defaults.stepSecond)) ,10),
				millisecMax  = parseInt((this._defaults.millisecMax - ((this._defaults.millisecMax - this._defaults.millisecMin) % this._defaults.stepMillisec)) ,10);

			if(this.hour_slider)
				this.hour_slider.slider("option", { min: this._defaults.hourMin, max: hourMax }).slider('value', this.hour);
			if(this.minute_slider)
				this.minute_slider.slider("option", { min: this._defaults.minuteMin, max: minMax }).slider('value', this.minute);
			if(this.second_slider)
				this.second_slider.slider("option", { min: this._defaults.secondMin, max: secMax }).slider('value', this.second);
			if(this.millisec_slider)
				this.millisec_slider.slider("option", { min: this._defaults.millisecMin, max: millisecMax }).slider('value', this.millisec);
		}

	},


	//########################################################################
	// when a slider moves, set the internal time...
	// on time change is also called when the time is updated in the text field
	//########################################################################
	_onTimeChange: function() {
		var hour   = (this.hour_slider) ? this.hour_slider.slider('value') : false,
			minute = (this.minute_slider) ? this.minute_slider.slider('value') : false,
			second = (this.second_slider) ? this.second_slider.slider('value') : false,
			millisec = (this.millisec_slider) ? this.millisec_slider.slider('value') : false,
			timezone = (this.timezone_select) ? this.timezone_select.val() : false,
			o = this._defaults;

		if (typeof(hour) == 'object') hour = false;
		if (typeof(minute) == 'object') minute = false;
		if (typeof(second) == 'object') second = false;
		if (typeof(millisec) == 'object') millisec = false;
		if (typeof(timezone) == 'object') timezone = false;

		if (hour !== false) hour = parseInt(hour,10);
		if (minute !== false) minute = parseInt(minute,10);
		if (second !== false) second = parseInt(second,10);
		if (millisec !== false) millisec = parseInt(millisec,10);

		var ampm = o[hour < 12 ? 'amNames' : 'pmNames'][0];

		// If the update was done in the input field, the input field should not be updated.
		// If the update was done using the sliders, update the input field.
		var hasChanged = (hour != this.hour || minute != this.minute
				|| second != this.second || millisec != this.millisec
				|| (this.ampm.length > 0
				    && (hour < 12) != ($.inArray(this.ampm.toUpperCase(), this.amNames) !== -1))
				|| timezone != this.timezone);

		if (hasChanged) {

			if (hour !== false)this.hour = hour;
			if (minute !== false) this.minute = minute;
			if (second !== false) this.second = second;
			if (millisec !== false) this.millisec = millisec;
			if (timezone !== false) this.timezone = timezone;

			if (!this.inst) this.inst = $.datepicker._getInst(this.$input[0]);

			this._limitMinMaxDateTime(this.inst, true);
		}
		if (o.ampm) this.ampm = ampm;

		//this._formatTime();
		this.formattedTime = $.datepicker.formatTime(this._defaults.timeFormat, this, this._defaults);
		if (this.$timeObj) this.$timeObj.text(this.formattedTime + o.timeSuffix);
		this.timeDefined = true;
		if (hasChanged) this._updateDateTime();
	},

	//########################################################################
	// call custom onSelect.
	// bind to sliders slidestop, and grid click.
	//########################################################################
	_onSelectHandler: function() {
		var onSelect = this._defaults.onSelect;
		var inputEl = this.$input ? this.$input[0] : null;
		if (onSelect && inputEl) {
			onSelect.apply(inputEl, [this.formattedDateTime, this]);
		}
	},

	//########################################################################
	// left for any backwards compatibility
	//########################################################################
	_formatTime: function(time, format) {
		time = time || { hour: this.hour, minute: this.minute, second: this.second, millisec: this.millisec, ampm: this.ampm, timezone: this.timezone };
		var tmptime = (format || this._defaults.timeFormat).toString();

		tmptime = $.datepicker.formatTime(tmptime, time, this._defaults);

		if (arguments.length) return tmptime;
		else this.formattedTime = tmptime;
	},

	//########################################################################
	// update our input with the new date time..
	//########################################################################
	_updateDateTime: function(dp_inst) {
		dp_inst = this.inst || dp_inst;
		var dt = $.datepicker._daylightSavingAdjust(new Date(dp_inst.selectedYear, dp_inst.selectedMonth, dp_inst.selectedDay)),
			dateFmt = $.datepicker._get(dp_inst, 'dateFormat'),
			formatCfg = $.datepicker._getFormatConfig(dp_inst),
			timeAvailable = dt !== null && this.timeDefined;
		this.formattedDate = $.datepicker.formatDate(dateFmt, (dt === null ? new Date() : dt), formatCfg);
		var formattedDateTime = this.formattedDate;
		if (dp_inst.lastVal !== undefined && (dp_inst.lastVal.length > 0 && this.$input.val().length === 0))
			return;

		if (this._defaults.timeOnly === true) {
			formattedDateTime = this.formattedTime;
		} else if (this._defaults.timeOnly !== true && (this._defaults.alwaysSetTime || timeAvailable)) {
			formattedDateTime += this._defaults.separator + this.formattedTime + this._defaults.timeSuffix;
		}

		this.formattedDateTime = formattedDateTime;

		if(!this._defaults.showTimepicker) {
			this.$input.val(this.formattedDate);
		} else if (this.$altInput && this._defaults.altFieldTimeOnly === true) {
			this.$altInput.val(this.formattedTime);
			this.$input.val(this.formattedDate);
		} else if(this.$altInput) {
			this.$altInput.val(formattedDateTime);
			this.$input.val(formattedDateTime);
		} else {
			this.$input.val(formattedDateTime);
		}

		this.$input.trigger("change");
	}

});

$.fn.extend({
	//########################################################################
	// shorthand just to use timepicker..
	//########################################################################
	timepicker: function(o) {
		o = o || {};
		var tmp_args = arguments;

		if (typeof o == 'object') tmp_args[0] = $.extend(o, { timeOnly: true });

		return $(this).each(function() {
			$.fn.datetimepicker.apply($(this), tmp_args);
		});
	},

	//########################################################################
	// extend timepicker to datepicker
	//########################################################################
	datetimepicker: function(o) {
		o = o || {};
		tmp_args = arguments;

		if (typeof(o) == 'string'){
			if(o == 'getDate')
				return $.fn.datepicker.apply($(this[0]), tmp_args);
			else
				return this.each(function() {
					var $t = $(this);
					$t.datepicker.apply($t, tmp_args);
				});
		}
		else
			return this.each(function() {
				var $t = $(this);
				$t.datepicker($.timepicker._newInst($t, o)._defaults);
			});
	}
});

//########################################################################
// format the time all pretty...
// format = string format of the time
// time = a {}, not a Date() for timezones
// options = essentially the regional[].. amNames, pmNames, ampm
//########################################################################
$.datepicker.formatTime = function(format, time, options) {
	options = options || {};
	options = $.extend($.timepicker._defaults, options);
	time = $.extend({hour:0, minute:0, second:0, millisec:0, timezone:'+0000'}, time);

	var tmptime = format;
	var ampmName = options['amNames'][0];

	var hour = parseInt(time.hour, 10);
	if (options.ampm) {
		if (hour > 11){
			ampmName = options['pmNames'][0];
			if(hour > 12)
				hour = hour % 12;
		}
		if (hour === 0)
			hour = 12;
	}
	tmptime = tmptime.replace(/(?:hh?|mm?|ss?|[tT]{1,2}|[lz])/g, function(match) {
		switch (match.toLowerCase()) {
			case 'hh': return ('0' + hour).slice(-2);
			case 'h':  return hour;
			case 'mm': return ('0' + time.minute).slice(-2);
			case 'm':  return time.minute;
			case 'ss': return ('0' + time.second).slice(-2);
			case 's':  return time.second;
			case 'l':  return ('00' + time.millisec).slice(-3);
			case 'z':  return time.timezone;
			case 't': case 'tt':
				if (options.ampm) {
					if (match.length == 1)
						ampmName = ampmName.charAt(0);
					return match.charAt(0) == 'T' ? ampmName.toUpperCase() : ampmName.toLowerCase();
				}
				return '';
		}
	});

	tmptime = $.trim(tmptime);
	return tmptime;
};

//########################################################################
// the bad hack :/ override datepicker so it doesnt close on select
// inspired: http://stackoverflow.com/questions/1252512/jquery-datepicker-prevent-closing-picker-when-clicking-a-date/1762378#1762378
//########################################################################
$.datepicker._base_selectDate = $.datepicker._selectDate;
$.datepicker._selectDate = function (id, dateStr) {
	var inst = this._getInst($(id)[0]),
		tp_inst = this._get(inst, 'timepicker');

	if (tp_inst) {
		tp_inst._limitMinMaxDateTime(inst, true);
		inst.inline = inst.stay_open = true;
		//This way the onSelect handler called from calendarpicker get the full dateTime
		this._base_selectDate(id, dateStr);
		inst.inline = inst.stay_open = false;
		this._notifyChange(inst);
		this._updateDatepicker(inst);
	}
	else this._base_selectDate(id, dateStr);
};

//#############################################################################################
// second bad hack :/ override datepicker so it triggers an event when changing the input field
// and does not redraw the datepicker on every selectDate event
//#############################################################################################
$.datepicker._base_updateDatepicker = $.datepicker._updateDatepicker;
$.datepicker._updateDatepicker = function(inst) {

	// don't popup the datepicker if there is another instance already opened
	var input = inst.input[0];
	if($.datepicker._curInst &&
	   $.datepicker._curInst != inst &&
	   $.datepicker._datepickerShowing &&
	   $.datepicker._lastInput != input) {
		return;
	}

	if (typeof(inst.stay_open) !== 'boolean' || inst.stay_open === false) {

		this._base_updateDatepicker(inst);

		// Reload the time control when changing something in the input text field.
		var tp_inst = this._get(inst, 'timepicker');
		if(tp_inst) tp_inst._addTimePicker(inst);
	}
};

//#######################################################################################
// third bad hack :/ override datepicker so it allows spaces and colon in the input field
//#######################################################################################
$.datepicker._base_doKeyPress = $.datepicker._doKeyPress;
$.datepicker._doKeyPress = function(event) {
	var inst = $.datepicker._getInst(event.target),
		tp_inst = $.datepicker._get(inst, 'timepicker');

	if (tp_inst) {
		if ($.datepicker._get(inst, 'constrainInput')) {
			var ampm = tp_inst._defaults.ampm,
				dateChars = $.datepicker._possibleChars($.datepicker._get(inst, 'dateFormat')),
				datetimeChars = tp_inst._defaults.timeFormat.toString()
								.replace(/[hms]/g, '')
								.replace(/TT/g, ampm ? 'APM' : '')
								.replace(/Tt/g, ampm ? 'AaPpMm' : '')
								.replace(/tT/g, ampm ? 'AaPpMm' : '')
								.replace(/T/g, ampm ? 'AP' : '')
								.replace(/tt/g, ampm ? 'apm' : '')
								.replace(/t/g, ampm ? 'ap' : '') +
								" " +
								tp_inst._defaults.separator +
								tp_inst._defaults.timeSuffix +
								(tp_inst._defaults.showTimezone ? tp_inst._defaults.timezoneList.join('') : '') +
								(tp_inst._defaults.amNames.join('')) +
								(tp_inst._defaults.pmNames.join('')) +
								dateChars,
				chr = String.fromCharCode(event.charCode === undefined ? event.keyCode : event.charCode);
			return event.ctrlKey || (chr < ' ' || !dateChars || datetimeChars.indexOf(chr) > -1);
		}
	}

	return $.datepicker._base_doKeyPress(event);
};

//#######################################################################################
// Override key up event to sync manual input changes.
//#######################################################################################
$.datepicker._base_doKeyUp = $.datepicker._doKeyUp;
$.datepicker._doKeyUp = function (event) {
	var inst = $.datepicker._getInst(event.target),
		tp_inst = $.datepicker._get(inst, 'timepicker');

	if (tp_inst) {
		if (tp_inst._defaults.timeOnly && (inst.input.val() != inst.lastVal)) {
			try {
				$.datepicker._updateDatepicker(inst);
			}
			catch (err) {
				$.datepicker.log(err);
			}
		}
	}

	return $.datepicker._base_doKeyUp(event);
};

//#######################################################################################
// override "Today" button to also grab the time.
//#######################################################################################
$.datepicker._base_gotoToday = $.datepicker._gotoToday;
$.datepicker._gotoToday = function(id) {
	var inst = this._getInst($(id)[0]),
		$dp = inst.dpDiv;
	this._base_gotoToday(id);
	var now = new Date();
	var tp_inst = this._get(inst, 'timepicker');
	if (tp_inst && tp_inst._defaults.showTimezone && tp_inst.timezone_select) {
		var tzoffset = now.getTimezoneOffset(); // If +0100, returns -60
		var tzsign = tzoffset > 0 ? '-' : '+';
		tzoffset = Math.abs(tzoffset);
		var tzmin = tzoffset % 60;
		tzoffset = tzsign + ('0' + (tzoffset - tzmin) / 60).slice(-2) + ('0' + tzmin).slice(-2);
		if (tp_inst._defaults.timezoneIso8609)
			tzoffset = tzoffset.substring(0, 3) + ':' + tzoffset.substring(3);
		tp_inst.timezone_select.val(tzoffset);
	}
	this._setTime(inst, now);
	$( '.ui-datepicker-today', $dp).click();
};

//#######################################################################################
// Disable & enable the Time in the datetimepicker
//#######################################################################################
$.datepicker._disableTimepickerDatepicker = function(target, date, withDate) {
	var inst = this._getInst(target),
	tp_inst = this._get(inst, 'timepicker');
	$(target).datepicker('getDate'); // Init selected[Year|Month|Day]
	if (tp_inst) {
		tp_inst._defaults.showTimepicker = false;
		tp_inst._updateDateTime(inst);
	}
};

$.datepicker._enableTimepickerDatepicker = function(target, date, withDate) {
	var inst = this._getInst(target),
	tp_inst = this._get(inst, 'timepicker');
	$(target).datepicker('getDate'); // Init selected[Year|Month|Day]
	if (tp_inst) {
		tp_inst._defaults.showTimepicker = true;
		tp_inst._addTimePicker(inst); // Could be disabled on page load
		tp_inst._updateDateTime(inst);
	}
};

//#######################################################################################
// Create our own set time function
//#######################################################################################
$.datepicker._setTime = function(inst, date) {
	var tp_inst = this._get(inst, 'timepicker');
	if (tp_inst) {
		var defaults = tp_inst._defaults,
			// calling _setTime with no date sets time to defaults
			hour = date ? date.getHours() : defaults.hour,
			minute = date ? date.getMinutes() : defaults.minute,
			second = date ? date.getSeconds() : defaults.second,
			millisec = date ? date.getMilliseconds() : defaults.millisec;

		//check if within min/max times..
		if ((hour < defaults.hourMin || hour > defaults.hourMax) || (minute < defaults.minuteMin || minute > defaults.minuteMax) || (second < defaults.secondMin || second > defaults.secondMax) || (millisec < defaults.millisecMin || millisec > defaults.millisecMax)) {
			hour = defaults.hourMin;
			minute = defaults.minuteMin;
			second = defaults.secondMin;
			millisec = defaults.millisecMin;
		}

		tp_inst.hour = hour;
		tp_inst.minute = minute;
		tp_inst.second = second;
		tp_inst.millisec = millisec;

		if (tp_inst.hour_slider) tp_inst.hour_slider.slider('value', hour);
		if (tp_inst.minute_slider) tp_inst.minute_slider.slider('value', minute);
		if (tp_inst.second_slider) tp_inst.second_slider.slider('value', second);
		if (tp_inst.millisec_slider) tp_inst.millisec_slider.slider('value', millisec);

		tp_inst._onTimeChange();
		tp_inst._updateDateTime(inst);
	}
};

//#######################################################################################
// Create new public method to set only time, callable as $().datepicker('setTime', date)
//#######################################################################################
$.datepicker._setTimeDatepicker = function(target, date, withDate) {
	var inst = this._getInst(target),
		tp_inst = this._get(inst, 'timepicker');

	if (tp_inst) {
		this._setDateFromField(inst);
		var tp_date;
		if (date) {
			if (typeof date == "string") {
				tp_inst._parseTime(date, withDate);
				tp_date = new Date();
				tp_date.setHours(tp_inst.hour, tp_inst.minute, tp_inst.second, tp_inst.millisec);
			}
			else tp_date = new Date(date.getTime());
			if (tp_date.toString() == 'Invalid Date') tp_date = undefined;
			this._setTime(inst, tp_date);
		}
	}

};

//#######################################################################################
// override setDate() to allow setting time too within Date object
//#######################################################################################
$.datepicker._base_setDateDatepicker = $.datepicker._setDateDatepicker;
$.datepicker._setDateDatepicker = function(target, date) {
	var inst = this._getInst(target),
	tp_date = (date instanceof Date) ? new Date(date.getTime()) : date;

	this._updateDatepicker(inst);
	this._base_setDateDatepicker.apply(this, arguments);
	this._setTimeDatepicker(target, tp_date, true);
};

//#######################################################################################
// override getDate() to allow getting time too within Date object
//#######################################################################################
$.datepicker._base_getDateDatepicker = $.datepicker._getDateDatepicker;
$.datepicker._getDateDatepicker = function(target, noDefault) {
	var inst = this._getInst(target),
		tp_inst = this._get(inst, 'timepicker');

	if (tp_inst) {
		this._setDateFromField(inst, noDefault);
		var date = this._getDate(inst);
		if (date && tp_inst._parseTime($(target).val(), tp_inst.timeOnly)) date.setHours(tp_inst.hour, tp_inst.minute, tp_inst.second, tp_inst.millisec);
		return date;
	}
	return this._base_getDateDatepicker(target, noDefault);
};

//#######################################################################################
// override parseDate() because UI 1.8.14 throws an error about "Extra characters"
// An option in datapicker to ignore extra format characters would be nicer.
//#######################################################################################
$.datepicker._base_parseDate = $.datepicker.parseDate;
$.datepicker.parseDate = function(format, value, settings) {
	var date;
	try {
		date = this._base_parseDate(format, value, settings);
	} catch (err) {
		if (err.indexOf(":") >= 0) {
			// Hack!  The error message ends with a colon, a space, and
			// the "extra" characters.  We rely on that instead of
			// attempting to perfectly reproduce the parsing algorithm.
			date = this._base_parseDate(format, value.substring(0,value.length-(err.length-err.indexOf(':')-2)), settings);
		} else {
			// The underlying error was not related to the time
			throw err;
		}
	}
	return date;
};

//#######################################################################################
// override formatDate to set date with time to the input
//#######################################################################################
$.datepicker._base_formatDate = $.datepicker._formatDate;
$.datepicker._formatDate = function(inst, day, month, year){
	var tp_inst = this._get(inst, 'timepicker');
	if(tp_inst) {
		tp_inst._updateDateTime(inst);
		return tp_inst.$input.val();
	}
	return this._base_formatDate(inst);
};

//#######################################################################################
// override options setter to add time to maxDate(Time) and minDate(Time). MaxDate
//#######################################################################################
$.datepicker._base_optionDatepicker = $.datepicker._optionDatepicker;
$.datepicker._optionDatepicker = function(target, name, value) {
	var inst = this._getInst(target),
		tp_inst = this._get(inst, 'timepicker');
	if (tp_inst) {
		var min = null, max = null, onselect = null;
		if (typeof name == 'string') { // if min/max was set with the string
			if (name === 'minDate' || name === 'minDateTime' )
				min = value;
			else if (name === 'maxDate' || name === 'maxDateTime')
				max = value;
			else if (name === 'onSelect')
				onselect = value;
		} else if (typeof name == 'object') { //if min/max was set with the JSON
			if (name.minDate)
				min = name.minDate;
			else if (name.minDateTime)
				min = name.minDateTime;
			else if (name.maxDate)
				max = name.maxDate;
			else if (name.maxDateTime)
				max = name.maxDateTime;
		}
		if(min) { //if min was set
			if (min == 0)
				min = new Date();
			else
				min = new Date(min);

			tp_inst._defaults.minDate = min;
			tp_inst._defaults.minDateTime = min;
		} else if (max) { //if max was set
			if(max==0)
				max=new Date();
			else
				max= new Date(max);
			tp_inst._defaults.maxDate = max;
			tp_inst._defaults.maxDateTime = max;
		} else if (onselect)
			tp_inst._defaults.onSelect = onselect;
	}
	if (value === undefined)
		return this._base_optionDatepicker(target, name);
	return this._base_optionDatepicker(target, name, value);
};

//#######################################################################################
// jQuery extend now ignores nulls!
//#######################################################################################
function extendRemove(target, props) {
	$.extend(target, props);
	for (var name in props)
		if (props[name] === null || props[name] === undefined)
			target[name] = props[name];
	return target;
};

$.timepicker = new Timepicker(); // singleton instance
$.timepicker.version = "1.0.0";

})(jQuery);
/*
 * jQuery Easing v1.3 - http://gsgd.co.uk/sandbox/jquery/easing/
 *
 * Uses the built in easing capabilities added In jQuery 1.1
 * to offer multiple easing options
 *
 * TERMS OF USE - jQuery Easing
 * 
 * Open source under the BSD License. 
 * 
 * Copyright © 2008 George McGinley Smith
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, 
 * are permitted provided that the following conditions are met:
 * 
 * Redistributions of source code must retain the above copyright notice, this list of 
 * conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list 
 * of conditions and the following disclaimer in the documentation and/or other materials 
 * provided with the distribution.
 * 
 * Neither the name of the author nor the names of contributors may be used to endorse 
 * or promote products derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY 
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *  COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 *  EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE
 *  GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED 
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 *  NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED 
 * OF THE POSSIBILITY OF SUCH DAMAGE. 
 *
*/

// t: current time, b: begInnIng value, c: change In value, d: duration
jQuery.easing['jswing'] = jQuery.easing['swing'];

jQuery.extend( jQuery.easing,
{
	def: 'easeOutQuad',
	swing: function (x, t, b, c, d) {
		//alert(jQuery.easing.default);
		return jQuery.easing[jQuery.easing.def](x, t, b, c, d);
	},
	easeInQuad: function (x, t, b, c, d) {
		return c*(t/=d)*t + b;
	},
	easeOutQuad: function (x, t, b, c, d) {
		return -c *(t/=d)*(t-2) + b;
	},
	easeInOutQuad: function (x, t, b, c, d) {
		if ((t/=d/2) < 1) return c/2*t*t + b;
		return -c/2 * ((--t)*(t-2) - 1) + b;
	},
	easeInCubic: function (x, t, b, c, d) {
		return c*(t/=d)*t*t + b;
	},
	easeOutCubic: function (x, t, b, c, d) {
		return c*((t=t/d-1)*t*t + 1) + b;
	},
	easeInOutCubic: function (x, t, b, c, d) {
		if ((t/=d/2) < 1) return c/2*t*t*t + b;
		return c/2*((t-=2)*t*t + 2) + b;
	},
	easeInQuart: function (x, t, b, c, d) {
		return c*(t/=d)*t*t*t + b;
	},
	easeOutQuart: function (x, t, b, c, d) {
		return -c * ((t=t/d-1)*t*t*t - 1) + b;
	},
	easeInOutQuart: function (x, t, b, c, d) {
		if ((t/=d/2) < 1) return c/2*t*t*t*t + b;
		return -c/2 * ((t-=2)*t*t*t - 2) + b;
	},
	easeInQuint: function (x, t, b, c, d) {
		return c*(t/=d)*t*t*t*t + b;
	},
	easeOutQuint: function (x, t, b, c, d) {
		return c*((t=t/d-1)*t*t*t*t + 1) + b;
	},
	easeInOutQuint: function (x, t, b, c, d) {
		if ((t/=d/2) < 1) return c/2*t*t*t*t*t + b;
		return c/2*((t-=2)*t*t*t*t + 2) + b;
	},
	easeInSine: function (x, t, b, c, d) {
		return -c * Math.cos(t/d * (Math.PI/2)) + c + b;
	},
	easeOutSine: function (x, t, b, c, d) {
		return c * Math.sin(t/d * (Math.PI/2)) + b;
	},
	easeInOutSine: function (x, t, b, c, d) {
		return -c/2 * (Math.cos(Math.PI*t/d) - 1) + b;
	},
	easeInExpo: function (x, t, b, c, d) {
		return (t==0) ? b : c * Math.pow(2, 10 * (t/d - 1)) + b;
	},
	easeOutExpo: function (x, t, b, c, d) {
		return (t==d) ? b+c : c * (-Math.pow(2, -10 * t/d) + 1) + b;
	},
	easeInOutExpo: function (x, t, b, c, d) {
		if (t==0) return b;
		if (t==d) return b+c;
		if ((t/=d/2) < 1) return c/2 * Math.pow(2, 10 * (t - 1)) + b;
		return c/2 * (-Math.pow(2, -10 * --t) + 2) + b;
	},
	easeInCirc: function (x, t, b, c, d) {
		return -c * (Math.sqrt(1 - (t/=d)*t) - 1) + b;
	},
	easeOutCirc: function (x, t, b, c, d) {
		return c * Math.sqrt(1 - (t=t/d-1)*t) + b;
	},
	easeInOutCirc: function (x, t, b, c, d) {
		if ((t/=d/2) < 1) return -c/2 * (Math.sqrt(1 - t*t) - 1) + b;
		return c/2 * (Math.sqrt(1 - (t-=2)*t) + 1) + b;
	},
	easeInElastic: function (x, t, b, c, d) {
		var s=1.70158;var p=0;var a=c;
		if (t==0) return b;  if ((t/=d)==1) return b+c;  if (!p) p=d*.3;
		if (a < Math.abs(c)) { a=c; var s=p/4; }
		else var s = p/(2*Math.PI) * Math.asin (c/a);
		return -(a*Math.pow(2,10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )) + b;
	},
	easeOutElastic: function (x, t, b, c, d) {
		var s=1.70158;var p=0;var a=c;
		if (t==0) return b;  if ((t/=d)==1) return b+c;  if (!p) p=d*.3;
		if (a < Math.abs(c)) { a=c; var s=p/4; }
		else var s = p/(2*Math.PI) * Math.asin (c/a);
		return a*Math.pow(2,-10*t) * Math.sin( (t*d-s)*(2*Math.PI)/p ) + c + b;
	},
	easeInOutElastic: function (x, t, b, c, d) {
		var s=1.70158;var p=0;var a=c;
		if (t==0) return b;  if ((t/=d/2)==2) return b+c;  if (!p) p=d*(.3*1.5);
		if (a < Math.abs(c)) { a=c; var s=p/4; }
		else var s = p/(2*Math.PI) * Math.asin (c/a);
		if (t < 1) return -.5*(a*Math.pow(2,10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )) + b;
		return a*Math.pow(2,-10*(t-=1)) * Math.sin( (t*d-s)*(2*Math.PI)/p )*.5 + c + b;
	},
	easeInBack: function (x, t, b, c, d, s) {
		if (s == undefined) s = 1.70158;
		return c*(t/=d)*t*((s+1)*t - s) + b;
	},
	easeOutBack: function (x, t, b, c, d, s) {
		if (s == undefined) s = 1.70158;
		return c*((t=t/d-1)*t*((s+1)*t + s) + 1) + b;
	},
	easeInOutBack: function (x, t, b, c, d, s) {
		if (s == undefined) s = 1.70158; 
		if ((t/=d/2) < 1) return c/2*(t*t*(((s*=(1.525))+1)*t - s)) + b;
		return c/2*((t-=2)*t*(((s*=(1.525))+1)*t + s) + 2) + b;
	},
	easeInBounce: function (x, t, b, c, d) {
		return c - jQuery.easing.easeOutBounce (x, d-t, 0, c, d) + b;
	},
	easeOutBounce: function (x, t, b, c, d) {
		if ((t/=d) < (1/2.75)) {
			return c*(7.5625*t*t) + b;
		} else if (t < (2/2.75)) {
			return c*(7.5625*(t-=(1.5/2.75))*t + .75) + b;
		} else if (t < (2.5/2.75)) {
			return c*(7.5625*(t-=(2.25/2.75))*t + .9375) + b;
		} else {
			return c*(7.5625*(t-=(2.625/2.75))*t + .984375) + b;
		}
	},
	easeInOutBounce: function (x, t, b, c, d) {
		if (t < d/2) return jQuery.easing.easeInBounce (x, t*2, 0, c, d) * .5 + b;
		return jQuery.easing.easeOutBounce (x, t*2-d, 0, c, d) * .5 + c*.5 + b;
	}
});

/*
 *
 * TERMS OF USE - EASING EQUATIONS
 * 
 * Open source under the BSD License. 
 * 
 * Copyright © 2001 Robert Penner
 * All rights reserved.
 * 
 * Redistribution and use in source and binary forms, with or without modification, 
 * are permitted provided that the following conditions are met:
 * 
 * Redistributions of source code must retain the above copyright notice, this list of 
 * conditions and the following disclaimer.
 * Redistributions in binary form must reproduce the above copyright notice, this list 
 * of conditions and the following disclaimer in the documentation and/or other materials 
 * provided with the distribution.
 * 
 * Neither the name of the author nor the names of contributors may be used to endorse 
 * or promote products derived from this software without specific prior written permission.
 * 
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY 
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 *  COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL,
 *  EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE
 *  GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED 
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING
 *  NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED 
 * OF THE POSSIBILITY OF SUCH DAMAGE. 
 *
 *//* malihu custom scrollbar plugin - http://manos.malihu.gr */
(function($) {
  function mCustomScrollbar(el, config) {
    this.config = config = $.extend({
      scrollType : "vertical",
      animSpeed : 900,
      easeType : "easeOutCirc",
      bottomSpace : 1.05,
      draggerDimType : "manual",
      mouseWheelSupport : "yes",
      scrollBtnsSupport : "yes", 
      scrollBtnsSpeed : 0,
      upDownBtnsSupport : false,
      upDownBtnsSize : 14
    }, config
        || {})
    //var scrollType, animSpeed, easeType, bottomSpace, draggerDimType, mouseWheelSupport, scrollBtnsSupport, scrollBtnsSpeed;

    var $this = $(el).addClass('scroll-container'), me = this;
    var $customScrollBox = $this.find(".scroll-box");
    var oriHeight = $this.height();
    if ($customScrollBox.length == 0) {
      //temporary content
      var content = $this.html();
      var tpl =
          '<div class="scroll-box">'
              + '  <div class="container">'
              + '    <div class="content">'
              + content
              + '    </div>'
              + '  </div>'
              + '  <div class="dragger_container">'
              + '    <div class="dragger"></div>'
              + '  </div>'
              + '</div>'
              + (config.upDownBtnsSupport ? '<a href="#" class="scrollUpBtn" style="display: inline-block; ">up</a>'
                  : '')
              + (config.upDownBtnsSupport ? '<a href="#" class="scrollDownBtn" style="display: inline-block; ">down</a>'
                  : '');
      $this.empty().append(tpl);
      $customScrollBox = $this.find(".scroll-box");
    }
    $this.trigger('resize');
    var $customScrollBox_container = $customScrollBox.find(".container");
    var $customScrollBox_content = $customScrollBox.find(".content");
    //console.log($customScrollBox_content.height());
    var $dragger_container = $this.find(".dragger_container");
    var $dragger = $dragger_container.find(".dragger");
    var $scrollUpBtn = $this.find(".scrollUpBtn");
    var $scrollDownBtn = $this.find(".scrollDownBtn");
    var $customScrollBox_horWrapper = $customScrollBox.find(".horWrapper");
    //Resize content based on parent
    $customScrollBox_container.width($this.width()
        - ($dragger.width() + 20));
    $dragger_container.height(oriHeight
        - (config.upDownBtnsSupport ? (config.upDownBtnsSize * 2)+6 : 0));
    
    $dragger_container.css({
      top : config.upDownBtnsSupport ? config.upDownBtnsSize+3:0
    });
    if (config.upDownBtnsSupport) {
      $this.find('.scrollUpBtn,.scrollDownBtn').css({
        width: config.upDownBtnsSize+'px',
        height: config.upDownBtnsSize+'px',
      });
      $this.find('.scrollUpBtn').css({
        top:'0px',
        position:'absolute'
      });
      $this.find('.scrollDownBtn').css({
        bottom:'0px',
        position:'absolute'
      });
     
    }
    //get & store minimum dragger height & width (defined in css)
    if (!$customScrollBox.data("minDraggerHeight")) {
      $customScrollBox.data("minDraggerHeight", $dragger.height());
    }
    if (!$customScrollBox.data("minDraggerWidth")) {
      $customScrollBox.data("minDraggerWidth", $dragger.width());
    }

    //get & store original content height & width
    if (!$customScrollBox.data("contentHeight")) {
      $customScrollBox.data("contentHeight", $customScrollBox_container
          .height());
    }
    if (!$customScrollBox.data("contentWidth")) {
      $customScrollBox.data("contentWidth", $customScrollBox_container.width());
    }

    CustomScroller();

    function CustomScroller(reloadType) {
      //horizontal scrolling ------------------------------
      if (me.config.scrollType == "horizontal") {
        var visibleWidth = $customScrollBox.width();
        //set content width automatically
        $customScrollBox_horWrapper.css("width", 999999); //set a rediculously high width value ;)
        $customScrollBox.data("totalContent", $customScrollBox_container
            .width()); //get inline div width
        $customScrollBox_horWrapper.css("width", $customScrollBox
            .data("totalContent")); //set back the proper content width value

        if ($customScrollBox_container.width() > visibleWidth) { //enable scrollbar if content is long
          //$dragger.css("display","block");
          if (reloadType != "resize"
              && $customScrollBox_container.width() != $customScrollBox
                  .data("contentWidth")) {
            $dragger.css("left", 0);
            $customScrollBox_container.css("left", 0);
            $customScrollBox.data("contentWidth", $customScrollBox_container
                .width());
          }
          $dragger_container.css("display", "block");
          $scrollDownBtn.css("display", "inline-block");
          $scrollUpBtn.css("display", "inline-block");
          var totalContent = $customScrollBox_content.width();
          var minDraggerWidth = $customScrollBox.data("minDraggerWidth");
          var draggerContainerWidth = $dragger_container.width();

          function AdjustDraggerWidth() {
            if (me.config.draggerDimType == "auto") {
              var adjDraggerWidth = Math.round(totalContent
                  - ((totalContent - visibleWidth) * 1.3)); //adjust dragger width analogous to content
              if (adjDraggerWidth <= me.config.minDraggerWidth) { //minimum dragger width
                $dragger.css("width", me.config.minDraggerWidth
                    + "px");
              } else if (adjDraggerWidth >= me.config.draggerContainerWidth) {
                $dragger.css("width", me.config.draggerContainerWidth
                    - 10 + "px");
              } else {
                $dragger.css("width", adjDraggerWidth
                    + "px");
              }
            }
          }
          AdjustDraggerWidth();

          var targX = 0;
          var draggerWidth = $dragger.width();
          $dragger.draggable({
            axis : "x",
            containment : "parent",
            drag : function(event, ui) {
              ScrollX();
            },
            stop : function(event, ui) {
              DraggerRelease();
            }
          });

          $dragger_container.click(function(e) {
            var $this = $(this);
            var mouseCoord = (e.pageX - $this.offset().left);
            if (mouseCoord < $dragger.position().left
                || mouseCoord > ($dragger.position().left + $dragger.width())) {
              var targetPos = mouseCoord
                  + $dragger.width();
              if (targetPos < $dragger_container.width()) {
                $dragger.css("left", mouseCoord);
                ScrollX();
              } else {
                $dragger.css("left", $dragger_container.width()
                    - $dragger.width());
                ScrollX();
              }
            }
          });

          //mousewheel
          $(function($) {
            if (me.config.mouseWheelSupport == "yes") {
              $customScrollBox.unbind("mousewheel");
              $customScrollBox.bind("mousewheel", function(event, delta) {
                var vel = Math.abs(delta * 10);
                $dragger.css("left", $dragger.position().left
                    - (delta * vel));
                ScrollX();
                if ($dragger.position().left < 0) {
                  $dragger.css("left", 0);
                  $customScrollBox_container.stop();
                  ScrollX();
                }
                if ($dragger.position().left > $dragger_container.width()
                    - $dragger.width()) {
                  $dragger.css("left", $dragger_container.width()
                      - $dragger.width());
                  $customScrollBox_container.stop();
                  ScrollX();
                }
                return false;
              });
            }
          });

          //scroll buttons
          if (me.config.scrollBtnsSupport == "yes") {
            $scrollDownBtn.mouseup(function() {
              BtnsScrollXStop();
            }).mousedown(function() {
              BtnsScrollX("down");
            }).mouseout(function() {
              BtnsScrollXStop();
            });

            $scrollUpBtn.mouseup(function() {
              BtnsScrollXStop();
            }).mousedown(function() {
              BtnsScrollX("up");
            }).mouseout(function() {
              BtnsScrollXStop();
            });

            $scrollDownBtn.click(function(e) {
              e.preventDefault();
            });
            $scrollUpBtn.click(function(e) {
              e.preventDefault();
            });

            btnsScrollTimerX = 0;

            function BtnsScrollX(dir) {
              if (dir == "down") {
                var btnsScrollTo = $dragger_container.width()
                    - $dragger.width();
                var scrollSpeed = Math.abs($dragger.position().left
                    - btnsScrollTo)
                    * (100 / me.config.scrollBtnsSpeed);
                $dragger.stop().animate({
                  left : btnsScrollTo
                }, scrollSpeed, "linear");
              } else {
                var btnsScrollTo = 0;
                var scrollSpeed = Math.abs($dragger.position().left
                    - btnsScrollTo)
                    * (100 / me.config.scrollBtnsSpeed);
                $dragger.stop().animate({
                  left : -btnsScrollTo
                }, scrollSpeed, "linear");
              }
              clearInterval(btnsScrollTimerX);
              btnsScrollTimerX = setInterval(ScrollX, 20);
            }

            function BtnsScrollXStop() {
              clearInterval(btnsScrollTimerX);
              $dragger.stop();
            }
          }

          //scroll
          var scrollAmount = (totalContent - visibleWidth)
              / (draggerContainerWidth - draggerWidth);
          function ScrollX() {
            var draggerX = $dragger.position().left;
            var targX = -draggerX
                * scrollAmount;
            var thePos = $customScrollBox_container.position().left
                - targX;
            $customScrollBox_container.stop().animate({
              left : "-="
                  + thePos
            }, me.config.animSpeed, me.config.easeType);
          }
        } else { //disable scrollbar if content is short
          $dragger.css("left", 0).css("display", "none"); //reset content scroll
          $customScrollBox_container.css("left", 0);
          $dragger_container.css("display", "none");
          $scrollDownBtn.css("display", "none");
          $scrollUpBtn.css("display", "none");
        }
        //vertical scrolling ------------------------------
      } else {
        var visibleHeight = $customScrollBox.height();
        if ($customScrollBox_container.height() > visibleHeight) { //enable scrollbar if content is long
          //$dragger.css("display","block");
          if (reloadType != "resize"
              && $customScrollBox_container.height() != $customScrollBox
                  .data("contentHeight")) {
            $dragger.css("top", 0);
            $customScrollBox_container.css("top", 0);
            $customScrollBox.data("contentHeight", $customScrollBox_container
                .height());
          }
          $dragger_container.css("display", "block");
          $scrollDownBtn.css("display", "inline-block");
          $scrollUpBtn.css("display", "inline-block");
          var totalContent = $customScrollBox_content.height();
          var minDraggerHeight = $customScrollBox.data("minDraggerHeight");
          var draggerContainerHeight = $dragger_container.height();

          function AdjustDraggerHeight() {
            if (me.config.draggerDimType == "auto") {
              var adjDraggerHeight = Math.round(totalContent
                  - ((totalContent - visibleHeight) * 1.3)); //adjust dragger height analogous to content
              if (adjDraggerHeight <= minDraggerHeight) { //minimum dragger height
                $dragger.css("height", minDraggerHeight
                    + "px").css("line-height", minDraggerHeight
                    + "px");
              } else if (adjDraggerHeight >= draggerContainerHeight) {
                $dragger.css("height", draggerContainerHeight
                    - 10 + "px").css("line-height", draggerContainerHeight
                    - 10 + "px");
              } else {
                $dragger.css("height", adjDraggerHeight
                    + "px").css("line-height", adjDraggerHeight
                    + "px");
              }
            }
          }
          AdjustDraggerHeight();

          var targY = 0;
          var draggerHeight = $dragger.height();
          $dragger.draggable({
            axis : "y",
            containment : "parent",
            drag : function(event, ui) {
              Scroll();
            },
            stop : function(event, ui) {
              DraggerRelease();
            }
          });

          $dragger_container.click(function(e) {
            var $this = $(this);
            var mouseCoord = (e.pageY - $this.offset().top);
            if (mouseCoord < $dragger.position().top
                || mouseCoord > ($dragger.position().top + $dragger.height())) {
              var targetPos = mouseCoord
                  + $dragger.height();
              if (targetPos < $dragger_container.height()) {
                $dragger.css("top", mouseCoord);
                Scroll();
              } else {
                $dragger.css("top", $dragger_container.height()
                    - $dragger.height());
                Scroll();
              }
            }
          });

          //mousewheel
          $(function($) {
            if (me.config.mouseWheelSupport == "yes") {
              $customScrollBox.unbind("mousewheel");
              $customScrollBox.bind("mousewheel", function(event, delta) {
                var vel = Math.abs(delta * 10);
                $dragger.css("top", $dragger.position().top
                    - (delta * vel));
                Scroll();
                if ($dragger.position().top < 0) {
                  $dragger.css("top", 0);
                  $customScrollBox_container.stop();
                  Scroll();
                }
                if ($dragger.position().top > $dragger_container.height()
                    - $dragger.height()) {
                  $dragger.css("top", $dragger_container.height()
                      - $dragger.height());
                  $customScrollBox_container.stop();
                  Scroll();
                }
                return false;
              });
            }
          });

          //scroll buttons
          if (me.config.scrollBtnsSupport == "yes") {
            $scrollDownBtn.mouseup(function() {
              BtnsScrollStop();
            }).mousedown(function() {
              BtnsScroll("down");
            }).mouseout(function() {
              BtnsScrollStop();
            });

            $scrollUpBtn.mouseup(function() {
              BtnsScrollStop();
            }).mousedown(function() {
              BtnsScroll("up");
            }).mouseout(function() {
              BtnsScrollStop();
            });

            $scrollDownBtn.click(function(e) {
              e.preventDefault();
            });
            $scrollUpBtn.click(function(e) {
              e.preventDefault();
            });

            btnsScrollTimer = 0;

            function BtnsScroll(dir) {
              if (dir == "down") {
                var btnsScrollTo = $dragger_container.height()
                    - $dragger.height();
                var scrollSpeed = Math.abs($dragger.position().top
                    - btnsScrollTo)
                    * (100 / me.config.scrollBtnsSpeed);
                $dragger.stop().animate({
                  top : btnsScrollTo
                }, scrollSpeed, "linear");
              } else {
                var btnsScrollTo = 0;
                var scrollSpeed = Math.abs($dragger.position().top
                    - btnsScrollTo)
                    * (100 / me.config.scrollBtnsSpeed);
                $dragger.stop().animate({
                  top : -btnsScrollTo
                }, scrollSpeed, "linear");
              }
              clearInterval(btnsScrollTimer);
              btnsScrollTimer = setInterval(Scroll, 20);
            }

            function BtnsScrollStop() {
              clearInterval(btnsScrollTimer);
              $dragger.stop();
            }
          }

          //scroll
          if (me.config.bottomSpace < 1) {
            me.config.bottomSpace = 1; //minimum bottomSpace value is 1
          }
          var scrollAmount =
              (totalContent - (visibleHeight / me.config.bottomSpace))
                  / (draggerContainerHeight - draggerHeight);
          function Scroll() {
            var draggerY = $dragger.position().top;
            var targY = -draggerY
                * scrollAmount;
            var thePos = $customScrollBox_container.position().top
                - targY;
            $customScrollBox_container.stop().animate({
              top : "-="
                  + thePos
            }, me.config.animSpeed, me.config.easeType);
          }
        } else { //disable scrollbar if content is short
          $dragger.css("top", 0).css("display", "none"); //reset content scroll
          $customScrollBox_container.css("top", 0);
          $dragger_container.css("display", "none");
          $scrollDownBtn.css("display", "none");
          $scrollUpBtn.css("display", "none");
        }
      }

      $dragger.mouseup(function() {
        DraggerRelease();
      }).mousedown(function() {
        DraggerPress();
      });

      function DraggerPress() {
        $dragger.addClass("dragger_pressed");
      }

      function DraggerRelease() {
        $dragger.removeClass("dragger_pressed");
      }
    }

    $(window).resize(function() {
      if (me.config.scrollType == "horizontal") {
        if ($dragger.position().left > $dragger_container.width()
            - $dragger.width()) {
          $dragger.css("left", $dragger_container.width()
              - $dragger.width());
        }
      } else {
        if ($dragger.position().top > $dragger_container.height()
            - $dragger.height()) {
          $dragger.css("top", $dragger_container.height()
              - $dragger.height());
        }
      }
      CustomScroller("resize");
    });
  }
  $.fx.prototype.cur = function() {
    if (this.elem[this.prop] != null
        && (!this.elem.style || this.elem.style[this.prop] == null)) {
      return this.elem[this.prop];
    }
    var r = parseFloat(jQuery.css(this.elem, this.prop));
    return typeof r == 'undefined' ? 0 : r;
  }
  $.fn.mCustomScrollbar =
      function(option) {
        return this.each(function() {
          var $this = $(this), data = $this.data('mCustomScrollbar'), options =
              typeof option == 'object'
                  && option
          if (!data)
            $this.data('mCustomScrollbar', (data =
                new mCustomScrollbar(this, options)))
          if (typeof option == 'string')
            data[option](arg);
          return $this.data('dockable');
        });
      }
})(jQuery);/*! Copyright (c) 2011 Brandon Aaron (http://brandonaaron.net)
 * Licensed under the MIT License (LICENSE.txt).
 *
 * Thanks to: http://adomas.org/javascript-mouse-wheel/ for some pointers.
 * Thanks to: Mathias Bank(http://www.mathias-bank.de) for a scope bug fix.
 * Thanks to: Seamus Leahy for adding deltaX and deltaY
 *
 * Version: 3.0.6
 * 
 * Requires: 1.2.2+
 */
(function(a){function d(b){var c=b||window.event,d=[].slice.call(arguments,1),e=0,f=!0,g=0,h=0;return b=a.event.fix(c),b.type="mousewheel",c.wheelDelta&&(e=c.wheelDelta/120),c.detail&&(e=-c.detail/3),h=e,c.axis!==undefined&&c.axis===c.HORIZONTAL_AXIS&&(h=0,g=-1*e),c.wheelDeltaY!==undefined&&(h=c.wheelDeltaY/120),c.wheelDeltaX!==undefined&&(g=-1*c.wheelDeltaX/120),d.unshift(b,e,g,h),(a.event.dispatch||a.event.handle).apply(this,d)}var b=["DOMMouseScroll","mousewheel"];if(a.event.fixHooks)for(var c=b.length;c;)a.event.fixHooks[b[--c]]=a.event.mouseHooks;a.event.special.mousewheel={setup:function(){if(this.addEventListener)for(var a=b.length;a;)this.addEventListener(b[--a],d,!1);else this.onmousewheel=d},teardown:function(){if(this.removeEventListener)for(var a=b.length;a;)this.removeEventListener(b[--a],d,!1);else this.onmousewheel=null}},a.fn.extend({mousewheel:function(a){return a?this.bind("mousewheel",a):this.trigger("mousewheel")},unmousewheel:function(a){return this.unbind("mousewheel",a)}})})(jQuery);
(function($){
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
  $._gridInstances = {};
  function Pagination(table, configs) {
    this.$table = table;
    var tpl =
        '<td  class="btn-group">'
            + '<a class="btn" data-grid-action="refresh"><i class="icon icon-refresh"></i></a>'
            +  '</td>';

    tpl += '<td  class="btn-group navigation">';
    tpl +=
        '<a data-grid-action="pagination" data-grid-param="prev" class="btn prev">Prev</a>';
    for ( var i = 0; i < Math.round(configs.rowCount
        / configs.rowPerPage); i++) {
      tpl +=
          '<a data-grid-action="pagination" data-grid-param="'
              + i + '" class="btn" href="javascript:void(0)">'
              + (i + 1).toString() + '</a>';
    }
    tpl +=
        '<a data-grid-action="pagination" data-grid-param="next" class="btn next" href="javascript:void(0)">Next</a>';
    tpl += '</td>';

    var me = this;
    this.$el = table.$foot.append(tpl);
    this.$el.find('[data-grid-action]').click(function(e) {
      e.preventDefault();
      var $dg = $(this).attr('data-grid-action');
      if ($(this).hasClass('disabled')) {
        return;
      }
      switch ($dg) {
        case 'refresh':
          me.reload();
          break;
        case 'pagination':
          switch ($(this).attr('data-grid-param')) {
            case 'prev':
              me.prev();
              break;
            case 'next':
              me.next();
              break;
            default:
              me.gotoPage($(this).attr('data-grid-param'));
              break;
          }
          break;
      }
    });
    this.update();
  }

  Pagination.prototype = {
    reload : function() {
      this.$table.loadPage(this.$table.configs.pagination.currentPage);
    },
    next : function() {
      this.$table.loadPage(this.$table.configs.pagination.currentPage++);
    },
    prev : function() {
      this.$table.loadPage(this.$table.configs.pagination.currentPage--);
    },
    update : function() {
      var pcount = Math.round(this.$table.configs.rowCount
          / this.$table.configs.rowPerPage);
      console.log(pcount);
      if (this.$table.configs.pagination.currentPage <= 0) {
        this.$el.find('.prev').addClass('disabled');
      } else {
        this.$el.find('.prev').removeClass('disabled');
      }
      if (pcount === 0) {
        this.$el.find('.next').addClass('disabled');
      }
    },
    gotoPage : function(page) {
      this.$table.loadPage(page);
    },
    setVisible : function(visible) {
      if (visible) {
        this.$el.show();
      } else {
        this.$el.hide();
      }
    }
  }
  function Grid(el, configs) {
    this.init(el, configs);
    return this;
  }
  Grid.prototype =
      {
        defaults : {
          sort : {
            field : null,
            order : null
          },
          dataUrl : null,
          pagination : {
            currentPage : 0
          },
          renderTo : 'body'
        },
        getId : function() {
          return this.$el.attr('id');
        },
        init : function(el, configs) {
          var me = this;
          this.configs = $.extend(this.defaults, configs
              || {});
          var tpl =
              '<table class="table table-bordered grid"><thead></thead><tbody></tbody><tfoot>'
                  + '<tr><td class="grid-action"colspan="'
                  + this.configs.columns.length
                  + '"><table><tr></tr></table></td></tr>' + '</tfoot></table>';
          this.$el = $(tpl).appendTo(el);

          this.$head = this.$el.find('>thead');
          tpl = '<tr>';
          for ( var j = 0; c = this.configs.columns[j]; j++) {
            tpl +=
                '<th data-sortable="true" data-field="'
                    + c.field
                    + '">'
                    + c.title
                    + '<span class="sort-icon"><span class="up"></span><span class="down"></span></span></th>';
          }
          tpl += '</tr>';
          $(tpl).appendTo(this.$head);
          this.$head.find('[data-sortable="true"]').click(function(e) {
            e.preventDefault();
            var self=$(this);
            me.configs.sort.field = $(this).attr('data-field');
            me.configs.sort.order =  me.configs.sort.order ==='asc' ? 'desc' :'asc';
            me.loadPage(null,function() {
              var si = self.find('.sort-icon');
              if (this.configs.sort.order=='desc') {
                si.find('down').addClass('disabled');
                si.find('up').removeClass('disabled');
              }else{
                si.find('down').removeClass('disabled');
                si.find('up').addClass('disabled');
              }
            });
          });
          this.$body = this.$el.find('>tbody');
          this.$foot = this.$el.find('>tfoot table tr');
          this.reloadPagination();
          this.loadPage(this.configs.pagination.currentPage);
        },
        reloadPagination : function() {
          this.pagination = new Pagination(this, this.configs);
          this.pagination.setVisible(this.configs.pagination.visible);
        },
        bindRows : function(data) {
          this.$body.empty();
          for ( var i = 0; item = data[i]; i++) {
            var tpl = '<tr>';
            for ( var j = 0; c = this.configs.columns[j]; j++) {
              tpl += '<td>';
              if (typeof (item[c.field]) !== 'undefined') {
                  tpl += item[c.field];
              } else {
                tpl += '&nbsp;';
              }
              tpl += '</td>';
            }
            tpl += '</tr>';
            $(tpl).appendTo(this.$body);
          }

        },
        loadPage : function(page,cb) {
          this.$body.addClass('loading');
          var me = this;
          this.configs.pagination.currentPage = page
              || this.configs.pagination.currentPage;
          cgaf.getJSON(this.configs.dataUrl, {
            _cp : page,
            _rpp : this.configs.rowPerPage,
            _sidx : this.configs.sort.field || '',
            _sord : this.configs.sort.order || 'asc'
          }, function(data) {
            me.bindRows(data);
            if (cb) {
              cb.call(me);
            }
            me.$body.removeClass('loading');
          });
        }
      }
  $.fn.grid =
      function(option) {
        return this.each(function() {
          var $this = $(this), data = $this.data('grid'), options =
              typeof option == 'object'
                  && option
          if (!data) {
            $this.data('grid', (data = new Grid(this, options)))
            $._gridInstances[$this.attr('id')] = data;
          }
          if (typeof option == 'string')
            data[option](arg);

          return $this.data('grid');
        });
      }
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
})(jQuery);(function() {
	$.fn.scrollbar = function(options) {
		options = $.extend({
			scrollHeight : 150
		}, options || {});
		return this.each(function() {
			var $$ = $(this);
			var content = $$.children(0).css({
				position : "relative"
			});
			if (!$$.data("scrollbar")) {
				var sc = $$.find(".scrollbar");
				if (sc.length === 0) {
					sc = $('<div class="scrollbar"></div>').prependTo($$);
					s = $("<div></div>").appendTo(sc);
					sc.css({
						padding : 0,
						margin : 0,
						backgroundColor : "rgba(118, 118, 118, 1)",
						cssFloat : "right",
						width : "6px"
					});
					s.css({
						height : 25 + "px",
						position : "relative",
						border : "1px solid rgba(118, 118, 118, 1)",
						borderRadius : "2px",
						backgroundColor : "red",
						cursor : "pointer"
					});
					s.draggable({
						containment : "parent",
						axis : "y",
						start : function() {
						},
						drag : function(event, ui) {
							recalculate(ui.position.top);
						},
						stop : function(event, ui) {
							recalculate(ui.position.top);
						}
					});
					s.bind("mousewheel", function() {
						recalculate();
					});
				}
				function recalculate(top) {
					sc.css({
						height : $$.height() + "px"
					});
					content.css({
						top : top * -1
					});
				}

				recalculate();
				this.recalculate = recalculate;
				$$.data("scrollbar", this);
			} else
				$$.data("scrollbar").recarculate();
			return this;
		});
	};
})(jQuery);/* ========================================================
 * bootstrap-tab.js v2.0.3
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


!function ($) {

  "use strict"; // jshint ;_;


 /* TAB CLASS DEFINITION
  * ==================== */

  var Tab = function ( element ) {
    this.element = $(element)
  }

  Tab.prototype = {

    constructor: Tab

  , show: function () {
      var $this = this.element
        , $ul = $this.closest('ul:not(.dropdown-menu)')
        , selector = $this.attr('data-target')
        , previous
        , $target
        , e

      if (!selector) {
        selector = $this.attr('href')
        selector = selector && selector.replace(/.*(?=#[^\s]*$)/, '') //strip for ie7
      }

      if ($this.closest('li').hasClass('active')) return

      previous = $ul.find('.active a').last()[0]

      e = $.Event('show', {
        relatedTarget: previous
      })

      $this.trigger(e)

      if (e.isDefaultPrevented()) return

      $target = $(selector)

      this.activate($this.closest('li'), $ul)
      this.activate($target, $target.parent(), function () {
        $this.trigger({
          type: 'shown'
        , relatedTarget: previous
        })
      })

      var url = $this.attr('data-url');
      if (url) {
        if (!$this.attr('data-loaded')) {
          $target.addClass('loading');
          $.ajax({
            url : url
          , success : function(data) {
              $this.attr('data-loaded', true);
              $target.html(data);
              $target.removeClass('loading');
            }
          });
         }
       }
    }
  , activate: function ( element, container, callback) {
      var $active = container.find('> .active')
        , transition = callback
            && $.support.transition
            && $active.hasClass('fade')

      function next() {
        $active
          .removeClass('active')
          .find('> .dropdown-menu > .active')
          .removeClass('active')

        element.addClass('active')

        if (transition) {
          element[0].offsetWidth // reflow for transition
          element.addClass('in')
        } else {
          element.removeClass('fade')
        }

        if ( element.parent('.dropdown-menu') ) {
          element.closest('li.dropdown').addClass('active')
        }

        callback && callback()
      }

      transition ?
        $active.one($.support.transition.end, next) :
        next()

      $active.removeClass('in')
    }
  }


 /* TAB PLUGIN DEFINITION
  * ===================== */

  $.fn.tab = function ( option ) {
    return this.each(function () {
      var $this = $(this)
        , data = $this.data('tab')
      if (!data) $this.data('tab', (data = new Tab(this)))
      if (typeof option == 'string') data[option]()
    })
  }

  $.fn.tab.Constructor = Tab


 /* TAB DATA-API
  * ============ */

  $(function () {
    $('body').on('click.tab.data-api', '[data-toggle="tab"], [data-toggle="pill"]', function (e) {
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

    $.fn.tooltip = function(option, arg) {
      return this.each(function() {
        var $this = $(this), data = $this.data('tooltip'), options =
            typeof option == 'object'
                && option
        if (!data)
          $this.data('tooltip', (data = new tooltip(this, options)))
        if (typeof option == 'string')
          data[option](arg);
        return $this.data('tooltip');
      });
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
  function Wizard(el, options) {
    var me = this;
    this.options = $.extend($.fn.wizard.defaults, options
        || {});
    this.$el = $(el);
    this.$el.addClass('ui-wizard tabbable tabs-left');
    this.$nav = this.$el.find('>ul').addClass('nav nav-tabs');
    this.$content = this.$el.find('>.tab-content');
    this.$nav.children().removeClass('active');
    this.gotoTab(this.options.activetab);
    this.$nav.children().click(function(e) {
        e.preventDefault();
        me.gotoTab($(this).attr('data-step') || 0);
    });
  }
  Wizard.prototype = {
    gotoTab : function(idx) {
      if (typeof (this.$nav.children()[idx]) === 'undefined') {
        idx = 0;
      }
      var el = $(this.$nav.children()[idx]);
      el.parent().children().removeClass('active');
      el.addClass('active');
      var step = el.attr('data-step') || 0;
      var c = $(this.$content.children()[step]);
      if (!c.data('w-content')) {
        c.addClass('loading');
        c.load(el.attr('data-url'), function(data) {
          c.data('w-content', true);
          c.removeClass('loading');
        });
      }
      this.$content.children().hide();
      c.show();
    }
  }
  $.fn.wizard =
      function(option, arg) {
        return this.each(function() {
          var $this = $(this), data = $this.data('wizard'), options =
              typeof option == 'object'
                  && option
          if (!data)
            $this.data('wizard', (data = new Wizard(this, options)))
          if (typeof option == 'string')
            data[option](arg);
          return $this.data('wizard');
        });
      }
  $.fn.wizard.defaults = {
    activetab : 0
  }
})(jQuery);