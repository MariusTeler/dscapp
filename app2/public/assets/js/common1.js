$(document).ready(function(){
    $( ".button" ).button();

    if ($('.dataora').length > 0) {
    	$(".dataora").datepicker({
				showOn: 'both',
				buttonImage : HTTP + "assets/images/calendar.gif",
				buttonImageOnly : true,
				dateFormat : 'dd.mm.yy'
			});
	}
  });
/* //////////////////////////////////////////////////////
					START	FUNCTII DEFAULT
//////////////////////////////////////////////////////  */
$.ctrl = function(key, callback, args) {
    var isCtrl = false;
    $(document).keydown(function(e) {
        if(!args) args=[]; // IE barks when args is null

        if(e.ctrlKey) isCtrl = true;
        if(e.keyCode == key.charCodeAt(0) && isCtrl) {
            callback.apply(this, args);
            return false;
        }
    }).keyup(function(e) {
        if(e.ctrlKey) isCtrl = false;
    });
};

function PrepareResponse(txt) {
	if (txt.length > 1) {
		alert("AJAX Response Error: " + txt.substr(0, txt.length - 1));
		txt = txt.substr(-1);
	}
	return txt;
}

function AfiseazaEroare(mesaj, title, height, width) {
	if (!height)
		height = 300;
	if (!width)
		width = 500;
	$("#eroare").html(mesaj);
	if (!title)
		title = '<span style="color:#ffff00">EROARE!</span>';
	$("#eroare").attr('title', title);
	$("#eroare").dialog({
		modal : true,
		draggable : false,
		height : height,
		width : width,
		resizable : false,
		buttons : {
			Ok : function() {
				$(this).dialog("destroy");
			}
		}
	});
}

function numbersonly(myfield, e, dec)
{
	var key = e.key || e.which || e.keyCode;
	var ctrl = e.ctrlKey;
    var meta = e.metaKey;

	if ((key === 'x' || key === 88 || key === 'v' || key === 86 || key === 'c' || key === 67 || key === 'z' || key === 88) && (ctrl || meta)){
		return true;
	}
    // control keys
    if ( ['Backspace', 'Tab', 'Shift', 'Enter', 'Escape', 'Insert', 'Delete',  'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(key)
		|| [8, 9, 13, 16, 27, 37, 38, 39, 40, 45, 46].includes(key))
        return true;
    // numbers
	//console.log(/^\d+(\.\d*)?$/.test(myfield.value + key));
	if(dec) return /^\d+(\.\d*)?$/.test(myfield.value + key);
	return /^\d+$/.test(myfield.value + key);
}

function lettersonly(myfield, e)
{
    var key = e.key || e.which || e.keyCode;
    var ctrl = e.ctrlKey;
    var meta = e.metaKey;

	if ((key === 'x' || key === 88 || key === 'v' || key === 86 || key === 'c' || key === 67 || key === 'z' || key === 88) && (ctrl || meta))
		return true;

    // control keys
    if ( ['Backspace', 'Tab', 'Shift', 'Enter', 'Escape', 'Insert', 'Delete',  'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown'].includes(key)
		|| [8, 9, 13, 16, 27, 37, 38, 39, 40, 45, 46].includes(key))
        return true;

	return /^[0-9a-zA-Z\.\$\^\&\*\(\)\{\}\[\]':,!@#%]+$/.test(myfield.value + key);
}

function isNumber(n) {
  return !isNaN(n) && !isNaN(parseFloat(n)) && isFinite(n);
}

function isValidCui(cuiToValidate) {
	var cui = cuiToValidate.trim().toUpperCase();
	if(cui.startsWith("RO")) cui = cui.substring(2);
	if(!/^\d{2,10}$/.test(cui)) return false;

	var v = 753217532;
	var c1 = cui % 10;
	var cui = Math.floor(cui/10);

	var t = 0;
 	while(cui > 0){
 		t += (cui % 10) * (v % 10);
 		cui = Math.floor(cui/10);
 		v = Math.floor(v/10);
 	}

 	// aplica inmultirea cu 10 si afla modulo 11
 	c2 = (t * 10) % 11;

 	// daca modulo 11 este 10, atunci cifra de control este 0
 	if(c2 == 10){
 		c2 = 0;
 	}

 	return c1 == c2;
}

function isValidCnp(cnpToValidate) {
	return /^[1-9]\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\d|3[01])(0[1-9]|[1-4]\d|5[0-2]|99)(00[1-9]|0[1-9]\d|[1-9]\d\d)\d$/.test(cnpToValidate);
}

function ValidareField(field,type){
	if(!type) type=0;
	var title=$('#'+field).attr('rel');
	var value=$('#'+field).val();

	if(type==1){
		if ($('#'+field).is(':checked')){
			value=1;
		}else{
			value=0;
		}
	}

	if(value!=title){
		if(type==0)
			$('#'+field).addClass('camp_modificat');
		else
			$('#LABEL_'+field).addClass('camp_modificat');
	}else{
		if(type==0)
			$('#'+field).removeClass('camp_modificat');
		else
			$('#LABEL_'+field).removeClass('camp_modificat');
	}
}

function Confirmare(mesaj,title,height, width) {
	if (!height) height = 125;
	if (!width) width = 250;
	$("#eroare").html(mesaj);
	if (!title) title = 'Confirmare'
	$("#eroare").attr('title', title);
	$("#eroare").dialog({
		modal : true,
		draggable : false,
		height : height,
		width : width,
		resizable : false,
		buttons : {
			'Da' : function() {
				alert('da');
				return 1;
			},
			'Nu' : function() {
				$(this).dialog("destroy");
			}
		}
	});
}

function SetareDate()
{
	var dates = jQuery("#data_start, #data_final")
		.datepicker(
		{
			changeMonth : true,
			changeYear : true,
			showOn : "both",
			buttonImage : HTTP + "assets/images/calendar.gif",
			buttonImageOnly : true,
			dateFormat : 'dd.mm.yy',
			onSelect : function(selectedDate) {
				var option = this.id == "data_start" ? "minDate"
						: "", instance = $(this).data("datepicker");
				date = $.datepicker
						.parseDate(
								instance.settings.dateFormat
										|| $.datepicker._defaults.dateFormat,
								selectedDate, instance.settings);
				dates.not(this).datepicker("option", option, date);
				if (this.id == "data_start") {
					dates.not(this).datepicker("setDate", date);
				}
			}
	});
}

function ValidareCampuriRetur(field){
	var checked;
	if($('#'+field).is(':checked')) checked=1;
	else checked=0;

	if( $("#"+field).attr('rel')=='' && checked==1 )
		$("#label_"+field).css('color', 'red');
	else if( $("#"+field).attr('rel')=='checked' && checked==0 )
		$("#label_"+field).css('color', 'red');
	else{
		$("#label_"+field).css('color', '#232222');
	}
}

function FocusInput(field){
	if(field=='plateste1' || field=='plateste2' || field=='plateste3')
		$('#content_'+field).addClass('content_plateste_focus');
	if(field=='TIP_OBJ_1' || field=='TIP_OBJ_3' || field=='RET_NT' || field=='RET_DOC'  || field=='LIV_S'  || field=='RET_AMB' || field=='RET_COLET'  || field=='LIV_SEDIU'  || field=='COPEN' || field=='SMS' ){
		$('#content_'+field).addClass('content_checkbox_focus');
	}
}
function BlurInput(field){
	if(field=='plateste1' || field=='plateste2' || field=='plateste3')
		$('#content_'+field).removeClass('content_plateste_focus');
	if(field=='TIP_OBJ_1' || field=='TIP_OBJ_3' || field=='RET_NT' || field=='RET_DOC'  || field=='LIV_S'  || field=='RET_AMB' || field=='RET_COLET'  || field=='LIV_SEDIU' || field=='COPEN' || field=='SMS')
	{
		$('#content_'+field).removeClass('content_checkbox_focus');
	}
}

function roundNumber(num, dec) {
	var result = (Math.round(num * Math.pow(10,dec))/Math.pow(10,dec)).toFixed(2);
	return result;
}

function htmlspecialchars(str) {
	return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
}

function base64EncodeUnicode(str) {
  return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g,
    (match, p1) => String.fromCharCode('0x' + p1)
  ));
}

jQuery.download = function(url, data, pJson, method){
	//url and data options required
	var inputs = '';
    if( url && data ){
		if(pJson == true){
			inputs="<input type='hidden' id='pJson' name='pJson' value='"+ base64EncodeUnicode(data) +"' />";
		}
		else {
			//data can be string of parameters or array/object
			data = typeof data == 'string' ? data : jQuery.param(data);
			//split params into form inputs
			jQuery.each(data.split('&'), function(){
				var pair = this.split('=');
				if(pair[0] == 'filters') inputs+='<input type="hidden" name="'+ pair[0] +'" value="'+ htmlspecialchars(pair[1]) +'" />';
				else if(pair[0] == 'pJson') inputs+="<input type='hidden' name='pJson' value='"+ base64EncodeUnicode(pair[1]) +"' />";
				else inputs+='<input type="hidden" name="'+ pair[0] +'" value="'+ pair[1] +'" />';
			});
		}
        //send request
        //alert(inputs);
        jQuery('<form action="'+ url +'" method="'+ (method||'post') +'">'+inputs+'</form>')
        .appendTo('body').submit().remove();
    };
};

jQuery.downloadWithFilters = function(url, data, _search, filters, method){
	//url and data options required
	var inputs = '';
    if( url && data ){
		//data can be string of parameters or array/object
		data = typeof data == 'string' ? data : jQuery.param(data);
		//split params into form inputs
		jQuery.each(data.split('&'), function(){
			var pair = this.split('=');
			inputs+='<input type="hidden" name="'+ pair[0] +'" value="'+ pair[1] +'" />';
		});
		if(_search && filters) {
			inputs+='<input type="hidden" name="_search" value="'+ _search +'" />';
			inputs+='<input type="hidden" name="filters" value="'+ btoa(filters) +'" />';
		}
        //send request
        //alert(inputs);
        jQuery('<form action="'+ url +'" method="'+ (method||'post') +'">'+inputs+'</form>')
        .appendTo('body').submit().remove();
    };
};

function checkDate(sender){
	if(!sender.value) return;
	var reg = new RegExp(/^(\d{1,2}[./]\d{1,2}[./](19|20|21)[0-9]{2})$/);
	var p = reg.test(sender.value);
	if(!p)
			AfiseazaEroare("Format data invalid : "+sender.value);
	try{
		var sDate = new Date($.datepicker.parseDate('dd.mm.yy', sender.value));
		var minDate = new Date($.datepicker.parseDate('dd.mm.yy','01.01.2017'));
		var maxDate = new Date($.datepicker.parseDate('dd.mm.yy', '01.01.2025'));
		var t = minDate < sDate && sDate < maxDate;
		if(!t)
			{AfiseazaEroare("Data eronata : "+sender.value); return false;}
		return true;
	}
	catch(e){
		AfiseazaEroare("Data eronata : " + sender.value);
		return false;
	}
}

function b64DecodeUnicode(str) {
    return decodeURIComponent(atob(str).split('').map(function(c) {
        return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));
}

function MakeDialog() {
	var form, dialog;
	dialog = $( "#dialog-form" ).dialog({
		autoOpen: false,
		height: "auto",
		width: 650,
		modal: true,
		resizable: false,
		title: "",
		buttons: {
		   "Inchide [esc]": function() {
				dialog.dialog("close");
		   }
		},
		close: function() {
		  form[0].reset();
		}
	  });
	 form = dialog.find("form").on( "submit", function( event ) {
			event.preventDefault();
	  });
	  dialog.prev().find(".ui-dialog-titlebar-close").hide();

}

function InitConfirmDialog() {
	$( "#dialog-confirm" ).dialog({
		autoOpen: false,
		resizable: false,
		height: "auto",
		width: 400,
		modal: true,
		title: "Confirmare",
		buttons: {
		   "Inchide [esc]": function() {
				dialog.dialog("close");
		   }
		}
	});
}

//SET CURSOR POSITION
$.fn.setCursorPosition = function(pos) {
    this.each(function(index, elem) {
        if (elem.setSelectionRange) {
            elem.setSelectionRange(pos, pos);
        } else if (elem.createTextRange) {
            var range = elem.createTextRange();
            range.collapse(true);
            range.moveEnd('character', pos);
            range.moveStart('character', pos);
            range.select();
        }
    });
    return this;
};
