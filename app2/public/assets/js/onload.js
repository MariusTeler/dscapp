function ComboLocalitati(field) {
	$( "#"+field ).autocomplete({
		source: function( request, response ) {
			$.ajax({
				url : HTTP + "expeditii/json/localitati",
				dataType: "json",
				data: {
					style: "full",
					maxRows: 20,
					name_startsWith : request.term
				},
				success: function( data ) {
					response( $.map( data.rezultat, function( item ) {
						return {
							value: item.value,
							label : item.label
						}
					}));
				}
			});
		},
		minLength: 1,
		select: function( event, ui ) {
			//return false;
		},
		open: function() {
			$( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" );
		},
		close: function() {
			$( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" );
		}
	});
}

function ComboClienti(field,field_label,field_value) {
	$("#"+field).autocomplete({
		source : function(request, response) {
			$.ajax({
				url : HTTP + "expeditii/json/clienti",
				dataType : "json",
				data : {
					maxRows : 15,
					name_startsWith : request.term,
					label : ($.trim($('#'+field_label)).length ? $('#'+field_label).val() : ''),
					value : ($.trim($('#'+field_label)).length ? $('#'+field_value).val() : '')
				},
				success : function(data){
					response($.map(data.rezultat, function(item) {
						return {
							value : item.value,
							label : item.label
						}
					}));
				}
			});
		},
		minLength : 3,
		select : function(event, ui) {
			$("#" + field).val(ui.item.label);
			return false;
		},
		open : function() {
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}

function ComboAgenti(field) {
	$( "#"+field + "_nume" ).autocomplete({
		source: function( request, response ) {
			$.ajax({
				url : HTTP + "expeditii/json/agenti",
				dataType: "json",
				data: {
					style: "full",
					maxRows: 20,
					name_startsWith : request.term
				},
				success: function( data ) {
					response( $.map( data.rezultat, function( item ) {
						return {
							value: item.value,
							label : item.label
						}
					}));
				}
			});
		},
		minLength: 3,
		select : function(event, ui) {
			$("#" + field + "_nume").val(ui.item.label);
			$("#" + field).val(ui.item.value);
			return false;
		},
		change : function(event, ui) {
			if(!($.trim($("#" + field + "_nume").val()).length))
				$("#" + field).val("");
		},
		open : function() {
			$("#" + field).val('');
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}
function ComboOperatori(field) {
	$( "#"+field+'_nume' ).autocomplete({
		source: function( request, response ) {
			$.ajax({
				url : HTTP + "expeditii/json/operatori",
				dataType: "json",
				data: {
					style: "full",
					maxRows: 20,
					name_startsWith : request.term
				},
				success: function( data ) {
					response( $.map( data.rezultat, function( item ) {
						return {
							value: item.value,
							label : item.label
						}
					}));
				}
			});
		},
		minLength : 1,
		select : function(event, ui) {
			$("#" + field + "_nume").val(ui.item.label);
			$("#" + field).val(ui.item.value);
			return false;
		},
		change : function() {
			if(!($.trim($("#" + field + "_nume").val()).length))
				$("#" + field).val("");
		},
		open : function() {
			$("#" + field).val('');
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}
function PrintExpeditie(sel,tip){
	sel=parseInt(sel);
	if(isNaN(sel) || sel == 0) return false;
	var url = HTTP + 'expeditii/print_expeditie/'+sel;
	$("#detalii_expeditie").dialog("destroy");
    window.open(url,'_blank');
	return true;
}
/* //////////////////////////////////////////////////////
					END	FUNCTII DEFAULT
//////////////////////////////////////////////////////  */

/* //////////////////////////////////////////////////////
					START	CAUTARE EXPEDITII
//////////////////////////////////////////////////////  */
function CautareExpeditii(sel) {
	if (sel == 1) {
		if ($('#perioada').is(':checked')) {
			$('#data_start').removeAttr('disabled');
			$('#data_final').removeAttr('disabled');
			$('.ui-datepicker-trigger').show();
		} else {
			$('#data_start').attr('disabled', true);
			$('#data_final').attr('disabled', true);
			$('.ui-datepicker-trigger').hide();
		}
	} else if (sel == 2) {
		if ($('#operatie').is(':checked')) {
			$('#operatiune').removeAttr('disabled');
			$('#search').removeAttr('disabled');
		} else {
			$('#operatiune').attr('disabled', true);
			$('#search').attr('disabled', true);
		}
	} else if (sel == 3) {
		if ($('#user').is(':checked')) {
			$('#operator_nume').removeAttr('disabled');
		} else {
			$('#operator_nume').val('');
			$('#operator_nume').attr('disabled', true);
		}
	} else if (sel == 4) {
		if ($('#expeditor').is(':checked')) {
			$('#expeditor_nume').removeAttr('disabled');
			$('#expeditor_localitate_nume').removeAttr('disabled');
		} else {
			$('#expeditor_nume').attr('disabled', true);
			$('#expeditor_localitate_nume').attr('disabled', true);
			$('#expeditor').val('');
			$('#expeditor_nume').val('');
			$('#expeditor_localitate_nume').val('');
		}
	} else if (sel == 5) {
		if ($('#destinatar').is(':checked')) {
			$('#destinatar_nume').removeAttr('disabled');
			$('#destinatar_localitate_nume').removeAttr('disabled');
		} else {
			$('#destinatar_nume').attr('disabled', true);
			$('#destinatar_localitate_nume').attr('disabled', true);
			$('#destinatar').val('');
			$('#destinatar_nume').val('');
			$('#destinatar_localitate_nume').val('');
		}
	} else if (sel == 6) {
		if ($('#curier').is(':checked')) {
			$('#curier_preluare_nume').removeAttr('disabled');
			$('#curier_livrare_nume').removeAttr('disabled');
		} else {
			$('#curier_preluare_nume').val('');
			$('#curier_livrare_nume').val('');
			$('#curier_preluare_nume').attr('disabled', true);
			$('#curier_livrare_nume').attr('disabled', true);
		}
	} else if (sel == 7) {
		if ($('#expeditor').is(':checked')) {
			$('#expeditor_nume').removeAttr('disabled');
			$('#expeditor_centru_nume').removeAttr('disabled');
		} else {
			$('#expeditor_nume').attr('disabled', true);
			$('#expeditor_centru_nume').attr('disabled', true);
		}
	} else if (sel == 8) {
		if ($('#destinatar').is(':checked')) {
			$('#destinatar_nume').removeAttr('disabled');
			$('#destinatar_centru_nume').removeAttr('disabled');
		} else {
			$('#destinatar_nume').attr('disabled', true);
			$('#destinatar_centru_nume').attr('disabled', true);
		}
	} else if (sel == 9) {
		if ($('#platitor').is(':checked')) {
			$('#platitor_nume').removeAttr('disabled');
			$('#platitor_centru_nume').removeAttr('disabled');
		} else {
			$('#platitor_nume').attr('disabled', true);
			$('#platitor_platitor_nume').attr('disabled', true);
		}
	} else if (sel == 10) {
		if ($('#user').is(':checked')) {
			$('#operator_nume').removeAttr('disabled');
			$('#operatiune').removeAttr('disabled');
		} else {
			$('#operator_nume').attr('disabled', true);
			$('#operatiune').attr('disabled', true);
		}
	} else if (sel == 11) {
		if ($('#livrare').is(':checked')) {
			$('#tip_livrare').removeAttr('disabled');
		}else {
			$('#tip_livrare').val(1);
			$('#tip_livrare').attr('disabled', true);
		}
	}

}

function AfisareCautareExpeditii()
{
	var cond = '';
	var search = $('#search').val();
	if(search != '')
	{
		var operatiune = $('#operatiune').val();
		cond += '&operatiune=' + escape(operatiune) + '&search='+ escape(search);
	}

	if ($('#perioada').is(':checked')) {
		var data_start = $('#data_start').val();
		var data_final = $('#data_final').val();
		cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	}
	if ($('#user').is(':checked')) {
		if ($('#operator_nume').val() != 'Operator') {
			var operator = $('#operator_nume').val();
			cond += '&operator=' + escape(operator);
		}
	}
	if ($('#expeditor').is(':checked')) {
		if ($('#expeditor_localitate_nume').val() != 'Localitate' && $('#expeditor_localitate_nume').val() != '') {
			var expeditor_localitate = $('#expeditor_localitate_nume').val();
			cond += '&expeditor_localitate=' + escape(expeditor_localitate);
		}
		if ($('#expeditor_nume').val() != 'Client' && $('#expeditor_nume').val() != '') {
			var expeditor = $('#expeditor_nume').val();
			cond += '&expeditor=' + escape(expeditor);
		}
	}
	if ($('#destinatar').is(':checked')) {
		if ($('#destinatar_localitate_nume').val() != 'Localitate' && $('#destinatar_localitate_nume').val() != '') {
			var destinatar_localitate = $('#destinatar_localitate_nume').val();
			cond += '&destinatar_localitate=' + escape(destinatar_localitate);
		}
		if ($('#destinatar_nume').val() != 'Client' && $('#destinatar_nume').val() != '') {
			var destinatar = $('#destinatar_nume').val();
			cond += '&destinatar=' + escape(destinatar);
		}
	}
	if ($('#curier').is(':checked')) {
		if ($('#curier_preluare_nume').val() != 'Preluare' && $('#curier_preluare_nume').val() != '') {
			var curier_preluare = $('#curier_preluare').val();
			cond += '&curier_preluare=' + curier_preluare;
		}
		if ($('#curier_livrare_nume').val() != 'Livrare' && $('#curier_livrare_nume').val() != '') {
			var curier_livrare = $('#curier_livrare').val();
			cond += '&curier_livrare=' + curier_livrare;
		}
	}
	if ($('#livrare').is(':checked')) {
		if ($('#tip_livrare').val() > 1 ) {
			var livrare = $('#tip_livrare').val();
			cond += '&livrare=' + livrare;
		}
	}

	if ($('#raport_alex').val() > 0 ) {
		var raport_alex = $('#raport_alex').val();
		cond += '&raport_alex=' + raport_alex;
	}

	var postExpeditii = '';
	if($('#expeditii_area').val().trim().length > 0) {
		var expeditii = $('#expeditii_area').val().split(new RegExp(/[\r\n\x0B\x0C\u0085\u2028\u2029,]+/g)).filter(function (el) {
			return el != null && el != "";
		});
		$('#nr_expeditii_area').html(expeditii.length + " expeditii");
		if(expeditii.length > 201) { AfiseazaEroare("Nu se pot afisa mai mult de 200 awb-uri !"); return false; }
		postExpeditii = $('#expeditii_area').val().replace(/[\r\n\s\t\v\u0085\u2028\u2029,]+/g, ',');
		jQuery("#cautare_expeditii").jqGrid('setGridParam',{ url: HTTP + 'expeditii/json/cautare', postData: { expeditii: postExpeditii }}).trigger("reloadGrid");
		return true;
	}
	jQuery("#cautare_expeditii").jqGrid('setGridParam',{ url: HTTP + 'expeditii/json/cautare?a' + cond, postData : { expeditii: 0 }}).trigger("reloadGrid");
}

function Grid_CautareExpeditii()
{
	SetareDate();
	var grid = jQuery('#cautare_expeditii');

	grid.jqGrid(
	{
		url : HTTP + 'expeditii/json/cautare',
		datatype: 'json',
		mtype: 'POST',
		colNames : [ 'Nr. Exp', 'Tip exp', 'Expeditor', 'Destinatar','Centru Expeditor','Centru Destinatar', 'Data','Plicuri','Colete','Paleti','Greutate','Km Prel','Km Livr','Asigurare','Ramburs','Tip plata','Val Exp','Curier Preluare','Curier Livrare','Observatii','' ],
		colModel : [ {
			name : 'expeditie',
			index : 'ep.expeditie',
			sorttype : 'int',
			width: 60,
			align: 'right'
		}, {
			name : 'tip_exp',
			index : 'ep.tip_exp',
			formatter: 'select',
			edittype : 'select',
			editoptions : {
				value : "0:Initiala;1:Retur NT;2:Retur Doc.;3:Ramburs;5:Returnare;6:Retur Ambalaj;7:Retur Colet;33:Borderou RBS Cash"
			},
			stype : 'select',
			searchoptions : {
				value : ":Toate;0:Initiala;1:Retur NT;2:Retur Doc.;3:Ramburs;5:Returnare;6:Retur Ambalaj;7:Retur Colet;33:Borderou RBS Cash",
				sopt : [ 'eq' ]
			},
			width: 60,
			align: 'center'
		},{
			name : 'expeditor',
			index : 'cle.nume',
			width: 130
		}, {
			name : 'destinatar',
			index : 'cld.nume',
			width: 130
		}, {
			name : 'expeditor_centru',
			index : 'cee.nume',
			width: 100
		}, {
			name : 'destinatar_centru',
			index : 'ced.nume',
			width: 100
		}, {
			name : 'data_expeditie',
			index : 'ep.data_expeditie',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			width: 60,
			align: 'center'
		}, {
			name : 'plicuri',
			index : 'ep.plicuri',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		}, {
			name : 'colete',
			index : 'ep.colete',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		}, {
			name : 'paleti',
			index : 'ep.paleti',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		}, {
			name : 'greutate',
			index : 'ep.greutate',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		}, {
			name : 'km_preluare',
			index : 'ep.km_preluare',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		}, {
			name : 'km_livrare',
			index : 'ep.km_livrare',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		}, {
			name : 'valoare_asigurata',
			index : 'ep.valoare_asigurata',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		},{
			name : 'ramburs',
			index : 'ep.ramburs',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		}, {
			name : 'tip_plata',
			index : 'ep.tip_plata',
			formatter:'select',
			stype : 'select',
			searchoptions: {
				value: ":Toate;0:cash;1:bo;2:cec;3:cont",
				sopt : [ 'eq' ]
			},
			edittype : "select",
			editoptions: {
				value: {0:'cash', 1:'bo', 2:'cec', 3:'cont'}
			},
			align: 'center',
			width: 40,
		},{
			name : 'valoare_expeditie',
			index : 'ep.valoare_expeditie',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		}, {
			name : 'curier_preluare',
			index : 'agp.nume_ag',
			width: 100
		}, {
			name : 'curier_livrare',
			index : 'agl.nume_ag',
			width: 100
		}, {
			name : 'observatii',
			index : 'ep.observatii',
			width: 150
		}, {
			name : 'opt',
			search : false,
			sortable : false,
			width: 20
		} ],
		rownumbers : true,
		scroll : 1,
		rowNum : 100,
		width : 970,
		height : 295,
		sortname : 'ep.data_expeditie',
		sortorder : "desc",
		viewrecords : true,
		pager : '#paginatie',
		shrinkToFit: false,
		caption:'Lista Expeditii',
		forceFit: true,
		multiselect: true,
		beforeSelectRow: function (rowid, e) {
                    var $myGrid = $(this),
                        i = $.jgrid.getCellIndex($(e.target).closest('td')[0]),
                        cm = $myGrid.jqGrid('getGridParam', 'colModel');
                    return (cm[i].name === 'cb');
        },
        ondblClickRow : function(id) {
			AfisareDetaliiExpeditieCuID(id)
		},
		gridComplete: function()
		{
		    var rows = grid.getDataIDs();
		    for (var i = 0; i < rows.length; i++)
		    {
		        var status = grid.getCell(rows[i],"opt");
		        if(status == "LS")
		        {
		            grid.jqGrid('setRowData',rows[i],false, {  color:'black',weightfont:'bold',background:'#ece1b6'});
		        }
		    }
		}
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});

}

function redirectTo(path){
	var postData = {};
	var grid = jQuery('#cautare_expeditii');
	var sel_ids = grid.jqGrid('getGridParam', 'selarrrow');
	for(var i=0, l=sel_ids.length; i<l; i++) postData[i] = sel_ids[i];
	var sel = JSON.stringify(postData);
	var url = HTTP + path + "/"+sel;
	window.open(url,'_blank');
}

function PrintareMultiplaCautareExpeditii(){

	var grid = jQuery('#cautare_expeditii');
	var sel_ids = grid.jqGrid('getGridParam', 'selarrrow');
	if(sel_ids.length==0) { AfiseazaEroare("Selectioneaza minimum un awb pentru printare!"); return false; }
	else if(sel_ids.length>200) { AfiseazaEroare("Nu se pot selectiona mai mult de 200 awb-uri pentru printare !"); return false; }
	var postData = {};

	for(var i=0, l=sel_ids.length; i<l; i++) postData[i] = sel_ids[i];
	var postData = JSON.stringify(postData);
	//alert("JSON serialized jqGrid data:\n" + postData);
	$("#detalii_expeditie").dialog("destroy");
	var url = HTTP + 'expeditii/printare_multipla/'+postData;
	window.open(url,'_blank');
	return true;
}

function PrintareMultiplaSelectieCautareExpeditii() {
	var grid = jQuery('#cautare_expeditii');
	var sel_ids = grid.jqGrid('getGridParam', 'selarrrow');
	if(sel_ids.length==0) { AfiseazaEroare("Selectioneaza minimum un awb pentru printare!"); return false; }
	else if(sel_ids.length>100) { AfiseazaEroare("Nu se pot selectiona mai mult de 100 awb-uri pentru printare !"); return false; }
	var postData = {};

	for(var i=0, l=sel_ids.length; i<l; i++) postData[i] = sel_ids[i];
	var sel = JSON.stringify(postData);

	$('#detalii_expeditie').html('<label style="width: 75px;display: block;float: left;">A4</label><button onclick="redirectTo(\'expeditii/printare_multipla_master_puisori\')" type="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" role="button" aria-disabled="false"><span class="ui-button-text">Multipla</span></button><br>' +
		'<label style="width: 75px;display: block;float: left;">Autocolant</label> <button style="display:none;" onclick="redirectTo(\'expeditii/printare_multipla_master_autocolant\')" type="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" role="button" aria-disabled="false"><span class="ui-button-text">Master</span></button> <button onclick="redirectTo(\'expeditii/printare_multipla_puisori_autocolant\')"  type="button" class="ui-button ui-widget ui-state-default ui-corner-all ui-button-text-only" role="button" aria-disabled="false"><span class="ui-button-text">Puisori</span></button>');
	$("#detalii_expeditie").dialog("destroy");
	$("#detalii_expeditie").dialog({
		width: 320,
		height: 170,
		draggable: false,
		resizable: false,
		modal: true,
		title: 'Printare puisori pe etichete autocolante',
		buttons: {
			'Inchide': function () {
				$(this).dialog("destroy");
			}
		},
		close: function () {
			$(this).dialog("destroy");
		}
	});
}

function PrintareMultiplaCautareExpeditiiMasterPuisori(){

	var grid = jQuery('#cautare_expeditii');
	var sel_ids = grid.jqGrid('getGridParam', 'selarrrow');
	if(sel_ids.length==0) { AfiseazaEroare("Selectioneaza minimum un awb pentru printare!"); return false; }
	else if(sel_ids.length>100) { AfiseazaEroare("Nu se pot selectiona mai mult de 100 awb-uri pentru printare !"); return false; }
	var postData = {};

	for(var i=0, l=sel_ids.length; i<l; i++) postData[i] = sel_ids[i];
	var exps = JSON.stringify(postData);
    //alert("JSON serialized jqGrid data:\n" + postData);
    $("#detalii_expeditie").dialog("destroy");
    var url = HTTP + 'expeditii/printare_multipla/'+exps;
    window.open(url,'_blank');
	return true;
}

function ExportCautareExpeditii(){
	var cond = '';
	var search = $('#search').val();
	if(search != '')
	{
		var operatiune = $('#operatiune').val();
		cond += '&operatiune=' + escape(operatiune) + '&search='+ escape(search);
	}

	if ($('#perioada').is(':checked')) {
		var data_start = $('#data_start').val();
		var data_final = $('#data_final').val();
		cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	}
	if ($('#user').is(':checked')) {
		if ($('#operator_nume').val() != 'Operator') {
			var operator = $('#operator_nume').val();
			cond += '&operator=' + escape(operator);
		}
	}
	if ($('#expeditor').is(':checked')) {
		if ($('#expeditor_localitate_nume').val() != 'Localitate' && $('#expeditor_localitate_nume').val() != '') {
			var expeditor_localitate = $('#expeditor_localitate_nume').val();
			cond += '&expeditor_localitate=' + escape(expeditor_localitate);
		}
		if ($('#expeditor_nume').val() != 'Client' && $('#expeditor_nume').val() != '') {
			var expeditor = $('#expeditor_nume').val();
			cond += '&expeditor=' + escape(expeditor);
		}
	}
	if ($('#destinatar').is(':checked')) {
		if ($('#destinatar_localitate_nume').val() != 'Localitate' && $('#destinatar_localitate_nume').val() != '') {
			var destinatar_localitate = $('#destinatar_localitate_nume').val();
			cond += '&destinatar_localitate=' + escape(destinatar_localitate);
		}
		if ($('#destinatar_nume').val() != 'Client' && $('#destinatar_nume').val() != '') {
			var destinatar = $('#destinatar_nume').val();
			cond += '&destinatar=' + escape(destinatar);
		}
	}
	if ($('#curier').is(':checked')) {
		if ($('#curier_preluare_nume').val() != 'Preluare' && $('#curier_preluare_nume').val() != '') {
			var curier_preluare = $('#curier_preluare').val();
			cond += '&curier_preluare=' + curier_preluare;
		}
		if ($('#curier_livrare_nume').val() != 'Livrare' && $('#curier_livrare_nume').val() != '') {
			var curier_livrare = $('#curier_livrare').val();
			cond += '&curier_livrare=' + curier_livrare;
		}
	}
	if ($('#livrare').is(':checked')) {
		if ($('#tip_livrare').val() > 1 ) {
			var livrare = $('#tip_livrare').val();
			cond += '&livrare=' + livrare;
		}
	}

	if ($('#raport_alex').val() > 0 ) {
		var raport_alex = $('#raport_alex').val();
		cond += '&raport_alex=' + raport_alex;
	}

	if($('#expeditii_area').val().trim().length > 0) {
		var sel_ids = $('#cautare_expeditii').jqGrid('getGridParam', 'selarrrow');
		if(sel_ids.length==0) { AfiseazaEroare("Selectioneaza minimum o expeditie !"); return false; }
		cond='expeditii='+sel_ids.join(',');
	}

	var filters =  $('#cautare_expeditii').jqGrid('getGridParam','postData').filters;
	if(filters) cond+='&_search=true&filters='+filters;
	sidx = $('#cautare_expeditii').jqGrid('getGridParam','sortname');
	if(sidx) cond+='&sidx='+sidx;
	sord = $('#cautare_expeditii').jqGrid('getGridParam','sortorder');
	if(sord) cond+='&sord='+sord;

	$.download('expeditii/export/cautare', cond, false);
}

function AfisareDetaliiExpeditie(field)
{
	var grid = jQuery('#'+field);
	var sel_id = grid.jqGrid('getGridParam', 'selrow');
	var sel = grid.jqGrid('getCell', sel_id, 'expeditie');
//alert(field+' - '+sel_id+' - '+sel);
	$.post(HTTP + 'expeditii/detalii_expeditie/', {expeditie : sel}, function(response) {
		$("#detalii_expeditie").html(response);
		$("#detalii_expeditie:ui-dialog").dialog("destroy");
		$("#detalii_expeditie").dialog({
			width : 624,
			height : 650,
			modal : true,
			draggable : false,
			resizable : false,
			title : 'Nr. AWB: '+sel
		});
	});
	//return true;
}

function AfisareDetaliiExpeditieCuID(sel)
{
	$.post(HTTP + 'expeditii/detalii_expeditie/', {expeditie : sel}, function(response) {
		$("#detalii_expeditie").html(response);
		$("#detalii_expeditie:ui-dialog").dialog("destroy");
		$("#detalii_expeditie").dialog({
			width : 628,
			height : 670,
			modal : true,
			draggable : false,
			resizable : false,
			title : 'Nr. AWB: '+sel
		});
	});
}

function DetaliiIstoricExpeditii(id) {
	jQuery("#istoric_expeditii_detalii").jqGrid({
		url : HTTP + 'expeditii/json/istoric?expeditie=' + id,
		datatype : "json",
		colNames : [ 'Operatiune', 'Data', 'Operator', 'Data operarii', 'Detalii' ],
		colModel : [ {
			name : 'operatiune',
			index : 'c.OP_RO',
			sorttype : 'int'
		}, {
			name : 'data',
			index : 'a.DATA',
			sorttype : 'date',
			formatter : 'date',
			datefmt : 'd.m.Y h:i'
		}, {
			name : 'operator',
			index : 'b.USER'
		}, {
			name : 'data_operarii',
			index : 'a.DATA_OP',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d H:i:s',
				newformat : 'd.m.Y H:i'
			}
		}, {
            name : 'detalii',
            index : 'detalii',
			hidden: true
        } ],
		rowNum : 100,
		width : 580,
		height : 100,
		mtype : "GET",
		rownumbers : false,
		gridview : true,
		sortname : 'a.DATA_OP',
		viewrecords : true,
		sortorder : "asc",
		pager : '#paginatie_istoric',
		caption : false,
		multiselect : false,
		subGrid : false,
        ondblClickRow : function(rowid, nr, col,  row) {
            var rowData = $("#istoric_expeditii_detalii").getRowData(rowid);
			AfisareDetaliiIstoricExpeditieCuID(rowData.detalii)
        },

	});
	jQuery("#istoric_expeditii_detalii").jqGrid('bindKeys');
	jQuery("#istoric_expeditii_detalii").jqGrid('navGrid', '#paginatie_istoric_detalii', {
		del : false,
		add : false,
		edit : false,
		search : false,
		refresh : false
	});
}


function Verificari_IstoricRecantariri(exp){
	jQuery("#istoric_recantariri").jqGrid({
		url : HTTP + 'verificari/istoric_recantariri?expeditie='+exp,
		datatype : "json",
		colModel : [ {
			name : 'data',
			index : 'data',
			width: 100
		}, {
			label : 'Operator',
			name : 'operator',
			index : 'user',
			width: 110
		}, {
			label : 'Gr. veche',
			name : 'oldKg',
			index : 'oldKg',
			width: 70,
			align : 'center'
		}, {
			label : 'Gr. noua',
			name : 'kg',
			index : 'kg',
			width: 70,
			align : 'center'
		}, {
			label : 'Lungime',
			name : 'lungime',
			index : 'lungime',
			width: 50,
			align : 'center'
		}, {
			label : 'Latime',
			name : 'latime',
			index : 'latime',
			width: 50,
			align : 'center'
		}, {
			label : 'Inaltime',
			name : 'inaltime',
			index : 'inaltime',
			width: 50,
			align : 'center'
		}, {
			label : 'Poza',
			name : 'poza',
			search : false,
			sortable : false,
			width: 50,
			align : 'center'
		}],
		rowNum :4,
		rowList : [ 15, 50 ],
		rownumbers : true,
		width : 950,
		height : 'auto',
		sortname : 'er.id',
		sortorder : "desc",
		viewrecords : true,
		pager : '#paginatie_istoric',
		shrinkToFit: false,
		caption:'',
		forceFit: true,
		ondblClickRow : function(rowId, iRow, iCol, e) {
			var cellcontent = $(this).jqGrid('getCell', rowId, 'poza');
			if(cellcontent == '1')
				opDownloadRecantarire(rowId);
		},
		gridComplete: function()
		{
			var userData = $("#istoric_recantariri").getGridParam("userData");
			if(userData.afisare_racantarire){
				$('.racantarire_expeditie').show();
				$('#afisare_recantariri').show();
				$('#afisare_recantariri').html($("#istoric_recantariri").getGridParam("records") + ' Recantariri');
			} else {
				$('.racantarire_expeditie').hide();
				$('#afisare_recantariri').hide();
				$('#afisare_recantariri').html('0 Recantariri');
			}
		}
	});
	jQuery("#istoric_recantariri").jqGrid('bindKeys');
	jQuery("#istoric_recantariri").jqGrid('navGrid', '#paginatie_istoric', {
		del : false,
		add : false,
		edit : false,
		search : false,
		refresh : true
	});
}

function AfisareDetaliiIstoricExpeditieCuID(id){
    var obj = jQuery.parseJSON( id );
    if(!obj)
    	return;
    var html = '';
    for (var i = 0; i < obj.length; i++) {
        html += '<li><span class="name_field">' + obj[i].nume + '</span><span class="old_value_field">' + obj[i].old + '</span><span class="new_value_field">' + obj[i].new + '</span></li>';
    }
	$('#istoric_detalii_expeditii_detalii').html(html);
}

function ExportRapoarteCentre(){
	var cond = '';
	if ($('#perioada').is(':checked')) {
		var data_start = $('#data_start').val();
		var data_final = $('#data_final').val();
		cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	}
	if ($('#user').is(':checked')) {
		if ($('#operator_nume').val() != 'Operator') {
			var operator = $('#operator').val();
			cond += '&operator=' + escape(operator);
		}
		if ($('#operatiune').val() > 0) {
			var operatiune = $('#operatiune').val();
			cond += '&operatiune=' + escape(operatiune);
		}
	}
	if ($('#expeditor').is(':checked')) {

		if ($('#expeditor_centru_nume').val() != 'Centru') {
			var expeditor_centru = $('#expeditor_centru').val();
			cond += '&expeditor_centru=' + escape(expeditor_centru);
		}
		if ($('#expeditor_nume').val() != 'Client') {
			var expeditor = $('#expeditor_nume').val();
			cond += '&expeditor=' + escape(expeditor);
		}
	}
	if ($('#destinatar').is(':checked')) {
		if ($('#destinatar').val() != 'Centru') {
			var destinatar_centru = $('#destinatar_centru').val();
			cond += '&destinatar_centru=' + escape(destinatar_centru);
		}
		if ($('#destinatar_nume').val() != 'Client') {
			var destinatar = $('#destinatar_nume').val();
			cond += '&destinatar=' + escape(destinatar);
		}
	}
	if ($('#platitor').is(':checked')) {
		if ($('#platitor').val() != 'Centru') {
			var platitor_centru = $('#platitor_centru').val();
			cond += '&platitor_centru=' + escape(platitor_centru);
		}
		if ($('#platitor_nume').val() != 'Client') {
			var platitor = $('#platitor_nume').val();
			cond += '&platitor=' + escape(platitor);
		}
	}
	if ($('#curier').is(':checked')) {
		if ($('#curier_preluare_nume').val() != 'Preluare') {
			var curier_preluare = $('#curier_preluare').val();
			cond += '&curier_preluare=' + curier_preluare;
		}
		if ($('#curier_livrare_nume').val() != 'Livrare') {
			var curier_livrare = $('#curier_livrare').val();
			cond += '&curier_livrare=' + curier_livrare;
		}
	}

    var url = HTTP + 'expeditii/export/rapoarte_centre?a' + cond
    window.location = url;
}


function Grid_RapoarteCentreExpeditii()
{
	SetareDate();
	var grid = jQuery('#cautare_expeditii');

	grid.jqGrid(
	{
		url : HTTP + 'expeditii/json/rapoarte_centre',
		datatype: 'json',
		mtype: 'POST',
		colNames : [ 'Nr. Exp', 'Expeditor', 'Destinatar','Centru Expeditor','Centru Destinatar', 'Data','Plicuri','Colete','Paleti','Greutate','Km Prel','Km Livr','Val Asig','Val Exp','Curier Preluare','Curier Livrare','Status','Data op', 'Master' ],
		colModel : [ {
			name : 'expeditie',
			index : 'ep.expeditie',
			sorttype : 'int',
			width: 60,
			align: 'right'
		}, {
			name : 'expeditor',
			index : 'cle.nume',
			width: 130
		}, {
			name : 'destinatar',
			index : 'cld.nume',
			width: 130
		}, {
			name : 'expeditor_centru',
			index : 'cee.nume',
			width: 100
		}, {
			name : 'destinatar_centru',
			index : 'ced.nume',
			width: 100
		}, {
			name : 'data_expeditie',
			index : 'ep.data_expeditie',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			width: 60,
			align: 'center'
		}, {
			name : 'plicuri',
			index : 'ep.plicuri',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		}, {
			name : 'colete',
			index : 'ep.colete',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		}, {
			name : 'paleti',
			index : 'ep.paleti',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		}, {
			name : 'greutate',
			index : 'ep.greutate',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		}, {
			name : 'km_preluare',
			index : 'ep.km_preluare',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		}, {
			name : 'km_livrare',
			index : 'ep.km_livrare',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		}, {
			name : 'valoare_asigurata',
			index : 'ep.valoare_asigurata',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		}, {
			name : 'valoare_expeditie',
			index : 'ep.valoare_expeditie',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 60
		}, {
			name : 'curier_preluare',
			index : 'agp.nume',
			width: 100
		}, {
			name : 'curier_livrare',
			index : 'agl.nume',
			width: 100
		}, {
			name : 'operatiune',
			index : 'ep.operatiune',
			width: 80
		} , {
			name : 'data_op',
			index : 'ep.data_op',
			width: 80
		},
		{
			name : 'Master',
			search : false,
			sortable : false,
			width: 80
		} ],
		rownumbers : true,
		scroll : 1,
		rowNum : 100,
		width : 970,
		height : 295,
		sortname : 'ep.data_expeditie',
		sortorder : "desc",
		viewrecords : true,
		pager : '#paginatie',
		shrinkToFit: false,
		caption:'Lista Expeditii',
		forceFit: true,
		ondblClickRow : function(id) {
			AfisareDetaliiExpeditieCuID(id)
		}
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});

}

function AfisareRapoarrteCentre()
{
	var cond = '';
	if ($('#perioada').is(':checked')) {
		var data_start = $('#data_start').val();
		var data_final = $('#data_final').val();
		cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	}
	if ($('#user').is(':checked')) {
		if ($('#operator_nume').val() != 'Operator') {
			var operator = $('#operator').val();
			cond += '&operator=' + escape(operator);
		}
		if ($('#operatiune').val() > 0) {
			var operatiune = $('#operatiune').val();
			cond += '&operatiune=' + escape(operatiune);
		}
	}
	if ($('#expeditor').is(':checked')) {

		if ($('#expeditor_centru_nume').val() != 'Centru') {
			var expeditor_centru = $('#expeditor_centru').val();
			cond += '&expeditor_centru=' + escape(expeditor_centru);
		}
		if ($('#expeditor_nume').val() != 'Client') {
			var expeditor = $('#expeditor_nume').val();
			cond += '&expeditor=' + escape(expeditor);
		}
	}
	if ($('#destinatar').is(':checked')) {
		if ($('#destinatar').val() != 'Centru') {
			var destinatar_centru = $('#destinatar_centru').val();
			cond += '&destinatar_centru=' + escape(destinatar_centru);
		}
		if ($('#destinatar_nume').val() != 'Client') {
			var destinatar = $('#destinatar_nume').val();
			cond += '&destinatar=' + escape(destinatar);
		}
	}
	if ($('#platitor').is(':checked')) {
		if ($('#platitor').val() != 'Centru') {
			var platitor_centru = $('#platitor_centru').val();
			cond += '&platitor_centru=' + escape(platitor_centru);
		}
		if ($('#platitor_nume').val() != 'Client') {
			var platitor = $('#platitor_nume').val();
			cond += '&platitor=' + escape(platitor);
		}
	}
	if ($('#curier').is(':checked')) {
		if ($('#curier_preluare_nume').val() != 'Preluare') {
			var curier_preluare = $('#curier_preluare').val();
			cond += '&curier_preluare=' + curier_preluare;
		}
		if ($('#curier_livrare_nume').val() != 'Livrare') {
			var curier_livrare = $('#curier_livrare').val();
			cond += '&curier_livrare=' + curier_livrare;
		}
	}
	jQuery("#cautare_expeditii").jqGrid('setGridParam',{url : HTTP + 'expeditii/json/rapoarte_centre?a' + cond}).trigger("reloadGrid");
}

/* //////////////////////////////////////////////////////
				END	CAUTARE EXPEDITII
//////////////////////////////////////////////////////  */


/* //////////////////////////////////////////////////////
				START	URMARIRE EXPEDITII
//////////////////////////////////////////////////////  */
function AfisareOperatiuneUrmarire(sel){
	$('.field_search').hide();
	$('.field_search').val('');
	if(sel==1) $('#search').show();
	else if(sel==2) $('#expeditor_nume').show();
	else if(sel==3) $('#destinatar_nume').show();
	else if(sel==4) $('#centru_nume').show();
}
function AfisareUrmarireExpeditii()
{
	var cond = '';
	var filtru = $('#filtru').val();
	var search1 = $('#search').val();
	var search2 = $('#expeditor_nume').val();
	var search3 = $('#destinatar_nume').val();
	var search4 = $('#centru_nume').val();
	var postExpeditii = '';

	if(filtru==1 && search1 != '' && search1 !='Valoare')
		cond = '&filtru=' + escape(filtru) + '&search='+ escape(search1);
	else if(filtru==2 && search2 != '' && search2 !='Valoare')
		cond = '&filtru=' + escape(filtru) + '&search='+ escape(search2);
	else if(filtru==3 && search3 != '' && search3 !='Valoare')
		cond = '&filtru=' + escape(filtru) + '&search='+ escape(search3);
	else if(filtru==4 && search4 != '' && search4 !='Valoare')
		cond = '&filtru=' + escape(filtru) + '&search='+ escape(search4);

	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);


	if($('#expeditii_area').val().trim().length > 0) {
		var expeditii = $('#expeditii_area').val().split(new RegExp(/[\r\n\x0B\x0C\u0085\u2028\u2029,]+/g)).filter(function (el) {
			return el != null && el != "";
		});
		$('#nr_expeditii_area').html(expeditii.length + " expeditii");
		if(expeditii.length > 201) { AfiseazaEroare("Nu se pot afisa mai mult de 200 awb-uri !"); return false; }
		postExpeditii = $('#expeditii_area').val().replace(/[\r\n\s\t\v\u0085\u2028\u2029,]+/g, ',');
		jQuery("#urmarire_expeditii").jqGrid('setGridParam',{url : HTTP + 'expeditii/json/urmarire', mtype: 'POST', postData: { expeditii: postExpeditii }}).trigger("reloadGrid");
	}
	else {
		jQuery("#urmarire_expeditii").jqGrid('setGridParam',{url : HTTP + 'expeditii/json/urmarire?a' + cond, mtype: 'POST', postData : { expeditii: 0 }}).trigger("reloadGrid");

		if(filtru==1 && search1 != '' && search1 !='Valoare'){
			AfisareContinutIstoricExpeditie(0,search1);
			AfisareContinutUrmarireExpeditie(search1);
		}
	}
}

function ExportUrmarireExpeditii()
{
	var cond = '';
	var filtru = $('#filtru').val();
	var search1 = $('#search').val();
	var search2 = $('#expeditor_nume').val();
	var search3 = $('#destinatar_nume').val();
	var search4 = $('#centru_nume').val();
	if(filtru==1 && search1 != '' && search1 !='Valoare')
		cond = '&filtru=' + escape(filtru) + '&search='+ escape(search1);
	else if(filtru==2 && search2 != '' && search2 !='Valoare')
		cond = '&filtru=' + escape(filtru) + '&search='+ escape(search2);
	else if(filtru==3 && search3 != '' && search3 !='Valoare')
		cond = '&filtru=' + escape(filtru) + '&search='+ escape(search3);
	else if(filtru==4 && search4 != '' && search4 !='Valoare')
		cond = '&filtru=' + escape(filtru) + '&search='+ escape(search4);

	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);

	var filters =  $('#urmarire_expeditii').jqGrid('getGridParam','postData').filters;
	if(filters) cond+='&_search=true&filters='+filters;
	sidx = $('#urmarire_expeditii').jqGrid('getGridParam','sortname');
	if(sidx) cond+='&sidx='+sidx;
	sord = $('#urmarire_expeditii').jqGrid('getGridParam','sortorder');
	if(sord) cond+='&sord='+sord;

	if($('#expeditii_area').val().trim().length > 0) {
		var sel_ids = $('#urmarire_expeditii').jqGrid('getDataIDs');
		cond='expeditii='+sel_ids.join(',');
	}

	$.download('expeditii/export_urmarire_expeditii', cond, false);
}

var rowsToColor = [];
function Grid_UrmarireExpeditii()
{
	SetareDate();
	var grid = jQuery('#urmarire_expeditii');
	getColumnIndexByName = function(mygrid,columnName) {
        var cm = mygrid.jqGrid('getGridParam','colModel');
        for (var i=0,l=cm.length; i<l; i++) {
            if (cm[i].name===columnName) {
                return i; // return the index
            }
        }
        return -1;
    };

	grid.jqGrid(
	{
		url : HTTP + 'expeditii/json/urmarire',
		datatype: 'json',
		mtype: 'POST',
		postData : { expeditii: 0 },
		colNames : [ 'Data Prel','Nr. Exp', 'Tip exp.', 'Tip plata', 'Expeditor','Centru Exp','Destinatar','Centru Dest','Status', 'Data Op', 'Last ckp', 'Data ckp', 'Primitor','Samb', 'NT Scan'],
		colModel : [ {
			name : 'data_expeditie',
			index : 'ep.data_expeditie',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			width: 60,
			align: 'center'
		}, {
			name : 'expeditie',
			index : 'ep.expeditie',
			sorttype : 'int',
			width: 65,
			align: 'right'
		}, {
			name : 'tip_exp',
			width: 60,
			sortable:false,
			index : 'ep.tip_exp',
			formatter:'select',
			stype : 'select',
			searchoptions : {
				value: ":Toate;0:Initiala;1:Retur NT;2:Retur Doc;3:Ramburs;4:Interna;5:Returnare;6:Retur ambalaj;7:Retur Colet;33:Borderou RBS Cash",
				sopt : [ 'eq' ]
			},
			edittype : "select",
			editoptions: {
				value: {0:'Initiala', 1:'Retur NT', 2:'Retur Doc', 3:'Ramburs',4:'Interna',5:'Returnare',6:'Retur ambalaj',7:'Retur Colet',33:'Borderou RBS Cash'},
			}
		},{
			name : 'tip_plata',
			width: 40,
			sortable:false,
			index : 'ep.tip_plata',
			formatter:'select',
			stype : 'select',
			searchoptions: {
				value: ":Toate;0:cash;1:bo;2:cec;3:cont",
				sopt : [ 'eq' ]
			},
			edittype : "select",
			editoptions: {
				value: {0:'cash', 1:'bo', 2:'cec', 3:'cont'}
			},
			align: 'center'
		}, {
			name : 'expeditor',
			index : 'cle.nume',
			width: 150
		}, {
			name : 'expeditor_centru',
			index : 'cee.nume',
			width: 150
		}, {
			name : 'destinatar',
			index : 'cld.nume',
			width: 150
		}, {
			name : 'destinatar_centru',
			index : 'ced.nume',
			width: 150
		}, {
			name : 'operatiune',
			index : 'ep.operatiune',
			stype : 'select',
			searchoptions : {
				value : ":Toate;ABANDONAT:Abandonat;AVARIAT:Avariat;AVIZAT:Avizat;COLECTATA:Colectata;CONFISCAT:Confiscat;DISTRUS:Distrus;EXPEDITIE NESOSITA:Expeditie nesosita;IESIRE DEPOZIT:Iesire depozit;INCASAT:Incasat;LIVRARE NEREUSITA:Livrare nereusita;LIVRAT:Livrat;PRELUATA:Preluata;PIERDUT:Pierdut;PREALERTAT:Prealertat;REAVIZAT:Reavizat;RECEPTIE DEP. LOCAL:Receptie dep. local;RECEPTIE DP. CENTRAL:Receptie dp. central;REDIRECTIONAT:Redirectionat;REEXPEDIAT:Reexpediat;RETINUT:Retinut;RETINUT IN VAMA:Retinut in vama;RETURNAT:Returnat;SPRE LIVRARE:Spre livrare;VAMUIT:Vamuit",
				sopt : [ 'eq' ]
			},
			align: 'center',
			width: 100
			//formatter: rowColorFormatter
		}, {
			name : 'data_op',
			index : 'ep.data_op',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			width: 70,
			align: 'center'
		}, {
			name : 'last_ckp',
			index : 'ckp.abbr',
			width: 40,
			align: 'center'
		}, {
			name : 'last_ckp_data',
			index : 'scckp.last_ckp_data',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d H:i:s',
				newformat : 'd.m.Y H:i'
			},
			width: 80,
			align: 'center'
		},{
			name : 'primitor',
			index : 'ep.primitor',
			width: 150
		}, {
			name : 'liv_samb',
			index : 'ep.liv_samb',
			stype : 'select',
			searchoptions : {
				value : ":Toate;1:DA;0:NU",
				sopt : [ 'eq' ]
			},
			align: 'center',
			width: 60
			//formatter: rowColorFormatter
		}, {
			name : 'folder',
			index : 'folder',
			stype : 'select',
			searchoptions : {
				value : ":Toate;1:DA;0:NU",
				sopt : [ 'eq' ]
			},
			align: 'center',
			width: 60
			//formatter: rowColorFormatter
		} ],
		// rowList : [ 15, 50, 100,250,500,1000,2500,5000,10000 ],
		rownumbers : true,
		scroll : 1,
		rowNum : 100,
		mtype : "GET",
		width : 970,
		height : 350,
		sortname : 'data_expeditie',
		sortorder : "desc",
		viewrecords : true,
		pager : '#paginatie',
		shrinkToFit: false,
		caption:'Lista Expeditii',
		forceFit: true,
		onSelectRow : function(id) {
			var sel = grid.jqGrid('getCell', id, 'expeditie');
			AfisareContinutIstoricExpeditie(0,sel);
			AfisareContinutUrmarireExpeditie(sel);
		},
	    loadComplete: function() {
	            var iCol = getColumnIndexByName($(this),'operatiune'),
	                cRows = this.rows.length, iRow, row, className;
	            var iCol1 = getColumnIndexByName($(this),'liv_samb');

	            for (iRow=0; iRow<cRows; iRow++) {
	                row = this.rows[iRow];
	                className = row.className;
	                if ($.inArray('jqgrow', className.split(' ')) > 0) { // $(row).hasClass('jqgrow')
	                	var status = $(row.cells[iCol]).html();
	                    if (status == "COLECTATA") {
	                        if ($.inArray('myAltRowClass', className.split(' ')) === -1) {
	                            row.className = className + ' myAltRowClass';
	                        }
	                    }
	                }
	            }
	        }
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
}

function Grid_IstoricExpeditii() {
	jQuery("#istoric_expeditii").jqGrid({
		url : HTTP + 'expeditii/json/istoric',
		datatype : "json",
		mtype: 'POST',
		colNames : [ 'Operatiune', 'Data', 'Operator', 'Data operarii' ],
		colModel : [ {
			name : 'operatiune',
			index : 'c.OP_RO',
			sorttype : 'int',
			width: 170
		}, {
			name : 'data',
			index : 'a.DATA',
			sorttype : 'date',
			formatter : 'date',
			datefmt : 'd.m.Y h:i',
			width: 80
		}, {
			name : 'operator',
			index : 'b.USER',
			width: 180
		}, {
			name : 'data_operarii',
			index : 'a.DATA_OP',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d H:i:s',
				newformat : 'd.m.Y H:i'
			},
			width: 100
		} ],
		rowNum :100,
		rowList : [ 15, 50 ],
		rownumbers : true,
		width : 600,
		height : 'auto',
		sortname : 'a.COD_IST',
		sortorder : "desc",
		viewrecords : true,
		pager : '#paginatie_istoric',
		shrinkToFit: false,
		caption:'Istoric Expeditie',
		forceFit: true,
		onSelectRow : function(id) {
			AfisareContinutIstoricExpeditie(id,0);
		}
	});
	jQuery("#istoric_expeditii").jqGrid('bindKeys');
	jQuery("#istoric_expeditii").jqGrid('navGrid', '#paginatie_istoric', {
		del : false,
		add : false,
		edit : false,
		search : false,
		refresh : true
	});
}
function AfisareContinutIstoricExpeditie(sel_ist,sel_exp) {
	$.post(HTTP + 'expeditii/detalii_istoric_expeditie/', {
		exp:sel_exp,
		ist:sel_ist
	 }, function(response) {
		$("#editare_istoric").html(response);
	});
}
function AfisareContinutUrmarireExpeditie(sel) {
	jQuery("#istoric_expeditii").jqGrid('setGridParam', {url : HTTP + 'expeditii/json/istoric?expeditie=' + sel}).trigger("reloadGrid");
}
function VerificareIstoricOperatiune(sel) {
	if (sel != 3) {
		$.get(HTTP + 'expeditii/combo_comentarii_operatiune/' + sel, {},
				function(response) {
					$("#content_comentariu").html(response);
					$('#standard').removeClass('ascuns');
					$('#livrat').addClass('ascuns');
				});
	} else {
		$('#livrat').removeClass('ascuns');
		$('#standard').addClass('ascuns');
	}

	VerificareIstoric();
}

function VerificareIstoric() {
	var data_operatiei_old = $('#data_operatiei_old').val();
	var operatie_old = $('#operatie_old').val();
	var comentariu_old = $('#comentariu_old').val();
	var ag_liv_old = $('#ag_liv_old').val();
	var primitor_old = $('#primitor_old').val();

	var data_operatiei = $('#data_operatiei').val();
	var operatie = $('#operatie').val();
	var comentariu = $('#comentariu_operatiune').val();
	var ag_liv = $('#ag_liv').val();
	var primitor = $('#primitor').val();

	if (data_operatiei_old != data_operatiei) {
		$('#field_data_operatiei').addClass('camp_modificat');
	} else {
		$('#field_data_operatiei').removeClass('camp_modificat');
	}

	if (operatie_old != operatie) {
		$('#field_operatie').addClass('camp_modificat');
	} else {
		$('#field_operatie').removeClass('camp_modificat');
	}

	if (comentariu_old != comentariu)
		$('#field_comentariu').addClass('camp_modificat');
	else
		$('#field_comentariu').removeClass('camp_modificat');

	if (ag_liv_old != ag_liv)
		$('#field_ag_liv').addClass('camp_modificat');
	else
		$('#field_ag_liv').removeClass('camp_modificat');

	if (primitor_old != primitor)
		$('#field_primitor').addClass('camp_modificat');
	else
		$('#field_primitor').removeClass('camp_modificat');
}

function EditareIstoric(sel) {
	var expeditie = $('#expeditie').val();
	var cod_ist = $('#cod_ist').val();
	var cod_exp = $('#cod_exp').val();
	var operatie = $('#operatie').val();
	var data_operatiei = $('#data_operatiei').val();
	var curier = $('#AG_LIV').val();
	var curier_nume = $('#AG_LIV_NUME').val();
	var pastreaza_curier = 0;
	var inchide_retur = 0;
	if ($('#AG_LIV_RETINUT').is(':checked')) pastreaza_curier = 1;
	if ($('#inchide_ret').is(':checked')) inchide_retur = 1;
	var comentariu_operatie = $('#comentariu_operatiune').val();
	var primitor = $('#primitor').val();
	var sel_id = $('#cod_exp').val();
	$('#expeditie').select();
	$.post(HTTP+ 'expeditii/modificare_istoric_expeditie',
	{
		cod_ist : cod_ist,
		cod_exp : cod_exp,
		expeditie : expeditie,
		operatie : operatie,
		data_operatiei : data_operatiei,
		curier : curier,
		curier_nume : curier_nume,
		comentariu_operatie : comentariu_operatie,
		primitor : primitor,
		pastreaza_curier : pastreaza_curier,
		inchide_retur : inchide_retur,
		type : sel
	},
	function(response) {
		update = response.split('|||');
		PrepareResponse(update[0]);
		if (update[0] == 1) {
			$('#mesaj_expeditie').addClass('green');
			$('#mesaj_expeditie').html(update[1]);
			$('#expeditie').select();
			AfisareContinutUrmarireExpeditie(expeditie);
		} else {
			AfiseazaEroare(update[1]);
		}
	});
}

function EditareIstoric2(sel) {
	$("#mesaj_confirmare")
			.dialog(
					{
						modal : true,
						draggable : false,
						height : 130,
						width : 200,
						resizable : false,
						buttons : {
							'Da' : function() {
								var expeditie = $('#expeditie').val();
								var cod_ist = $('#cod_ist').val();
								var cod_exp = $('#cod_exp').val();
								var operatie = $('#operatie').val();
								var data_operatiei = $('#data_operatiei').val();
								var curier = $('#AG_LIV').val();
								var curier_nume = $('#AG_LIV_NUME').val();
								var pastreaza_curier = 0;
								var inchide_retur = 0;
								if ($('#AG_LIV_RETINUT').is(':checked')) pastreaza_curier = 1;
								if ($('#inchide_ret').is(':checked')) inchide_retur = 1;
								var comentariu_operatie = $('#comentariu_operatiune').val();
								var primitor = $('#primitor').val();
								var sel_id = $('#cod_exp').val();
								$.post(HTTP+ 'expeditii/modificare_istoric_expeditie',
								{
									cod_ist : cod_ist,
									cod_exp : cod_exp,
									expeditie : expeditie,
									operatie : operatie,
									data_operatiei : data_operatiei,
									curier : curier,
									curier_nume : curier_nume,
									comentariu_operatie : comentariu_operatie,
									primitor : primitor,
									pastreaza_curier : pastreaza_curier,
									inchide_retur : inchide_retur,
									type : sel
								},
								function(response) {
									update = response.split('|||');
									PrepareResponse(update[0]);
									if (update[0] == 1) {
										$('#mesaj_expeditie').addClass('green');
										$('#mesaj_expeditie').html(update[1]);
										AfisareContinutUrmarireExpeditie(expeditie);
										$('#expeditie').select();
									} else {
										AfiseazaEroare(update[1]);
									}
								});
								$(this).dialog("destroy");
							},
							'Nu' : function() {
								$(this).dialog("destroy");
							}
						}
					});
}
/* //////////////////////////////////////////////////////
				END	URMARIRE EXPEDITII
//////////////////////////////////////////////////////  */

/* //////////////////////////////////////////////////////
				START	LISTE EXPEDITII
//////////////////////////////////////////////////////  */

function SchimbareLista(sel){
	if(sel==3)
		$('#lista_centre').show();
	else
	{
		$('#lista_centre').hide();
		$('#centru').val('0');
	}
}
function CreareConditieListeExpeditii(){
	var cond = '';
	var centru = $('#centru').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	var operatiune = $('#operatiune').val();
	cond += '&operatiune=' + escape(operatiune);
	if(operatiune == 1) centru = 47;
	if (centru > 0) {
		cond += '&centru='+ escape(centru);
		ListeExpeditiiAgenti(centru);
		ListeExpeditiiDetalii(centru);
	}
	jQuery("#liste_expeditii").jqGrid('setGridParam', {
		 url : HTTP + 'expeditii/json/liste_expeditii?a' + cond
	}).trigger("reloadGrid");
}

function AfisareListeExpeditii(){
	var cond = '';
	var centru = $('#centru').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	var operatiune = $('#operatiune').val();
	cond += '&operatiune=' + escape(operatiune);
	if(operatiune==1) centru = 47;
	else if (centru > 0) {
		cond += '&centru='+ escape(centru);
		ListeExpeditiiAgenti(centru);
		ListeExpeditiiDetalii(centru);
	}
	jQuery("#liste_expeditii").jqGrid('setGridParam', {
		 url : HTTP + 'expeditii/json/liste_expeditii?a' + cond
	}).trigger("reloadGrid");
}

function VizualizareListeExpeditii(){
	var centru = $('#centru').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var operatiune = $('#operatiune').val();
	if(operatiune==1) centru = 47;

	$.post(HTTP + 'expeditii/vizualizare_liste_expeditie/', {centru : centru,data_start:data_start,data_final:data_final,operatiune:operatiune}, function(response) {
		$("#detalii_expeditie").html(response);
		$("#detalii_expeditie:ui-dialog").dialog("destroy");
		$("#detalii_expeditie").dialog({
			width : 770,
			height : 650,
			modal : true,
			draggable : false,
			resizable : false,
			title : 'Centralizator Expeditii'
		});
	});
}

function PrintListeExpeditii(){
	var centru = $('#centru').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var operatiune = $('#operatiune').val();
	if(operatiune==1) centru = 47;

	$.post(HTTP + 'expeditii/vizualizare_liste_expeditie/', {centru : centru,data_start:data_start,data_final:data_final,operatiune:operatiune}, function(response) {
		$("#detalii_expeditie").html(response).show();
		$('#detalii_expeditie').printElement(
		{
            leaveOpen:false,
            printMode:'modal',
            title : 'Centralizator Expeditii',
            overrideElementCSS:[
				HTTP+'assets/css/style_screen.css'
				 ,{href:HTTP+'assets/css/style_print.css',media:'print'}
				]

        });
        $("#detalii_expeditie").hide();
        $("#detalii_expeditie").html('');
	});
}

function ExportListeExpeditii(){
	var centru = $('#centru').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var operatiune = $('#operatiune').val();
	if(operatiune==1) centru = 47;
	else if(operatiune==2) centru = 47;

	var url = '';
	var url = HTTP + 'expeditii/export_liste_expeditii?a&centru='+centru+'&data_start='+data_start+'&data_final='+data_final+'&operatiune='+operatiune;
    window.location = url;
}

function Grid_ListeExpeditii() {
	SetareDate();

	var grid = jQuery('#liste_expeditii');

	grid.jqGrid({
				url : HTTP + 'expeditii/json/liste_expeditii',
				datatype : "json",
				colNames : [ 'Centru', 'Nr. Expeditii', 'Greutate', 'Plicuri','Colete', 'Paleti', 'Nr. Km','Samb' ],
				colModel : [ {
					name : 'destinatar_centru',
					index : 'destinatar_centru'
				}, {
					name : 'expeditii',
					sortable:false,
					index : 'ep.expeditii'
				}, {
					name : 'greutate',
					sortable:false,
					align : 'right'
				}, {
					name : 'plicuri',
					sortable:false,
					align : 'right'
				}, {
					name : 'colete',
					sortable:false,
					align : 'right'
				}, {
					name : 'paleti',
					sortable:false,
					align : 'right'
				}, {
					name : 'km',
					sortable:false,
					align : 'right'
				}, {
					name : 'liv_sambata',
					sortable:false,
					align : 'right'
				} ],
				rowNum : 15,
				rowList : [ 15, 50, 100 ],
				autowidth : true,
				width : 970,
				height : 'auto',
				mtype : "GET",
				rownumbers : false,
				gridview : true,
				sortname : 'destinatar_centru',
				viewrecords : true,
				sortorder : "ASC",
				pager : '#paginatie',
				caption : 'Expeditii repartizate pe centre',
				multiselect : false,
				subGrid : false,
				onSelectRow : function(id) {
					ListeExpeditiiDetalii(id);
				}
			});
		grid.jqGrid('bindKeys');
}

function Grid_ListeExpeditiiDetalii() {
	jQuery("#liste_expeditii_detalii").jqGrid(
			{
				url : HTTP + 'expeditii/json/liste_expeditii_detalii',
				datatype : "json",
				colNames : ['Localitate','Destinatar','Nr.Exp', 'Greutate','Plicuri','Colete','Paleti','Km','Samb'],
				colModel : [ {
					name : 'destinatar_localitate',
					index : 'lcd.nume_lc',
					width : 80
				}, {
					name : 'destinatar',
					index : 'cld.nume',
					width : 90
				}, {
					name : 'expeditii',
					index : 'expeditii',
					sortable:false,
					width : 40
				}, {
					name : 'greutate',
					index : 'greutate',
					align : 'right',
					sortable:false,
					width : 50
				}, {
					name : 'plicuri',
					index : 'plicuri',
					align : 'right',
					sortable:false,
					width : 40
				}, {
					name : 'colete',
					index : 'colete',
					sortable:false,
					align : 'right',
					width : 40
				}, {
					name : 'paleti',
					index : 'paleti',
					sortable:false,
					align : 'right',
					width : 30
				}, {
					name : 'km',
					index : 'km',
					sortable:false,
					align : 'right',
					width : 30
				}, {
					name : 'liv_sambata',
					index : 'liv_sambata',
					sortable:false,
					align : 'right',
					width : 30
				} ],
				rowNum : 15,
				rowList : [ 15, 50, 100 ],
				width : 600,
				height : 'auto',
				mtype : "GET",
				rownumbers : false,
				gridview : true,
				sortname : 'destinatar',
				viewrecords : true,
				sortorder : "ASC",
				pager : '#paginatie_detalii',
				caption : 'Expeditii pentru centrul selectat',
				multiselect : false,
				subGrid: true,
				subGridRowExpanded: function(subgrid_id, row_id) {
					var subgrid_table_id, pager_id;
					subgrid_table_id = subgrid_id+"_t";
					pager_id = "p_"+subgrid_table_id;
					$("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table><div id='"+pager_id+"' class='scroll'></div>");

					var centru = $('#centru').val();
					var operatiune = $('#operatiune').val();
					var cond2 = '&data_start=' + escape($('#data_start').val()) + '&data_final='+ escape($('#data_final').val());
					cond2 += '&operatiune=' + escape(operatiune);
					if(operatiune==1) centru = 47;

					jQuery("#"+subgrid_table_id).jqGrid({
						url : HTTP + 'expeditii/json/liste_expeditii_detalii_client?centru='+centru+'&client='+row_id+cond2,
						datatype : "json",
						colNames : ['Expeditie','Expeditor','Localitate', 'Greutate','Plicuri','Colete','Paleti','Km','Samb'],
						colModel : [ {
							name : 'expeditie',
							index : 'ep.expeditie',
							width : 80
						}, {
							name : 'expeditor',
							index : 'cle.nume',
							width : 110
						}, {
							name : 'expeditor_localitate',
							index : 'lce.nume_lc',
							width : 110
						}, {
							name : 'greutate',
							index : 'ep.greutate',
							align : 'right',
							width : 50
						}, {
							name : 'plicuri',
							index : 'ep.plicuri',
							align : 'right',
							width : 40
						}, {
							name : 'colete',
							index : 'ep.colete',
							align : 'right',
							width : 40
						}, {
							name : 'paleti',
							index : 'ep.paleti',
							align : 'right',
							width : 30
						}, {
							name : 'km',
							index : 'km',
							align : 'right',
							width : 30
						}, {
							name : 'liv_samb',
							index : 'ep.liv_samb',
							align : 'right',
							width : 30
						} ],
						rowNum : 20,
					   	pager: pager_id,
					   	sortname: 'cle.nume',
					    sortorder: "asc",
					    height: '100%',
					    ondblClickRow : function(id) {
							AfisareDetaliiExpeditieCuID(id)
						},
					});
					jQuery("#"+subgrid_table_id).jqGrid('navGrid',"#"+pager_id,{edit:false,add:false,del:false})
				},
				subGridRowColapsed: function(subgrid_id, row_id) {
					// this function is called before removing the data
					//var subgrid_table_id;
					//subgrid_table_id = subgrid_id+"_t";
					//jQuery("#"+subgrid_table_id).remove();
				}
			});
	jQuery("#liste_expeditii_detalii").jqGrid('bindKeys');
}

function ListeExpeditiiDetalii(id) {
	var cond = 'centru='+id;
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	jQuery("#liste_expeditii_detalii").jqGrid('setGridParam', {
		url : HTTP + 'expeditii/json/liste_expeditii_detalii?'+cond
	}).trigger("reloadGrid");
	ListeExpeditiiAgenti(id);
}


function Grid_ListeExpeditiiAgenti() {
	jQuery("#liste_expeditii_agenti").jqGrid(
			{
				url : HTTP + 'expeditii/json/liste_expeditii_agenti',
				datatype : "json",
				colNames : ['Nume','Contact'],
				colModel : [ {
					name : 'nume',
					index : 'nume_ag',
					width : 150
				}, {
					name : 'Contact',
					index : 'telefon',
					width : 180
				} ],
				rowNum : 15,
				rowList : [ 15, 50, 100 ],
				width : 330,
				height : 'auto',
				mtype : "GET",
				rownumbers : false,
				gridview : true,
				sortname : 'nume_ag',
				viewrecords : true,
				sortorder : "desc",
				pager : '#paginatie_agenti',
				caption : 'Agenti pentru centrul selectat',
				multiselect : false,
				subGrid : false,
			});
	jQuery("#liste_expeditii_agenti").jqGrid('bindKeys');
}

function ListeExpeditiiAgenti(id) {
	var cond = 'centru='+id;
	jQuery("#liste_expeditii_agenti").jqGrid('setGridParam', {
		url : HTTP + 'expeditii/json/liste_expeditii_agenti?'+cond
	}).trigger("reloadGrid");
}

function ComboCentre(field) {
	$("#" + field + "_NUME").autocomplete({
		source : function(request, response) {
			var cod_lc = $("#EXPEDITOR_LOCALITATE").val();
			$.ajax({
				url : HTTP + "expeditii/json/centre",
				dataType : "json",
				data : {
					maxRows : 20,
					cod_lc : cod_lc,
					name_startsWith : request.term
				},
				success : function(data) {
					response($.map(data.rezultat, function(item) {
						return {
							value : item.cod,
							label : item.nume
						}
					}));
				}
			});
		},
		minLength : 1,
		focus : function(event, ui) {
			$("#" + field + "_NUME").val(ui.item.label);
			if($("#" + field + "_NUME").val()=='') $("#" + field).val(0);
			return false;
		},
		select : function(event, ui) {
			$("#" + field).val(ui.item.value);
			if($("#" + field + "_NUME").val()=='') $("#" + field).val(0);
			return false;
		},
		change : function() {
			if($("#" + field + "_NUME").val()=='') $("#" + field).val(0);
			return true;
		},
		open : function() {
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
		},
		close : function() {
			if($("#" + field + "_NUME").val()=='') $("#" + field).val(0);
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}

function ComboCentre1(field) {
	$("#"+field+'_nume').autocomplete({
		source : function(request, response) {
			$.ajax({
				url : HTTP + "expeditii/json/centre",
				dataType : "json",
				data : {
					maxRows : 20,
					name_startsWith : request.term
				},
				success : function(data){
					response($.map(data.rezultat, function(item) {
						return {
							value : item.cod,
							label : item.nume
						}
					}));
				}
			});
		},
		minLength : 3,
		focus : function(event, ui) {
			$("#" + field + "_nume").val(ui.item.label);
			return false;
		},
		select : function(event, ui) {
			$("#" + field).val(ui.item.value);
			return false;
		},
		change : function(event, ui) {
			return true;
		},
		open: function() {
			$("#"+field).val('');
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
		},
		close : function() {
			if($("#" + field + "_nume").val()=='') $("#" + field).val(0);
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}
/* //////////////////////////////////////////////////////
				END	LISTE EXPEDITII
//////////////////////////////////////////////////////  */






/* //////////////////////////////////////////////////////
				START	RAPOARTE LISTE EXPEDITII
//////////////////////////////////////////////////////  */

function Grid_RapoarteListeExpeditii() {
	SetareDate();
	var grid = jQuery('#liste_expeditii');

	grid.jqGrid({
		url : HTTP + 'expeditii/json/rapoarte_liste_expeditii',
		datatype : "json",
		colNames : [ 'Expeditie', 'Nr. Piese', 'Greutate','Expeditor','Destinatar','Ag. Expeditie','Ag. Destinatie', 'Colectare'],
		colModel : [ {
			name : 'expeditie',
			index : 'ep.expeditie',
			width: 80
		}, {
			name : 'nr_piese',
			sortable:false,
			search:false,
			align : 'right',
			width: 50
		}, {
			name : 'greutate',
			index : 'ep.greutate',
			align : 'right',
			width: 80
		}, {
			name : 'expeditor',
			index : 'cle.nume',
			width: 150
		}, {
			name : 'destinatar',
			index : 'cld.nume',
			width: 150
		}, {
			name : 'expeditor_centru',
			index : 'cee.nume',
			width: 130
		}, {
			name : 'destinatar_centru',
			index : 'ced.nume',
			width: 140
		}, {
			name : 'ep.data_expeditie',
			sortable:false,
			search:false,
			width: 100
		} ],
		rowNum : 15,
		rowList : [ 15, 50, 100,250,500,1000,2500,5000,10000,20000,30000 ],
		rownumbers : true,
		width : 970,
		height : 330,
		sortname : 'ep.data_expeditie',
		sortorder : "desc",
		viewrecords : true,
		pager : '#paginatie',
		shrinkToFit: false,
		caption:'Lista Expeditii',
		forceFit: true,
		ondblClickRow : function(id) {
			AfisareDetaliiExpeditieCuID(id)
		},
		footerrow: true,
	    userDataOnFooter: true
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
}

function CreareConditieRapoarteListeExpeditii(){
	var cond = '';
	var categorie = $('#categorie').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	$('#lista_centre_expeditie').hide();
	$('#lista_centre_destinatie').hide();
	$('#ascunde_centre_expeditie').hide();
	$('#ascunde_centre_destinatie').hide();
	$('#afiseaza_centre_expeditie').show();
	$('#afiseaza_centre_destinatie').show();

	var centre_expeditie='';
	$.each($('#export_lista .centre_expeditie').serializeArray(), function(i, field) {
	    centre_expeditie += '_'+field.value;
	});
	centre_expeditie = centre_expeditie.substr(1);
	var centre_destinatie='';
	$.each($('#export_lista .centre_destinatie').serializeArray(), function(i, field) {
	    centre_destinatie += '_'+field.value;
	});
	cond = 'categorie='+categorie+'&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	cond += '&centre_destinatie='+centre_destinatie+'&centre_expeditie='+centre_expeditie;
	if(centre_destinatie.length==0 || centre_expeditie.length==0){
		AfiseazaEroare('Selectati centrele!');
		return false;
	}

	return cond;
}


function AfisareRapoarteListeExpeditii() {
	var cond = CreareConditieRapoarteListeExpeditii();
	if(cond){
		jQuery("#liste_expeditii").jqGrid('setGridParam', {
			url : HTTP + 'expeditii/json/rapoarte_liste_expeditii?'+cond
		}).trigger("reloadGrid");
	}
	return true;
}

function PrintRapoarteListeExpeditii(){
	var cond = CreareConditieRapoarteListeExpeditii();
	if(cond){
		$.post(HTTP + 'expeditii/print_rapoarte_expeditii?a&'+cond,{},function(response) {
			$("#detalii_expeditie").html(response);
			$("#detalii_expeditie").html(response).show();
			$('#detalii_expeditie').printElement(
			{
		        leaveOpen:false,
		        printMode:'iframe',
		        pageTitle: 'Raport Expeditii',
		        overrideElementCSS:[
					HTTP+'assets/css/style_screen.css'
					,{href:HTTP+'assets/css/style_print.css',media:'print'}
					]

		    });
		    $("#detalii_expeditie").hide();
		    $("#detalii_expeditie").html('');
		});
	}
}


function ExportRapoarteListeExpeditii(){
	var cond = CreareConditieRapoarteListeExpeditii();
	var url = HTTP + 'expeditii/export_rapoarte_expeditii?a&' + cond
    window.location = url;
}

/* //////////////////////////////////////////////////////
				END	RAPOARTE LISTE EXPEDITII
//////////////////////////////////////////////////////  */

function _Functii_Clienti(){}
function ClientiListare() {
	$("#clienti_listare").jqGrid({
		url : HTTP + 'clienti/listare_json',
		datatype : "json",
		colNames : [ 'Nr. cont', 'Master', 'Nume', 'Localitate', 'Contract', 'Mod Plata', 'Tip Plata', 'Status', 'Cod fiscal', 'Nume societate', 'Email factura', 'Tarif Manual', 'Tarif Individual', 'Tip facturare','Stop credit', 'Blocat', 'Proc. maj.', 'Ag. vanzari' ],
		colModel : [ {
            name : 'cod_cl',
            index : 'c.cod_cl',
            width : 50,
			sorttype : 'int',
			searchoptions:{sopt : [ 'eq' ]}
        },{
            name : 'master',
            index : 'c.master',
            width : 50,
            sorttype : 'int',
			searchoptions:{sopt : [ 'eq' ]}
        },{
			name : 'nume',
			index : 'c.NUME',
			width : 170,
			searchoptions:{sopt : [ 'bw' ]}
        }, {
			name : 'localitate',
			index : 'lc.nume_lc',
            width : 60,
			searchoptions:{sopt : [ 'bw' ]}
		}, {
			name : 'tarif',
			index : 'c.TARIF',
			stype : 'select',
            width : 60,
			searchoptions : {
				value : ":Toate;0:Nu;1:T. negociat;2:T. lista",
				sopt : [ 'eq' ]
			},
		}, {
			name : 'mod_plata',
			index : 'c.MOD_PLATA',
			stype : 'select',
            width : 60,
			searchoptions : {
				value : ":Toate;0:Per NT;1:Factura periodica",
				sopt : [ 'eq' ]
			},
		}, {
			name : 'tip_plata',
			index : 'c.TIP_PLATA',
			stype : 'select',
            width : 60,
			searchoptions : {
				value : ":Toate;0:Cash;1:Virament",
				sopt : [ 'eq' ]
			},
		},{
			name : 'activ',
			index : 'c.ACTIV',
			stype : 'select',
            width : 60,
			searchoptions : {
				value : ":Toate;0:Inactiv;1:Activ",
				sopt : [ 'eq' ]
			},
		}, {
			name : 'cod_fiscal',
			index : 'c.COD_FISCAL',
            width : 60,
		}, {
            name : 'nume_societate',
            index : 'c.NUME_SOCIETATE',
            width : 170,
			searchoptions:{sopt : [ 'bw' ]}
        }, {
            name : 'EMAIL_FACTURA',
            index : 'c.EMAIL_FACTURA',
            width : 150,
			searchoptions:{sopt : [ 'bw' ]}
        }, {
            name : 'TARIF_MANUAL',
            index : 'c.TARIF_MANUAL',
            width : 60,
			searchoptions:{sopt : [ 'eq' ]}
        },{
            name : 'TARIF_INDIVIDUAL',
            index : 'c.TARIF_INDIVIDUAL',
            width : 60,
			searchoptions:{sopt : [ 'eq' ]}
        }, {
			name : 'TIP_FACTURARE',
			index : 'c.TIP_FACTURARE',
			width : 60,
			searchoptions:{sopt : [ 'eq' ]}
		}, {
			name : 'OBS_SC',
			index : 'c.OBS_SC',
			width : 60,
		}, {
			name : 'OBS_BL',
			index : 'c.OBS_BL',
			width : 60,
		},{
			name : 'PROC_MAJ',
			index : 'c.ff_discount',
			width : 60,
		},{
			name : 'ag_vanzari',
			index : 'ag.nume',
			width : 100,
			searchoptions:{sopt : [ 'bw' ]}
        } ],
		height : 450,
		width : 580,
		scroll : 1,
		rowNum : 100,
		mtype : "GET",
		rownumbers : true,
		rownumWidth : 20,
        shrinkToFit:false,
        forceFit:true,
		gridview : true,
		pager : '#clienti_listare_pag',
		sortname : 'c.NUME',
		viewrecords : true,
		sortorder : "asc",
		caption : 'Listare Clienti',
		rowattr: function (rd) {
                    if (rd.tarif != 'Nu') {
                        return {"class": "myAltRowClass"};
            	}},
		//editurl : HTTP + 'clienti/editare?q=dummy',
		onSelectRow : function(id) {
            	ClientiDetalii(id);

            }
	});
	$("#clienti_listare").jqGrid('bindKeys');
	$("#clienti_listare").jqGrid('filterToolbar', {
		stringResult : true,
		searchOnEnter : true
	});
	$("#clienti_listare").jqGrid('navGrid', '#agenti_listare_pag', {
		del : false,
		add : true,
		edit : false,
		search : false
	});
}

function ClientiListareContacte() {
	$("#contacte_clienti_listare").jqGrid({
		url : HTTP + 'clienti/contacte_json',
		datatype : "json",
		colNames : [ 'Nume', 'Telefon' ],
		colModel : [ {
			name : 'nume',
			index : 'NUME_PERS'
		}, {
			name : 'contact',
			index : 'CONTACT'
		} ],
		height : 120,
		width : 350,
		scroll : 1,
		rowNum : 10,
		mtype : "GET",
		rownumbers : true,
		rownumWidth : 30,
		gridview : true,
		pager : '#contacte_clienti_listare_pag',
		sortname : 'NUME_PERS',
		viewrecords : false,
		sortorder : "asc",
		caption : false,
		onSelectRow : function(id) {
			ClientiDetaliiContacte(id);
		}
	});
	$("#contacte_clienti_listare").jqGrid('bindKeys');
	$("#contacte_clienti_listare").jqGrid('navGrid', '#agenti_listare_pag', {
		del : false,
		add : false,
		edit : false,
		search : false
	});
}

function ClientiDetaliiContacte(sel) {
	$.get(HTTP + 'clienti/contacte_client/' + sel, {}, function(response) {
		$("#contacte_client").html(response);
		$('#COD_CONT_CL').val(sel);
	});
}

function ClientiComboLocalitati(field) {
	$("#" + field + "_NUME").autocomplete({
		source : function(request, response) {
			$.ajax({
				url : HTTP + "expeditii/json/localitati_old",
				dataType : "json",
				data : {
					maxRows : 20,
					name_startsWith : request.term
				},
				success : function(data) {
					response($.map(data.rezultat, function(item) {
						return {
							value : item.value,
							label : item.label,
							cod_lc : item.cod_lc
						}
					}));
				}
			});
		},
		minLength : 2,
		select : function(event, ui) {
			$("#" + field + "_NUME").val(ui.item.value);
			$("#" + field).val(ui.item.cod_lc);
			return false;
		},
		change : function(event, ui) {
			if($("#" + field + "_NUME").val() == '')
				$("#" + field).val("");
		},
		open : function() {
			$("#" + field).val('');
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}

function ClientiComboLocalitatiOnly(field) {
	$("#" + field + "_nume").autocomplete({
		source : function(request, response) {
			$.ajax({
				url : HTTP + "comenzi/json/localitati_only",
				dataType : "json",
				data : {
					maxRows : 20,
					name_startsWith : request.term
				},
				success : function(data) {
					response($.map(data.rezultat, function(item) {
						return {
							id : item.cod,
							label : item.nume,
							value : item.localitate
						}
					}));
				}
			});
		},
		minLength : 2,
		select : function(event, ui) {
			$("#" + field +'_id').val(ui.item.id);
			$("#" + field + "_nume").val(ui.item.value);
		},
		open : function(event, ui) {
			$("#" + field +'_id').val("");
		},
		change : function(event, ui) {
			if($("#" + field + "_nume").val() == '')
			{
				$("#" + field +'_id').val("");
			}
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}

function ClientiComboAgentiVanzari(field) {
	$("#" + field + "_NUME").autocomplete({
		source : function(request, response) {
			$.ajax({
				url : HTTP + "clienti/json/ag_vanzari",
				dataType : "json",
				data : {
					maxRows : 15,
					name_startsWith : request.term
				},
				success : function(data) {
					response($.map(data.rezultat, function(item) {
						return {
							id : item.id,
							label : item.label,
							value : item.value
						}
					}));
				}
			});
		},
		minLength : 1,
		select : function(event, ui) {
			$("#" + field).val(ui.item.id);
			$("#" + field + "_NUME").val(ui.item.value);
		},
		open : function(event, ui) {
			$("#" + field).val("");
		},
		change : function(event, ui) {
			if($("#" + field + "_NUME").val() == '')
			{
				$("#" + field).val("");
			}
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}

function ClientiEditare(sel){
	$('.raspuns_detalii').html('');
	if ($('#ACTIV').is(':checked')) $('#ACTIV').val(1);
	else $('#ACTIV').val(0);
	if ($('#CONT_ACTIV').is(':checked')) $('#CONT_ACTIV').val(1);
	else $('#CONT_ACTIV').val(0);
	if ($('#persoana_fizica').is(':checked')) $('#persoana_fizica').val(1);
	else $('#persoana_fizica').val(0);
	if ($('#CC').is(':checked')) $('#CC').val(1);
	else $('#CC').val(0);
	if ($('#ICC').is(':checked')) $('#ICC').val(1);
	else $('#ICC').val(0);
	if ($('#RET_RBS').is(':checked')) $('#RET_RBS').val(1);
	else $('#RET_RBS').val(0);
	if ($('#OBS_RP').is(':checked')) $('#OBS_RP').val(1);
	else $('#OBS_RP').val(0);
	if ($('#OBS_OC').is(':checked')) $('#OBS_OC').val(1);
	else $('#OBS_OC').val(0);
    if ($('#OBS_SC').is(':checked')) $('#OBS_SC').val(1);
    else $('#OBS_SC').val(0);
    if ($('#OBS_BL').is(':checked')) $('#OBS_BL').val(1);
	else $('#OBS_BL').val(0);
	var RBS_DAYS = $("input:checkbox[name='rbs_days']").filter(":checked").map(function() {
		return $(this).attr('id');
	  }).get().join();

	$.post(HTTP + 'clienti/editare_client', {
		metoda : sel,
		MASTER : $('#MASTER').val(),
		USER_ID : $('#USER_ID').val(),
		CONTRACT : $('#CONTRACT').val(),
		DATA_CONTRACT : $('#DATA_CONTRACT').val(),
		COD_CL : $('#COD_CL').val(),
		persoana_fizica : $('#persoana_fizica').val(),
		ACTIV : $('#ACTIV').val(),
		CONT_ACTIV : $('#CONT_ACTIV').val(),
		NUME : $('#NUME').val(),
		COD_LC : $('#LOCALITATE').val(),
		LOCALITATE : $('#LOCALITATE_NUME').val(),
		ADRESA : $('#ADRESA').val(),
		KM_EXT : $('#KM_EXT').val(),
		REG_COM : $('#REG_COM').val(),
		COD_FISCAL : $('#COD_FISCAL').val(),
		CONT : $('#CONT').val(),
		BANCA : $('#BANCA').val(),
		CONT_RBS : $('#CONT_RBS').val(),
		BANCA_RBS : $('#BANCA_RBS').val(),
		TARIF : $('#TARIF').val(),
		MOD_PLATA : $('#MOD_PLATA').val(),
		TIP_PLATA : $('#TIP_PLATA').val(),
		CONTACT : $('#CONTACT').val(),
		OBS_RP : $('#OBS_RP').val(),
        OBS_SC : $('#OBS_SC').val(),
        OBS_BL : $('#OBS_BL').val(),
		CONT_NUME : $('#CONT_NUME').val(),
		CONT_EMAIL : $('#CONT_EMAIL').val(),
		CONT_TELEFON : $('#CONT_TELEFON').val(),
		PAROLA : $('#PAROLA').val(),
		AFISARE_PRETURI : $('input:radio[name=AFISARE_PRETURI]:checked').val(),
		IMPORT_CSV : $('input:radio[name=IMPORT_CSV]:checked').val(),
		RECANTARITE : $('input:radio[name=RECANTARITE]:checked').val(),
		SELECTIE_PUNCTE_DE_LUCRU : $('input:radio[name=SELECTIE_PUNCTE_DE_LUCRU]:checked').val(),
		SHOW_MASTER_CLIENTI : $('input:radio[name=SHOW_MASTER_CLIENTI]:checked').val(),
		DE_LA : $('#DE_LA').val(),
		PANA_LA : $('#PANA_LA').val(),
		CC : $('#CC').val(),
		ICC : $('#ICC').val(),
		RET_RBS : $('#RET_RBS').val(),
		AG_VANZARI : $('#AG_VANZARI').val(),
		TERMEN_PLATA : $('#TERMEN_PLATA').val(),
		TIP_TVA : $('#TIP_TVA').val(),
		FACTURARE_TIP_TRANZACTIE : $('#FACTURARE_TIP_TRANZACTIE').val(),
		TVA_INCASARE : $('#TVA_INCASARE').val(),
        TIP_FACTURARE : $('#TIP_FACTURARE').val(),
		DATA_FACTURARE : $('#DATA_FACTURARE').val(),
		TARIF_INDIVIDUAL : $('#TARIF_INDIVIDUAL').is(':checked'),
        FACTURARE_SEPARATA : $('#FACTURARE_SEPARATA').is(':checked'),
		FACTURARE_FARA_TVA : $('#FACTURARE_FARA_TVA').is(':checked'),
		FARA_FACTURA : $('#FARA_FACTURA').is(':checked'),
        BORDEROU_PDF : $('#BORDEROU_PDF').is(':checked'),
        FF_ULTIMA_ZI : $('#FF_ULTIMA_ZI').is(':checked'),
        TARIF_MANUAL : $('#TARIF_MANUAL').is(':checked'),
		VALOARE_MAXIMA_FACTURA : $('#VALOARE_MAXIMA_FACTURA').val(),
		NUME_SOCIETATE : $('#NUME_SOCIETATE').val(),
		COD_LC_SEDIU_SOCIAL : $('#LOCALITATE_SEDIU_SOCIAL').val(),
		LOCALITATE_SEDIU_SOCIAL : $('#LOCALITATE_SEDIU_SOCIAL_NUME').val(),
		ADRESA_SEDIU_SOCIAL : $('#ADRESA_SEDIU_SOCIAL').val(),
		EMAIL_FACTURA : $('#EMAIL_FACTURA').val(),
		RBS_DAYS : RBS_DAYS,
		PROC_MAJ : $('#PROC_MAJ').val(),
	}, function(response) {
		var rasp = response.split('|||');
		PrepareResponse(rasp[0]);
		if (rasp[0] == 1) {
			$('.raspuns_detalii').css('color','green');
			$('.raspuns_detalii1').css('color','green');
			$('.raspuns_detalii2').css('color','green');
		}
		else {
			$('.raspuns_detalii').css('color','red');
			$('.raspuns_detalii1').css('color','red');
			$('.raspuns_detalii2').css('color','red');
		}
		$('.raspuns_detalii').html(rasp[1]);
		$('.raspuns_detalii1').html(rasp[1]);
		$('.raspuns_detalii2').html(rasp[1]);

		jQuery("#conturi_clienti_listare").jqGrid('setGridParam', {
			url : HTTP + 'clienti/conturi_json'
		}).trigger("reloadGrid");

	});
}
function ClientiEditareContacte(sel){
	if ($('#ACTIV1').is(':checked')) $('#ACTIV1').val(1);
	else $('#ACTIV1').val(0);
	$.post(HTTP + 'clienti/editare_client_contacte', {
		metoda : sel,
		COD_CL : $('#COD_CL').val(),
		COD_CONT_CL : $('#COD_CONT_CL').val(),
		ACTIV : $('#ACTIV1').val(),
		NUME_PERS : $('#NUME_PERS').val(),
		FUNCTIE : $('#FUNCTIE').val(),
		CONTACT_PERS : $('#CONTACT_PERS').val(),
		ROL : $('#ROL').val(),
		ROL2 : $('#ROL2').val(),
		OBS : $('#OBS').val()
	}, function(response) {
		var rasp = response.split('|||');
		PrepareResponse(rasp[0]);
		if (rasp[0] == 1)
			$('#raspuns_detalii1').css('color','green');
		else
			$('#raspuns_detalii1').css('color','red');
		$('#raspuns_detalii1').html(rasp[1]);

		jQuery("#contacte_clienti_listare").jqGrid('setGridParam', {
			url : HTTP + 'clienti/contacte_json'
		}).trigger("reloadGrid");
	});
}

function ClientiDetalii(sel) {
	$('#codul_clientului').val(sel);//cod client folosit pentru editarea tarifelor

    var active_tab = $("#taburi_clienti .ui-tabs-panel:visible").attr("id");
	$.get(HTTP + 'clienti/detalii_client/' + sel, {active_tab:active_tab}, function(response) {
        $("#detalii_client").html(response);
		$('#COD_CL').val(sel);
        $( "#taburi_clienti" ).tabs({ active: 2 });
	});
	$.get(HTTP + 'clienti/detalii_contract/' + sel, {}, function(response) {
		$("#detalii_contract").html(response);
		$('#client_cod_client').val(sel);
	});
}

function ClientiEditareContract(){
	$.get(HTTP + 'clienti/detalii_contract_client/' + $('#COD_CL').val(), {}, function(response) {
		$("#detalii_contract").html(response);
		$("#detalii_contract").dialog({
			autoOpen : true,
			width : 780,
			height : 550,
			draggable : false,
			resizable : false,
			modal: true,
			title : 'Tarife Client: '+$("#NUME").val()
		});
	});
}

function ClientiPopupContract(){
	$("#detalii_contract").dialog({
		create: function (event, ui) {
	        $(".ui-widget-header").hide();
	    },
		autoOpen : false,
		height : 590,
		width :795,
		resizable: false,
		modal : false
	});
}

function SalvareTarife(){
	var val=0;
	$('input[type=checkbox]').each(function () {
  		if ($('#'+this.id).is(':checked')) val=1;
		else val=0;
  		$('#'+this.id).val(val);
  	});
	$.post(HTTP + 'clienti/editare_tarif_lista', {
		TARIF_PREL_TIP : $('#TARIF_PREL_TIP').val(),
		TARIF_LIVR_TIP : $('#TARIF_LIVR_TIP').val(),
		TARIF_PREL : $('#TARIF_PREL').val(),
		TARIF_LIVR : $('#TARIF_LIVR').val(),
		KM_LIMIT_PREL : $('#KM_LIMIT_PREL').val(),
		KM_LIMIT_LIVR : $('#KM_LIMIT_LIVR').val(),
		PLATA_RETUR : $('#PLATA_RETUR').val(),
		TAXA_RAMBURS : $('#TAXA_RAMBURS').val(),
		TAXA_EXPEDIERE : $('#TAXA_EXPEDIERE').val(),
		TAXA_DESTINATIE : $('#TAXA_DESTINATIE').val(),
		TARIF_RETURNARE : $('#TARIF_RETURNARE').val(),
		RET_AMB : $('#RET_AMB').val(),
		KG_RET_AMB : $('#KG_RET_AMB').val(),
		TARIF_SMS : $('#TARIF_SMS').val(),
		TARIF_OPEN : $('#TARIF_OPEN').val(),
		TARIF_PROC_INDEXC : $('#TARIF_PROC_INDEXC').val(),
		OBS : $('#OBS_T').val(),
		COD_CLIENT : $('#codul_clientului').val()
	}, function(response) {
		//alert(response);
		var rasp = response.split('|||');
		var val=0;
		PrepareResponse(rasp[0]);
		$('.mesaj_confirmare').css('font-size','16px');
		if (rasp[0] == 1){
			$('.mesaj_confirmare').css('color','green');
			$('input[type=text]').each(function () {
          		$('#'+this.id).removeClass('camp_modificat');
          		val = $('#'+this.id).val();
          		$('#'+this.id).attr('rel',val);
		  	});
		  	$('input[type=checkbox]').each(function () {
		  		$('#LABEL_'+this.id).removeClass('camp_modificat');
		  		if ($('#'+this.id).is(':checked')) val=1;
				else val=0;
          		$('#'+this.id).attr('rel',val);
		  	});
		  	$('select').each(function () {
		  		$('#'+this.id).removeClass('camp_modificat');
		  		val = $('#'+this.id).val();
          		$('#'+this.id).attr('rel',val);
		  	});
		}else
			$('.mesaj_confirmare').css('color','red');
		$('.mesaj_confirmare').html(rasp[1]);

	});
}

function SalvareTarife2(id,tip){
	var val=0;
	$('input[type=checkbox]').each(function () {
  		if ($('#'+this.id).is(':checked')) val=1;
		else val=0;
  		$('#'+this.id).val(val);
  	});
  	var tip_tarif=0;
	if(tip=='LOCO') tip_tarif=0;
	else tip_tarif=1;

	$.post(HTTP + 'clienti/editare_tarif_lista_2', {
		ID_TARIFE : $('#ID_TARIFE_DET_'+tip).val(),
		TIP_TARIF : tip_tarif,
		PLIC : $('#'+tip+'_PLIC').val(),
		RETUR_NT : $('#'+tip+'_RETUR_NT').val(),
		LIV_SAMBATA : $('#'+tip+'_LIV_SAMBATA').val(),
		LIV_SEDIU : $('#'+tip+'_LIV_SEDIU').val(),
		COLET : $('#'+tip+'_COLET').val(),
		PALET : $('#'+tip+'_PALET').val(),
		RETUR_DOC : $('#'+tip+'_RETUR_DOC').val(),
		RETURNARE : $('#'+tip+'_RETURNARE').val(),
		PROC_ASIG : $('#'+tip+'_PROC_ASIG').val(),
		ASIG_RAMB : $('#'+tip+'_ASIG_RAMB').val(),
		TAXA_RAMB : $('#'+tip+'_TAXA_RAMB').val(),
		ASIG_EXPEDIERE : $('#'+tip+'_ASIG_EXPEDIERE').val(),
		ASIG_RAMBURS : $('#'+tip+'_ASIG_RAMBURS').val(),
		COD_CLIENT : $('#codul_clientului').val()
	}, function(response) {//alert(response);
		var rasp = response.split('|||');
		var val=0;
		PrepareResponse(rasp[0]);
		$('.mesaj_confirmare').css('font-size','16px');
		if (rasp[0] == 1){
			$('.mesaj_confirmare').css('color','green');
			$('input[type=text]').each(function () {
          		$('#'+this.id).removeClass('camp_modificat');
          		val = $('#'+this.id).val();
          		$('#'+this.id).attr('rel',val);
		  	});
		  	$('input[type=checkbox]').each(function () {
		  		$('#LABEL_'+this.id).removeClass('camp_modificat');
		  		if ($('#'+this.id).is(':checked')) val=1;
				else val=0;
          		$('#'+this.id).attr('rel',val);
		  	});
		  	$('select').each(function () {
		  		$('#'+this.id).removeClass('camp_modificat');
		  		val = $('#'+this.id).val();
          		$('#'+this.id).attr('rel',val);
		  	});
		}else
			$('.mesaj_confirmare').css('color','red');
		$('.mesaj_confirmare').html(rasp[1]);
	});
}

function TableGreutate(sel,table,tip) {
	var cod_client = $('#codul_clientului').val();
	if(sel < 2 )
	{
		$("#"+table).jqGrid({
			url : HTTP + 'clienti/listare_greutate_json?tip='+sel+'&cod_client='+cod_client,
			datatype : "json",
			colNames : [ 'De la(Kg)', 'Pana la(Kg)', 'Val. init', 'Creste cu','La fiecare(Kg)'],
			colModel : [ {
				name : 'G_INIT',
				index : 'G_INIT',
				sorttype : 'int',
				sortable:false
			},{
				name : 'G_FIN',
				index : 'G_FIN',
				sorttype : 'int',
				sortable:false
			}, {
				name : 'VAL_INIT',
				index : 'VAL_INIT',
				sorttype : 'int',
				sortable:false
			}, {
				name : 'INC_VAL',
				index : 'INC_VAL',
				sorttype : 'int',
				sortable:false
			}, {
				name : 'INC_GREUT',
				index : 'INC_GREUT',
				sortable:false
			} ],
			height : 'auto',
			width : 680,
			mtype : "GET",
			rownumbers : false,
			gridview : true,
			pager : false,
			sortname : 'G_INIT',
			viewrecords : true,
			sortorder : "asc",
			caption : false,
			onSelectRow : function(id) {
				DetaliiRowGreutate(id,tip,sel);
			}
		});
	}
	else
	{
		$("#"+table).jqGrid({
			url : HTTP + 'clienti/listare_greutate_json?tip='+sel+'&cod_client='+cod_client,
			datatype : "json",
			colNames : [ 'De la(Kg)', 'Pana la(Kg)','De la(Km)', 'Pana la(Km)', 'Val. init', 'Creste cu','La fiecare(Kg)'],
			colModel : [ {
				name : 'G_INIT',
				index : 'G_INIT',
				sorttype : 'int',
				sortable:false
			},{
				name : 'G_FIN',
				index : 'G_FIN',
				sorttype : 'int',
				sortable:false
			},{
				name : 'KM_INIT',
				index : 'KM_INIT',
				sorttype : 'int',
				sortable:false
			},{
				name : 'KM_FIN',
				index : 'KM_FIN',
				sorttype : 'int',
				sortable:false
			}, {
				name : 'VAL_INIT',
				index : 'VAL_INIT',
				sorttype : 'int',
				sortable:false
			}, {
				name : 'INC_VAL',
				index : 'INC_VAL',
				sorttype : 'int',
				sortable:false
			}, {
				name : 'INC_GREUT',
				index : 'INC_GREUT',
				sortable:false
			} ],
			height : 'auto',
			width : 680,
			mtype : "GET",
			rownumbers : false,
			gridview : true,
			pager : false,
			sortname : 'G_INIT',
			viewrecords : true,
			sortorder : "asc",
			caption : false,
			onSelectRow : function(id) {
				DetaliiRowGreutate(id,tip,sel);
			}
		});
	}
}


function DetaliiRowGreutate(sel,tip,type) {
	$('#id_row_listare').val(sel);
	$.get(HTTP + 'clienti/detalii_row_greutate/' + sel, {}, function(response) {
		//alert(response);
		var rasp = response.split('|||');
		PrepareResponse(rasp[0]);
		if (rasp[0] == 1){
			var a = '';
			if(type>1)
				a='_PALET';

			$('#'+tip+'_G_INIT'+a).val(rasp[1]);
			$('#'+tip+'_G_FIN'+a).val(rasp[2]);
			if(type>1)
			{
				$('#'+tip+'_KM_INIT'+a).val(rasp[6]);
				$('#'+tip+'_KM_FIN'+a).val(rasp[7]);
			}
			$('#'+tip+'_VAL_INIT'+a).val(rasp[8]);
			$('#'+tip+'_INC_VAL'+a).val(rasp[3]);
			$('#'+tip+'_INC_GREUT'+a).val(rasp[4]);
			$('#ID_TARIFE_DET_LOCO'+a).val(rasp[5]);
		}
	});
}

function ModificareRowGreutate(type,table,tip){
	var tip_tarif=0;
	var a = '';
	if(tip=='LOCO') tip_tarif=0;
	else if(tip=='NATIONAL') tip_tarif=1;

	if(tip=='NATIONAL_PALET') {
		tip_tarif=3;
		a = '_PALET';
		tip = 'NATIONAL';
	}else if(tip=='LOCO_PALET') {
		tip_tarif=2;
		a = '_PALET';
		tip = 'LOCO';
	}

	if((type==2 || type==3) && $("#"+table).jqGrid('getGridParam','selrow') == null)
		{ AfiseazaEroare("Selectati o linie in tabela !"); return false; }

	if($('#'+tip+'_G_INIT'+a).val() == "" || $('#'+tip+'_G_FIN'+a).val() == "" || $('#'+tip+'_INC_VAL'+a).val() == "" || $('#'+tip+'_INC_GREUT'+a).val() == "")
		{ AfiseazaEroare("Va rog sa completati toate campurile !"); return false; }

	var km_init = 0, km_fin = 5000;
	if(tip_tarif==2 || tip_tarif==3)
	{
		km_init = $('#'+tip+'_KM_INIT'+a).val();
		km_fin = $('#'+tip+'_KM_FIN'+a).val();
	}
	$.ajax({
  		type: "POST",
  		url: HTTP + 'clienti/editare_row_greutate',
  		data: {
			G_INIT : $('#'+tip+'_G_INIT'+a).val(),
			G_FIN : $('#'+tip+'_G_FIN'+a).val(),
			KM_INIT : km_init,
			KM_FIN : km_fin,
			VAL_INIT : $('#'+tip+'_VAL_INIT'+a).val(),
			INC_VAL : $('#'+tip+'_INC_VAL'+a).val(),
			INC_GREUT : $('#'+tip+'_INC_GREUT'+a).val(),
			ID : $("#"+table).jqGrid('getGridParam','selrow'),
			ID_TARIFE_DET: $('#ID_TARIFE_DET_'+tip).val(),
			TYPE : type	,
			TIP_TARIF : tip_tarif
		},
  		success: function(data){
        	var cod_client = $('#codul_clientului').val();
			jQuery("#"+table).jqGrid('setGridParam', {
				url : HTTP + 'clienti/listare_greutate_json?tip='+tip_tarif+'&cod_client='+cod_client
			}).trigger("reloadGrid");
			$('#'+tip+'_G_INIT'+a).val("");
			$('#'+tip+'_G_FIN'+a).val("");
			if(tip_tarif==2 || tip_tarif==3)
			{
				$('#'+tip+'_KM_INIT'+a).val("");
				$('#'+tip+'_KM_FIN'+a).val("");
			}
			$('#'+tip+'_VAL_INIT'+a).val("");
			$('#'+tip+'_INC_VAL'+a).val("");
			$('#'+tip+'_INC_GREUT'+a).val("");
  		},
  		error: function(XMLHttpRequest, textStatus, errorThrown) {
     		AfiseazaEroare(xhr.responseText);
  		}
	});
}

function _Functii_Localitati(){}

function LocalitatiShow() {
	$("#localitati_listare").jqGrid({
		url : HTTP + 'localitati/listare_json',
		datatype : "json",
		colNames : [ 'Localitate', 'Judet', 'Centru', 'Km', 'Zile livrare'],
		colModel : [ {
			name : 'localitate',
			index : 'a.NUME_LC'
		},

		{
			name : 'judet',
			index : 'c.NUME_JD',
		}, {
			name : 'centru',
			index : 'b.NUME',
		}, {
			name : 'km',
			index : 'a.DIST_KM',
			sorttype : 'int'
		}, {
			name : 'zile_liv',
			sortable:false,
			search:false,
			formatter:'select',
			editable : true,
			edittype : "select",
			editoptions: {
				multiple: true,
				size: 7,
				value: {0: '', 1: 'Luni', 2: 'Marti', 3: 'Miercuri', 4: 'Joi', 5: 'Vineri', 6: 'Sambata'},
				defaultValue: 0
			}
		} ],
		height : 400,
		width : 970,
		scroll : 1,
		rowNum : 50,
		mtype : "GET",
		rownumbers : true,
		rownumWidth : 40,
		gridview : true,
		pager : '#localitati_listare_pag',
		sortname : 'a.NUME_LC',
		viewrecords : true,
		sortorder : "asc",
		caption : '',
	});
	$("#localitati_listare").jqGrid('bindKeys');
	$("#localitati_listare").jqGrid('filterToolbar', {
		stringResult : true,
		searchOnEnter : false
	});
}

function LocalitatiListare() {
	var centre = (function() {
		var centre_list = null;
		$.ajax({
			async : false,
			global : false,
			url : HTTP + 'localitati/centre_json',
			dataType : 'json',
			success : function(data) {
				centre_list = data;
			}
		});
		return centre_list;
	})();
	var judete = (function() {
		var judete_list = null;
		$.ajax({
			async : false,
			global : false,
			url : HTTP + 'localitati/judete_json',
			dataType : 'json',
			success : function(data) {
				judete_list = data;
			}
		});
		return judete_list;
	})();

	$("#localitati_listare").jqGrid({
		url : HTTP + 'localitati/listare_json',
		datatype : "json",
		colNames : [ 'Localitate', 'Judet', 'Centru', 'Km', 'Zile livrare'],
		colModel : [ {
			name : 'localitate',
			index : 'a.NUME_LC',
			editable : true,
			editrules : {
				custom : true,
				custom_func : check_localitate
			}
		},
		{
			name : 'judet',
			index : 'c.NUME_JD',
			editable : true,
			edittype : "select",
			editoptions : {
				value : judete
			}
		}, {
			name : 'centru',
			index : 'b.NUME',
			editable : true,
			edittype : "select",
			editoptions : {
				value : centre
			}
		}, {
			name : 'km',
			index : 'a.DIST_KM',
			sorttype : 'int',
			editable : true,
			editrules : {
				custom : true,
				custom_func : check_km
			}
		},
		{
			name : 'zile_liv',
			sortable:false,
			search:false,
			formatter:'select',
			editable : true,
			edittype : "select",
			editoptions: {
				multiple: true,
				size: 7,
				value: {0: '', 1: 'Luni', 2: 'Marti', 3: 'Miercuri', 4: 'Joi', 5: 'Vineri', 6: 'Sambata'},
				defaultValue: 0
			}
		} ],
		height : 400,
		width : 970,
		scroll : 1,
		rowNum : 50,
		mtype : "GET",
		rownumbers : true,
		rownumWidth : 40,
		gridview : true,
		pager : '#localitati_listare_pag',
		sortname : 'a.NUME_LC',
		viewrecords : true,
		sortorder : "asc",
		caption : 'Liste Localitati',
		editurl : HTTP + 'localitati/editare?q=dummy'
	});

	$("#localitati_listare").jqGrid('bindKeys');
	$("#localitati_listare").jqGrid('filterToolbar', {
		stringResult : true,
		searchOnEnter : false
	});
	$("#localitati_listare").jqGrid('navGrid', '#localitati_listare_pag', {
		del : true,
		add : true,
		edit : true,
		search : false
	});
    $("#localitati_listare").jqGrid('navButtonAdd','#localitati_listare_pag',{
        caption:"Export to CSV",
        onClickButton : function () {
            window.location.href = HTTP + 'localitati/export_csv';
        }
    });
}

function check_localitate(value) {
	if (value == "") {
		return [ false, "Localitatea este obligatorie!", "" ];
	} else {
		return [ true, "", "" ];
	}
}
function check_km(value) {
	if (value == "") {
		return [ false, "Numarul de km este obligatoriu!", "" ];
	} else {
		return [ true, "", "" ];
	}
}

function _Functii_Centre(){}

function CentreListare() {
	var dispecerate = (function() {
		var dispecerate_list = null;
		$.ajax({
			async : false,
			global : false,
			url : HTTP + 'centre/dispecerate_json',
			dataType : 'json',
			success : function(data) {
				dispecerate_list = data;
			}
		});
		return dispecerate_list;
	})();

	var mcentre = (function() {
		var centre_list = null;
		$.ajax({
			async : false,
			global : false,
			url : HTTP + 'centre/centre_json',
			dataType : 'json',
			success : function(data) {
				centre_list = data;
			}
		});
		return centre_list;
	})();

	$("#centre_listare").jqGrid({
		url : HTTP + 'centre/listare_json',
		datatype : "json",
		colNames : [ 'ID', 'Nume', 'Cod', 'Dispecerat', 'Email', 'Financiar ce.', 'Financiar', 'Rut_BVH', 'Rut_SIB', 'Rut_BUC', 'Geocode'],
		colModel : [ {
			name : 'id',
			index : 'ce.id',
			editable : false,
			sorttype : 'integer'
		}, {
			name : 'nume',
			index : 'ce.nume',
			editable : true,
			editrules : {
				custom : true,
				custom_func : check_nume
			}
		},{
			name : 'label',
			index : 'ce.label',
			editable : true,
			editrules : {
				custom : true,
				custom_func : check_nume
			},
			align: 'center',
		},{
			name : 'dispecerat',
			index : 'd.nume',
			editable : true,
			edittype : "select",
			editoptions : {
				value : dispecerate
			}
		}, {
			name : 'email',
			index : 'ce.email',
			editable : true
		}, {
			name : 'master',
			index : 'mce.nume',
			editable : true,
			edittype : "select",
			editoptions : {
				value : mcentre
			}
		},{
			name : 'financiar',
			index : 'ce.financiar',
			search : false,
			sortable : true,
			editable : true,
			edittype:'checkbox',
			editoptions: { value:"1:0"},
			formatter: "checkbox",
			formatoptions: {readonly : true},
			align: 'center'
		},{
			name : 'rut_bvh',
			index : 'ce.rut_bvh',
			editable : true,
			align: 'center',
			editrules : {
				custom : true,
				custom_func : check_rut
			}
		},
		{
			name : 'rut_buh',
			index : 'ce.rut_buh',
			editable : true,
			align: 'center',
			editrules : {
				custom : true,
				custom_func : check_rut
			}
		},
		{
			name : 'rut_buc',
			index : 'ce.rut_buc',
			editable : true,
			align: 'center',
			editrules : {
				custom : true,
				custom_func : check_rut
			}
		},{
			name : 'geocode',
			index : 'ce.geocode',
			search : false,
			sortable : false,
			editable : false,
			edittype:'checkbox',
			editoptions: { value:"1:0"},
			formatter: "checkbox",
			formatoptions: {readonly : true},
			align: 'center'
		} ],
		height : 400,
		width : 970,
		scroll : 1,
		rowNum : 100,
		mtype : "GET",
		rownumbers : false,
		gridview : true,
		pager : '#centre_listare_pag',
		sortname : 'ce.nume',
		viewrecords : true,
		sortorder : "asc",
		caption : 'Listare Centre',
		editurl : HTTP + 'centre/editare?q=dummy'
	});
	$("#centre_listare").jqGrid('bindKeys');
	$("#centre_listare").jqGrid('filterToolbar', {
		stringResult : true,
		searchOnEnter : false
	});
	$("#centre_listare").jqGrid('navGrid', '#centre_listare_pag', {edit:true,add:true,del:false,search:false},
       			{
                    reloadAfterSubmit:true,
                    closeOnEscape:true,
                    savekey: [true,13],
					closeAfterEdit:true,
					errorTextFormat: function (data) {
                        if (data.status == 500)
                            return 'Error: ' + data.responseText
                    }
            	},
            	{
                    reloadAfterSubmit:true,
                    savekey: [true,13],
                    closeOnEscape:true,
					closeAfterAdd:true,
					errorTextFormat: function (data) {
                        if (data.status == 500)
                            return 'Error: ' + data.responseText
                    }
            	}
    );
}

function DispecerateListare() {
	$("#dispecerate_listare").jqGrid({
		url : HTTP + 'centre/dispecerate_listare_json',
		datatype : "json",
		colNames : [ 'Nume', 'Comment'],
		colModel : [ {
			name : 'nume',
			index : 'nume',
			editable : true
		},
		{
			name : 'comment',
			index : 'comment',
			editable : true,
		}],
		height : 400,
		width : 970,
		scroll : 1,
		rowNum : 50,
		mtype : "GET",
		rownumbers : true,
		rownumWidth : 40,
		gridview : true,
		pager : '#dispecerate_listare_pag',
		sortname : 'nume',
		viewrecords : true,
		sortorder : "asc",
		caption : 'Lista Dispecerate',
		editurl : HTTP + 'centre/dispecerate_editare?q=dummy'
	});

	$("#dispecerate_listare").jqGrid('bindKeys');
	$("#dispecerate_listare").jqGrid('filterToolbar', {
		stringResult : true,
		searchOnEnter : false
	});
	$("#dispecerate_listare").jqGrid('navGrid', '#dispecerate_listare_pag', {
		del : false,
		add : true,
		edit : true,
		search : false
	});
}

function check_nume(value) {
	if (value == "")
		return [ false, "Numele este obligatoriu!", "" ];
	return [ true, "", "" ];
}

function check_rut(value) {
	if(!value)
		return [ true, "", "" ];
	var rutRegexp = new RegExp(/^(?:[a-zA-Z]\d|[a-zA-Z]|\d[a-zA-Z]|\d{1,2})$/g);
	if(!rutRegexp.test(value)) {
		return [ false, "Respecta formatul pentru Rut_ : X sau XX", "" ];
	}
	return [ true, "", "" ];
}

function _Functii_Agenti(){}
function AgentiListare() {
	var centre = (function() {
		var centre_list = null;
		$.ajax({
			async : false,
			global : false,
			url : HTTP + 'agenti/centre_json',
			dataType : 'json',
			success : function(data) {
				centre_list = data;
			}
		});
		return centre_list;
	})();
	$("#agenti_listare").jqGrid({
		url : HTTP + 'agenti/listare_json',
		datatype : "json",
		colNames : [ 'Cod agent', 'Nume', 'Tip', 'Telefon', 'Cnp', 'Centru', 'Status', 'Login', 'Parola', 'Last login', 'MID', 'Android app v', 'DbSize(Mb)', 'PozeError', 'PozeFile'],
		colModel : [ {
			name : 'cod_ag',
			index : 'a.COD_AG',
			editable : false,
            sorttype : 'integer'
		},{
			name : 'nume',
			index : 'a.NUME_AG',
			editable : true,
			editrules : {
				custom : true,
				custom_func : check_nume
			}
		}, {
			name : 'tip',
			index : 'a.tip',
			editable : true,
			edittype : 'select',
			editoptions : {
				value : "0:Curier;1:Centru;2:Departament;3:Hub;4:SBK"
			},
			stype : 'select',
			formatter: 'select',
			searchoptions : {
				value : ":Toti;0:Curier;1:Centru;2:Departament;3:Hub;4:SBK",
				sopt : [ 'eq' ]
			}
		},{
            name : 'telefon',
            index : 'a.TELEFON',
            editable : true
        },{
            name : 'cnp',
            editable : true,
            hidden: true,
            edittype: 'text',
            editrules : {
                edithidden:true
            },
        }, {
			name : 'centru',
			index : 'b.NUME',
			editable : true,
			edittype : "select",
			editoptions : {
				value : centre
			}
		}, {
			name : 'activ',
			index : 'a.ACTIV',
			editable : true,
			edittype : 'select',
			editoptions : {
				value : "0:Inactiv;1:Activ"
			},
			stype : 'select',
			formatter: 'select',
			searchoptions : {
				value : ":Toti;0:Inactivi;1:Activi",
				defaultValue : 1,
				sopt : [ 'eq' ]
			}
		},{
			name : 'user',
			index : 'a.USER',
			editable : true
		},{
			name : 'parola',
			editable : true,
			edittype: 'password',
			editrules : {
				edithidden:true
			},
			hidden: true
		},{
			name : 'last_login',
			index : 'agh.created_at',
			search : false,
			editable : false
		},{
			name : 'mid',
			index : 'a.mid',
			search : false,
			editable : true,
            edittype: 'text'
		},{
			name : 'android_app_version',
			index : 'agh.androidAppVersion',
			editable : false
		},{
			name : 'dbSize',
			index : 'agh.dbSize',
			editable : false,
			sorttype : 'integer',
			search : false
		},{
			name : 'pozeError',
			index : 'agh.pozeError',
			editable : false,
			sorttype : 'integer',
			search : false
		},{
			name : 'pozeFile',
			index : 'agh.pozeFile',
			editable : false,
			sorttype : 'integer',
			search : false
		}],
		height : 400,
		width : 970,
		scroll : 1,
		rowNum : 200,
		mtype : "GET",
		rownumbers : false,
		rownumWidth : 40,
		gridview : true,
		pager : '#agenti_listare_pag',
		sortname : 'a.NUME_AG',
		viewrecords : true,
		sortorder : "asc",
		caption : 'Listare Agenti',
		editurl : HTTP + 'agenti/editare?q=dummy',
		ondblClickRow: function(rowid) {
    		jQuery(this).jqGrid('editGridRow', rowid);
		},
		beforeRequest: function () {
			// Define a local reference to the grid
			var $requestGrid = $(this);
			// Check a data value for whether we've completed the setup.
			// This should only resolve to true once, on the first run.
			if ($requestGrid.data('areFiltersDefaulted') !== true) {
				// Flip the status so this part never runs again
				$requestGrid.data('areFiltersDefaulted', true);
				// After a short timeout (after this function returns false!), now
				// you can trigger the search
				setTimeout(function () { $requestGrid[0].triggerToolbar(); }, 50);
				// Abort the first request
				return false;
			}
			// Subsequent runs are always allowed
			return true;
		},
	});
	$("#agenti_listare").jqGrid('bindKeys');
	$("#agenti_listare").jqGrid('filterToolbar', {
		stringResult : true,
		searchOnEnter : false
	});
	$("#agenti_listare").jqGrid('navGrid', '#agenti_listare_pag', {
			edit : true,
			add : true,
			del : false,
			search : false
		},
		// options for the Edit Dialog
		{
			recreateForm: true,
			reloadAfterSubmit:true,
			closeAfterEdit: true,
			closeOnEscape:true,
			savekey: [true,13],
			errorTextFormat: function (data) {
				if (data.status == 500)
					return 'Error: ' + data.responseText
			}
		},
		// options for the Add Dialog
		{
			closeAfterAdd: false,
			recreateForm: true,
			closeOnEscape:true,
			savekey: [true,13],
			errorTextFormat: function (data) {
				if (data.status == 500)
					return 'Error: ' + data.responseText
			}
		},
		// options for the Delete Dailog
		{
			errorTextFormat: function (data) {
				if (data.status == 500)
					return 'Error: ' + data.responseText
			}
		});
}

function AgentiVanzariListare() {
	var centre = (function() {
		var centre_list = null;
		$.ajax({
			async : false,
			global : false,
			url : HTTP + 'ag_vanzari/centre_json',
			dataType : 'json',
			success : function(data) {
				centre_list = data;
			}
		});
		return centre_list;
	})();
	$("#agenti_listare").jqGrid({
		url : HTTP + 'ag_vanzari/listare_json',
		datatype : "json",
		colNames : [ 'ID', 'Nume', 'Telefon', 'Email', 'Centru', 'Activ'],
		colModel : [{
            name : 'id',
            index : 'a.id',
			width:50
        },{
            name : 'nume',
            index : 'a.nume',
            editable : true,
            editrules : {
                required: true,
                custom : true,
                custom_func : check_nume
            }
        },{
			name : 'telefon',
			index : 'a.telefon',
			editable : true
		},{
			name : 'email',
			index : 'a.email',
			editable : true,
			editrules : {email : true}
		}, {
			name : 'centru',
			index : 'b.nume',
			editable : true,
			edittype : "select",
			editoptions : {
				value : centre
			}
		}, {
			name : 'activ',
			index : 'a.activ',
			editable : true,
			edittype : 'select',
			editoptions : {
				value : "1:Activ;0:Inactiv"
			},
			stype : 'select',
			searchoptions : {
				value : ":Toti;0:Inactivi;1:Activi",
				sopt : [ 'eq' ]
			}
		}],
		height : 400,
		width : 970,
		scroll : 1,
		rowNum : 50,
		mtype : "GET",
		rownumbers : false,
		rownumWidth : 40,
		gridview : true,
		pager : '#agenti_listare_pag',
		sortname : 'a.nume',
		viewrecords : true,
		sortorder : "asc",
		caption : 'Listare Agenti vanzari',
		editurl : HTTP + 'ag_vanzari/editare?q=dummy',
		ondblClickRow: function(rowid) {
    		jQuery(this).jqGrid('editGridRow', rowid);
		}
	});
	$("#agenti_listare").jqGrid('bindKeys');
	$("#agenti_listare").jqGrid('filterToolbar', {
		stringResult : true,
		searchOnEnter : false
	});
	$("#agenti_listare").jqGrid('navGrid', '#agenti_listare_pag', {
		edit : true,
		add : true,
		del : false,
		search : false
	},
	{
        reloadAfterSubmit:true,
        closeOnEscape:true,
        savekey: [true,13],
        closeAfterEdit:true,
        afterSubmit: function(response,postdata){
        	var json   = response.responseText; // response text is returned from server.
        	var result = JSON.parse(json); // convert json object into javascript object.
        	return [result.status,result.message,null];
    	}
    },
    {
        reloadAfterSubmit:true,
    	savekey: [true,13],
        closeOnEscape:true,
    	closeAfterAdd:true,
        afterSubmit: function(response,postdata){
        	var json   = response.responseText; // response text is returned from server.
        	var result = JSON.parse(json); // convert json object into javascript object.
        	return [result.status,result.message,null];
		}
    });
}


function _Functii_Operatori(){}
function OperatoriListare() {
	var centre = (function() {
		var centre_list = null;
		$.ajax({
			async : false,
			url : HTTP + 'operatori/centre_json',
			dataType : 'json',
			success : function(data) {
				centre_list = data;
			}
		});
		//alert(JSON.stringify(centre_list));
		return centre_list;
	})();

	$("#operatori_listare").jqGrid({
		url : HTTP + 'operatori/listare_json',
		datatype : "json",
		colNames : [ 'Id', 'Username','Nume','Email', 'Functie','Centru','Nivel Acces','Parola','CNP','Status','Print', 'Autologin', 'Last Login', 'Ip'],
		colModel : [ {
			name : 'id',
			index : 'u.id',
			sorttype : 'int',
			editable : false,
		},{
			name : 'user',
			index : 'u.user',
			editable : true,
			editrules : {
				custom : true,
				custom_func : check_user
			}
		}, {
			name : 'nume',
			index : 'u.nume',
			editable : true
		}, {
			name : 'mail',
			index : 'u.mail',
			editable : true,
			editrules : {
				custom : true,
				custom_func : check_email
			}
		}, {
			name : 'functie',
			index : 'u.functie',
			editable : true
		}, {
			name : 'centru',
			index : 'c.nume',
			editable : true,
			edittype : "select",
			editoptions : {
				value : centre,
				defaultValue : '47'
			}
		}, {
			name : 'nivel_acces',
			index : 'u.nivel_acces',
			sorttype : 'int',
			editable : true,
			formatter: 'integer',
			editrules : {
				custom : true,
				custom_func : check_nivel
			}
		}, {
			name : 'parola',
			editable : true,
			edittype: 'password',
			editrules : {
				edithidden:true
			},
			hidden: true
		}, {
			name : 'cnp',
			editable : true,
			editrules : {
				edithidden:true
			},
			hidden: true
		}, {
			name : 'activ',
			index : 'u.activ',
			editable : true,
			edittype : 'select',
			editoptions : {
				value : "0:Inactiv;1:Activ"
			},
			stype : 'select',
			formatter: 'select',
			searchoptions : {
				value : ":Toti;0:Inactivi;1:Activi",
				defaultValue : 1,
				sopt : [ 'eq' ]
			}
		}, {
			name : 'print',
			editable : false,
			search : false,
			sortable : false
		}, {
            name : 'autologin',
            index : 'u.autologin',
            editable : true,
            editrules : {
                edithidden:true
            },
            hidden: true
        },
		{
            name : 'last_login',
			index : 'f_last_login',
            editable : false,
            search : false
        },
		{
            name : 'last_ip',
			sortable : false,
            editable : false,
            search : false
        } ],
		height : 400,
		width : 970,
		scroll : 1,
		rowNum : 50,
		mtype : "GET",
		rownumbers : true,
		rownumWidth : 40,
		gridview : true,
		pager : '#operatori_listare_pag',
		sortname : 'u.user',
		viewrecords : true,
		sortorder : "asc",
		caption : 'Listare Operatori',
		editurl : HTTP + 'operatori/editare?q=dummy',
		ondblClickRow: function(rowid) {
    		jQuery(this).jqGrid('editGridRow', rowid, {
			recreateForm: true,
			reloadAfterSubmit:true,
			closeAfterEdit: true,
			closeOnEscape:true,
			savekey: [true,13],
			errorTextFormat: function (data) {
				if (data.status == 500)
					return 'Error: ' + data.responseText
			}});
		},
		beforeRequest: function () {
			// Define a local reference to the grid
			var $requestGrid = $(this);
			// Check a data value for whether we've completed the setup.
			// This should only resolve to true once, on the first run.
			if ($requestGrid.data('areFiltersDefaulted') !== true) {
				// Flip the status so this part never runs again
				$requestGrid.data('areFiltersDefaulted', true);
				// After a short timeout (after this function returns false!), now
				// you can trigger the search
				setTimeout(function () { $requestGrid[0].triggerToolbar(); }, 50);
				// Abort the first request
				return false;
			}
			// Subsequent runs are always allowed
			return true;
		},
	});
	$("#operatori_listare").jqGrid('bindKeys');
	$("#operatori_listare").jqGrid('filterToolbar', {
		stringResult : true,
		searchOnEnter : false
	});
	$("#operatori_listare").jqGrid('navGrid', '#operatori_listare_pag', {
		add : true,
		edit : true,
		del : false,
		search : false
		},
		// options for the Edit Dialog
		{
			recreateForm: true,
			reloadAfterSubmit:true,
			closeAfterEdit: true,
			closeOnEscape:true,
			savekey: [true,13],
			errorTextFormat: function (data) {
				if (data.status == 500)
					return 'Error: ' + data.responseText
			}
		},
		// options for the Add Dialog
		{
			closeAfterAdd: false,
			recreateForm: true,
			closeOnEscape:true,
			savekey: [true,13],
			errorTextFormat: function (data) {
				if (data.status == 500)
					return 'Error: ' + data.responseText
			}
		},
		// options for the Delete Dailog
		{
			errorTextFormat: function (data) {
				if (data.status == 500)
					return 'Error: ' + data.responseText
			}
		});
}

function check_user(value) {
	if (value == "") {
		return [ false, "Utilizatorul este obligatoriu!", "" ];
	} else {
		return [ true, "", "" ];
	}
}
function check_nivel(value){
	if (value == "" || !isNumber(value)) {
		return [ false, "Nivelul de acces este obligatoriu!", "" ];
	} else {
		return [ true, "", "" ];
	}

}
function check_email(value) {
	if (value == "") {
		return [ false, "Adresa de email este obligatorie!", "" ];
	} else {
		return [ true, "", "" ];
	}
}

function cnpToPDF(sel,autologin){
	if(sel=="" || sel==0) return;
	var url = HTTP + 'operatori/print/'+sel + '/'+autologin;
    window.open(url,'_blank');
	return true;
}


function AfisareDataOnly(){
	var dates = jQuery("#data_start")
	.datepicker({
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
		}
	});
}

function AfisarePerioada(){
	var dates = jQuery("#data_start, #data_final")
	.datepicker({
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

function AfisarePerioadaOra(){
	$('#data_start').datetimepicker({
		showOn : "button",
			buttonImage : HTTP + "assets/images/calendar.gif",
			buttonImageOnly : true,
			dateFormat : 'dd.mm.yy',
			timeFormat : 'HH:mm',
			hour: 10,
			stepMinute: 15,
			controlType: 'select',
			oneLine: true,
		onSelect: function (selectedDateTime){
			var start = $(this).datetimepicker('getDate');
			$('#data_final').datetimepicker('option', 'minDate', new Date(start.getTime()) );
		},
		onClose: function(dateText, inst) {
			var endDateTextBox = $('#data_final');
			if (endDateTextBox.val() != '') {
				var testStartDate = new Date(dateText);
				var testEndDate = new Date(endDateTextBox.val());
				if (testStartDate > testEndDate)
					endDateTextBox.val(dateText);
			}
			else {
				endDateTextBox.val(dateText);
			}
		}
	});

	$('#data_final').datetimepicker({
		showOn : "button",
			buttonImage : HTTP + "assets/images/calendar.gif",
			buttonImageOnly : true,
			dateFormat : 'dd.mm.yy',
			timeFormat : 'HH:mm',
			hour: 23,
			stepMinute: 15,
			controlType: 'select',
			oneLine: true,
		onSelect: function (selectedDateTime){
			var end = $(this).datetimepicker('getDate');
			$('#data_start').datetimepicker('option', 'maxDate', new Date(end.getTime()) );
		},
		onClose: function(dateText, inst) {
			var startDateTextBox = $('#data_start');
			if (startDateTextBox.val() != '') {
				var testStartDate = new Date(startDateTextBox.val());
				var testEndDate = new Date(dateText);
				if (testStartDate > testEndDate)
					startDateTextBox.val(dateText);
			}
			else {
				startDateTextBox.val(dateText);
			}
		}
	});
}

function AfisareActivitate(){
	var cond = '';
	var centru = $('#centru').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	if(centru!=0);
	cond += '&centru='+ escape(centru);

	jQuery("#activitate_livrari").jqGrid('setGridParam', {
		 url : HTTP + 'expeditii/json/activitate_livrari?a' + cond
	}).trigger("reloadGrid");
	jQuery("#activitate_colectari").jqGrid('setGridParam', {
		 url : HTTP + 'expeditii/json/activitate_colectari?a' + cond
	}).trigger("reloadGrid");

}

function Grid_ActivitateLivrari() {
	jQuery("#activitate_livrari").jqGrid(
			{
				url : HTTP + 'expeditii/json/activitate_livrari',
				datatype : "json",
				colNames : ['Colectat','Localitate','Km Loc','Destinatar','Km Dest','Expeditie','Greutate','Status'],
				colModel : [ {
					name : 'data_expeditie',
					index : 'ep.data_expeditie',
					sorttype : 'date',
					formatter : 'date',
					formatoptions : {
						srcformat : 'Y-m-d',
						newformat : 'd.m.Y'
					},
					width: 60,
					align: 'center'
				}, {
					name : 'destinatar_localitate',
					index : 'lcd.nume_lc',
					width: 100
				}, {
					name : 'km_loc',
					index : 'lcd.km_loc',
					align : 'right',
					width: 50
				}, {
					name : 'destinatar',
					index : 'cld.nume',
					width: 130
				}, {
					name : 'km_livrare',
					index : 'ep.km_livrare',
					align : 'right',
					width: 50
				}, {
					name : 'expeditie',
					index : 'ep.expeditie',
					align: 'center',
					width: 80
				}, {
					name : 'greutate',
					index : 'a.greutate',
					align : 'right',
					width : 50
				}, {
					name : 'operatiune',
					index : 'ep.operatiune',
					align : 'center',
					width : 50
				} ],
				rowNum : 15,
				rowList : [ 15, 50, 100 ],
				width : 970,
				height : 'auto',
				mtype : "GET",
				rownumbers : false,
				gridview : true,
				sortname : 'ep.data_expeditie',
				viewrecords : true,
				sortorder : "desc",
				pager : '#paginatie_livrari',
				caption : 'Activitate Livrari',
				multiselect : false,
				subGrid : false,
				ondblClickRow : function(id) {
					AfisareDetaliiExpeditieCuID(id)
				}
			});
	jQuery("#activitate_livrari").jqGrid('bindKeys');
}

function Grid_ActivitateColectari() {
	jQuery("#activitate_colectari").jqGrid(
			{
				url : HTTP + 'expeditii/json/activitate_colectari',
				datatype : "json",
				colNames : ['Colectat','Localitate','Km Loc','Expeditor','Km Exp','Expeditie','Greutate'],
				colModel : [ {
					name : 'data_expeditie',
					index : 'ep.data_expeditie',
					sorttype : 'date',
					formatter : 'date',
					formatoptions : {
						srcformat : 'Y-m-d',
						newformat : 'd.m.Y'
					},
					width: 60,
					align: 'center'
				}, {
					name : 'expeditor_localitate',
					index : 'lce.nume_lc',
					width: 100
				}, {
					name : 'km_localitate',
					index : 'lce.dist_km',
					align : 'right',
					width: 50
				}, {
					name : 'expeditor',
					index : 'cle.nume',
					width: 130
				}, {
					name : 'km_preluare',
					index : 'ep.km_preluare',
					align : 'right',
					width: 50
				}, {
					name : 'expeditie',
					index : 'ep.expeditie',
					align: 'center',
					width: 100
				}, {
					name : 'greutate',
					index : 'ep.greutate',
					align : 'right',
					width : 50
				} ],
				rowNum : 15,
				rowList : [ 15, 50, 100 ],
				width : 970,
				height : 'auto',
				mtype : "GET",
				rownumbers : false,
				gridview : true,
				sortname : 'ep.data_expeditie',
				viewrecords : true,
				sortorder : "desc",
				pager : '#paginatie_colectari',
				caption : 'Activitate Colectari',
				multiselect : false,
				subGrid : false,
				ondblClickRow : function(id) {
					AfisareDetaliiExpeditieCuID(id);
				}
			});
	jQuery("#activitate_colectari").jqGrid('bindKeys');
}

function VerificareCamp(sel){
	if(sel=='fara'){
		if ($('#fara_retururi').is(':checked'))
			$('#numai_retururi').attr('checked', false);
	}
	if(sel=='numai'){
		if ($('#numai_retururi').is(':checked'))
			$('#fara_retururi').attr('checked', false);
	}
	if(sel=='cash'){
		if ($('#cash').is(':checked'))
			$('#factura').attr('checked', false);
	}
	if(sel=='factura'){
		if ($('#factura').is(':checked'))
			$('#cash').attr('checked', false);
	}
}

function AfisareColectari() {
	var cond = '';
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();

	if(data_start=='' || data_final=='') {
		AfiseazaEroare('Selectati Perioada!');
		return false;
	}
	cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);

	if ($('#fara_retururi').is(':checked')) {
		cond += '&fara_retururi=1';
	}
	if ($('#numai_retururi').is(':checked')) {
		cond += '&numai_retururi=1';
	}
	if ($('#ramburs').is(':checked')) {
		cond += '&ramburs=1';
	}
	if ($('#cash').is(':checked')) {
		cond += '&cash=1';
	}
	if ($('#factura').is(':checked')) {
		cond += '&factura=1';
	}
	cond += '&platitor=' + escape($('#platitor').val());

	jQuery("#liste_centre").jqGrid('setGridParam', {
		url : HTTP + 'expeditii/json/colectari?a' + cond
	}).trigger("reloadGrid");
}

function AfisareLivrari() {
	var cond = '';
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();

	if(data_start=='' || data_final=='') {
		AfiseazaEroare('Selectati Perioada!');
		return false;
	}
	cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);

	if ($('#fara_retururi').is(':checked')) {
		cond += '&fara_retururi=1';
	}
	if ($('#numai_retururi').is(':checked')) {
		cond += '&numai_retururi=1';
	}
	if ($('#ramburs').is(':checked')) {
		cond += '&ramburs=1';
	}
	if ($('#cash').is(':checked')) {
		cond += '&cash=1';
	}
	if ($('#factura').is(':checked')) {
		cond += '&factura=1';
	}
	cond += '&platitor=' + escape($('#platitor').val());

	jQuery("#liste_centre").jqGrid('setGridParam', {
		url : HTTP + 'expeditii/json/livrari?a' + cond
	}).trigger("reloadGrid");
}

function AfisareRulajClient(){
	var client = parseInt($('#client').val());
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	if(client == 0) {
		AfiseazaEroare('Selectati Clientul!');
		return false;
	}
	if(data_start == '' || data_final == '') {
		AfiseazaEroare('Selectati Perioada!');
		return false;
	}
	$.post(HTTP + 'expeditii/rulaj_client/', {data_start:data_start,data_final:data_final,client:client}, function(response) {
		$("#detalii_rulaj").html(response);
	});
}

function VerificareExpeditieConfirmare(){

   	var exp = $('#expeditie').val();

	$('#istoric_nr_nt').html(exp);
	jQuery("#istoric_expeditii").jqGrid('setGridParam', {
		url : HTTP + 'expeditii/json/istoric?expeditie=' + exp
	}).trigger("reloadGrid");

	$.post(HTTP + 'expeditii/detalii_istoric_expeditie/', {
		exp:exp
	}, function(response) {
		$("#editare_istoric").html(response);
		$('#primitor').select();
	});
}

/* //////////////////////////////////////////////////////
				START INTRODUCERE EXPEDITIE
//////////////////////////////////////////////////////  */

function Introducere_ListareExpeditii() {
	var myGrid = $("#expeditii_listare").jqGrid(
			{
				url : HTTP + 'expeditii/json/introducere',
				datatype : "json",
				colNames : [ 'Nr. Exp', 'Expeditor', 'Centru Expeditor','Destinatar', 'Centru Destinatar',''],
				colModel : [ {
					name : 'expeditie',
					index : 'ep.expeditie',
					sorttype : 'int',
					width: 80
				},{
					name : 'expeditor',
					index : 'cle.nume',
					width: 200
				}, {
					name : 'expeditor_centru',
					index : 'cee.nume',
					width: 200
				}, {
					name : 'destinatar',
					index : 'cld.nume',
					width: 200
				}, {
					name : 'desctinatar_centru',
					index : 'ced.nume',
					width: 200
				}, {
					name : 'opt',
					search : false,
					sortable : false,
					width: 40
				}],
				rowNum : 5,
				rowList : [ 5, 10, 25, 50, 100 ],
				autowidth : true,
				width : 950,
				height : 'auto',
				mtype : "GET",
				rownumbers : false,
				gridview : true,
				sortname : 'data',
				viewrecords : true,
				sortorder : "desc",
				pager : '#paginatie',
				caption : false,
				multiselect : false,
				subGrid : false,
				onSelectRow : function(rowid, status, e) {
					AfisareContinutExpeditie(rowid);
				},
				gridComplete: function()
				{
				    var rows = jQuery("#expeditii_listare").getDataIDs();
				    for (var i = 0; i < rows.length; i++)
				    {
				        var status = jQuery("#expeditii_listare").getCell(rows[i],"opt");
				        if(status == "LS")
				        {
				            jQuery("#expeditii_listare").jqGrid('setRowData',rows[i],false, {  color:'black',weightfont:'bold',background:'#ece1b6'});
				        }
				    }
				}
			});
	jQuery("#expeditii_listare").jqGrid('bindKeys');
	jQuery("#expeditii_listare").jqGrid('navGrid', '#paginatie', {
		del : false,
		add : false,
		edit : false,
		search : false,
		refresh : false
	});
	jQuery("#expeditii_listare").jqGrid('filterToolbar', {
		stringResult : true,
		searchOnEnter : false
	});
}

function AfisareContinutExpeditie(sel) {
	var date = $('#expeditii_data').val();
	$.post(HTTP + 'expeditii/introducere_detalii/', {
		exp : sel,
		date : date
	}, function(response) {
		$("#expeditii_continut").html(response);
	});
}

function SelectareCamp(field,content){
	$("#confirmare_expeditie").html(content);
	$("#confirmare_expeditie").dialog("destroy");
	$("#confirmare_expeditie").dialog({
		width : 300,
		height : 160,
		draggable : false,
		resizable : false,
		modal: true,
		title : 'Eroare',
		buttons: {
			"Modifica": function() {
				$(this).dialog("close");
				$("#" + field + "_NUME").focus();
			}
		},
		close : function() {
			$(this).dialog("destroy");
			$("#" + field + "_NUME").focus();
		}
	});
}

function ComboLocalitati_Old(field) {
	$("#" + field + "_NUME").autocomplete({
		source : function(request, response) {
			$.ajax({
				url : HTTP + "expeditii/json/localitati_old",
				dataType : "json",
				data : {
					maxRows : 25,
					name_startsWith : request.term
				},
				success : function(data) {
					response($.map(data.rezultat, function(item) {
						return {
							value : item.value,
							label : item.label,
							cod_lc : item.cod_lc,
							km_ext : item.km_ext
						}
					}));
				}
			});
		},
		minLength : 1,
		select : function(event, ui) {
			$("#" + field + "_NUME").val(ui.item.value);
			$("#" + field).val(ui.item.cod_lc);
			if(field=='EXPEDITOR_LOCALITATE'){
				$("#KM_EXT_PREL").val(ui.item.km_ext);
				$("#EXPEDITOR").val('');
				$("#EXPEDITOR_NUME").val('');
			}
			else if(field=='DESTINATAR_LOCALITATE'){
				$("#KM_EXT_LIV").val(ui.item.km_ext);
				$("#DESTINATAR").val('');
				$("#DESTINATAR_NUME").val('');
			}
			ValidareCampuriIntroducere(field);
			return false;
		},
		change : function(event, ui) {
			if(!ui.item){
				$("#" + field).val("");
				if(field=='EXPEDITOR_LOCALITATE'){
					$("#EXPEDITOR").val("");
					$('#EXPEDITOR_NUME').val("");
					$('#EXPEDITOR_CUI').val("");
					$('#EXPEDITOR_ADRESA').val("");
					$('#EXPEDITOR_TELEFON').val('');
					$('#EXPEDITOR_CONTACT').val('');
					$("#KM_EXT_PREL").val('');
				}
				else if(field=='DESTINATAR_LOCALITATE'){
					$("#DESTINATAR").val("");
					$('#DESTINATAR_NUME').val("");
					$('#DESTINATAR_ADRESA').val("");
					$('#DESTINATAR_TELEFON').val('');
					$('#DESTINATAR_CONTACT').val('');
					$("#KM_EXT_LIV").val('');
				}
				return true;
			}
			else{
				if(field=='EXPEDITOR_LOCALITATE'){
					$("#EXPEDITOR").val('');
					$("#EXPEDITOR_NUME").val('');
					$('#EXPEDITOR_CUI').val("");
				}else if(field=='DESTINATAR_LOCALITATE'){
					$("#DESTINATAR").val('');
					$("#DESTINATAR_NUME").val('');
					$('#EXPEDITOR_CUI').val("");
				}
			}
		},
		open : function() {
			if(field=='EXPEDITOR_LOCALITATE'){
				$("#EXPEDITOR_LOCALITATE").val("");
				$("#EXPEDITOR").val("");
				$('#EXPEDITOR_NUME').val("");
				$('#EXPEDITOR_CUI').val("");
				$('#EXPEDITOR_ADRESA').val("");
				$('#EXPEDITOR_TELEFON').val('');
				$('#EXPEDITOR_CONTACT').val('');
				$("#KM_EXT_PREL").val('');
			}
			else if(field=='DESTINATAR_LOCALITATE'){
				$("#DESTINATAR_LOCALITATE").val("");
				$("#DESTINATAR").val("");
				$('#DESTINATAR_NUME').val("");
				$('#DESTINATAR_ADRESA').val("");
				$('#DESTINATAR_TELEFON').val('');
				$('#DESTINATAR_CONTACT').val('');
				$("#KM_EXT_LIV").val('');
			}
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}

function ComboPlatitor() {
	$("#PLATITOR_NUME").autocomplete({
		source : function(request, response) {
			$.ajax({
				url : HTTP + "expeditii/json/platitori",
				dataType : "json",
				data : {
					maxRows : 15,
					name_startsWith : request.term
				},
				success : function(data) {
					response($.map(data.rezultat, function(item) {
						return {
							value : item.cod,
							label : item.nume,
							adresa : item.adresa,
							cc : item.cc,
							mod_plata : item.mod_plata
						}
					}));
				}

			});
		},
		minLength : 1,
		focus : function(event, ui) {
			$("#PLATITOR_NUME").val(ui.item.label);
			return false;
		},
		select : function(event, ui) {
			$("#PLATITOR").val(ui.item.value);
			$("#PLATITOR_CC").val(ui.item.cc);
			if($("#PLATITOR_MOD_PLATA").length)
				$("#PLATITOR_MOD_PLATA").val(ui.item.mod_plata);
			SelectTipContract();
			ActualizarePlatitor("PLATITOR");
			return false;
		},
		open : function() {
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
			$("#PLATITOR").val('');
			$("#PLATITOR_CC").val(0);
			if($("#PLATITOR_MOD_PLATA").length)
				$("#PLATITOR_MOD_PLATA").val(0);
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}

function SetareCC() {
	var sel = $('#tip_plata').find(":selected").val();
	if($('#EXPEDITOR_CC').val() == '1' || ($('#cruser').val() == '1')){
		$('#tip_plata').find('option').remove();
		$("#tip_plata").append($("<option></option>").attr("value","3").text("cont"));
		if($('#cruser').val()=='1'){
			if(!Number($('#NR_EXP').val())){
				sel = 3;
			}
			$("#tip_plata").append($("<option style='color:#ae004c;'></option>").attr("value","0").text("cash"));
		}
		$("#tip_plata").append($("<option></option>").attr("value","1").text("bo"));
		$("#tip_plata").append($("<option></option>").attr("value","2").text("cec"));
	}
	else{
		$('#tip_plata').find('option').remove();
		$("#tip_plata").append($("<option></option>").attr("value","0").attr('selected',true).text("cash"));
		$("#tip_plata").append($("<option></option>").attr("value","1").text("bo"));
		$("#tip_plata").append($("<option></option>").attr("value","2").text("cec"));
		if(sel == 3) sel = 0;
	}
	$('#tip_plata').val(sel);
}

function ComboClienti_CUI(field) {
	var local=0;
	$("#" + field + "_CUI").autocomplete({
		source : function(request, response) {
			$.ajax({
				url : HTTP + "expeditii/json/clienti_cui",
				dataType : "json",
				data : {
					maxRows : 15,
					name_startsWith : request.term
				},
				success : function(data) {
					response($.map(data.rezultat, function(item) {
						return {
							value : item.value,
							label : item.label,
							cod_cl : item.cod,
							nume : item.nume,
							adresa : item.adresa,
							contact : item.contact,
							telefon : item.telefon,
							cod_lc : item.cod_lc,
							nume_lc : item.nume_lc,
							cc : item.cc,
							contract: item.contract,
							mod_plata: item.mod_plata,
							centru : item.centru,
							km_ext : item.km_ext
						}
					}));
				}

			});
		},
		minLength : 2,
		select : function(event, ui) {
			$("#" + field).val(ui.item.cod_cl);
			$("#" + field + "_CUI").val(ui.item.value);
			if($("#" + field + "_NUME").length)
				$("#" + field + "_NUME").val(ui.item.nume);
			if($("#" + field + "_LOCALITATE").length)
				$("#" + field + "_LOCALITATE").val(ui.item.cod_lc);
			if($("#" + field + "_LOCALITATE_NUME").length)
				$("#" + field + "_LOCALITATE_NUME").val(ui.item.nume_lc);
			if($("#" + field + "_CC").length)
				$("#" + field + "_CC").val(ui.item.cc);
			if($("#" + field + "_CONTRACT").length)
				$("#" + field + "_CONTRACT").val(ui.item.contract);
			if($("#" + field + "_MOD_PLATA").length)
				$("#" + field + "_MOD_PLATA").val(ui.item.mod_plata);
			if($("#" + field + "_ADRESA").length)
				$("#" + field + "_ADRESA").val(ui.item.adresa);
			if($("#" + field + "_CONTACT").length)
				$("#" + field + "_CONTACT").val(ui.item.contact);
			if($("#" + field + "_TELEFON").length)
				$("#" + field + "_TELEFON").val(ui.item.telefon);

			//daca platitorul este altul decat exp sau dest ii setez tariful si metoda de plata
			SelectTipContract();
			if(field == 'DESTINATAR'){
				$('#centru_livrare').val(ui.item.centru);
				$("#centru_livrare").css('color', 'red');
				$('#KM_EXT_LIV').val(ui.item.km_ext);
			}
			else{
				$('#KM_EXT_PREL').val(ui.item.km_ext);
			}
			ActualizarePlatitor(field);
			if(field == 'EXPEDITOR')
				SetareCC();
			return false;
		},
		change : function(event, ui) {
			/*
			if($("#" + field + "_CUI").val().trim() == ''){
				$("#" + field).val("");
				$("#" + field + "_NUME").val("");
			}
			*/
			return true;
		},
		open : function() {
			return true;
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	})
	.data("autocomplete")._renderItem = function(ul, item) {
    	return $("<li></li>").data("item.autocomplete", item).append("<a"+((item.contract == 1)?" style='color:red'":"")+">" + item.label + "</a>").appendTo(ul);
	};
}

function ComboClienti_Old(field, loc) {
	var local = 0;
	$("#" + field + "_NUME").autocomplete({
		source : function(request, response) {
			if(loc != 'ALL')
				local = parseInt($('#' + loc).val());
			else
				local = 'all';
			if(isNaN(local) || local == 0) local = 'all';
			$.ajax({
				url : HTTP + "expeditii/json/clienti_old",
				dataType : "json",
				data : {
					maxRows : 15,
					name_startsWith : request.term,
					localitate : local
				},
				success : function(data) {
					response($.map(data.rezultat, function(item) {
						return {
							value : item.value,
							label : item.label,
							cod_cl : item.cod,
							cui : item.cui,
							adresa : item.adresa,
							contact : item.contact,
							telefon : item.telefon,
							cod_lc : item.cod_lc,
							nume_lc : item.nume_lc,
							cc : item.cc,
							contract: item.contract,
							mod_plata: item.mod_plata,
							centru : item.centru,
							km_ext : item.km_ext
						}
					}));
				}

			});
		},
		minLength : 1,
		select : function(event, ui) {
			$("#" + field).val(ui.item.cod_cl);
			$("#" + field + "_NUME").val(ui.item.value);
			if($("#" + field + "_CUI").length)
				$("#" + field + "_CUI").val(ui.item.cui);
			if($("#" + field + "_CC").length)
				$("#" + field + "_CC").val(ui.item.cc);
			if($("#" + field + "_CONTRACT").length)
				$("#" + field + "_CONTRACT").val(ui.item.contract);
			if($("#" + field + "_MOD_PLATA").length)
				$("#" + field + "_MOD_PLATA").val(ui.item.mod_plata);
			if($("#" + field + "_ADRESA").length)
				$("#" + field + "_ADRESA").val(ui.item.adresa);
			if($("#" + field + "_CONTACT").length)
				$("#" + field + "_CONTACT").val(ui.item.contact);
			if($("#" + field + "_TELEFON").length)
				$("#" + field + "_TELEFON").val(ui.item.telefon);
			if(local == 'all' || $("#" + loc).val() == '') {
				$("#" + loc).val(ui.item.cod_lc);
				$("#" + loc + "_NUME").val(ui.item.nume_lc);
			}

			if(loc == 'ALL')//daca platitorul este altul decat exp sau dest ii setez tariful si metoda de plata
				SelectTipContract();
			if(field == 'DESTINATAR'){
				$('#centru_livrare').val(ui.item.centru);
				$("#centru_livrare").css('color', 'red');
				$('#KM_EXT_LIV').val(ui.item.km_ext);
			}else{
				$('#KM_EXT_PREL').val(ui.item.km_ext);
			}
			ActualizarePlatitor(field);
			if(field == 'EXPEDITOR')
				SetareCC();
			return false;
		},
		change : function(event, ui) {
			if($("#" + field + "_NUME").val().trim() == '')
				$("#" + field).val("");
			if (!ui.item && $("#" + field + "_LOCALITATE").val() != '' && $("#" + field + "_NUME").val() != 'Client') {
				$("#client_pj_fieldset").show();
				$("#client_cui_fieldset").show();
				$("#client_nume").val($("#" + field + "_NUME").val());
				if($("#" + field + "_CUI").length)
					$("#client_cui").val($("#" + field + "_CUI").val());
				$("#client_cod_localitate").val($("#" + field + "_LOCALITATE").val());
				$("#client_localitate").val($("#" + field + "_LOCALITATE_NUME").val());
				$("#client_tip").val(field);
				if(field != "EXPEDITOR") {
					$("#client_pj_fieldset").hide();
					$("#client_cui_fieldset").hide();
				}
				if(loc != 'ALL'){
					$("#adauga_client").dialog("open");
				}
				ActualizarePlatitor(field);
			}
			return true;
		},
		open : function() {
			$("#" + field).val('');
			$("#" + field + "_ADRESA").val('');
			$("#" + field + "_CC").val('');
			if($("#" + field + "_CONTRACT").length)
				$("#" + field + "_CONTRACT").val('');
			if($("#" + field + "_MOD_PLATA").length)
				$("#" + field + "_MOD_PLATA").val('');
			$('#' + field + '_TELEFON').val('');
			$('#' + field + '_CONTACT').val('');
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
			if(field == 'EXPEDITOR')
				SetareCC();
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	})
	.data("autocomplete")._renderItem = function(ul, item) {
    	return $("<li></li>").data("item.autocomplete", item).append("<a"+((item.contract == 1)?" style='color:red'":"")+">" + item.label + "</a>").appendTo(ul);
	};
}

//Selectez tipul de contract
 function SelectTipContract(){
	 var client=$('#PLATITOR').val();
	 if(client =='')
		 return false;

	 $.getJSON( HTTP + 'clienti/tip_contract/'+client, { } )
		 .done(function( json ) {
			 $('#mod_plata [value="'+json.mod_plata+'"]').attr('selected',true);
			 $('#TIP_TARIF').val(json.tarif);
		 })
		 .fail(function( jqxhr, textStatus, error ) {
			 $('#mod_plata [value="0"]').attr('selected',true);
			 $('#TIP_TARIF').val(0);
		 });
 }

function ValidareCampuriIntroducere(field){
	if(field=='plateste1' || field=='plateste2' || field=='plateste3'){
		if( $("#PLATITOR_NUME").attr('rel') != $("#PLATITOR_NUME").val() ){
			$("#label_platitor").css('color', 'red');
		}else
			$("#" + field).css('color', '#232222');
	}else if(field=='TIP_OBJ_1' || field=='RET_NT' || field=='RET_DOC' || field=='LIV_S' || field=='RET_AMB' || field=='RET_COLET' || field=='LIV_SEDIU' || field=='TIP_OBJ_3' || field=='COPEN' || field=='SMS'){
		ValidareCampuriRetur(field);
	}else{
		if( $("#" + field).attr('rel') != $("#" + field).val() ){
			$("#" + field).css('color', 'red');
		}else
			$("#" + field).css('color', '#232222');
	}
	return true;
}

//setez cine plateste expeditia
function SetarePlatitor(sel) {
	var val = parseInt($('#plateste_' + sel).val());
	$("#plateste_expeditor").attr("checked", false);
	$("#plateste_destinatar").attr("checked", false);
	$("#plateste_altul").attr("checked", false);
	$("#plateste").val(val);

	var expeditor = parseInt($('#EXPEDITOR').val());
	var destinatar = parseInt($('#DESTINATAR').val());

	if (val == 1) {//expeditor
		if(isNaN(expeditor) || expeditor == 0) { AfiseazaEroare('Introduceti expeditorul!'); return false; }
		$('#PLATITOR_NUME').val($('#EXPEDITOR_NUME').val());
		$('#PLATITOR').val(expeditor);
		$("#PLATITOR_NUME").prop('readonly', true);
	} else if (val == 2) {//destinatar
		if(isNaN(destinatar) || destinatar == 0) { AfiseazaEroare('Introduceti destinatarul!');  return false; }
		$('#PLATITOR_NUME').val($('#DESTINATAR_NUME').val());
		$('#PLATITOR').val(destinatar);
		$("#PLATITOR_NUME").prop('readonly', true);
	} else {//tert
		if(isNaN(expeditor) || expeditor == 0 || isNaN(destinatar) || destinatar == 0) { AfiseazaEroare('Introduceti expeditorul/destinatarul!');  return false; }
		$("#PLATITOR_NUME").prop('readonly', false);
		$('#PLATITOR_NUME').val('');
		$('#PLATITOR').val('');
	}
	$("#plateste_" + sel).attr("checked", true);
	SelectTipContract();//selectez tipul de contract a platitorului
	ValoareExpeditie(sel);
	return true;
}


function ActualizarePlatitor(field){
	if(field=='EXPEDITOR' && $('#plateste_expeditor').is(':checked')){
		SetarePlatitor('expeditor');
	}else if(field=='DESTINATAR' && $('#plateste_destinatar').is(':checked')){
		SetarePlatitor('destinatar');
	}else{
		ValoareExpeditie(0);
	}
}

function TipExpeditie(sel){
	$("#SMS").attr("disabled", sel != 0);
	
	if(sel==0 || sel==4){
		$("#referinta_expeditie").attr("readonly","true");
		$("#referinta_expeditie").val('');

		$("#GREUTATE").removeAttr('readonly');
		$("#TIP_OBJ_1").attr("disabled", false);
		$("#TIP_OBJ_3").attr("disabled", false);
		$("#TIP_OBJ_2").removeAttr('readonly');

		$("#proc_asig").removeAttr('readonly');
		$("#asigurare").removeAttr('readonly');
		$("#TAXA_ASIG").removeAttr('readonly');

		$("#RET_NT").attr("disabled", false);
		$("#RET_DOC").attr("disabled", false);
		$("#RET_AMB").attr("disabled", false);
		$("#RET_COLET").attr("disabled", false);
		$("#COPEN").attr("disabled", false);

		$("#ramburs").removeAttr('readonly');
		$("#VOLUM1").removeAttr('readonly');
		$("#VOLUM2").removeAttr('readonly');
		$("#VOLUM3").removeAttr('readonly');

	}
	else if(sel<5 ){
		$("#referinta_expeditie").attr("readonly", false);

		$("#TIP_OBJ_1").attr("checked", true);
		$("#TIP_OBJ_3").attr("checked", false);
		$("#TIP_OBJ_2").val(0);

		$("#RET_NT").attr("checked", false);
		$("#RET_DOC").attr("checked", false);

		$("#GREUTATE").val("0.500");
		$("#GREUTATE_VOL").val("");
		$("#VOLUM1").val("___");
		$("#VOLUM2").val("___");
		$("#VOLUM3").val("___");

		$("#KM_EXT_PREL").val('');
		if(sel == 1 || sel == 2)
		{
			$("#proc_asig").val("");
			$("#asigurare").val("");
		}

		$("#LIV_S").attr("checked", false);
		$("#RET_AMB").attr("checked", false);
		$("#RET_COLET").attr("checked", false);
		$("#LIV_SEDIU").attr("checked", false);

		$("#ramburs").attr("readonly", true);

		$("#GREUTATE").attr("readonly", true);

		$("#TIP_OBJ_1").attr("disabled", true);
		$("#TIP_OBJ_3").attr("disabled", true);
		$("#TIP_OBJ_2").attr("readonly", true);

		$("#RET_NT").attr("disabled", true);
		$("#RET_DOC").attr("disabled", true);

		$("#proc_asig").attr("readonly", true);
		$("#asigurare").attr("readonly", true);
		$("#TAXA_ASIG").attr("readonly", true);

		$("#LIV_S").attr("disabled", true);
		$("#RET_AMB").attr("disabled", true);
		$("#RET_COELT").attr("disabled", true);
		$("#LIV_SEDIU").attr("disabled", true);
		$("#COPEN").attr("disabled", true);

		$("#VOLUM1").attr("readonly", true);
		$("#VOLUM2").attr("readonly", true);
		$("#VOLUM3").attr("readonly", true);
	}
	else if(sel==5 ){
		$("#referinta_expeditie").attr("readonly", false);

		$("#RET_NT").attr("checked", false);
		$("#RET_DOC").attr("checked", false);

		$("#LIV_S").attr("checked", false);
		$("#RET_AMB").attr("checked", false);
		$("#RET_COLET").attr("checked", false);
		$("#LIV_SEDIU").attr("checked", false);
		$("#COPEN").attr("checked", false);
		$("#SMS").attr("checked", false);

		$("#ramburs").val("");
	}
	else if(sel==6){
		$("#referinta_expeditie").attr("readonly", false);

		$("#GREUTATE").removeAttr('readonly');
		$("#TIP_OBJ_1").attr("disabled", true);
		$("#TIP_OBJ_3").attr("disabled", true);
		$("#TIP_OBJ_2").removeAttr('readonly');

		$("#RET_NT").attr("disabled", true);
		$("#RET_DOC").attr("disabled", true);
		$("#RET_AMB").attr("disabled", true);
		$("#RET_COLET").attr("disabled", true);
		$("#COPEN").attr("disabled", true);

		$("#VOLUM1").removeAttr('readonly');
		$("#VOLUM2").removeAttr('readonly');
		$("#VOLUM3").removeAttr('readonly');

		$("#ramburs").attr("readonly", true);
		$("#proc_asig").attr("readonly", true);
		$("#asigurare").attr("readonly", true);
		$("#TAXA_ASIG").attr("readonly", true);
		$("#LIV_S").attr("disabled", true);
		$("#LIV_SEDIU").attr("disabled", true);

		$("#ramburs").val("");
		$("#proc_asig").val("");
		$("#asigurare").val("");
		$("#LIV_S").attr("checked", false);
		$("#RET_AMB").attr("checked", false);
		$("#RET_COLET").attr("cheked", false);
		$("#LIV_SEDIU").attr("checked", false);
		$("#RET_NT").attr("checked", false);
		$("#RET_DOC").attr("checked", false);
		$("#TIP_OBJ_1").attr("checked", false);
		$("#TIP_OBJ_3").attr("checked", false);
	} else if(sel==7){
		$("#referinta_expeditie").attr("readonly", false);

		$("#GREUTATE").removeAttr('readonly');
		$("#VOLUM1").removeAttr('readonly');
		$("#VOLUM2").removeAttr('readonly');
		$("#VOLUM3").removeAttr('readonly');
		$("#TIP_OBJ_1").attr("disabled", true);
		$("#TIP_OBJ_3").attr("disabled", true);
		$("#TIP_OBJ_2").attr("readonly", true);

		$("#RET_NT").attr("disabled", true);
		$("#RET_DOC").attr("disabled", true);
		$("#RET_AMB").attr("disabled", true);
		$("#RET_COLET").attr("disabled", true);
		$("#COPEN").attr("disabled", true);

		$("#VOLUM1").removeAttr('readonly');
		$("#VOLUM2").removeAttr('readonly');
		$("#VOLUM3").removeAttr('readonly');

		$("#ramburs").attr("readonly", true);
		$("#proc_asig").attr("readonly", true);
		$("#asigurare").attr("readonly", true);
		$("#TAXA_ASIG").attr("readonly", true);
		$("#LIV_S").attr("disabled", true);
		$("#LIV_SEDIU").attr("disabled", true);

		$("#LIV_S").attr("checked", false);
		$("#RET_AMB").attr("checked", false);
		$("#RET_COLET").attr("checked", false);
		$("#LIV_SEDIU").attr("checked", false);
		$("#RET_NT").attr("checked", false);
		$("#RET_DOC").attr("checked", false);
		$("#TIP_OBJ_1").attr("checked", false);
		$("#TIP_OBJ_3").attr("checked", false);
	}

}


function ValoareExpeditie(sel){

	var piese = parseInt($('#TIP_OBJ_2').val());
	if(isNaN(piese))  piese = 0;
	//console.log("0");

	if(!($('#TIP_OBJ_1').is(':checked') || $('#TIP_OBJ_3').is(':checked') || piese > 0))
		return false;
	//console.log("1");
	if(sel==1 && $('#TIP_OBJ_1').is(':checked')) {
		$('#TIP_OBJ_3').attr('checked', false);
		$('#TIP_OBJ_2').val('0');

		$('#GREUTATE').attr('readonly', true);
		$('#VOLUM1').attr('readonly', true);
		$('#VOLUM2').attr('readonly', true);
		$('#VOLUM3').attr('readonly', true);

		$('#GREUTATE').val('0.500');
		$('#VOLUM1').val('');
		$('#VOLUM2').val('');
		$('#VOLUM3').val('');
		$('#GREUTATE_VOL').val('');

	}else if(sel==2 && piese > 0) {
		$('#TIP_OBJ_3').attr('checked', false);
		$('#TIP_OBJ_1').attr('checked', false);

		$('#GREUTATE').removeAttr('readonly');
		$('#VOLUM1').removeAttr('readonly');
		$('#VOLUM2').removeAttr('readonly');
		$('#VOLUM3').removeAttr('readonly');

		ValidareCampuriIntroducere('TIP_OBJ_3');
		ValidareCampuriIntroducere('TIP_OBJ_2');
		ValidareCampuriRetur('TIP_OBJ_1');

	}
	else if(sel==3 && $('#TIP_OBJ_3').is(':checked')) {
		$('#TIP_OBJ_2').val('0');
		$('#TIP_OBJ_1').attr('checked', false);

		$('#GREUTATE').removeAttr('readonly');
		$('#VOLUM1').removeAttr('readonly');
		$('#VOLUM2').removeAttr('readonly');
		$('#VOLUM3').removeAttr('readonly');

		ValidareCampuriIntroducere('TIP_OBJ_3');
		ValidareCampuriIntroducere('TIP_OBJ_2');
		ValidareCampuriRetur('TIP_OBJ_1');
	}
	else if(sel==9){
		var vol1 = parseInt($('#VOLUM1').val());
		if(vol1.length==3){
			$('#VOLUM2').focus();
		}
		var vol2 = parseInt($('#VOLUM2').val());
		var vol3 = parseInt($('#VOLUM3').val());
		if(isNaN(vol1) || isNaN(vol2) || isNaN(vol3) || vol1 == 0 || vol2 == 0 || vol3 == 0){
			$('#GREUTATE_VOL').val('');
		}
		else {
			var vol = (vol1*vol2*vol3)/6000;
			vol = Math.ceil(vol).toFixed(2);
			$('#GREUTATE_VOL').val(vol);
		}
		ValoareExpeditie(0);
		return;
	}
	else if(sel==10){
		var vol2 = parseInt($('#VOLUM2').val());
		if(vol2.length==3){
			$('#VOLUM3').focus();
		}
		var vol1 = parseInt($('#VOLUM1').val());
		var vol3 = parseInt($('#VOLUM3').val());
		if(isNaN(vol1) || isNaN(vol2) || isNaN(vol3) || vol1 == 0 || vol2 == 0 || vol3 == 0){
			$('#GREUTATE_VOL').val('');
		}
		else {
			var vol = (vol1*vol2*vol3)/6000;
			vol = Math.ceil(vol).toFixed(2);
			$('#GREUTATE_VOL').val(vol);
		}
		ValoareExpeditie(0);
		return;
	}
	else if(sel==11){
		var vol1 = parseInt($('#VOLUM1').val());
		var vol2 = parseInt($('#VOLUM2').val());
		var vol3 = parseInt($('#VOLUM3').val());
		if(isNaN(vol1) || isNaN(vol2) || isNaN(vol3) || vol1 == 0 || vol2 == 0 || vol3 == 0){
			$('#GREUTATE_VOL').val('');
		}
		else {
			var vol = (vol1*vol2*vol3)/6000;
			vol = Math.ceil(vol).toFixed(2);
			$('#GREUTATE_VOL').val(vol);
		}
		ValoareExpeditie(0);
		return;
	}

	if ($('#TIP_OBJ_1').is(':checked') || $('#TIP_OBJ_3').is(':checked')){
		piese = 0;
	}

	var ret_nt=ret_doc=liv_sambata=liv_sediu=ret_amb=ret_colet=copen=sms=0;

	if ($('#RET_NT').is(':checked'))
		ret_nt=1;
	if ($('#RET_DOC').is(':checked'))
		ret_doc=1;
	if ($('#LIV_S').is(':checked'))
		liv_sambata=1;
	if ($('#RET_AMB').is(':checked'))
		ret_amb=1;
	if ($('#RET_COLET').is(':checked')){
		ret_colet=1;
	}
	if ($('#LIV_SEDIU').is(':checked'))
		liv_sediu=1;
	if ($('#COPEN').is(':checked'))
		copen=1;
	if ($('#SMS').is(':checked'))
		sms=1;

	var km_livrare=$('#KM_EXT_LIV').val();
	var km_preluare=$('#KM_EXT_PREL').val();

	var gr=$('#GREUTATE').val();
	var volum1 = parseInt($('#VOLUM1').val());
	var volum2 = parseInt($('#VOLUM2').val());
	var volum3 = parseInt($('#VOLUM3').val());
	if(isNaN(volum1) || isNaN(volum2) || isNaN(volum3) || volum1 == 0 || volum2 == 0 || volum3 == 0){
		volum1 = volum2 = volum3 = 0;
	}

	var asigurare=$('#asigurare').val();
	var ramburs=$('#ramburs').val();
	var pret_impus = $('#PRET_IMPUS').val();
	var tip_plata = 0;
	if(Number($('#ramburs').val()) > 0 && !isNaN(parseInt($('#tip_plata').val()))) tip_plata = parseInt($('#tip_plata').val());

	var expeditie = parseInt($('#NR_EXP').val());
	var referire = parseInt($('#referinta_expeditie').val());
	var tip_exp = parseInt($('#tip_exp').val());
	var platitor=parseInt($('#PLATITOR').val());
	var expeditor=parseInt($('#EXPEDITOR').val());
	var expeditor_localitate = parseInt($('#EXPEDITOR_LOCALITATE').val());
	var destinatar=parseInt($('#DESTINATAR').val());
	var destinatar_localitate = parseInt($('#DESTINATAR_LOCALITATE').val());

	var tip_tarif=0;
	if(expeditor_localitate != destinatar_localitate) tip_tarif = 1;  //tipul de contract

	//console.log("3");
	if(isNaN(expeditor) || expeditor == 0) { return false; }
	//console.log("4");
	if(isNaN(expeditor_localitate) || expeditor_localitate == 0) { return false; }
	//console.log("5");
	if(isNaN(destinatar) || destinatar == 0) { return false; }
	//console.log("6");
	if(isNaN(destinatar_localitate) || destinatar_localitate == 0) { return false; }
	//console.log("7");
	if(isNaN(platitor) || platitor == 0) { return false; }
	//console.log("8");

	$.post(HTTP + 'expeditii/valoare_expeditie/',{
		NR_EXP: expeditie,
		referinta_expeditie: referire,
		tip_exp: tip_exp,
		PLATITOR:platitor,
		EXPEDITOR:expeditor,
		EXPEDITOR_LOCALITATE:expeditor_localitate,
		DESTINATAR:destinatar,
		DESTINATAR_LOCALITATE:destinatar_localitate,
		TIP_OBJ_1:$('#TIP_OBJ_1').is(':checked')?1:0,
		TIP_OBJ_2:piese,
		TIP_OBJ_3:$('#TIP_OBJ_3').is(':checked')?1:0,
		tip_tarif:tip_tarif,
		RET_NT:ret_nt,
		RET_DOC:ret_doc,
		LIV_S:liv_sambata,
		LIV_SEDIU:liv_sediu,
		RET_AMB:ret_amb,
		RET_COLET:ret_colet,
		COPEN:copen,
		SMS:sms,
		KM_EXT_LIV:km_livrare,
		KM_EXT_PREL:km_preluare,
		GREUTATE:gr,
		VOLUM1:volum1,
		VOLUM2:volum2,
		VOLUM3:volum3,
		asigurare:asigurare,
		ramburs:ramburs,
		tip_plata:tip_plata,
		PRET_IMPUS:pret_impus,
	}, function(response) {
		try {
			var vals = JSON.parse(response);
		}
		catch(err) {
			return false;
		}
        if (vals.error == 1){
			$('#valoare_expeditie').html(vals.tExpeditie);
            $('#valoare_km').html(vals.tKm);
            $('#valoare_greutate').html(vals.tGreutate);
            $('#valoare_asigurare').html(vals.tAsigurare);
			$('#valoare_ramburs').html(vals.tRamburs);
			$('#proc_asig').val(vals.procAsigurare);
            var moneda = vals.moneda;

            $('#span_ht').html(vals.ht + ' '+ moneda);
            $('#span_tva').html(vals.tva + ' '+ moneda);
            $('#span_ttc').html(vals.ttc +' '+ moneda);
			$('#expeditie_moneda').val(moneda);

			return true;
		}
		return false;
	});
}

function AdaugaExpeditie(){

	if($("#EXPEDITOR_LOCALITATE").val().trim() == '') { SelectareCamp('EXPEDITOR_LOCALITATE','Selectati localitatea expeditor!'); return false;}
	else if($("#EXPEDITOR").val().trim() == '') { SelectareCamp('EXPEDITOR','Selectati expeditorul!'); return false;}
	else if($("#DESTINATAR_LOCALITATE").val().trim() == '')  { SelectareCamp('DESTINATAR_LOCALITATE','Selectati localitatea destinatar!'); return false;}
	else if($("#DESTINATAR").val().trim() == '') { SelectareCamp('DESTINATAR','Selectati destinatarul!'); return false;}
	else if(!($('#plateste_expeditor').is(':checked') || $('#plateste_destinatar').is(':checked') || $('#plateste_altul').is(':checked'))) { SelectareCamp('PLATITOR','Selectati platitorul!'); return false;}
	else if($('#plateste_altul').is(':checked') && $("#PLATITOR").val().trim() == '') { SelectareCamp('PLATITOR','Selectati platitorul!'); return false;}

	var expeditie = parseInt($('#NR_EXP').val().trim());
	if(isNaN(expeditie)) expeditie = 0;
	if(expeditie > 0) {
		AfiseazaEroare('Pentru Adaugare expeditie noua, click pe "Generare Nr" sau "NT noua"!');
		return false;
	}

	var greutate = Number($('#GREUTATE').val().trim());
	var plicuri = 0;
	var colete = parseInt($('#TIP_OBJ_2').val().trim());
	if(isNaN(colete)) colete = 0;
	var paleti = 0;
	var sms = 0;
	var telefonRegexp = new RegExp(/^07\d{8}$/g);
	var telefon = $('#DESTINATAR_TELEFON').val().trim();
	var tip_exp = parseInt($('#tip_exp').val().trim());

	if($('#TIP_OBJ_1').is(':checked') || $('#TIP_OBJ_3').is(':checked'))
		plicuri=1;

	if($('#SMS').is(':checked'))
		sms=1;

	if(plicuri == 0 && paleti == 0 && tip_exp != 6 && tip_exp != 5){
		if(colete == 0){
			AfiseazaEroare('Introduceti numarul de colete!');
			$('#TIP_OBJ_2').focus();
			return false;
		}
		if(colete < 1 || colete > 99){
			AfiseazaEroare('Minim colete : 1, Maxim colete 99 !');
			$('#TIP_OBJ_2').focus();
			return false;
		}
	}

	if((colete > 0 || paleti == 1) && (isNaN(greutate) || greutate < 1) && tip_exp != 6 && tip_exp != 5){
		AfiseazaEroare('Introduceti greutatea!');
		$('#GREUTATE').focus();
		return false;
	}

	if(sms == 1 && (!telefon || !telefonRegexp.test(telefon))) {
		AfiseazaEroare('Corectati telefon destinatar : 07XXXXXXXX');
		return false;
	}

	if(!PlatitorValid())
		return;

	if(expeditie == 0){
		$('#NR_EXP').val('NEW');
		AdaugareExpeditie();
	}
}

function AdaugareExpeditie(){
	var greutate = Number($('#GREUTATE').val().trim());
	var plicuri = 0;
	var colete = parseInt($('#TIP_OBJ_2').val().trim());
	if(isNaN(colete))  colete = 0;
	var paleti = 0;
	var sms = 0;
	var telefonRegexp = new RegExp(/^07\d{8}$/g);
	var telefon = $('#DESTINATAR_TELEFON').val().trim();
	var tip_exp = parseInt($('#tip_exp').val().trim());

	if($('#TIP_OBJ_1').is(':checked'))
		plicuri=1;

	if($('#TIP_OBJ_3').is(':checked'))
		paleti=1;

	if($('#SMS').is(':checked'))
		sms=1;

	if(plicuri == 0 && paleti == 0 && tip_exp != 6 && tip_exp != 5){
		if(colete == 0){
			AfiseazaEroare('Introduceti numarul de colete!');
			$('#TIP_OBJ_2').focus();
			return false;
		}
		if(colete < 1 || colete > 99){
			AfiseazaEroare('Minim colete : 1, Maxim colete 99 !');
			$('#TIP_OBJ_2').focus();
			return false;
		}
	}


	if((colete > 0 || paleti == 1) && (isNaN(greutate) || greutate < 1) && tip_exp != 6 && tip_exp != 5){
		AfiseazaEroare('Introduceti greutatea!');
		$('#GREUTATE').focus();
		return false;
	}

	if(sms == 1 && (!telefon || !telefonRegexp.test(telefon))) {
		AfiseazaEroare('Corectati telefon destinatar : 07XXXXXXXX');
		return false;
	}

	if(!PlatitorValid())
		return;

	if(greutate > 1500.00){
		$('#confirmare_expeditie').html('<p>Acest AWB are sigur peste 1500 de kg? </p>');
		$("#confirmare_expeditie").dialog("destroy");
		$("#confirmare_expeditie").dialog({
			width : 400,
			height : 210,
			draggable : false,
			resizable : false,
			modal: true,
			buttons: {
				"DA": function() {
					$(this).dialog("close");
					AdaugareExpeditie_backup();
				},
				'Nu': function() {
					$(this).dialog("close");
				}
			},
			close : function() {
				$(this).dialog("destroy");
			}
		});
	}
	else
		AdaugareExpeditie_backup();
	return true;
}


function AdaugareExpeditie_backup(){
	$.post(HTTP + 'expeditii/adaugare_expeditie/',{
		data: $("#form_expeditie").serializeArray(),
	}, function(response) {
		var rasp = response.split('|||');
		PrepareResponse(rasp[0]);
		if (rasp[0] == 1){
			jQuery("#expeditii_listare").jqGrid('setGridParam', {
				url : HTTP + 'expeditii/json/introducere'
			}).trigger("reloadGrid");

			AfisareContinutExpeditie(rasp[1]);

			setTimeout(function(){
	            jQuery("#expeditii_listare").setSelection (rasp[1], true);
	        },1000);

			AfiseazaEroare('Expeditie : <b>' + rasp[1] + '</b> adaugata!','Mesaj');
		}else{
			AfiseazaEroare(rasp[1]);
			return false;
		}
	});
}

function PlatitorValid(){
	if($('#plateste_altul').is(':checked') && $('#mod_plata').val() == 0  && $('#PLATITOR').val()  > 0) {
		AfiseazaEroare("Platitorul tert nu trebuie sa aiba Plata per NT");
		$('#plateste_altul').attr("checked", false);
		$('#PLATITOR').val('');
		$('#PLATITOR_CC').val('');
		$('#PLATITOR_NUME').val('');
		$('#plateste').val('');
		return false;
	} else {
		return true;
	}
}

function EditareExpeditie(){
	var greutate = Number($('#GREUTATE').val().trim());
	var plicuri = 0;
	var colete = parseInt($('#TIP_OBJ_2').val().trim());
	var tip_exp = parseInt($('#tip_exp').val().trim());
	if(isNaN(colete))  colete = 0;
	var paleti = 0;
	var sms = 0;
	var telefonRegexp = new RegExp(/^07\d{8}$/g);
	var telefon = $('#DESTINATAR_TELEFON').val().trim();

	if($('#TIP_OBJ_1').is(':checked'))
		plicuri=1;

	if($('#TIP_OBJ_3').is(':checked'))
		paleti=1;

	if($('#SMS').is(':checked'))
		sms=1;
	//alert(greutate + ' ' + plicuri + ' ' + colete + ' ' + paleti + ' ' + sms + ' ' + tip_exp);
	if(plicuri == 0 && paleti == 0 && tip_exp != 6 && tip_exp != 5){
		if(colete == 0){
			AfiseazaEroare('Introduceti numarul de colete!');
			$('#TIP_OBJ_2').focus();
			return false;
		}
		if(colete < 1 || colete > 99){
			AfiseazaEroare('Minim colete : 1, Maxim colete 99 !');
			$('#TIP_OBJ_2').focus();
			return false;
		}
	}


	if((colete > 0 || paleti == 1) && (isNaN(greutate) || greutate < 1) && tip_exp != 6 && tip_exp != 5){
		AfiseazaEroare('Introduceti greutatea!');
		$('#GREUTATE').focus();
		return false;
	}

	if(sms == 1 && (!telefon || !telefonRegexp.test(telefon))) {
		AfiseazaEroare('Corectati telefon destinatar : 07XXXXXXXX');
		return false;
	}

	if(!PlatitorValid())
		return;

	$("#confirmare_expeditie").html('Sunteti Sigur!');
	$("#confirmare_expeditie").dialog("destroy");
	$("#confirmare_expeditie").dialog({
		width : 200,
		height : 120,
		draggable : false,
		resizable : false,
		modal: true,
		title : 'Eroare',
		buttons: {
			"Modifica": function() {

				$.post(HTTP + 'expeditii/editare_expeditie/',{
					data: $("#form_expeditie").serializeArray(),
				}, function(response) {
					var rasp = response.split('|||');
					PrepareResponse(rasp[0]);
					if (rasp[0] == 1){
						jQuery("#expeditii_listare").jqGrid('setGridParam', {
							url : HTTP + 'expeditii/json/introducere'
						}).trigger("reloadGrid");
						AfisareContinutExpeditie(rasp[1]);

						setTimeout(function(){
				            jQuery("#expeditii_listare").setSelection (rasp[1], true);
				        },1000);

						//AfiseazaEroare('Expeditie Editata!');

					}else{
						AfiseazaEroare(rasp[1]);
						return false;
					}
				});
				$(this).dialog("close");
			}
		},
		close : function() {
			$(this).dialog("destroy");
		}
	});
}


function StergereExpeditie(){
	$("#confirmare_expeditie").html('Sunteti Sigur!');
	$("#confirmare_expeditie").dialog("destroy");
	$("#confirmare_expeditie").dialog({
		width : 200,
		height : 120,
		draggable : false,
		resizable : false,
		modal: true,
		title : 'Eroare',
		buttons: {
			"Sterge": function() {
				$.post(HTTP + 'expeditii/sterge_expeditie/',{
					data: $("#form_expeditie").serializeArray(),
				}, function(response) {

					var rasp = response.split('|||');
					PrepareResponse(rasp[0]);
					if (rasp[0] == 1){
						jQuery("#expeditii_listare").jqGrid('setGridParam', {
							url : HTTP + 'expeditii/json/introducere'
						}).trigger("reloadGrid");
					}else{
						AfiseazaEroare(rasp[1]);
						return false;
					}
				});
				$(this).dialog("close");
			}
		},
		close : function() {
			$(this).dialog("destroy");
		}
	});
}

function InitiereExpeditie(){

	var height = 125;
	var width = 250;
	var title = 'Confirmare';
	$("#eroare").html('Sunteti sigur?');
	$("#eroare").attr('title', title);
	$("#eroare").dialog({
		modal : true,
		draggable : false,
		height : height,
		width : width,
		resizable : false,
		buttons : {
			'Da' : function() {
				$('#valoare_expeditie').html('0.00');
				$('#valoare_km').html('0.00');
				$('#valoare_greutate').html('0.00');
				$('#valoare_asigurare').html('0.00');
				$('#proc_asig').val('');
				$('#asigurare').val('');

				$('#span_ht').html('0.00 LEI');
				$('#span_tva').html('0.00 LEI');
				$('#span_ttc').html('0.00 LEI');

				$('#tip_exp').val('0');
				$('#TIP_OBJ_1').attr("checked", false);
				$('#TIP_OBJ_2').val('');
				$('#TIP_OBJ_3').attr("checked", false);
				$('#RET_NT').attr("checked", false);
				$('#RET_DOC').attr("checked", false);
				$('#GREUTATE').val('0.500');
				$('#VOLUM1').val('');
				$('#VOLUM2').val('');
				$('#VOLUM3').val('');
				$('#GREUTATE_VOL').val('');
				$('#KM_EXT_PREL').val('0');
				$('#KM_EXT_LIV').val('0');
				$('#ramburs').val('');
				$('#CODURI').val('');
				$('#RET_AMB').attr("checked", false);
				$('#RET_COLET').attr("checked", false);
				$('#LIV_S').attr("checked", false);
				$('#LIV_SEDIU').attr("checked", false);
				$('#OBSERVATII').val('');
				$('#id_comanda').val('');

				$('#referinta_expeditie').val('');
				$('#centru_livrare').val('');
				$('#proc_asig').val('');

				$('#DESTINATAR').val('');
				$('#DESTINATAR_NUME').val('');
				$('#DESTINATAR_LOCALITATE').val('');
				$('#DESTINATAR_LOCALITATE_NUME').val('');
				$('#DESTINATAR_CONTACT').val('');
				$('#DESTINATAR_ADRESA').val('');
				$('#DESTINATAR_TELEFON').val('');

				$('#EXPEDITOR').val('');
				$('#EXPEDITOR_CUI').val('');
				$('#EXPEDITOR_CC').val('');
				$('#EXPEDITOR_NUME').val('');
				$('#EXPEDITOR_LOCALITATE').val('');
				$('#EXPEDITOR_LOCALITATE_NUME').val('');
				$('#EXPEDITOR_CONTACT').val('');
				$('#EXPEDITOR_ADRESA').val('');
				$('#EXPEDITOR_TELEFON').val('');

				$('#PLATITOR').val('');
				$('#PLATITOR_NUME').val('');
				$("#PLATITOR_NUME").prop('readonly', true);

				$('#plateste_expeditor').attr("checked", false);
				$('#plateste_destinatar').attr("checked", false);
				$('#plateste_altul').attr("checked", false);

				$('#mod_plata').val(0);

				SetareCC();

				GenerareNrExpeditie();

				$(this).dialog("destroy");
			},
			'Nu' : function() {
				$(this).dialog("destroy");
			}
		}
	});
}

function GenerareNrExpeditie(){
	$('#NR_EXP').val('NEW');
	$('#NR_EXP').css('color','red');
}

/* //////////////////////////////////////////////////////
				END INTRODUCERE EXPEDITIE
//////////////////////////////////////////////////////  */



/* //////////////////////////////////////////////////////
				START COMENZI
//////////////////////////////////////////////////////  */
function refreshClientiDispecerat(disp)
{
	disp = parseInt(disp);
	jQuery("#clienti_listare").jqGrid('setGridParam', {
				 url : HTTP + 'comenzi/json/listare_clienti?dispecerat=' + disp
			}).trigger("reloadGrid");

}

function Grid_ClientiListare(disp) {
	var t_url = HTTP + 'comenzi/json/listare_clienti';
	if(disp) t_url += '?dispecerat=' + disp;
	$("#clienti_listare").jqGrid({
		url : t_url,
		datatype : "json",
		colNames : [ 'Nume', 'Localitate', 'Adresa', 'Contract', 'Mod Plata'],
		colModel : [ {
			name : 'nume',
			index : 'cl.nume',
			width: 200
		}, {
			name : 'localitate',
			index : 'lc.nume_lc',
			width: 60
		}, {
			name : 'adresa_grid',
			sortable:false,
			search:false,
			width: 130
		}, {
			name : 'tarif',
			index : 'cl.tarif',
			stype : 'select',
			searchoptions : {
				value : ":Toate;0:Nu;1:T. negociat;2:T. lista",
				sopt : [ 'eq' ]
			},
			width: 50
		}, {
			name : 'mod_plata',
			index : 'cl.mod_plata',
			stype : 'select',
			searchoptions : {
				value : ":Toate;0:Per NT;1:Factura periodica",
				sopt : [ 'eq' ]
			},
			width: 50
		} ],
		rowNum : 40,
		rowList : [ 50, 100 ],
		rownumbers : false,
		height : 400,
		width : 548,
		pager : '#clienti_paginatie',
		viewrecords : true,
		mtype : "GET",
		sortname : 'cl.mod_plata',
		sortorder : "desc",
		caption : 'Lista Clienti',
		onSelectRow : function(id) {
			var row = jQuery("#clienti_listare").jqGrid('getRowData',id);
			$('#client_nume').val(row.nume.trim());
			$('#client_id').val(id);
			//preluare detalii client
			$.post('/comenzi/prmVclient/', {
				client_id: id
			}, function(response) {
				var rasp = response.split('|||');
				$('#localitate_id').val(rasp[0]);
				$('#localitate_nume').val(rasp[1]);
				$('#adresa').val(rasp[2].trim());
				$('#contact').val(rasp[3].trim());
				$('#telefon').val(rasp[4].trim());
				var today = new Date();
				var hCurr = today.getHours();

				$("#collect_at").datepicker('setDate', today);
				if(hCurr > 18)
				var hEnd = parseInt($("#h_end").val());
				$("#h_start").val(hCurr+1);
				if(hEnd < hCurr +1) $("#h_end").val(hCurr+2);
			});
		},
		rowattr: function (rd) {
			if (rd.tarif != 'Nu') {
				return {"class": "myAltRowClass"};
		}}
	});
	$("#clienti_listare").jqGrid('bindKeys');
	jQuery("#clienti_listare").jqGrid('filterToolbar', {
		stringResult : true,
		searchOnEnter : false
	});
}

function AdaugaComanda()
{
	$("#confirmare").html('Trimiteti comanda ?');
	$("#confirmare").attr('title', 'Confirmare');
	$("#confirmare").dialog({
		modal : true,
		draggable : false,
		height : 150,
		width : 250,
		resizable : false,
		buttons : {
				"DA": function() {
					$(this).dialog("close");
					$.ajax({
  						type: 'POST',
  						url: HTTP + 'comenzi/adaugare_comanda',
  						data: { data:$("#comanda_form").serializeArray() },
  						success: function(response) {
  							var id = parseInt(response);
							if (id > 0) {
								$("#comanda_form")[0].reset();
								$("#messageBox").css('color','green');
								$("#messageBox").html('Comanda nr. '+id+' preluata cu succes');
								$("#messageBox").show();
							}
							else {
								$("#messageBox").css('color','red');
								$("#messageBox").html(response);
								$("#messageBox").show();
							}
							$("#collect_at").datepicker('setDate', new Date());
						},
  						async:false
					});
				},
				'Nu': function() {
					$(this).dialog("close");
				}
		},
		close : function() {
			$(this).dialog("destroy");
		}
	});
}

function DistribuieComanda(comanda_id){
	var agent_id = $('#agent_id').val();
	var comunicare = $('#comunicare').val();
	var mesaj = $('#mesaj').val();

	if(comanda_id=='' || agent_id==''){
		AfiseazaEroare('Selectati toate informatiile!');
		return false;
	}
	$.post(HTTP + 'comenzi/distribuire_comanda/', {
		comanda_id: comanda_id,
		agent_id: agent_id,
		comunicare: comunicare,
		mesaj: mesaj
	}, function(response) {
		var rasp = response.split('|||');
		PrepareResponse(rasp[0]);
		if (rasp[0] == 1){
			jQuery("#lista_comenzi").trigger("reloadGrid");
		}
		else
		{
			AfiseazaEroare(rasp[1]);
			return false;
		}
	});
	return true;
}

function ReDistribuieComanda(comanda_id){
	var agent_id = $('#agent_id').val();
	var mesaj = $('#mesaj').val();

	if(comanda_id=='' || agent_id==''){
		AfiseazaEroare('Selectati toate informatiile!');
		return false;
	}
	$.post(HTTP + 'comenzi/redistribuire_comanda/', {
		comanda_id: comanda_id,
		agent_id: agent_id,
		mesaj: mesaj
	}, function(response) {
		var rasp = response.split('|||');
		PrepareResponse(rasp[0]);
		if (rasp[0] == 1){
			jQuery("#lista_comenzi").trigger("reloadGrid");
		}
		else
		{
			AfiseazaEroare(rasp[1]);
			return false;
		}
	});
	return true;
}

function ModificaDcComanda(comanda_id){
	var mesaj = $('#mesaj').val();
	var collect_at = $('#collect_at').val();
	var h_start = $('#h_start').val();
	var h_end = $('#h_end').val();

	if(comanda_id==''){
		AfiseazaEroare('comanda inexistenta');
		return false;
	}

	$.post(HTTP + 'comenzi/modifica_comanda_dc/', {
		comanda_id: comanda_id,
		collect_at: collect_at,
		h_start: h_start,
		h_end: h_end,
		mesaj: mesaj
	}, function(response) {
		var rasp = response.split('|||');
		PrepareResponse(rasp[0]);
		if (rasp[0] == 1){
			jQuery("#lista_comenzi").trigger("reloadGrid");
		}
		else
		{
			AfiseazaEroare(rasp[1]);
			return false;
		}
	});
	return true;
}

function ColecteazaComanda(comanda_id, agent_id){
	if(comanda_id==''){
		AfiseazaEroare('comanda inexistenta');
		return false;
	}

	$.post(HTTP + 'comenzi/colectare_comanda/', {
		comanda_id: comanda_id,
		agent_id: agent_id
	}, function(response) {
		var rasp = response.split('|||');
		PrepareResponse(rasp[0]);
		if (rasp[0] == 1){
			jQuery("#lista_comenzi").trigger("reloadGrid");
		}
		else
		{
			AfiseazaEroare(rasp[1]);
			return false;
		}
	});
	return true;
}

function AnuleazaComanda(comanda_id){
	var mesaj = $('#mesaj').val();

	if(comanda_id==''){
		AfiseazaEroare('comanda inexistenta');
		return false;
	}

	$.post(HTTP + 'comenzi/anulare_comanda/', {
		comanda_id: comanda_id,
		mesaj: mesaj
	}, function(response) {
		var rasp = response.split('|||');
		PrepareResponse(rasp[0]);
		if (rasp[0] == 1){
			jQuery("#lista_comenzi").trigger("reloadGrid");
		}
		else
		{
			AfiseazaEroare(rasp[1]);
			return false;
		}
	});
	return true;
}

function AfisareDetaliiComanda(id){
	  $.get(HTTP + 'comenzi/detalii_comanda/'+id,'', function(response) {
			var rasp = response.split('|||');
			PrepareResponse(rasp[0]);
			$('#dialog-form #agent').prop("readonly", false);
			$('#dialog-form #comunicare').removeAttr("disabled");
			$('#dialog-form #mesaj').prop("readonly", false);
			if (rasp[0] == 1){
				$('#dialog-form #comanda_id').val(id);
				$('#dialog-form #collect_at').val(rasp[2]);
				$('#dialog-form #client').text(rasp[3]);
				$('#dialog-form #localitate').text(rasp[4]);
				$('#dialog-form #localitate_id').val(rasp[5]);
				$('#dialog-form #adresa').text(rasp[6]);
				$('#dialog-form #contact').text(rasp[7]);
				$('#dialog-form #telefon').text(rasp[8]);
				$('#dialog-form #nr_obj_colet').text(rasp[9]);
				$('#dialog-form #nr_obj_palet').text(rasp[10]);
				$('#dialog-form #kg_obj').text(rasp[11]);
				$('#dialog-form #vol_obj').text(rasp[12]);
				$('#dialog-form #h_start').val(rasp[13]);
				$('#dialog-form #h_end').val(rasp[14]);
				$('#dialog-form #observatii').text(rasp[15]);
				$('#dialog-form #ch_created_at').text(rasp[16]);
				$('#dialog-form #status').text(rasp[17]);
				$('#dialog-form #motiv').text(rasp[18]);
				$('#dialog-form #agent_id').val(rasp[19]);
				$('#dialog-form #agent').val(rasp[20]);
				$('#dialog-form #telefon_agent').val(rasp[21]);
				$('#dialog-form #comunicare').val(rasp[22]);
				$('#dialog-form #mesaj').val(rasp[23]);
				$('#dialog-form').dialog( "option", "title", "Detalii comanda nr. : "+id );
				var dist = 'Transmite';
				var redist = 'Retransmite';
				var btn_cancel = {"text": 'Anuleaza comanda', click: function() { if(AnuleazaComanda(id)) $( this ).dialog( "close" );}};
				var btn_modify_dc = {"text": 'Modifica data colectare', click: function() { if(ModificaDcComanda(id)) $( this ).dialog( "close" );}};
				var btn_close = {"text": "Inchide [esc]",click: function() {$( this ).dialog( "close" );}}
				var btn_collect = {"text": 'Comanda colectata', click: function() { if(ColecteazaComanda(id, rasp[19])) $( this ).dialog( "close" );}};

				if(rasp[17] == 'refuzata') dist = 'Retransmite';
				//alert(rasp[17]);
				if(rasp[17] == 'initiala' || rasp[17] == 'refuzata')
				{
					$('#dialog-form').dialog( "option", "buttons",
  						[
  							btn_modify_dc,
  							btn_cancel,
    						{
    							"text": dist,
        						click: function() {
          							if(DistribuieComanda(id))
          								$( this ).dialog( "close" );
        	 					}
      						},
      						btn_close
  						]
					);
				}
				else if(rasp[17] == 'transmisa'){
					$('#dialog-form #agent').prop("readonly", true);
					$('#dialog-form #comunicare').prop("disabled", "disabled");
					$('#dialog-form #mesaj').prop("readonly", true);
					$('#dialog-form').dialog( "option", "buttons",
  						[
  							btn_cancel,
    						{
    							"text": redist,
        						click: function() {
          							if(ReDistribuieComanda(id))
          								$( this ).dialog( "close" );
        	 					}
      						},
      						btn_close
  						]
					);
				}
				else if(rasp[17] == 'acceptata' && rasp[22] == 2){
					$('#dialog-form #agent').prop("readonly", true);
					$('#dialog-form #comunicare').prop("disabled", "disabled");
					$('#dialog-form').dialog( "option", "buttons",
  						[
  							btn_collect,
  							btn_modify_dc,
  							btn_cancel,
      						btn_close
  						]
					);
				}
				else if(rasp[17] != 'colectata' && rasp[17] != 'anulata') {
					$('#dialog-form #agent').prop("readonly", true);
					$('#dialog-form #comunicare').prop("disabled", "disabled");
					$('#dialog-form').dialog( "option", "buttons",
  						[
  							btn_modify_dc,
  							btn_cancel,
      						btn_close
  						]
					);
				}
				else {
					$('#dialog-form #agent').prop("readonly", true);
					$('#dialog-form #comunicare').prop("disabled", "disabled");
					$('#dialog-form #mesaj').prop("readonly", true);
					$('#dialog-form').dialog( "option", "buttons",
  						[
      						btn_close
  						]
					);
				}
			}else{
				//error
				$('#dialog-form').text("Comanda nr. "+id+" invalida");
			}
			$('#dialog-form').dialog("open");
	  });
}

function AfisareDetaliiComandaIstoric(id){
	  $.get(HTTP + 'comenzi/detalii_comanda/'+id,'', function(response) {
			var rasp = response.split('|||');
			PrepareResponse(rasp[0]);
			$('#dialog-form #agent').prop("readonly", false);
			$('#dialog-form #comunicare').removeAttr("disabled");
			$('#dialog-form #mesaj').prop("readonly", false);
			if (rasp[0] == 1){
				$('#dialog-form #comanda_id').val(id);
				$('#dialog-form #collect_at').text(rasp[2]);
				$('#dialog-form #client').text(rasp[3]);
				$('#dialog-form #localitate').text(rasp[4]);
				$('#dialog-form #localitate_id').val(rasp[5]);
				$('#dialog-form #adresa').text(rasp[6]);
				$('#dialog-form #contact').text(rasp[7]);
				$('#dialog-form #telefon').text(rasp[8]);
				$('#dialog-form #nr_obj_colet').text(rasp[9]);
				$('#dialog-form #nr_obj_palet').text(rasp[10]);
				$('#dialog-form #kg_obj').text(rasp[11]);
				$('#dialog-form #vol_obj').text(rasp[12]);
				$('#dialog-form #h_start').text(rasp[13]);
				$('#dialog-form #h_end').text(rasp[14]);
				$('#dialog-form #observatii').text(rasp[15]);
				$('#dialog-form #ch_created_at').text(rasp[16]);
				$('#dialog-form #status').text(rasp[17]);
				$('#dialog-form #motiv').text(rasp[18]);
				$('#dialog-form #agent_id').val(rasp[19]);
				$('#dialog-form #agent').val(rasp[20]);
				$('#dialog-form #telefon_agent').val(rasp[21]);
				$('#dialog-form #comunicare').val(rasp[22]);
				$('#dialog-form #mesaj').val(rasp[23]);
				$('#dialog-form').dialog( "option", "title", "Detalii comanda nr. : "+id );
				var dist = 'Transmite';
				var btn_close = {"text": "Inchide [esc]",click: function() {$( this ).dialog( "close" );}}

				$('#dialog-form #agent').prop("readonly", true);
				$('#dialog-form #comunicare').prop("disabled", "disabled");
				$('#dialog-form #mesaj').prop("readonly", true);
				$('#dialog-form').dialog( "option", "buttons",
  						[
      						btn_close
  						]
				);
			}else{
				//error
				$('#dialog-form').text("Comanda nr. "+id+" invalida");
			}
			$('#dialog-form').dialog("open");
	  });
}

function AfisarePerioada_Distribuire(){
	var dates = jQuery("#preluate_data_start, #preluate_data_final")
	.datepicker({
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
			if (this.id == "preluate_data_start") {
				dates.not(this).datepicker("setDate", date);
			}
			AfiseazaComenziPreluate();
		}
	});

	var dates1 = jQuery("#distribuite_data_start, #distribuite_data_final")
	.datepicker({
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
			dates1.not(this).datepicker("option", option, date);
			if (this.id == "distribuite_data_start") {
				dates1.not(this).datepicker("setDate", date);
			}
			AfiseazaComenziDistribuite();
		}
	});
}

function AgentiDistribuireComenzi(){
	$("#agent").autocomplete({
		source : function(request, response) {
			$.ajax({
				url : HTTP + "comenzi/json/agenti_distribuire",
				type: "POST",
				dataType : "json",
				data : {
					name_startsWith : request.term,
					localitate_id : parseInt($('#localitate_id').val())
				},
				success : function(data) {
					response($.map(data.rezultat, function(item) {
						return {
							cod : item.cod,
							label : item.nume+' ('+item.centru+')',
							value : item.nume,
							telefon : item.telefon
						}
					}));
				}
			});
		},
		minLength : 1,
		focus : function(event, ui) {
			$("#agent").val(ui.item.label);
			return false;
		},
		select : function(event, ui) {
			$("#agent_id").val(ui.item.cod);
			$("#agent").val(ui.item.value);
			$("#telefon_agent").val(ui.item.telefon);
			return false;
		},
		open : function() {
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
			$("#agent_id").val('');
			$("#telefon_agent").val('');
		},
		change : function(event, ui) {
			if($("#agent").val() == '')
			{
				$("#agent_id").val("");
			}
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}

function IntroducereMesajDistribuire(max){
	$('#mesaj').keyup(function(){
        if($(this).val().length > max){
            $(this).val($(this).val().substr(0, max));
        }

        $('#mesaj_nr_caractere').html((max - $(this).val().length));
    });
}

function Grid_ListaComenziDistribuire(disp) {
	var t_url = HTTP + 'comenzi/json/lista_distribuire';
	if(disp) t_url += '?dispecerat=' + disp;

	var grid = jQuery('#lista_comenzi');

	grid.jqGrid(
	{
		url : t_url,
		datatype: 'json',
		mtype: 'GET',
		colNames : [ 'Nr. comanda', 'Data colectarii','Data status', 'Status', 'S.Time', 'status_time_sec', 'Motiv', 'Comunicare', 'Centru', 'Client', 'Localitate', 'Adresa', 'Colete', 'Paleti', 'Greutate', 'Curier','Tel. curier','Logat','Rosu',''],
		colModel : [ {
			name : 'comanda_id',
			sortable:false,
			width : 50
		}, {
			name : 'collect_at',
			sorttype : 'date',
			formatter : 'date',
			sortable:false,
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			align: 'center',
			editable: false,
			width : 70
		},
		{
			name : 'created_at',
			sorttype : 'date',
			formatter : 'date',
			sortable:false,
			formatoptions : {
				srcformat : 'Y-m-d H:i:s',
				newformat : 'd.m.Y H:i'
			},
			align: 'center',
			width : 90
		},{
			name : 'status',
			align : 'center',
			formatter: 'select',
			sortable:false,
			editable: false,
			stype: 'select',
			edittype:'select',
			editoptions : {
				value : ":toate;1:initiala;2:transmisa;3:distribuita;4:acceptata;5:refuzata;6:colectata;7:anulata"
			},
			width : 65
		},{
			name : 'status_time',
			width : 80,
			sortable:false,
			align:'center'
		},{
			name : 'status_time_sec',
			hidden:true
		}, {
			name : 'motiv',
			sortable:false,
			width : 85
		},{
			name : 'comunicare',
			align : 'center',
			index : 'ch.comunicare',
			formatter: 'select',
			editable: false,
			stype: 'select',
			edittype:'select',
			editoptions : {
				value : ":toate;1:android;2:telefonic"
			},
			width : 65
		},{
			name : 'centru',
			width : 60,
			sortable:false,
			align:'left'
		},{
			name : 'client',
			width : 70,
			sortable:false,
			align:'left'
		},{
			name : 'localitate',
			width : 80,
			sortable:false,
			align:'left'
		}, {
			name : 'adresa',
			width : 100,
			sortable:false,
			align:'right'
		},{
			name : 'colete',
			width : 33,
			sortable:false,
			align:'right'
		},
		{
			name : 'paleti',
			width : 33,
			sortable:false,
			align:'right'
		},{
			name : 'greutate',
			width : 47,
			sortable:false,
			align:'right'
		},{
			name : 'agent',
			width : 80,
			sortable:false,
			align:'right'
		},{
			name : 'ag_telefon',
			width : 60,
			sortable:false,
			align:'right'
		},{
			name : 'last_login',
			width : 60,
			sortable:false,
			align:'right'
		},{
			name : 'rosu',
			width : 60,
			sortable:false,
			hidden:true
	},{
			name : 'acc',
			width : 60,
			sortable:false
         }],
		rowNum : 20,
		scroll : 1,
		rownumbers : false,
		width : 970,
		height : 295,
		viewrecords : true,
		pager : '#paginatie_comenzi',
		shrinkToFit: false,
		caption:'',
		forceFit: true,
		ondblClickRow : function(rowid,iRow,iCol,e){
			AfisareDetaliiComanda(rowid);
		},
		rowattr: function (rd) {
                    if (rd.rosu == 1) {
                        return {"class": "myAltRowClass"};
        }},
		gridComplete: function()
		{
			/*var rows = jQuery("#lista_comenzi").getDataIDs();
			setInterval(function() {
				for (var i = 0; i < rows.length; i++)
				{
					var row = jQuery("#lista_comenzi").jqGrid('getRowData',rows[i]);
					row.status_time = getDateDiff(row.status_time_sec);
					$('#lista_comenzi').jqGrid('setRowData', rows[i], row);

				}

			}, 1000);*/
		}


	});
	grid.jqGrid('bindKeys');
}

function getDateDiff(time1) {
	var d1=  time1 * 1000 ; //new Date(time1); // jan,1 2011
	var d2=  new Date().getTime(); // now

	var diff=d2-d1,sign=diff<0?-1:1,milliseconds,seconds,minutes,hours,days;
	diff/=sign; // or diff=Math.abs(diff);
	diff=(diff-(milliseconds=diff%1000))/1000;
	diff=(diff-(seconds=diff%60))/60;
	diff=(diff-(minutes=diff%60))/60;
	days=(diff-(hours=diff%24))/24;

	var val = " ";

	if(days)
		val += days+" d ";
	if(hours)
		val += hours+" h ";
	if(minutes)
		val += minutes+" m ";

	return val;

}

function Grid_ListaComenziIstoric(disp) {
	var t_url = HTTP + 'comenzi/json/lista_istoric';
	if(disp) t_url += '?dispecerat=' + disp;

	var grid = jQuery('#lista_comenzi');

	grid.jqGrid(
	{
		url : t_url,
		datatype: 'json',
		mtype: 'GET',
		colNames : [ 'Nr. comanda', 'Data colectarii','Data status', 'Status','Motiv', 'Comunicare', 'Dispecerat', 'Centru', 'Client', 'Localitate', 'Adresa', 'Colete', 'Paleti', 'Greutate', 'Curier','Tel. curier','Rosu'],
		colModel : [ {
			name : 'comanda_id',
			index : 'c.id',
			editable: false,
			width : 70
		}, {
			name : 'collect_at',
			index : 'c.collect_at',
			sorttype : 'date',
			formatter : 'date',
			search: false,
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			align: 'center',
			editable: false,
			width : 90
		},
		{
			name : 'created_at',
			index : 'ch.created_at',
			sorttype : 'date',
			formatter : 'date',
			editable: false,
			search: false,
			formatoptions : {
				srcformat : 'Y-m-d H:i:s',
				newformat : 'd.m.Y H:i'
			},
			align: 'center',
			width : 90
		},{
			name : 'status',
			align : 'center',
			index : 'ch.status',
			formatter: 'select',
			editable: false,
			stype: 'select',
			edittype:'select',
			editoptions : {
				value : ":toate;0:notificare android;1:initiala;2:transmisa;3:distribuita;4:acceptata;5:refuzata;6:colectata;7:anulata"
			},
			width : 65
		}, {
			name : 'motiv',
			editable: false,
			search: false,
			sortable:false,
			width : 85
		},{
			name : 'comunicare',
			align : 'center',
			index : 'ch.comunicare',
			formatter: 'select',
			editable: false,
			stype: 'select',
			edittype:'select',
			editoptions : {
				value : ":toate;1:android;2:telefonic"
			},
			width : 65
		},{
			name : 'dispecerat',
			index : 'dc.nume',
			width : 60,
			sortable:false,
			align:'left'
		},{
			name : 'centru',
			index : 'cc.nume',
			width : 60,
			sortable:false,
			align:'left'
		},{
			name : 'client',
			index : 'c.client',
			width : 70,
			editable: false,
			align:'left'
		},{
			name : 'localitate',
			index : 'l.nume_lc',
			width : 80,
			editable: false,
			align:'left'
		}, {
			name : 'adresa',
			width : 100,
			search: false,
			editable: false,
			sortable:false,
			align:'right'
		},{
			name : 'colete',
			width : 33,
			search: false,
			editable: false,
			sortable:false,
			align:'right'
		},
		{
			name : 'paleti',
			width : 33,
			search: false,
			editable: false,
			sortable:false,
			align:'right'
		},{
			name : 'greutate',
			width : 47,
			search: false,
			editable: false,
			sortable:false,
			align:'right'
		},{
			name : 'agent',
			index: 'ag.nume_ag',
			width : 80,
			align:'right'
		},{
			name : 'ag_telefon',
			width : 60,
			search: false,
			editable: false,
			sortable:false,
			align:'right'
		},
		{
			name : 'rosu',
			width : 60,
			search: false,
			editable: false,
			sortable:false,
			hidden:true
		}],
		rowNum : 12,
		rowList : [ 15, 50, 100 ],
		rownumbers : false,
		width : 970,
		height : 295,
		sortname : 'case when ch.status = 5 then 3 when ch.status = 3 then 4 when ch.status = 4 then 5 else ch.status end, ch.created_at',
		sortorder : "asc",
		viewrecords : true,
		pager : '#paginatie_comenzi',
		shrinkToFit: false,
		caption:'',
		forceFit: true,
		ondblClickRow : function(rowid,iRow,iCol,e){
			AfisareDetaliiComandaIstoric(rowid);
		},
		rowattr: function (rd) {
                    if (rd.rosu == 1) {
                        return {"class": "myAltRowClass"};
        }},
		subGrid: true,
		subGridRowExpanded: function(subgrid_id, row_id) {
			var subgrid_table_id, pager_id;
			subgrid_table_id = subgrid_id+"_t";
			pager_id = "p_"+subgrid_table_id;
			$("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table><div id='"+pager_id+"' class='scroll'></div>");

			jQuery("#"+subgrid_table_id).jqGrid({
				url : HTTP + 'comenzi/json/istoric/'+row_id,
						datatype : "json",
						colNames : [ 'Status', 'Data status','Status by','Motiv','Mesaj'],
						colModel : [ {
							name : 'status',
							formatter: 'select',
							stype: 'select',
							edittype:'select',
							editoptions : {
								value : ":toate;0:notificare android;1:initiala;2:transmisa;3:distribuita;4:acceptata;5:refuzata;6:colectata;7:anulata"
							},
							search: false,
							editable: false,
							sortable:false,
							width: 100,
							align: 'right'
						}, {
							name : 'created_at',
							sorttype : 'date',
							formatter : 'date',
							search: false,
							editable: false,
							sortable:false,
							formatoptions : {
								srcformat : 'Y-m-d H:i:s',
								newformat : 'd.m.Y H:i'
							},
							editable: false,
							width: 100,
							align: 'right'
						}, {
							name : 'created_by',
							search: false,
							editable: false,
							sortable:false,
							width: 150,
							align: 'right'
						}, {
							name : 'motiv',
							search: false,
							editable: false,
							sortable:false,
							width: 250,
							align: 'right'
						}, {
							name : 'mesaj',
							search: false,
							editable: false,
							sortable:false,
							width: 250,
							align: 'right'
						}],
						rowNum : 50,
					   	sortname: 'created_at',
					    sortorder: "desc",
					    height: 100,
					    gridview : false,
						pager : false,
						viewrecords : false,
						ondblClickRow: function(rowid,iRow,iCol,e){
							e.stopPropagation();
							return false;
						}
			});
		}
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('navGrid', '#paginatie_comenzi', {
		del : false,
		add : false,
		edit : false,
		search : false
	});
	grid.jqGrid('filterToolbar', {
		stringResult : true,
		searchOnEnter : false
	});

}

function AfisareListaComenzi(){
	var cond = '';
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var t_url = HTTP + 'comenzi/json/lista_distribuire?a';
	if($('#dispecerate').val() != '') t_url += '&dispecerat=' + $('#dispecerate').val();

	t_url += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	jQuery("#lista_comenzi").jqGrid('setGridParam', {
		url : t_url
	}).trigger("reloadGrid");
}

function AfisareListaComenziIstoric(){
	var cond = '';
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var t_url = HTTP + 'comenzi/json/lista_istoric?a';
	if($('#dispecerate').val() != '') t_url += '&dispecerat=' + $('#dispecerate').val();

	t_url += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	jQuery("#lista_comenzi").jqGrid('setGridParam', {
		url : t_url
	}).trigger("reloadGrid");
}

function ExportComenziIstoric(){
	var dispecerat = $('#dispecerate').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var cond = '&data_start=' + encodeURIComponent(data_start) + '&data_final='+ encodeURIComponent(data_final) + '&dispecerat='+ dispecerat;

	var postData = $('#lista_comenzi').getGridParam("postData");
	if(postData._search) {
		cond += '&_search='+postData._search;
		if(postData.filters) {
			cond += '&filters='+postData.filters;
		}
	}

	if (postData.sidx) {
		cond += '&sidx='+encodeURIComponent(postData.sidx);
	}
	if (postData.sord) {
		cond += '&sord='+postData.sord;
	}

	$.download('comenzi/export_istoric', cond, false);
}

/* //////////////////////////////////////////////////////
				END COMENZI
//////////////////////////////////////////////////////  */
function Grid_ColectariCentre()
{
	SetareDate();
	var grid = jQuery('#liste_centre');

	grid.jqGrid(
	{
		url : HTTP + 'expeditii/json/colectari',
		datatype: 'json',
		mtype: 'POST',
		colNames : ['Centru', 'Colectari','Colete','Paleti','Plicuri','Greutate','Col. Ext.','Km. Ext.'],
		colModel : [ {
			name : 'expeditor_centru',
			index : 'cee.nume',
			width: 130
		}, {
			name : 'colectari',
			index : 'colectari',
			sorttype : 'int',
			width: 60,
			align: 'right'
		}, {
			name : 'colete',
			index : 'colete',
			sorttype : 'int',
			width: 50,
			align: 'right'
		}, {
			name : 'paleti',
			index : 'paleti',
			sorttype : 'int',
			width: 50,
			align: 'right'
		}, {
			name : 'plicuri',
			index : 'plicuri',
			sorttype : 'int',
			width: 50,
			align: 'right'
		}, {
			name : 'greutate',
			index : 'greutate',
			sorttype : 'int',
			width: 50,
			align: 'right'
		}, {
			name : 'col_ext',
			index : 'col_ext',
			sorttype : 'int',
			width: 50,
			align: 'right'
		}, {
			name : 'km_ext',
			index : 'km_ext',
			sorttype : 'int',
			width: 50,
			align: 'right'
		} ],
		rowNum : 10000,
		rowList : [ 100,250,500,1000,2500,5000,10000 ],
		rownumbers : true,
		width : 970,
		height : 'auto',
		sortname : 'cee.nume',
		sortorder : "asc",
		viewrecords : false,
		pager : false,
		shrinkToFit: false,
		caption:'Lista Centre',
		forceFit: true,
	    footerrow: true,
	    userDataOnFooter: true,
	    multiselect: false,
	    onSelectRow : function(id) {
			ListeColectariDetalii(id);
		}
	});
	grid.jqGrid('bindKeys');
}

function ListeColectariDetalii(id) {
	var cond = 'centru='+id;
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	jQuery("#liste_expeditii_detalii").jqGrid('setGridParam', {
		url : HTTP + 'expeditii/json/colectari_detalii?'+cond
	}).trigger("reloadGrid");
	ListeExpeditiiAgenti(id);
}

function Grid_ColectariDetalii() {
	jQuery("#liste_expeditii_detalii").jqGrid(
			{
				url : HTTP + 'expeditii/json/colectari_detalii?centru=0',
				datatype : "json",
				colNames : ['Localitate','Expeditor','Nr.Exp', 'Greutate','Plicuri','Colete','Paleti','Km'],
				colModel : [ {
					name : 'expeditor_localitate',
					index : 'lce.nume_lc',
					width : 80
				}, {
					name : 'expeditor',
					index : 'cle.nume',
					width : 100
				}, {
					name : 'expeditii',
					index : 'expeditii',
					width : 40
				}, {
					name : 'greutate',
					index : 'greutate',
					align : 'right',
					width : 50
				}, {
					name : 'plicuri',
					index : 'plicuri',
					align : 'right',
					width : 40
				}, {
					name : 'colete',
					index : 'colete',
					align : 'right',
					width : 40
				}, {
					name : 'paleti',
					index : 'paleti',
					align : 'right',
					width : 40
				}, {
					name : 'km',
					index : 'km',
					align : 'right',
					width : 40
				} ],
				rowNum : 15,
				rowList : [ 15, 50, 100 ],
				width : 600,
				height : 'auto',
				mtype : "GET",
				rownumbers : false,
				gridview : true,
				sortname : 'cle.nume',
				viewrecords : true,
				sortorder : "ASC",
				pager : '#paginatie_detalii',
				caption : 'Expeditii pentru centrul selectat',
				multiselect : false,
				subGrid: true,
				subGridRowExpanded: function(subgrid_id, row_id) {
					var subgrid_table_id, pager_id;
					subgrid_table_id = subgrid_id+"_t";
					pager_id = "p_"+subgrid_table_id;
					$("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table><div id='"+pager_id+"' class='scroll'></div>");

					var g_centre = jQuery('#liste_centre');
					var g_sel_id = g_centre.jqGrid('getGridParam', 'selrow');

					var cond = '';
					var centru = g_sel_id;
					var data_start = $('#data_start').val();
					var data_final = $('#data_final').val();
					cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);

					jQuery("#"+subgrid_table_id).jqGrid({
						url : HTTP + 'expeditii/json/colectari_detalii_client?centru='+centru+'&client='+row_id+cond,
						datatype : "json",
						colNames : ['Expeditie','Destinatar','Localitate', 'Greutate','Plicuri','Colete','Paleti','Km'],
						colModel : [ {
							name : 'expeditie',
							index : 'ep.expeditie',
							width : 80
						}, {
							name : 'destinatar',
							index : 'cld.nume',
							width : 110
						}, {
							name : 'destinatar_localitate',
							index : 'lcd.nume_lc',
							width : 120
						}, {
							name : 'greutate',
							index : 'ep.greutate',
							align : 'right',
							width : 50
						}, {
							name : 'plicuri',
							index : 'ep.plicuri',
							align : 'right',
							width : 40
						}, {
							name : 'colete',
							index : 'ep.colete',
							align : 'right',
							width : 40
						}, {
							name : 'paleti',
							index : 'ep.paleti',
							align : 'right',
							width : 40
						}, {
							name : 'km',
							index : 'km',
							align : 'right',
							width : 40
						} ],
						rowNum : 20,
					   	pager: pager_id,
					   	sortname: 'cld.nume',
					    sortorder: "asc",
					    height: '100%',
					    ondblClickRow : function(id) {
							AfisareDetaliiExpeditieCuID(id)
						},
					});
					jQuery("#"+subgrid_table_id).jqGrid('navGrid',"#"+pager_id,{edit:false,add:false,del:false})
				},
				subGridRowColapsed: function(subgrid_id, row_id) {
					// this function is called before removing the data
				}
			});
	jQuery("#liste_expeditii_detalii").jqGrid('bindKeys');
}

function Grid_LivrariCentre()
{
	SetareDate();
	var grid = jQuery('#liste_centre');

	grid.jqGrid(
	{
		url : HTTP + 'expeditii/json/livrari',
		datatype: 'json',
		mtype: 'POST',
		colNames : ['Centru', 'Livrari','Colete','Paleti','Plicuri','Greutate','Col. Ext.','Km. Ext.'],
		colModel : [ {
			name : 'destinatar_centru',
			index : 'destinatar_centru',
			width: 130
		}, {
			name : 'livrari',
			index : 'livrari',
			sorttype : 'int',
			width: 60,
			align: 'right'
		}, {
			name : 'colete',
			index : 'colete',
			sorttype : 'int',
			width: 50,
			align: 'right'
		}, {
			name : 'paleti',
			index : 'paleti',
			sorttype : 'int',
			width: 50,
			align: 'right'
		}, {
			name : 'plicuri',
			index : 'plicuri',
			sorttype : 'int',
			width: 50,
			align: 'right'
		}, {
			name : 'greutate',
			index : 'greutate',
			sorttype : 'float',
			width: 50,
			align: 'right'
		}, {
			name : 'col_ext',
			index : 'col_ext',
			sorttype : 'int',
			width: 50,
			align: 'right'
		}, {
			name : 'km_ext',
			index : 'km_ext',
			sorttype : 'int',
			width: 50,
			align: 'right'
		} ],
		rowNum : 10000,
		rowList : [ 100,250,500,1000,2500,5000,10000 ],
		rownumbers : true,
		width : 970,
		height : 'auto',
		sortname : 'destinatar_centru',
		sortorder : "asc",
		viewrecords : false,
		pager : false,
		shrinkToFit: false,
		caption:'Lista Centre',
		forceFit: true,
	    footerrow: true,
	    userDataOnFooter: true,
	    multiselect: false,
	    onSelectRow : function(id) {
			ListeLivrariDetalii(id);
		}
	});
	grid.jqGrid('bindKeys');
}

function ListeLivrariDetalii(id) {
	var cond = 'centru='+id;
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	jQuery("#liste_expeditii_detalii").jqGrid('setGridParam', {
		url : HTTP + 'expeditii/json/livrari_detalii?'+cond
	}).trigger("reloadGrid");
	ListeExpeditiiAgenti(id);
}

function Grid_LivrariDetalii() {
	jQuery("#liste_expeditii_detalii").jqGrid(
			{
				url : HTTP + 'expeditii/json/livrari_detalii',
				datatype : "json",
				colNames : ['Localitate','Destinatar','Nr.Exp', 'Greutate','Plicuri','Colete','Paleti','Km'],
				colModel : [ {
					name : 'destinatar_localitate',
					index : 'lcd.nume_lc',
					width : 80
				}, {
					name : 'destinatar',
					index : 'cld.nume',
					width : 100
				}, {
					name : 'expeditii',
					index : 'expeditii',
					width : 40
				}, {
					name : 'greutate',
					index : 'greutate',
					align : 'right',
					width : 50
				}, {
					name : 'plicuri',
					index : 'plicuri',
					align : 'right',
					width : 40
				}, {
					name : 'colete',
					index : 'colete',
					align : 'right',
					width : 40
				}, {
					name : 'paleti',
					index : 'paleti',
					align : 'right',
					width : 40
				}, {
					name : 'km',
					index : 'km',
					align : 'right',
					width : 40
				} ],
				rowNum : 15,
				rowList : [ 15, 50, 100 ],
				width : 600,
				height : 'auto',
				mtype : "GET",
				rownumbers : false,
				gridview : true,
				sortname : 'cld.nume',
				viewrecords : true,
				sortorder : "ASC",
				pager : '#paginatie_detalii',
				caption : 'Expeditii pentru centrul selectat',
				multiselect : false,
				subGrid: true,
				subGridRowExpanded: function(subgrid_id, row_id) {
					var subgrid_table_id, pager_id;
					subgrid_table_id = subgrid_id+"_t";
					pager_id = "p_"+subgrid_table_id;
					$("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table><div id='"+pager_id+"' class='scroll'></div>");

					var g_centre = jQuery('#liste_centre');
					var g_sel_id = g_centre.jqGrid('getGridParam', 'selrow');

					var cond = '';
					var centru = g_sel_id;
					var data_start = $('#data_start').val();
					var data_final = $('#data_final').val();
					cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);

					jQuery("#"+subgrid_table_id).jqGrid({
						url : HTTP + 'expeditii/json/livrari_detalii_client?centru='+centru+'&client='+row_id+cond,
						datatype : "json",
						colNames : ['Expeditie','Expeditor','Localitate', 'Greutate','Plicuri','Colete','Paleti','Km'],
						colModel : [ {
							name : 'expeditie',
							index : 'ep.expeditie',
							width : 80
						}, {
							name : 'expeditor',
							index : 'cle.nume',
							width : 110
						}, {
							name : 'expeditor_localitate',
							index : 'lce.nume',
							width : 120
						}, {
							name : 'greutate',
							index : 'ep.greutate',
							align : 'right',
							width : 50
						}, {
							name : 'plicuri',
							index : 'ep.plicuri',
							align : 'right',
							width : 40
						}, {
							name : 'colete',
							index : 'ep.colete',
							align : 'right',
							width : 40
						}, {
							name : 'paleti',
							index : 'ep.paleti',
							align : 'right',
							width : 40
						}, {
							name : 'km',
							index : 'km',
							align : 'right',
							width : 40
						} ],
						rowNum : 20,
					   	pager: pager_id,
					   	sortname: 'cle.nume',
					    sortorder: "asc",
					    height: '100%',
					    ondblClickRow : function(id) {
							AfisareDetaliiExpeditieCuID(id)
						},
					});
					jQuery("#"+subgrid_table_id).jqGrid('navGrid',"#"+pager_id,{edit:false,add:false,del:false})
				},
				subGridRowColapsed: function(subgrid_id, row_id) {
					// this function is called before removing the data
					//var subgrid_table_id;
					//subgrid_table_id = subgrid_id+"_t";
					//jQuery("#"+subgrid_table_id).remove();
				}
			});
	jQuery("#liste_expeditii_detalii").jqGrid('bindKeys');
}

function ComboClienti_2(field,loc) {
	if(loc!='ALL')
		loc = 0;
	else
		local = 'all';
	$("#" + field + "_NUME").autocomplete({
		source : function(request, response) {
			if ($('#'+field+'_LOCALITATE').length > 0) {
				local = $('#'+field+'_LOCALITATE').val();
			}
			$.ajax({
				url : HTTP + "expeditii/json/clienti_old",
				dataType : "json",
				data : {
					maxRows : 15,
					name_startsWith : request.term,
					localitate : local
				},
				success : function(data) {
					response($.map(data.rezultat, function(item) {
						return {
							value : item.value,
							label : item.label,
							cod_cl : item.cod,
							adresa : item.adresa
						}
					}));
				}
			});
		},
		minLength : 1,
		select : function(event, ui) {
			$("#" + field).val(ui.item.cod_cl);
			$("#" + field + "_NUME").val(ui.item.value);
			return false;
		},
		change : function(event, ui) {
			if($("#" + field + "_NUME").val().trim() == '')
				$("#" + field).val("");
			return true;
		},
		open : function() {
			$("#" + field).val('');
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}

function ComboAgenti3_Old(field) {
	$("#" + field + "_NUME").autocomplete({
		source : function(request, response) {
			var cod_lc = $("#EXPEDITOR_LOCALITATE").val();
			$.ajax({
				url : HTTP + "expeditii/json/agenti1_old",
				dataType : "json",
				data : {
					maxRows : 20,
					cod_lc : cod_lc,
					name_startsWith : request.term
				},
				success : function(data) {
					response($.map(data.rezultat, function(item) {
						return {
							value : item.cod,
							label : item.nume,
							centru : item.centru
						}
					}));
				}
			});
		},
		minLength : 1,
		focus : function(event, ui) {
			$("#" + field + "_NUME").val(ui.item.centru);
			return false;
		},
		select : function(event, ui) {
			$("#" + field).val(ui.item.value);
			return false;
		},
		open : function() {
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
			$("#"+field).val('');
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}

function ComboAgenti3_Old_All(field) {
	$("#" + field + "_NUME").autocomplete({
		source : function(request, response) {
			var cod_lc = $("#EXPEDITOR_LOCALITATE").val();
			if($("#centru_nume").val() != '') {
				cod_lc = $("#centru_nume").val();
			}
			$.ajax({
				url : HTTP + "expeditii/json/agenti1_old_all",
				dataType : "json",
				data : {
					maxRows : 20,
					name_startsWith : request.term
				},
				success : function(data) {
					response($.map(data.rezultat, function(item) {
						return {
							value : item.cod,
							label : item.nume,
							centru : item.centru
						}
					}));
				}
			});
		},
		minLength : 1,
		focus : function(event, ui) {
			$("#" + field + "_NUME").val(ui.item.centru);
			return false;
		},
		select : function(event, ui) {
			$("#" + field).val(ui.item.value);
			return false;
		},
		open : function() {
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
			$("#"+field).val('');
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}

function AfisareGreutate(sel){
	$('#greutate_colete_national').hide();
	$('#greutate_paleti_national').hide();

	$('#greutate_colete_loco').hide();
	$('#greutate_paleti_loco').hide();

	$('#asigurare_loco').hide();
	$('#asigurare_national').hide();


	$('#tab-greutate_colete_national').removeClass('selected-tab');
	$('#tab-greutate_paleti_national').removeClass('selected-tab');

	$('#tab-greutate_colete_loco').removeClass('selected-tab');
	$('#tab-greutate_paleti_loco').removeClass('selected-tab');

	$('#tab-asigurare_loco').removeClass('selected-tab');
	$('#tab-asigurare_national').removeClass('selected-tab');

	$('#tab-'+sel).addClass('selected-tab');
	$('#'+sel).show();
}

function ResetareCampuri(){
	GenerareNrExpeditie();
	$('#CODURI').val('');

	$("#GREUTATE_VOL").val("");
	$("#VOLUM1").val("");
	$("#VOLUM2").val("");
	$("#VOLUM3").val("");
	$('#valoare_expeditie').html('0.00');
	$('#valoare_km').html('0.00');
	$('#valoare_greutate').html('0.00');
	$('#valoare_asigurare').html('0.00');
	$('#span_ht').html('0.00 LEI');
	$('#span_tva').html('0.00 LEI');
	$('#span_ttc').html('0.00 LEI');

	$('#RET_AMB').attr('disabled', false);
	$('#RET_COLET').attr('disabled', false);
	$('#LIV_S').attr('disabled', false);
	$('#LIV_SEDIU').attr('disabled', false);

	$('#RET_AMB').attr("checked", false);
	$('#RET_COLET').attr("checked", false);
	$('#LIV_S').attr("checked", false);
	$('#LIV_SEDIU').attr("checked", false);

	$('#OBSERVATII').val('');

	$('#ramburs').val('');
	$('#proc_asig').val('');
	$('#asigurare').val('');

	$('#valoare_asigurare').html('0');

	$('#tip_exp').val(0);
	$('#referinta_expeditie').val('');

	ResetarePlatitor();
	TipExpeditie(0);
}

function ResetarePlatitor(){
	$('#plateste_expeditor').prop('checked', false);
	$('#plateste_destinatar').prop('checked', false);
	$('#plateste_altul').prop('checked', false);

	$('#PLATITOR_NUME').val('');
	$("#PLATITOR_NUME").prop('readonly', true);
	$('#PLATITOR').val('');
	$('#plateste').val(0);
	$('#mod_plata').val(0);
}

function SelectDreptAcces(){
	var sel = $('#drept_acces').val();
	if(sel < 1) AfiseazaEroare('Selectati Nivelul de acces!');
	else{
		$.get(HTTP + 'operatori/drept_acces/' + sel, {}, function(response) {
			jQuery("#form_drepturi").html(response);
		});
	}
}

function EditareDreptAcces(){
	$('#acces_id').val($('#drept_acces').val());
	var data = $('#form_drepturi').serialize()

	$.post(HTTP + 'operatori/editare_drept_acces/', {data:data}, function(response) {
		AfiseazaEroare(response);
	});


}

/////////////Borderouri\\\\\\\\\\\\\\

function Grid_BorderouriClienti() {
	jQuery("#liste_borderouri").jqGrid(
			{
				url : HTTP + 'expeditii/json/borderouri_clienti',
				datatype : "json",
				colNames : ['Expeditor','Localitate','Nr. Bord','Expeditii','Data','Status','Operator','Curier','Optiuni'],
				colModel : [ {
					name : 'expeditor',
					index : 'cle.nume',
					width: 200
				}, {
					name : 'expeditor_localitate',
					index : 'lce.nume_lc',
					width: 100
				}, {
					name : 'id',
					index : 'a.id',
					formatter: 'number',
					sorttype: 'number',
					formatoptions: {
						decimalPlaces: 0
					},
					width: 60,
					align:'center'
				}, {
					name : 'expeditii',
					index : 'a.expeditii',
					formatter: 'number',
					sorttype: 'number',
					formatoptions: {
						decimalPlaces: 0
					},
					width: 60,
					align:'center'
				}, {
					name : 'data',
					index : 'a.data',
					sorttype : 'date',
					formatter : 'date',
					formatoptions : {
						srcformat : 'Y-m-d',
						newformat : 'Y-m-d'
					},
					align: 'center',
					width: 60
				}, {
					name : 'status',
					index : 'a.status',
					align: 'center',
					width: 100
				}, {
					name : 'operator',
					index : 'a.operator_nume',
					width: 100
				}, {
					name : 'curier',
					index : 'a.curier_nume',
					align: 'center',
					width: 100
				}, {
					name : 'optiuni',
					search : false,
					sortable : false,
					align: 'center'
				} ],
				rowNum : 15,
				rowList : [ 15, 50, 100 ],
				width : 970,
				height : 'auto',
				mtype : "GET",
				rownumbers : false,
				gridview : true,
				sortname : 'id',
				viewrecords : true,
				sortorder : "desc",
				pager : '#paginatie',
				caption : 'Lista Borderouri',
				multiselect : false,
				subGrid: true,
				subGridRowExpanded: function(subgrid_id, row_id) {
					var subgrid_table_id, pager_id;
					subgrid_table_id = subgrid_id+"_t";
					pager_id = "p_"+subgrid_table_id;
					$("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table><div id='"+pager_id+"' class='scroll'></div>");

					jQuery("#"+subgrid_table_id).jqGrid({
						url : HTTP + 'expeditii/json/liste_expeditii_borderou/'+row_id,
						datatype : "json",
						colNames : [ 'Expeditie','Destinatar','Localitate','Data','Tip','Piese','Greut','Km Ext','Asig','Ramburs','Valoare','TVA'],
						colModel : [ {
							name : 'expeditie',
							index : 'ep.expeditie',
							sorttype : 'int',
							width: 60,
							align: 'right'
						}, {
							name : 'destinatar',
							index : 'cld.nume',
							width: 180
						}, {
							name : 'destinatar_localitate',
							index : 'lcd.nume_lc',
							width: 100
						}, {
							name : 'data_expeditie',
							index : 'ep.data_expeditie',
							sorttype : 'date',
							formatter : 'date',
							formatoptions : {
								srcformat : 'Y-m-d',
								newformat : 'Y-m-d'
							},
							width: 60,
							align: 'center'
						}, {
							name : 'tip_obj',
							index : 'ep.tip_obj',
							formatter: 'select',
							stype : 'select',
							searchoptions : {
								value : ":Toate;1:PLIC;2:COLET;3:PALET",
								sopt : [ 'eq' ]
							},
							edittype : 'select',
							editoptions : {
								value : "1:PLIC;2:COLET;3:PALET",
							},
							width: 60,
							align: 'center'
						}, {
							name : 'piese',
							index : 'ep.piese',
							formatter: 'number',
							sorttype: 'number',
							formatoptions: {
								decimalPlaces: 0
							},
							align: 'right',
							width: 30
						}, {
							name : 'greutate',
							index : 'ep.greutate',
							formatter: 'number',
							sorttype: 'number',
							align: 'right',
							width: 60
						}, {
							name : 'km',
							index : 'km',
							formatter: 'number',
							sorttype: 'number',
							align: 'right',
							width: 60
						}, {
							name : 'asigurare',
							index : 'ep.asigurare',
							formatter: 'number',
							sorttype: 'number',
							align: 'right',
							width: 60
						}, {
							name : 'ramburs',
							index : 'ep.ramburs',
							formatter: 'number',
							sorttype: 'number',
							align: 'right',
							width: 60
						}, {
							name : 'valoare_totala',
							index : 'ep.valoare_totala',
							formatter: 'number',
							sorttype: 'number',
							align: 'right',
							width: 60
						}, {
							name : 'valoare_tva',
							index : 'ep.valoare_tva',
							formatter: 'number',
							sorttype: 'number',
							align: 'right',
							width: 60
						} ],
						rowNum : 20,
					   	pager: pager_id,
					   	sortname: 'id',
					    sortorder: "desc",
					    height: '100%',
					    ondblClickRow : function(id) {
							AfisareDetaliiExpeditieCuID_Client(id)
						},
					});
					jQuery("#"+subgrid_table_id).jqGrid('navGrid',"#"+pager_id,{edit:false,add:false,del:false})
				}
			});
	$("#liste_borderouri").jqGrid('bindKeys');
	$("#liste_borderouri").jqGrid('filterToolbar', {
		stringResult : true,
		searchOnEnter : false
	});
	$("#liste_borderouri").jqGrid('navGrid', '#paginatie', {
		del : false,
		add : false,
		edit : false,
		search : false
	});
}

function AfisareDetaliiExpeditieCuID_Client(sel)
{
	$.post(HTTP + 'expeditii/detalii_expeditie_client/', {expeditie : sel}, function(response) {
		$("#detalii_expeditie").html(response);
		$("#detalii_expeditie:ui-dialog").dialog("destroy");
		$("#detalii_expeditie").dialog({
			width : 624,
			height : 410,
			modal : true,
			draggable : false,
			resizable : false,
			title : 'Nr. AWB: '+sel
		});
	});
	//return true;
}

function StergereBorderou(sel) {
	$("#eroare").html('Sunteti sigur ca doriti sa stergeti acest borderou?');
	$("#eroare").attr('title', 'Confirmare');
	$("#eroare").dialog({
		modal : true,
		draggable : false,
		height : 150,
		width : 250,
		resizable : false,
		buttons : {
			'Da' : function() {
				$.ajax(HTTP + 'expeditii/borderou_stergere/' + sel);
				$(this).dialog("destroy");
				jQuery("#liste_borderouri").jqGrid('setGridParam',{url : HTTP + 'expeditii/json/borderouri_clienti'}).trigger("reloadGrid");
			},
			'Nu' : function() {
				$(this).dialog("destroy");
			}
		}
	});
}


function StergereBorderouReceptionat(sel) {
	$("#eroare").html('Sunteti sigur ca doriti sa stergeti acest borderou receptionat?');
	$("#eroare").attr('title', 'Confirmare');
	$("#eroare").dialog({
		modal : true,
		draggable : false,
		height : 150,
		width : 250,
		resizable : false,
		buttons : {
			'Da' : function() {
				$.ajax(HTTP + 'expeditii/borderou_receptionat_stergere/' + sel);
				$(this).dialog("destroy");
				jQuery("#liste_borderouri").jqGrid('setGridParam',{url : HTTP + 'expeditii/json/borderouri_clienti'}).trigger("reloadGrid");
			},
			'Nu' : function() {
				$(this).dialog("destroy");
			}
		}
	});
}

function ReceptieBorderou(sel) {
	$("#receptie_borderou").attr('title', 'Confirmare');
	$("#receptie_borderou").dialog({
		modal : true,
		draggable : false,
		height : 210,
		width : 250,
		resizable : false,
		buttons : {
			'Da' : function() {
				var curier_nume=$('#AG_PREL_NUME').val();
				var curier=$('#AG_PREL').val();
				var comanda=$('#id_comanda').val();
				if(curier_nume==''){
					AfiseazaEroare('Selectati Curierul');
				}else{
					$(this).dialog("destroy");
					$.post(HTTP + 'expeditii/borderou_receptie/' + sel,{curier_nume : curier_nume,curier : curier,comanda : comanda},
						function(returned) { //What to do if the POST finishes. 'returned' is the value recieved back from the script.
    						if (returned == 'OK') {AfiseazaEroare('Receptie OK');} else {AfiseazaEroare('Eroare receptie : '+returned);}
    					}
    				);
					jQuery("#liste_borderouri").jqGrid('setGridParam',{url : HTTP + 'expeditii/json/borderouri_clienti'}).trigger("reloadGrid");
				}
			},
			'Nu' : function() {
				$(this).dialog("destroy");
			}
		}
	});
}

function DetaliiExpeditiiEditare(url){
	$('#detalii_expeditie').dialog("destroy");
	window.open(url, '_blank');
  	window.focus();
}

function opDownloadConfirmare(exp){
	var expeditie = parseInt(exp);
	if(!isNaN(expeditie) && expeditie > 0)
    	window.location = HTTP + 'expeditii/download_confirmare?expeditie='+expeditie;
}

function opDownloadRecantarire(id){
	var id = parseInt(id);
	if(!isNaN(id) && id > 0)
    	window.location = HTTP + 'expeditii/download_recantarire?expeditie='+id;
}

function GenerareRuta(){
	var categorie = $('#categorie').val();

	var centru = $('#centru').val();
	if(centru==0){
		AfiseazaEroare('Selectati centru de expeditie!');
		return false;
	}

	var centre_destinatie='';
	$.each($('#generare_transport .centre_destinatie').serializeArray(), function(i, field) {
	   centre_destinatie += '_'+field.value;
	});
	if(centre_destinatie==''){
		AfiseazaEroare('Selectati centre de destinatie!');
		return false;
	}

	$('#generare_transport').submit();
}

function Grid_ListeRute(){
	jQuery("#liste_rute").jqGrid(
			{
				url : HTTP + 'rute/json/liste_rute',
				datatype : "json",
				colNames : [ 'Nr.','Denumire','Centru Exp','Centre Destinatie','Observatii','Data','Kg max',''],
				colModel : [ {
					name : 'nr_transport',
					index : 'ru.id',
					width: 40
				}, {
					name : 'denumire',
					index : 'ru.denumire',
					align : 'right',
					width: 50
				}, {
					name : 'centru_expeditie',
					index : 'ce.nume',
					align : 'right',
					width: 80
				}, {
					name : 'centre_destinatie',
					search: false,
					sortable: false,
					align : 'right',
					width: 390
				}, {
					name : 'observatii',
					search: false,
					sortable: false,
					align : 'center',
					width: 120
				}, {
					name : 'data',
					search: false,
					sortable: false,
					align : 'center',
					width: 80
				},{
					name : 'kg_max',
					search: false,
					sortable: false,
					align : 'right',
					width: 70
				}, {
					name : 'option',
					search : false,
					sortable : false,
					align : 'center',
					width: 90
				} ],
				rowNum : 10000,
				autowidth : true,
				width : 960,
				height : 400,
				scroll:1,
				mtype : "GET",
				rownumbers : false,
				gridview : true,
				sortname : 'ru.id',
				viewrecords : true,
				sortorder : "asc",
				pager : '#paginatie',
				caption : false,
				multiselect : false,
				subGrid : false,
				grouping: false
			});
	jQuery("#liste_rute").jqGrid('bindKeys');
	jQuery("#liste_rute").jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
}

function SergereRuta(sel){
	var mesaj = 'Sunteti sigur?';
	$("#eroare").html(mesaj);
	$("#eroare").attr('title', 'Confirmare');
	$("#eroare").dialog({
		modal : true,
		draggable : false,
		height : 120,
		width : 200,
		resizable : false,
		buttons : {
			'Da' : function() {
				$(this).dialog("destroy");
				$.get(HTTP + 'rute/ruta_stergere/' + sel);
				jQuery("#liste_rute").jqGrid('setGridParam', {
					url : HTTP + 'rute/json/liste_rute'
				}).trigger("reloadGrid");
			},
			'Nu' : function() {
				$(this).dialog("destroy");
			}
		}
	});
}

function ClientImportExpeditii(sel){
	var nr_exp = $('#nr_expeditii').val();
	var expeditii = $('#expeditii').val();
	var campuri = $('#campuri').val();

	var curier = $('#curier').val();
	var expeditor = $('#expeditor').val();


	if(sel<=nr_exp){
		$.post(HTTP + 'expeditii/import_expeditie/', {
			sel:sel,
			expeditor:expeditor,
			curier:curier,
			expeditii:expeditii,
			campuri:campuri
		 }, function(response) {
			var val = parseInt((100/nr_exp)*sel);
			var progressbar = $("#progressbar");
			progressbar.progressbar("value", val);
			sel++;
			ClientImportExpeditii(sel);
		});
	}
	return true;
}

//situatie centru
function Grid_IstoricCodBare(cod, id){
	var autowidth = true;
	var width = 960;
	if(!id){
		id = 'istoric_codbare';
	} else {
		autowidth = false;
		width = 600;
	}


	jQuery("#" + id).jqGrid(
			{
				url : HTTP + 'scanare/json/istoric_codbare?codbare='+cod,
				datatype : "json",
				colNames : [ 'Cod','Centru','Ruta','Curier','Tip','Data'],
				colModel : [ {
					name : 'cod',
					index : 'sc.cod',
					width: 100
				}, {
					name : 'centru',
					index : 'ce.nume',
					width: 100
				}, {
					name : 'ruta',
					index : 'ru.denumire',
					width: 70
				}, {
					name : 'curier',
					sortable: false,
					search: false,
					width: 100
				}, {
					name : 'tip',
					index : 'sc.tip',
					width: 100
				}, {
					name : 'data',
					index : 'sc.data',
					sorttype : 'date',
					formatter : 'date',
					formatoptions : {
						srcformat : 'Y-m-d H:i:s',
						newformat : 'd.m.Y H:i:s'
					},
					width: 110
				} ],
				rowNum : 1500,
				autowidth : autowidth,
				width : width,
				height : 400,
				scroll:1,
				mtype : "GET",
				rownumbers : false,
				gridview : true,
				sortname : 'sc.data',
				viewrecords : true,
				sortorder : "desc",
				caption : false,
				multiselect : false,
				subGrid : false,
				grouping: false,
			    footerrow: false,
			    userDataOnFooter: false,
				loadComplete: function(responce) {
					if ( $( "#afisare_scanari" ).length ) {
						var count = 0;
						for (var k in responce.rows) {
							if (responce.rows.hasOwnProperty(k)) {
								++count;
							}
						}
						$('#afisare_scanari').html(count + ' Scanari');
					}
				}
			});
	jQuery("#istoric_codbare").jqGrid('bindKeys');
}

//afisez expeditii din centrul selectat
function AfisareIstoricCodBare(){

	var codbare = $('#codbare').val();
	if(codbare){
		jQuery("#istoric_codbare").jqGrid('setGridParam', {
			url : HTTP + 'scanare/json/istoric_codbare?codbare='+codbare
		}).trigger("reloadGrid");
	}else{
		AfiseazaEroare('Introdu CodBare!');
		return false;
	}
	return true;
}
//detalii expeditie
function DetaliiIstoricCodBare(){
	var codbare = $('#codbare').val();
	if(codbare){
		$.post(HTTP + 'expeditii/detalii_expeditie/', {expeditie : codbare}, function(response) {
			$("#detalii_expeditie").html(response);
			$("#detalii_expeditie:ui-dialog").dialog("destroy");
			$("#detalii_expeditie").dialog({
				width : 624,
				height : 650,
				modal : true,
				draggable : false,
				resizable : false,
				title : 'Nr. AWB: '+codbare
			});
		});
	}else{
		AfiseazaEroare('Introdu CodBare!');
		return false;
	}
	return true;
}

function ComboRute(field) {
	$("#" + field + "_NUME").autocomplete({
		source : function(request, response) {
			$.ajax({
				url : HTTP + "rute/json/liste_rute_combo",
				dataType : "json",
				data : {
					maxRows : 50,
					name_startsWith : request.term
				},
				success : function(data) {
					response($.map(data.rezultat, function(item) {
						return {
							value : item.cod,
							label : item.nume
						}
					}));
				}
			});
		},
		minLength : 1,
		focus : function(event, ui) {
			$("#" + field + "_NUME").val(ui.item.label);
			return false;
		},
		select : function(event, ui) {
			$("#" + field).val(ui.item.value);
			return false;
		},
		open : function() {
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}


function AfisareIstoricScanareListeExpeditii(){
	var cond = '';

	if($('#agent_NUME').val() == '')$('#agent').val('');
	if($('#centru_nume').val() == '')$('#centru').val('');
	if($('#ruta_NUME').val() == '')$('#ruta').val('');

	var categorie = $('#categorie').val();
	var borderou = $('#borderou').val();
	var tip_scanare = $('#tip_scanare').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var agent = $('#agent').val();
	var centru = $('#centru').val();
	var ruta = $('#ruta').val();
	var puisori = ($('#puisori').is(':checked'))?1:0;

	cond = '?puisori='+puisori+'&tip_scanare='+tip_scanare+'&ruta='+ruta+'&agent='+agent+'&centru='+centru+'&borderou='+borderou+'&categorie='+categorie+'&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	jQuery("#liste_istoric_scanare").jqGrid('setGridParam', {
		url : HTTP + 'scanare/json/istoric_scanari'+cond
	}).trigger("reloadGrid");
}


//situatie centru
function Grid_IstoricScanare(){
	jQuery("#liste_istoric_scanare").jqGrid(
			{
				url : HTTP + 'scanare/json/istoric_scanari',
				datatype : "json",
				colNames : [ 'CodBare','Nr. NT','Bor','Categ','Piese','Kg','Tip','Status','Ruta','Centru','Centru dest.','Agent','Expeditor','Localitate','Destinatar','Localitate','Cash','Asig/Ramb','Colectare','Scanare','User','Centru exp','Centru dest'],
				colModel : [ {
					name : 'codbare',
					index : 'sc.cod',
					width: 70
				}, {
					name : 'expeditie',
					index : 'ep.expeditie',
					width: 60
				}, {
					name : 'borderou',
					index : 'sc.borderou',
					width: 40
				}, {
					name : 'categorie',
					index : 'tip_obj',
					stype : 'select',
					formatter: 'select',
					editoptions : {
						value : ":Toate;0:NA;1:plic;2:colet;3:palet;4:colet+palet"
					},
					searchoptions : {
						value : ":Toate;1:plic;2:colet;3:palet;4:colet+palet",
						sopt : [ 'eq' ]
					},
					align: 'center',
					width: 60,
					sortable:false
				}, {
					name : 'piese',
					sortable:false,
					search:false,
					width: 20,
				},{
					name : 'greutate',
					index : 'ep.greutate',
					width: 40,
				}, {
					name : 'tip',
					index : 'ck.denumire',
					width: 80,
					sortable:false
				}, {
					name : 'operatiune',
					index : 'ep.operatiune',
					width: 80,
					sortable:true
				}, {
					name : 'ruta',
					index : 'ru.denumire',
					width: 90,
					sortable:false
				}, {
					name : 'centru',
					index : 'cee.nume',
					width: 100
				},{
					name : 'destinatar_centru',
					index : 'destinatar_centru',
					width: 100
				}, {
					name : 'curier',
					index : 'ag.nume_ag',
					width: 90,
					sortable:false
				}, {
					name : 'expeditor',
					index : 'cle.nume',
					width: 100
				}, {
					name : 'expeditor_localitate',
					index : 'lce.nume_lc',
					width: 80
				}, {
					name : 'destinatar',
					index : 'cld.nume',
					width: 100
				}, {
					name : 'destinatar_localitate',
					index : 'lcd.nume_lc',
					width: 80
				}, {
					name : 'cash',
					index : 'ep.valoare_totala_expeditie',
					width: 60
				}, {
					name : 'ramburs',
					index : 'ep.valoare_asigurata',
					width: 60
				}, {
					name : 'data_expeditie',
					index : 'ep.data_expeditie',
					width: 60
				}, {
					name : 'data_scanare',
					index : 'sc.data',
					width: 110
				}, {
                    name : 'user',
                    index : 'u.user',
                    width: 80
                }, {
                    name : 'expeditor_centru_cod',
                    index : 'expeditor_centru_cod',
                    width: 80
                }, {
                    name : 'destinatar_centru_cod',
                    index : 'destinatar_centru_cod',
                    width: 80
                } ],
				autowidth : true,
				width : 960,
				height : 400,
				rowNum : 300,
				// scroll : 1,
				mtype : "GET",
				shrinkToFit: false,
				forceFit: true,
				rownumbers : true,
				gridview : true,
				sortname : 'cld.nume',
				sortorder : "asc",
				viewrecords : true,
				pager : true,
				pager : '#paginatie',
				caption : false,
				multiselect : false,
				subGrid : false,
				grouping: false,
			    footerrow: true,
			    userDataOnFooter: true,
			    ondblClickRow : function(id) {
					var expeditie = jQuery("#liste_istoric_scanare").jqGrid('getCell', id, 'expeditie');
					if(expeditie){
						AfisareDetaliiExpeditieCuID(expeditie);
					}else{
						AfiseazaEroare('Nu exista expeditia','Eroare',125);
						return false;
					}
				}
			});
	jQuery("#liste_istoric_scanare").jqGrid('bindKeys');
	jQuery("#liste_istoric_scanare").jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
	jQuery("#liste_istoric_scanare").jqGrid('bindKeys');
}

function PrintIstoricScanareListeExpeditii(){
	var cond = '';
	if($('#agent_NUME').val() == '') $('#agent').val('');
	if($('#centru_nume').val() == '') $('#centru').val('');
	if($('#ruta_NUME').val() == '') $('#ruta').val('');

	var categorie = $('#categorie').val();
	var borderou = $('#borderou').val();
	var tip_scanare = $('#tip_scanare').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var agent = $('#agent').val();
	var centru = $('#centru').val();
	var ruta = $('#ruta').val();
	var puisori = ($('#puisori').is(':checked'))?1:0;

	var filters =  $('#liste_istoric_scanare').jqGrid('getGridParam','postData').filters;
	if(filters) cond+='&filters='+filters;
	sidx = $('#liste_istoric_scanare').jqGrid('getGridParam','sortname');
	if(sidx) cond+='&sidx='+sidx;
	sord = $('#liste_istoric_scanare').jqGrid('getGridParam','sortorder');
	if(sord) cond+='&sord='+sord;

	cond = '?puisori='+puisori+'&tip_scanare='+tip_scanare+'&agent='+agent+'&ruta='+ruta+'&centru='+centru+'&borderou='+borderou+'&categorie='+categorie+'&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	window.open(HTTP+"scanare/print_expeditii_istoric_scanare"+cond, '_blank');
  	return true;

}


function ExportIstoricScanareListeExpeditii(){
	var cond = '';
	if($('#agent_NUME').val() == '')$('#agent').val('');
	if($('#centru_nume').val() == '')$('#centru').val('');
	if($('#ruta_NUME').val() == '')$('#ruta').val('');

	var categorie = $('#categorie').val();
	var borderou = $('#borderou').val();
	var tip_scanare = $('#tip_scanare').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var agent = $('#agent').val();
	var centru = $('#centru').val();
	var ruta = $('#ruta').val();
	var puisori = ($('#puisori').is(':checked'))?1:0;

	cond = '?puisori='+puisori+'&tip_scanare='+tip_scanare+'&agent='+agent+'&ruta='+ruta+'&centru='+centru+'&borderou='+borderou+'&categorie='+categorie+'&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);

	var filters =  $('#liste_istoric_scanare').jqGrid('getGridParam','postData').filters;
	if(filters) cond+='&filters='+filters;
	sidx = $('#liste_istoric_scanare').jqGrid('getGridParam','sortname');
	if(sidx) cond+='&sidx='+sidx;
	sord = $('#liste_istoric_scanare').jqGrid('getGridParam','sortorder');
	if(sord) cond+='&sord='+sord;

	window.open(HTTP+"scanare/export_expeditii_istoric_scanare"+cond, '_blank');
  	return true;
}

function AfisareExpeditiiFaraKey(){
	var cond = '';

	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();

	cond = '?data_start=' + escape(data_start) + '&data_final='+ escape(data_final);

	jQuery("#liste_istoric_scanare").jqGrid('setGridParam', {
		url : HTTP + 'scanare/json/fara_key'+cond
	}).trigger("reloadGrid");
}

//situatie centru
function Grid_FaraKey(){
	jQuery("#liste_istoric_scanare").jqGrid(
			{
				url : HTTP + 'scanare/json/fara_key',
				datatype : "json",
				colNames : [ 'Cod','Scan','Expeditor','Centru alocare', 'Date','Centre','Utilizatori','Statusuri'],
				colModel : [ {
                    name : 'cod',
                    index : 'sc.cod',
                    width: 100
                },{
                    name : 'scan',
                    index : 'scan',
                    width: 25
                }, {
					name : 'expeditor',
					index : 'cle.nume',
					width: 100
				}, {
					name : 'centru_awb',
					index : 'cea.nume',
					width: 100
				},{
					name : 'data',
					width: 150,
					index : 'data_scanari',
				}, {
					name : 'centre',
					width: 150,
					sortable:false
				}, {
					name : 'utilizatori',
					width: 150,
					sortable:false
				}, {
					name : 'checkpointuri',
					width: 150,
					sortable:false
				} ],
				autowidth : true,
				width : 1300,
				height : 400,
				rowNum : 1000,
				scroll : 1,
				mtype : "GET",
				shrinkToFit: false,
				forceFit: true,
				rownumbers : false,
				gridview : true,
				sortname : 'data_scanari',
				sortorder : "asc",
				viewrecords : true,
				pager : "#pager",
				caption : false,
				multiselect : false,
				subGrid : false,
				grouping: false,
			    footerrow: true,
			    userDataOnFooter: true
			});
			$('#liste_istoric_scanare').jqGrid('navGrid', '#pager', {
        			search: false,
        			edit: false,
        			add: false,
        			del: false,
        			refresh: true
    				},
    				{}, // default settings for edit
    				{}, // default settings for add
    				{}, // delete
    				{}
			)
			.navButtonAdd('#pager', {
        			caption: "Export",
        			buttonicon: "ui-icon-disk",
        			onClickButton: function () {
					var cond = '';

        				var data_start = $('#data_start').val();
        				var data_final = $('#data_final').val();

        				cond = '?data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
            				window.open(HTTP+"scanare/export_expeditii_fara_key"+cond, '_blank');
        			},
        			position: "last"
    			});
	jQuery("#liste_istoric_scanare").jqGrid('bindKeys');
}

function AfisareDiferenteRute(){
	var cond = '';

	var categorie = $('#categorie').val();
	var centru = $('#centru').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond = '?centru='+centru+'&categorie='+categorie+'&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);

	jQuery("#liste_istoric_scanare").jqGrid('setGridParam', {
		url : HTTP + 'scanare/json/diferente_rute'+cond
	}).trigger("reloadGrid");
}


function ExportaDiferenteRute(){
	var cond = '';

	var categorie = $('#categorie').val();
	var centru = $('#centru').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond = '?centru='+centru+'&categorie='+categorie+'&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);

	var url = HTTP + 'scanare/export/diferente_rute'+cond;
	window.location = url;
}

//situatie centru
function Grid_DiferenteRute(){
	jQuery("#liste_istoric_scanare").jqGrid(
			{
				url : HTTP + 'scanare/json/diferente_rute',
				datatype : "json",
				colNames : [ 'Codbare','Expeditie','Tip','Ruta','Curier','Borderou','Tip exp.', 'Tip plata', 'Tip','Piese','Destinatar','Expeditor','Centru Liv','Localitate Liv','Centru Col','Localitate Col','Data'],
				colModel : [ {
					name : 'codbare',
					index : 'sc.cod',
					width: 90,
					sortable:false,
					frozen:true
				}, {
					name : 'expeditie',
					index : 'ep.expeditie',
					width: 70,
					sortable:false,
					frozen:true
				}, {
					name : 'tip',
					index : 'sc.tip',
					width: 30,
					sortable:false,
					search: false
				}, {
					name : 'ruta',
					index : 'ru.denumire',
					width: 70,
					sortable:false,
					search: false
				}, {
					name : 'curier',
					index : 'ag.nume_ag',
					width: 90,
					sortable:false,
					search: false
				}, {
					name : 'borderou',
					index : 'sc.borderou',
					width: 70,
					sortable:false,
					search: false
				}, {
					name : 'tip_exp',
					width: 70,
					sortable:false,
					index : 'ep.tip_exp',
					formatter:'select',
					stype : 'select',
					searchoptions : {
						value: ":Toate;0:Initiala;1:Retur NT;2:Retur Doc;3:Ramburs;4:Interna;5:Returnare;6:Retur ambalaj",
						sopt : [ 'eq' ]
					},
					edittype : "select",
					editoptions: {
						value: {0:'Initiala', 1:'Retur NT', 2:'Retur Doc', 3:'Ramburs',4:'Interna',5:'Returnare',6:'Retur ambalaj'}
					}
				},{
					name : 'tip_plata',
					width: 70,
					sortable:false,
					index : 'ep.tip_plata',
					formatter:'select',
					stype : 'select',
					searchoptions: {
						value: ":Toate;0:cash;1:bo;2:cec;3:cont",
						sopt : [ 'eq' ]
					},
					edittype : "select",
					editoptions: {
						value: {0:'cash', 1:'bo', 2:'cec', 3:'cont'}
					}
				},{
					name : 'tip',
					width: 70,
					sortable:false,
					search: false
				}, {
					name : 'piese',
					search: false,
					sortable: false,
					width: 30,
					sortable:false,
					search: false
				}, {
					name : 'destinatar',
					index : 'cld.nume',
					width: 160,
					sortable:false
				}, {
					name : 'expeditor',
					index : 'cle.nume',
					width: 160,
					sortable:false
				}, {
					name : 'destinatar_centru',
					index : 'ced.nume',
					width: 110,
					sortable:false
				}, {
					name : 'destinatar_localitate',
					index : 'lcd.nume_lc',
					width: 110,
					sortable:false
				}, {
					name : 'expeditor_centru',
					index : 'cee.nume',
					width: 110,
					sortable:false
				}, {
					name : 'expeditor_localitate',
					index : 'lce.nume_lc',
					width: 110,
					sortable:false
				}, {
					name : 'data_scanare',
					index : 'sc.data',
					width: 100,
					sortable:false,
					search: false
				} ],
				autowidth : true,
				width : 960,
				height : 400,
				rowNum : 1000,
				scroll : 1,
				mtype : "GET",
				shrinkToFit: false,
				forceFit: true,
				rownumbers : true,
				gridview : true,
				sortname : 'ep.expeditie',
				sortorder : "asc",
				viewrecords : true,
				pager : false,
				caption : false,
				multiselect : false,
				subGrid : false,
				grouping: false,
			    footerrow: true,
			    userDataOnFooter: true,
			    ondblClickRow : function(id) {
					AfisareDetaliiExpeditieCuID(id)
				}
			});
	jQuery("#liste_istoric_scanare").jqGrid('bindKeys');
	jQuery("#liste_istoric_scanare").jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
}

function ClientiListareAlocare() {
	$("#clienti_listare").jqGrid({
		url : HTTP + 'clienti/alocare_json',
		datatype : "json",
		colNames : [ 'Nume', 'Localitate', 'Contract', 'Mod Plata', 'Status' ],
		colModel : [ {
			name : 'nume',
			index : 'NUME'
		}, {
			name : 'localitate',
			index : 'LOCALITATE',
			width: 90
		}, {
			name : 'tarif',
			index : 'TARIF',
			stype : 'select',
			searchoptions : {
				value : ":Toate;0:Nu;1:T. negociat;2:T. lista",
				sopt : [ 'eq' ]
			},
			width: 50
		}, {
			name : 'mod_plata',
			index : 'MOD_PLATA',
			stype : 'select',
			searchoptions : {
				value : ":Toate;0:Per NT;1:Factura periodica",
				sopt : [ 'eq' ]
			},
			width: 80
		}, {
			name : 'activ',
			index : 'ACTIV',
			stype : 'select',
			searchoptions : {
				value : ":Toate;0:Inactiv;1:Activ",
				sopt : [ 'eq' ]
			},
			width: 50
		} ],
		height : 450,
		width : 560,
		scroll : 1,
		rowNum : 100,
		mtype : "GET",
		rownumbers : true,
		rownumWidth : 50,
		gridview : true,
		pager : '#clienti_listare_pag',
		sortname : 'NUME',
		viewrecords : true,
		sortorder : "asc",
		caption : 'Listare Clienti',
		//editurl : HTTP + 'clienti/editare?q=dummy',
		onSelectRow : function(id) {
			ClientiDetaliiAlocare(id);
		}
	});
	$("#clienti_listare").jqGrid('bindKeys');
	$("#clienti_listare").jqGrid('filterToolbar', {
		stringResult : true,
		searchOnEnter : false
	});
	$("#clienti_listare").jqGrid('navGrid', '#agenti_listare_pag', {
		del : false,
		add : true,
		edit : false,
		search : false
	});
}


function ClientiDetaliiAlocare(id){

	var grid = jQuery('#clienti_listare');
	var sel = grid.jqGrid('getCell', id, 'nume');

	var clienti = $('#lista_id_alocare').val();
	if(clienti.search(id)<0){
		if(clienti != '') $('#lista_id_alocare').val(clienti+','+id);
		else $('#lista_id_alocare').val(id);
		$('#lista_clienti_alocare').append('<div id="client_alocat_'+id+'"><div style="clear:both; padding-left:20px; float:left; width: 300px; font-size:13px; line-height: 22px;">'+sel+'</div><div  style="float:left; width: 50px; font-size:13px;"><a style=" color: red !important; text-decoration: none;" href="javascript:;" onclick="StergeClientAlocat('+id+')">sterge</a></div></div>');
	}
}


function StergeClientAlocat(id){
	$('#client_alocat_'+id).remove();
	var clienti = $('#lista_id_alocare').val();
	var result = clienti.replace(id,"");
	$('#lista_id_alocare').val(result);
}

function AlocareClienti(sel){

	var client = $('#client').val();
	if(client==''){
		AfiseazaEroare('Selectati CLIENTUL la care se aloca expeditiile!','Eroare',135);
		return false;
	}
	var clienti = $('#lista_id_alocare').val();
	if(clienti==''){
		AfiseazaEroare('Selectati CLIENTII care se realoca!');
		return false;
	}
	var sterge=0;
	if ($('#sterge').is(':checked')) sterge=1;

	var client_nume = $('#client_NUME').val();

	var ids = new Array();
	ids = clienti.toString().split(',');

	if(sel<=ids.length){
		$('#progressbar').show();
		$.post(HTTP + 'clienti/alocare-clienti/', {
			client:client,
			client_nume:client_nume,
			clienti:ids[(sel-1)],
			sterge:sterge
		 }, function(response) {
			var val = parseInt((100/ids.length)*sel);
			var progressbar = $("#progressbar");
			progressbar.progressbar("value", val);
			sel++;
			AlocareClienti(sel);
		});
	}else{
		$('#progressbar').hide();
		AfiseazaEroare('Alocare cu succes!','Succes');
		$('#lista_clienti_alocare').html('');
		jQuery("#clienti_listare").jqGrid('setGridParam', {
			url : HTTP + 'clienti/alocare_json'
		}).trigger("reloadGrid");
		return true;
	}

}

function _Functii_Checkpoints(){}
function CheckpointsListare() {
	$("#checkpoints_listare").jqGrid({
		url : HTTP + 'checkpoints/listare_json',
		datatype : "json",
		colNames : [ 'Denumire', 'ABBR', 'Activ', 'Exceptie', 'Public'],
		colModel : [ {
			name : 'denumire',
			index : 'denumire',
			editable : true,
		}, {
			name : 'abbr',
			index : 'abbr',
			editable : true,
		}, {
			name : 'activ',
			index : 'activ',
			stype : 'select',
			formatter: 'select',
			editable : true,
			edittype : 'select',
			editoptions : {
				value : "0:Inactiv;1:Activ"
			},
			searchoptions : {
				value : ":Toti;0:Inactiv;1:Activ",
				sopt : [ 'eq' ]
			}
		},
		{
			name : 'is_exceptie',
			index : 'is_exceptie',
			stype : 'select',
			formatter: 'select',
			editable : true,
			edittype : 'select',
			editoptions : {
				value : "0:Nu;1:Da"
			},
			searchoptions : {
				value : ":Toti;0:Nu;1:Da",
				sopt : [ 'eq' ]
			}
		},
		{
			name : 'is_public',
			index : 'is_public',
			stype : 'select',
			formatter: 'select',
			editable : true,
			edittype : 'select',
			editoptions : {
				value : "0:Nu;1:Da"
			},
			searchoptions : {
				value : ":Toti;0:Nu;1:Da",
				sopt : [ 'eq' ]
			}
		}],
		height : 400,
		width : 970,
		scroll : 1,
		rowNum : 50,
		mtype : "GET",
		rownumbers : true,
		rownumWidth : 40,
		gridview : true,
		pager : '#checkpoints_listare_pag',
		sortname : 'denumire',
		viewrecords : true,
		sortorder : "asc",
		caption : 'Listare Checkpoints',
		editurl : HTTP + 'checkpoints/editare?q=dummy'
	});
	$("#checkpoints_listare").jqGrid('bindKeys');
	$("#checkpoints_listare").jqGrid('filterToolbar', {
		stringResult : true,
		searchOnEnter : false
	});
	$("#checkpoints_listare").jqGrid('navGrid', '#checkpoints_listare_pag', {
		del : true,
		add : true,
		edit : true,
		search : false
	});
}

// verificari
function VerificareIstoricExpeditie(){
	var expeditie = $('#expeditie').val();

	$.post(HTTP + 'verificari/detalii_expeditie/'+expeditie,{},function(response){
		update = response.split('|||');
		jQuery('#verificari_detalii_expeditie').html(response);

		jQuery("#istoric_expeditie").jqGrid('setGridParam', {
			 url : HTTP + 'verificari/istoric_expeditie?expeditie='+expeditie
		}).trigger("reloadGrid");
		jQuery("#istoric_codbare").jqGrid('setGridParam', {
			url : HTTP + 'scanare/json/istoric_codbare?codbare='+expeditie
		}).trigger("reloadGrid");

		jQuery("#istoric_recantariri").jqGrid('setGridParam', {
			url : HTTP + 'verificari/istoric_recantariri?expeditie='+expeditie
		}).trigger("reloadGrid");

	});

}
function Verificari_IstoricExpeditie(){
	jQuery("#istoric_expeditie").jqGrid({
		url : HTTP + 'verificari/istoric_expeditie',
		datatype : "json",
		colNames : [ 'Operatiune', 'Data', 'Operator', 'Data operarii', 'Primitor' ],
		colModel : [ {
			name : 'operatiune',
			index : 'c.OP_RO',
			sorttype : 'int',
			width: 200
		}, {
			name : 'data',
			index : 'a.DATA',
			sorttype : 'date',
			formatter : 'date',
			datefmt : 'd.m.Y h:i',
			width: 150
		}, {
			name : 'operator',
			index : 'b.USER',
			width: 250
		}, {
			name : 'data_operarii',
			index : 'a.DATA_OP',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d H:i:s',
				newformat : 'd.m.Y H:i'
			},
			width: 150
		}, {
			name : 'primitor',
			index : 'd.PRIMITOR',
			width: 150
		} ],
		rowNum :4,
		rowList : [ 15, 50 ],
		rownumbers : true,
		width : 950,
		height : 'auto',
		sortname : 'a.COD_IST',
		sortorder : "desc",
		viewrecords : true,
		pager : '#paginatie_istoric',
		shrinkToFit: false,
		caption:'',
		forceFit: true,
		onSelectRow : function(id) {
			AfisareContinutIstoricExpeditie(id,0);
		},
        gridComplete: function()
        {
			var userData = $("#istoric_expeditie").getGridParam("userData");
			if(userData.afisare_confirmare_buton){
				$('#confirmare_conform_scan').show();
		    } else {
                $('#confirmare_conform_scan').hide();
			}


        }
	});
	jQuery("#istoric_expeditii").jqGrid('bindKeys');
	jQuery("#istoric_expeditii").jqGrid('navGrid', '#paginatie_istoric', {
		del : false,
		add : false,
		edit : false,
		search : false,
		refresh : true
	});
}

function AfisareDiferenteCentru(){
	var cond = '';

	var categorie = $('#categorie').val();
	var centru = $('#centru').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond = '?centru='+centru+'&categorie='+categorie+'&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);

	jQuery("#liste_istoric_scanare").jqGrid('setGridParam', {
		url : HTTP + 'scanare/json/diferente_centru'+cond
	}).trigger("reloadGrid");
}


function ExportaDiferenteCentru(){
	var cond = '';

	var categorie = $('#categorie').val();
	var centru = $('#centru').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond = '?centru='+centru+'&categorie='+categorie+'&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);

	var url = HTTP + 'scanare/export/diferente_centru'+cond;
	window.location = url;
}


//situatie centru
function Grid_DiferenteCentru(){
	jQuery("#liste_istoric_scanare").jqGrid(
			{
				url : HTTP + 'scanare/json/diferente_centru',
				datatype : "json",
				colNames : [ 'Codbare','Expeditie','Tip','Piese','Destinatar','Expeditor','Centru Liv','Localitate Liv','Centru Col','Localitate Col','Data'],
				colModel : [ {
					name : 'codbare',
					index : 'sc.cod',
					width: 90,
					sortable:false,
					frozen:true
				}, {
					name : 'expeditie',
					index : 'ep.expeditie',
					width: 70,
					sortable:false,
					frozen:true
				}, {
					name : 'tip',
					index : 'tip',
					width: 70,
					sortable:false,
					search: false
				}, {
					name : 'piese',
					index : 'piese',
					width: 30,
					sortable:false,
					search: false
				}, {
					name : 'destinatar',
					index : 'cld.nume',
					width: 150,
					sortable:false
				}, {
					name : 'expeditor',
					index : 'cle.nume',
					width: 150,
					sortable:false
				}, {
					name : 'destinatar_centru',
					index : 'ced.nume',
					width: 100,
					sortable:false
				}, {
					name : 'destinatar_localitate',
					index : 'lcd.nume_lc',
					width: 100,
					sortable:false
				}, {
					name : 'expeditor_centru',
					index : 'cee.nume',
					width: 100,
					sortable:false
				}, {
					name : 'expeditor_localitate',
					index : 'lce.nume_lc',
					width: 100,
					sortable:false
				}, {
					name : 'data_scanare',
					index : 'sc.data',
					width: 110,
					sortable:false,
					search: false
				} ],
				autowidth : true,
				width : 960,
				height : 400,
				rowNum : 1000,
				scroll : 1,
				mtype : "GET",
				shrinkToFit: false,
				forceFit: true,
				rownumbers : true,
				gridview : true,
				sortname : 'ep.expeditie',
				sortorder : "asc",
				viewrecords : true,
				pager : false,
				caption : false,
				multiselect : false,
				subGrid : false,
				grouping: false,
			    footerrow: true,
			    userDataOnFooter: true,
			    ondblClickRow : function(id) {
					AfisareDetaliiExpeditieCuID(id)
				}
			});
	jQuery("#liste_istoric_scanare").jqGrid('bindKeys');
	jQuery("#liste_istoric_scanare").jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
}




//situatie centru
function Grid_DiferenteAgent(){
	jQuery("#liste_istoric_scanare").jqGrid(
			{
				url : HTTP + 'scanare/json/diferente_agent',
				datatype : "json",
				colNames : [ 'Codbare','Expeditie','Agent','Tip','Piese','Destinatar','Expeditor','Centru Liv','Localitate Liv','Centru Col','Localitate Col','Ramburs','Cash','Data','Incasat'],
				colModel : [ {
					name : 'codbare',
					index : 'sc.cod',
					width: 90,
					sortable:false,
					frozen:true
				}, {
					name : 'expeditie',
					index : 'ep.expeditie',
					width: 70,
					sortable:false,
					frozen:true
				}, {
					name : 'curier',
					search: false,
					width: 100,
					sortable:false
				},{
					name : 'tip',
					width: 70,
					sortable:false,
					search: false
				}, {
					name : 'piese',
					width: 30,
					sortable:false,
					search: false
				}, {
					name : 'destinatar',
					index : 'cld.nume',
					width: 150,
					sortable:false
				}, {
					name : 'expeditor',
					index : 'cle.nume',
					width: 150,
					sortable:false
				}, {
					name : 'destinatar_centru',
					index : 'ced.nume',
					width: 100,
					sortable:false
				}, {
					name : 'destinatar_localitate',
					index : 'lcd.nume_lc',
					width: 100,
					sortable:false
				}, {
					name : 'expeditor_centru',
					index : 'cee.nume',
					width: 100,
					sortable:false
				}, {
					name : 'expeditor_localitate',
					index : 'lce.nume_lc',
					width: 100,
					sortable:false
				}, {
					name : 'ramburs',
					index : 'ep.ramburs',
					align: 'right',
					width: 100,
					sortable:false
				}, {
					name : 'cash',
					index : 'ep.valoare_totala_expeditie',
					width: 100,
					align: 'right',
					sortable:false
				}, {
					name : 'data_scanare',
					index : 'sc.data',
					width: 110,
					sortable:false,
					align: 'center',
					search: false
				}, {
					name : 'incasat',
					search: false,
					width: 50,
					align: 'center',
					sortable:false
				} ],
				autowidth : true,
				width : 960,
				height : 400,
				rowNum : 1000,
				scroll : 1,
				mtype : "GET",
				shrinkToFit: false,
				forceFit: true,
				rownumbers : true,
				gridview : true,
				sortname : 'ep.expeditie',
				sortorder : "asc",
				viewrecords : true,
				pager : false,
				caption : false,
				multiselect : false,
				subGrid : false,
				grouping: false,
			    footerrow: true,
			    userDataOnFooter: true,
			    ondblClickRow : function(id) {
					AfisareDetaliiExpeditieCuID(jQuery("#liste_istoric_scanare").getCell(id,'expeditie'));
				},
				gridComplete: function()
				{
				    var rows = jQuery("#liste_istoric_scanare").getDataIDs();
				    for (var i = 0; i < rows.length; i++)
				    {
				        var status = jQuery("#liste_istoric_scanare").getCell(rows[i],"incasat");
				        if(status == "1")
				            jQuery("#liste_istoric_scanare").jqGrid('setRowData',rows[i],false, {  color:'black',weightfont:'bold',background:'#e7f899'});
				        else if(status == "2")
				            jQuery("#liste_istoric_scanare").jqGrid('setRowData',rows[i],false, {  color:'black',weightfont:'bold',background:'#f89999'});
				    }
				}
			});
	jQuery("#liste_istoric_scanare").jqGrid('bindKeys');
	/* jQuery("#liste_istoric_scanare").jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
	*/
}


function AfisareDiferenteAgent(){
	var cond = '';

	var categorie = $('#categorie').val();
	var agent = $('#agent').val();
	var centru = $('#centru').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond = '?agent='+agent+'&categorie='+categorie+'&centru='+centru+'&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	jQuery("#liste_istoric_scanare").jqGrid('setGridParam', {
		url : HTTP + 'scanare/json/diferente_agent'+cond
	}).trigger("reloadGrid");
}


function ExportaDiferenteAgent(){
	var cond = '';

	var categorie = $('#categorie').val();
	var agent = $('#agent').val();
	var centru = $('#centru').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond = '?agent='+agent+'&categorie='+categorie+'&centru='+centru+'&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);

	var url = HTTP + 'scanare/export/diferente_agent'+cond;
	window.location = url;
}


function AfisarePerioadaOra(){
	var dates = jQuery("#data_start, #data_final")
	.datetimepicker({
		changeMonth : true,
		changeYear : true,
		showOn : "both",
		buttonImage : HTTP + "assets/images/calendar.gif",
		buttonImageOnly : true,
		dateFormat : 'dd.mm.yy',
		timeFormat : 'hh:mm:ss',
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

function Grid_DifExpScan() {
	jQuery("#dif_exp_scan").jqGrid(
			{
				url : HTTP + 'expeditii/json/dif_exp_scan',
				datatype : "json",
				colNames : ['Centru','Nr. expeditii colectate'],
				colModel : [ {
					name : 'centru',
					index : 'cee.id',
					align: 'left',
					sortable:true,
					width: 100
				}, {
					name : 'exp_col',
					align: 'right',
					sortable:false,
					width: 100
				} ],
				rowNum : 1000,
				width : 970,
				height : 250,
				mtype : "GET",
				sortname : 'centru',
				sortorder : "asc",
				rownumbers : false,
				gridview : true,
				viewrecords : true,
				pager : false,
				caption : false,
				multiselect : false,
				subGrid : false,
				footerrow: true,
			    userDataOnFooter: true
			});
}

function AfisareDifExpScan() {
	var cond = '';
	var centru = $('#centru').val();
	var data_start = $('#data_start').val();
	cond += '&data_start=' + escape(data_start);
	cond += '&centru='+ escape(centru);

	jQuery("#dif_exp_scan").jqGrid('setGridParam', {
		 url : HTTP + 'expeditii/json/dif_exp_scan?a' + cond
	}).trigger("reloadGrid");
	jQuery('#eroare').html((jQuery("#dif_exp_scan").getGridParam('userData')).eroare);
}

function startVerificareOpXlsExpeditii()
{
    var fxls = $('#fxls').val();
    var cod_cl = $('#cod_cl').val();
	var sw_cl = $('#sw_cl').val();
    document.getElementById('progressor').style.width = "0%";
    var source = new EventSource('importuri-op-xls/expeditii/check?fxls='+fxls+'&cod_cl='+cod_cl+'&sw_cl='+sw_cl);
    document.getElementById('results').innerHTML = '';
    //a message is received
    source.addEventListener('message' , function(e)
	{
        var result = JSON.parse( e.data );
        var color = 'black';
        if(result.stop == 1 || result.stop == 2)
        	color = 'red';
        add_log(result.message, color);
        document.getElementById('progressor').style.width = result.progress + "%";
        if(result.stop == 2)
        {
            add_log('Fisierul initial are erori !!! <br/> Va rog sa il corectati si sa reluati procedura de import','red');
            $('#merror').val(1);
            source.close();
        }
        else if(result.stop == 3)
        {
			$('#sxls').removeAttr('disabled');
			source.close();
        }
	});

    source.addEventListener('error' , function(e)
    {
        add_log('Error occured','red');
        //kill the object ?
        source.close();
    });
}

function startImportOpXlsExpeditii(){
    document.getElementById('progressor').style.width = "0%";
    var fxls = $('#fxls').val();
    var cod_cl = $('#cod_cl').val();
	var sw_cl = $('#sw_cl').val();
    var source = new EventSource('importuri-op-xls/expeditii/import?fxls='+fxls+'&cod_cl='+cod_cl+'&sw_cl='+sw_cl);
    document.getElementById('results').innerHTML = '';
    //a message is received
    source.addEventListener('message' , function(e)
    {
    	// stop = 0 : ok
    	// stop = 1 : error
    	// stop = 2 : error + stop
    	// stop = 3 : stop
        var result = JSON.parse( e.data );
        var color = 'black';
        if(result.stop == 1 || result.stop == 2)
        	color = 'red';
        add_log(result.message, color);
        document.getElementById('progressor').style.width = result.progress + "%";
        if(result.stop == 3)
        {
            $('#sxls').attr('disabled', true);
            source.close();
        }
    });

    source.addEventListener('error' , function(e)
    {
        add_log('Error occured', 'red');
        $('#sxls').attr('disabled', true);
        //kill the object ?
        source.close();
    });
}

function startVerificareXlsExpeditii()
{
    var fxls = $('#fxls').val();
    var cod_cl = $('#cod_cl').val();
    document.getElementById('progressor').style.width = "0%";
    var source = new EventSource('importuri-xls/expeditii/check?fxls='+fxls+'&cod_cl='+cod_cl);
    document.getElementById('results').innerHTML = '';
    //a message is received
    source.addEventListener('message' , function(e)
	{
        var result = JSON.parse( e.data );
        var color = 'black';
        if(result.stop == 1 || result.stop == 2)
        	color = 'red';
        add_log(result.message, color);
        document.getElementById('progressor').style.width = result.progress + "%";
        if(result.stop == 2)
        {
            add_log('Fisierul initial are erori !!! <br/> Va rog sa il corectati si sa reluati procedura de import','red');
            $('#merror').val(1);
            source.close();
        }
        else if(result.stop == 3)
        {
			$('#sxls').removeAttr('disabled');
			source.close();
        }
	});

    source.addEventListener('error' , function(e)
    {
        add_log('Error occured','red');
        //kill the object ?
        source.close();
    });
}

function startImportXlsExpeditii(){
    document.getElementById('progressor').style.width = "0%";
    var fxls = $('#fxls').val();
    var cod_cl = $('#cod_cl').val();
    var source = new EventSource('importuri-xls/expeditii/import?fxls='+fxls+'&cod_cl='+cod_cl);
    document.getElementById('results').innerHTML = '';
    //a message is received
    source.addEventListener('message' , function(e)
    {
    	// stop = 0 : ok
    	// stop = 1 : error
    	// stop = 2 : error + stop
    	// stop = 3 : stop
        var result = JSON.parse( e.data );
        var color = 'black';
        if(result.stop == 1 || result.stop == 2)
        	color = 'red';
        add_log(result.message, color);
        document.getElementById('progressor').style.width = result.progress + "%";
        if(result.stop == 3)
        {
            $('#sxls').attr('disabled', true);
            source.close();
        }
    });

    source.addEventListener('error' , function(e)
    {
        add_log('Error occured', 'red');
        $('#sxls').attr('disabled', true);
        //kill the object ?
        source.close();
    });
}

function startVerificareCsvExpeditii()
{
    var fxls = $('#fxls').val();
    var cod_cl = $('#cod_cl').val();
    var sep = $('#separator').val();
    document.getElementById('progressor').style.width = "0%";
    var source = new EventSource('importuri-csv/expeditii/check?fxls='+fxls+'&cod_cl='+cod_cl+'&separator='+sep);
    document.getElementById('results').innerHTML = '';
    //a message is received
    source.addEventListener('message' , function(e)
	{
        var result = JSON.parse( e.data );
        var color = 'black';
        if(result.stop == 1 || result.stop == 2)
        	color = 'red';
        add_log(result.message, color);
        document.getElementById('progressor').style.width = result.progress + "%";
        if(result.stop == 2)
        {
            add_log('Fisierul initial are erori !!! <br/> Va rog sa il corectati si sa reluati procedura de import','red');
            $('#merror').val(1);
            source.close();
        }
        else if(result.stop == 3)
        {
			$('#sxls').removeAttr('disabled');
			source.close();
        }
	});

    source.addEventListener('error' , function(e)
    {
        add_log('Error occured','red');
        //kill the object ?
        source.close();
    });
}

function startImportCsvExpeditii(){
    document.getElementById('progressor').style.width = "0%";
    var fxls = $('#fxls').val();
    var cod_cl = $('#cod_cl').val();
    var sep = $('#separator').val();
    var source = new EventSource('importuri-csv/expeditii/import?fxls='+fxls+'&cod_cl='+cod_cl+'&separator='+sep);
    document.getElementById('results').innerHTML = '';
    //a message is received
    source.addEventListener('message' , function(e)
    {
    	// stop = 0 : ok
    	// stop = 1 : error
    	// stop = 2 : error + stop
    	// stop = 3 : stop
        var result = JSON.parse( e.data );
        var color = 'black';
        if(result.stop == 1 || result.stop == 2)
        	color = 'red';
        add_log(result.message, color);
        document.getElementById('progressor').style.width = result.progress + "%";
        if(result.stop == 3)
        {
            $('#sxls').attr('disabled', true);
            source.close();
        }
    });

    source.addEventListener('error' , function(e)
    {
        add_log('Error occured', 'red');
        $('#sxls').attr('disabled', true);
        //kill the object ?
        source.close();
    });
}

function startVerificareXlsDestinatari()
{
    var fxls = $('#fxls').val();
    var cod_cl = $('#cod_cl').val();
    document.getElementById('progressor').style.width = "0%";
    var source = new EventSource('importuri-xls/destinatari/check?fxls='+fxls+'&cod_cl='+cod_cl);
    document.getElementById('results').innerHTML = '';
    //a message is received
    source.addEventListener('message' , function(e)
	{
        var result = JSON.parse( e.data );
        var color = 'black';
        if(result.stop == 1 || result.stop == 2)
        	color = 'red';
        add_log(result.message, color);
        document.getElementById('progressor').style.width = result.progress + "%";
        if(result.stop == 2)
        {
            add_log('Fisierul initial are erori !!! <br/> Va rog sa il corectati si sa reluati procedura de import','red');
            $('#merror').val(1);
            source.close();
        }
        else if(result.stop == 3)
        {
			$('#sxls').removeAttr('disabled');
			source.close();
        }
	});

    source.addEventListener('error' , function(e)
    {
        add_log('Error occured','red');
        //kill the object ?
        source.close();
    });
}

function startImportXlsDestinatari(){
    document.getElementById('progressor').style.width = "0%";
    var fxls = $('#fxls').val();
    var cod_cl = $('#cod_cl').val();
    var source = new EventSource('importuri-xls/destinatari/import?fxls='+fxls+'&cod_cl='+cod_cl);
    document.getElementById('results').innerHTML = '';
    //a message is received
    source.addEventListener('message' , function(e)
    {
    	// stop = 0 : ok
    	// stop = 1 : error
    	// stop = 2 : error + stop
    	// stop = 3 : stop
        var result = JSON.parse( e.data );
        var color = 'black';
        if(result.stop == 1 || result.stop == 2)
        	color = 'red';
        add_log(result.message, color);
        document.getElementById('progressor').style.width = result.progress + "%";
        if(result.stop == 3)
        {
            $('#sxls').attr('disabled', true);
            source.close();
        }
    });

    source.addEventListener('error' , function(e)
    {
        add_log('Error occured', 'red');
        $('#sxls').attr('disabled', true);
        //kill the object ?
        source.close();
    });
}

function add_log(message, color)
{
	if(!color) color = 'black';
    if(message)
    {
        var r = document.getElementById('results');
        r.innerHTML += '<span style="color:'+color+'">'+message+'</span>' + '<br/>';
        r.scrollTop = r.scrollHeight;
    }
}

function ComboClientiContract(field){
		$('#'+field+"_nume").autocomplete({
			source : function (request, response) {
                      $.ajax({
                          type: "POST",
                          url: HTTP + "expeditii/json/clienti_ctr",
                          data: {term: request.term},
                          dataType: "json",
                          success: function(data){
                			response($.map(data.rezultat, function(item) {
                    			return {
                        			label : item.label,
                        			value : item.value,
                        			cod_cl : item.cod_cl
                    			}
                		  	}));
                		  },
                		  error: function(xhr, status, error) {
     						AfiseazaEroare(xhr.responseText);
  						  }
                     });
                },
                minLength: 1,
                select: function(event,ui){
                	$('#'+field).val(ui.item.cod_cl);
                    $('#'+field+"_nume").val(ui.item.value);
                },
            	open: function() {
					$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
                	$('#'+field).val("");
				},
				change : function(event, ui) {
					if(!($.trim($("#" + field + "_nume").val()).length))
						$("#" + field).val("");
				},
				close: function() {
					$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
				}
		});
}

function AfisareBoLivrareCurieri(){
	var cond = '';

	var data_start = $('#data_start').val();
	var agent = parseInt($('#agent').val());
	var centru = parseInt($('#centru').val());

	cond = '?data_start=' + escape(data_start)+'&centru='+centru;
	if(!isNaN(agent) && agent > 0) cond += '&agent='+agent;

	jQuery("#borderouri_livrare_curieri").jqGrid('setGridParam', {
		url : HTTP + 'scanare/json/bo_livrare_curieri'+cond
	}).trigger("reloadGrid");
}

function Grid_BoLivrareCurieri() {
	var centru = parseInt($('#centru').val());
	var data_start = $('#data_start').val();

	jQuery("#borderouri_livrare_curieri").jqGrid(
			{
				url : HTTP + 'scanare/json/bo_livrare_curieri?data_start=' + data_start+'&centru='+centru,
				datatype : "json",
				colNames : ['Borderou','Data','Nr. expeditii','Piese','Kg approx.', 'Agent', 'Android'],
				colModel : [ {
					name : 'borderou',
					index : 'sc.borderou',
					width: 80,
					sorttype : 'int',
					align:'center'
				}, {
					name : 'data_scanare',
					index : 'sc.data',
					sorttype : 'date',
					formatter : 'date',
					formatoptions : {
						srcformat : 'Y-m-d',
						newformat : 'Y-m-d'
					},
					align: 'center',
					width: 80
				}, {
					name : 'nr_expeditii',
					index: 'nr_expeditii',
					width: 40,
					align:'center'
				},{
					name : 'piese',
					search: false,
					sortable: false,
					width: 40,
					align:'right'
				},{
					name : 'kg',
					index : 'kg',
					width: 40,
					align:'right'
				},{
					name : 'agent',
					index : 'ag.nume_ag',
					width: 80,
					align:'center'
				} ,{
					name : 'ack',
					index : 'scb.ack',
					width: 80,
					align:'center'
				} ],
				rowNum : 1000,
				width : 970,
				height: 400,
				scroll : 1,
				mtype : "GET",
				rownumbers : false,
				gridview : true,
				sortname: 'sc.data',
				sortorder: "desc",
				viewrecords : true,
				caption : 'Lista Borderouri',
				multiselect : true,
				subGrid: true,
				footerrow: true,
	    		userDataOnFooter: true,
				subGridRowExpanded: function(subgrid_id, row_id) {
					var subgrid_table_id, pager_id;
					subgrid_table_id = subgrid_id+"_t";
					pager_id = "p_"+subgrid_table_id;
					$("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table><div id='"+pager_id+"' class='scroll'></div>");

					jQuery("#"+subgrid_table_id).jqGrid({
						url : HTTP + 'scanare/json/bo_livrare_curieri/'+row_id,
						datatype : "json",
						colNames : [ 'CodBare','Nr. NT','Bor','Categ','Piese','Kg','Tip','Status','Ruta','Centru','Centru dest.','Agent','Expeditor','Localitate','Destinatar','Localitate','Cash','Asig/Ramb','Colectare','Scanare','User','Centru exp','Centru dest'],
						colModel : [ {
							name : 'codbare',
							index : 'sc.cod',
							width: 70
						}, {
							name : 'expeditie',
							index : 'ep.expeditie',
							width: 60
						}, {
							name : 'borderou',
							index : 'sc.borderou',
							width: 40
						}, {
							name : 'categorie',
							search: false,
							sortable: false,
							width: 40
						}, {
							name : 'piese',
							search: false,
							sortable: false,
							width: 20,
						},{
							name : 'greutate',
							search: false,
							sortable: false,
							width: 40,
						}, {
							name : 'tip',
							index : 'ck.denumire',
							width: 80,
							sortable:true
						}, {
							name : 'operatiune',
							index : 'ep.operatiune',
							width: 80,
							sortable:true
						}, {
							name : 'ruta',
							index : 'ru.denumire',
							width: 90,
							sortable:false
						}, {
							name : 'centru',
							index : 'ce.nume',
							width: 100
						},{
							name : 'centru_dest',
							index : 'ced.nume',
							width: 100
						}, {
							name : 'curier',
							index : 'ag.nume_ag',
							width: 90,
							sortable:false
						}, {
							name : 'expeditor',
							index : 'cle.nume',
							width: 100
						}, {
							name : 'expeditor_localitate',
							index : 'lce.nume_lc',
							width: 80
						}, {
							name : 'destinatar',
							index : 'cld.nume',
							width: 100
						}, {
							name : 'destinatar_localitate',
							index : 'lcd.nume_lc',
							width: 80
						}, {
							name : 'cash',
							index : 'ep.valoare_totala_expeditie',
							width: 60
						}, {
							name : 'ramburs',
							index : 'ep.valoare_asigurata',
							width: 60
						}, {
							name : 'data_expeditie',
							index : 'ep.data_expeditie',
							width: 60
						}, {
							name : 'data_scanare',
							index : 'sc.data',
							width: 110
						}, {
							name : 'user',
							index : 'u.user',
							width: 80
						}, {
							name : 'expeditor_centru_cod',
							index : 'expeditor_centru_cod',
							width: 80
						}, {
							name : 'destinatar_centru_cod',
							index : 'destinatar_centru_cod',
							width: 80
						} ],
						rowNum : 2000,
					   	pager: pager_id,
					   	sortname: 'ep.data_expeditie',
					    sortorder: "desc",
					    height: 200,
					    gridview : false,
						pager : false,
						viewrecords : false,
					    ondblClickRow : function(id) {
							AfisareDetaliiExpeditieCuID(id)
						},
						footerrow: true,
	    				userDataOnFooter: true
					});
					jQuery("#"+subgrid_table_id).jqGrid('navGrid',"#"+pager_id,{edit:false,add:false,del:false})
				}
			});
	jQuery("#borderouri_livrare_curieri").jqGrid('bindKeys');
}

function PrintareBoLivrareCurieri(){
	var centru = parseInt($('#centru').val());
	var agent = parseInt($('#agent').val());
	if(isNaN(agent) || agent == 0) { AfiseazaEroare("Introduceti agentul!"); return false; }

	var grid = jQuery('#borderouri_livrare_curieri');
	var sel_ids = grid.jqGrid('getGridParam', 'selarrrow');
	if(sel_ids.length==0) { AfiseazaEroare("Selectioneaza minimum un borderou pentru printare!"); return false; }
	var postData = {};

	postData[0] = centru;
	postData[1] = agent;
	for(var i=0, l=sel_ids.length; i<l; i++) postData[i+2] = sel_ids[i];
	//alert("JSON serialized jqGrid data:\n" + postData);
	$.download('scanare/print/bo_livrare_curieri', JSON.stringify(postData), true);
	return true;
}

var rowsToColor = [];
function Grid_IstoricScanareExpeditii()
{
	SetareDate();
	var grid = jQuery('#istoric_scanari_expeditii');

	getColumnIndexByName = function(mygrid,columnName) {
        var cm = mygrid.jqGrid('getGridParam','colModel');
        for (var i=0,l=cm.length; i<l; i++) {
            if (cm[i].name===columnName) {
                return i; // return the index
            }
        }
        return -1;
    };

	grid.jqGrid(
	{
		url : HTTP + 'expeditii/json/istoric_scanari',
		datatype: 'json',
		mtype: 'POST',
		colNames : [ 'Data Prel','Nr. Exp', 'Expeditor','Centru Exp','Loc. Exp','Destinatar','Centru Dest','Loc. Dest', 'Last scan', 'Status', 'Data Op','Primitor','Samb', 'NT Scan'],
		colModel : [ {
			name : 'data_expeditie',
			index : 'ep.data_expeditie',
			width: 70,
			align: 'center'
		}, {
			name : 'expeditie',
			index : 'ep.expeditie',
			sorttype : 'int',
			width: 65,
			align: 'right'
		}, {
			name : 'expeditor',
			index : 'cle.nume',
			width: 150
		}, {
			name : 'expeditor_centru',
			index : 'cee.nume',
			width: 70
		},{
			name : 'expeditor_localitate',
			index : 'lce.nume_lc',
			width: 80
		}, {
			name : 'destinatar',
			index : 'cld.nume',
			width: 150
		}, {
			name : 'destinatar_centru',
			index : 'ced.nume',
			width: 70
		},{
			name : 'destinatar_localitate',
			index : 'lcd.nume_lc',
			width: 80
		}, {
			name : 'last_scan',
			search: false,
			sortable:false,
			align: 'center',
			width: 120
			//formatter: rowColorFormatter
		}, {
			name : 'operatiune',
			index : 'ep.operatiune',
			stype : 'select',
			searchoptions : {
				value : ":Toate;ABANDONAT:Abandonat;AVARIAT:Avariat;AVIZAT:Avizat;COLECTATA:Colectata;CONFISCAT:Confiscat;DISTRUS:Distrus;EXPEDITIE NESOSITA:Expeditie nesosita;IESIRE DEPOZIT:Iesire depozit;INCASAT:Incasat;LIVRARE NEREUSITA:Livrare nereusita;LIVRAT:Livrat;PRELUATA:Preluata;PIERDUT:Pierdut;PREALERTAT:Prealertat;REAVIZAT:Reavizat;RECEPTIE DEP. LOCAL:Receptie dep. local;RECEPTIE DP. CENTRAL:Receptie dp. central;REDIRECTIONAT:Redirectionat;REEXPEDIAT:Reexpediat;RETINUT:Retinut;RETINUT IN VAMA:Retinut in vama;RETURNAT:Returnat;SPRE LIVRARE:Spre livrare;VAMUIT:Vamuit",
				sopt : [ 'eq' ]
			},
			align: 'center',
			width: 100
			//formatter: rowColorFormatter
		}, {
			name : 'data_op',
			index : 'ep.data_op',
			width: 70
		}, {
			name : 'primitor',
			index : 'ep.primitor',
			width: 150
		}, {
			name : 'liv_samb',
			index : 'ep.liv_samb',
			stype : 'select',
			searchoptions : {
				value : ":Toate;1:DA;0:NU",
				sopt : [ 'eq' ]
			},
			align: 'center',
			width: 60
			//formatter: rowColorFormatter
		}, {
			name : 'folder',
			search: false,
			sortable:false,
			align: 'center',
			width: 60
			//formatter: rowColorFormatter
		} ],
		rownumbers : true,
		scroll : true,
		rowNum : 100,
		mtype : "GET",
		width : 970,
		height : 350,
		sortname : 'ep.data_expeditie',
		sortorder : "desc",
		viewrecords : true,
		shrinkToFit: false,
		caption:'Lista Expeditii',
		forceFit: true,
		onSelectRow : function(id) {
			AfisareIstoricStatusExpeditie(id);
			AfisareIstoricCheckpointsExpeditie(id);
			return false;
		},
	    loadComplete: function() {
	            var iCol = getColumnIndexByName($(this),'operatiune'),
	                cRows = this.rows.length, iRow, row, className;
	            var iCol1 = getColumnIndexByName($(this),'liv_samb');

	            for (iRow=0; iRow<cRows; iRow++) {
	                row = this.rows[iRow];
	                className = row.className;
	                if ($.inArray('jqgrow', className.split(' ')) > 0) { // $(row).hasClass('jqgrow')
	                	var status = $(row.cells[iCol]).html();
	                    if (status == "COLECTATA") {
	                        if ($.inArray('myAltRowClass', className.split(' ')) === -1) {
	                            row.className = className + ' myAltRowClass';
	                        }
	                    }
	                }
	            }
	        }
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
}

function Grid_IstoricStatusExpeditii() {
	jQuery("#istoric_status_expeditii").jqGrid({
		url : HTTP + 'expeditii/json/istoric_status',
		datatype : "json",
		colNames : [ 'Operatiune', 'Data', 'Operator', 'Data operarii' ],
		colModel : [ {
			name : 'operatiune',
			index : 'c.OP_RO',
			sorttype : 'int',
			width: 100
		}, {
			name : 'data',
			index : 'a.DATA',
			sorttype : 'date',
			formatter : 'date',
			datefmt : 'd.m.Y h:i',
			width: 70
		}, {
			name : 'operator',
			index : 'b.USER',
			width: 100
		}, {
			name : 'data_operarii',
			index : 'a.DATA_OP',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d H:i:s',
				newformat : 'd.m.Y H:i'
			},
			width: 90
		} ],
		rowNum :100,
		rownumbers : false,
		width : 380,
		height : 'auto',
		sortname : 'a.COD_IST',
		sortorder : "desc",
		viewrecords : true,
		shrinkToFit: false,
		caption:'Statusuri',
		forceFit: true,
	});
	jQuery("#istoric_status_expeditii").jqGrid('bindKeys');
}

function Grid_IstoricCheckpointsExpeditii() {
	jQuery("#istoric_checkpoints_expeditii").jqGrid({
		url : HTTP + 'expeditii/json/istoric_checkpoints',
		datatype : "json",
			colNames : [ 'Cod','Centru','Ruta','Curier','Tip','Data'],
			colModel : [ {
					name : 'cod',
					index : 'cod',
					width: 80
			}, {
					name : 'centru',
					index : 'centru',
					width: 60
			}, {
					name : 'ruta',
					index : 'denumire',
					width: 80
			}, {
					name : 'curier',
					index : 'curier',
					width: 80
			}, {
					name : 'tip',
					index : 'tip',
					width: 100
			}, {
					name : 'data',
					index : 'b.data',
					width: 100
			} ],
			rowNum : 100,
			rownumbers : false,
			width : 580,
			height : 'auto',
			mtype : "GET",
			sortname : 'data',
			viewrecords : true,
			sortorder : "asc",
			caption : 'Checkpoints',
			forcefit : true
	});
	jQuery("#istoric_checkpoints_expeditii").jqGrid('bindKeys');
}

function AfisareIstoricStatusExpeditie(sel) {
	sel=parseInt(sel);
	if(isNaN(sel) || sel == 0) return false;
	jQuery("#istoric_status_expeditii").jqGrid('setGridParam', {url : HTTP + 'expeditii/json/istoric_status?expeditie=' + sel}).trigger("reloadGrid");
}

function AfisareIstoricCheckpointsExpeditie(sel) {
	sel=parseInt(sel);
	if(isNaN(sel) || sel == 0) return false;
	jQuery("#istoric_checkpoints_expeditii").jqGrid('setGridParam', {url : HTTP + 'expeditii/json/istoric_checkpoints?expeditie=' + sel}).trigger("reloadGrid");
}

function AfisareIstoricScanariExpeditii()
{
	var cond = '';
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond = '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	jQuery("#istoric_scanari_expeditii").jqGrid('setGridParam',{url : HTTP + 'expeditii/json/istoric_scanari?a' + cond}).trigger("reloadGrid");
}

function ExportIstoricScanariExpeditii()
{
	var cond = '';
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond += '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	var filters =  $('#istoric_scanari_expeditii').jqGrid('getGridParam','postData').filters;
	if(filters) cond+='&filters='+filters;
	sidx = $('#istoric_scanari_expeditii').jqGrid('getGridParam','sortname');
	if(sidx) cond+='&sidx='+sidx;
	sord = $('#istoric_scanari_expeditii').jqGrid('getGridParam','sortorder');
	if(sord) cond+='&sord='+sord;

	$.download('expeditii/export_istoric_scanari', cond, false);
}

function vAfisareIstoricScanareListeExpeditii(){
	var cond = '';

	if($('#agent_NUME').val() == '')$('#agent').val('');
	if($('#centru_nume').val() == '')$('#centru').val('');
	if($('#ruta_NUME').val() == '')$('#ruta').val('');

	var categorie = $('#categorie').val();
	var borderou = $('#borderou').val();
	var tip_scanare = $('#tip_scanare').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var agent = $('#agent').val();
	var centru = $('#centru').val();
	var ruta = $('#ruta').val();
	var puisori = ($('#puisori').is(':checked'))?1:0;

	cond = '?puisori='+puisori+'&tip_scanare='+tip_scanare+'&ruta='+ruta+'&agent='+agent+'&centru='+centru+'&borderou='+borderou+'&categorie='+categorie+'&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	jQuery("#liste_istoric_scanare").jqGrid('setGridParam', {
		url : HTTP + 'expeditii/json/v_istoric_scanari'+cond
	}).trigger("reloadGrid");
}

function vGrid_IstoricScanare(){
	jQuery("#liste_istoric_scanare").jqGrid(
			{
				url : HTTP + 'expeditii/json/v_istoric_scanari',
				datatype : "json",
				colNames : [ 'CodBare','Nr. NT','Bor','Categ','Piese','Kg','Tip','Status','Ruta','Centru','Centru dest.','Agent','Expeditor','Localitate','Destinatar','Localitate','Cash','Asig/Ramb','Colectare','Scanare','User','Centru exp','Centru dest'],
				colModel : [ {
					name : 'codbare',
					index : 'a.cod',
					width: 70
				}, {
					name : 'expeditie',
					index : 'ep.expeditie',
					width: 60
				}, {
					name : 'borderou',
					index : 'a.borderou',
					width: 40
				}, {
					name : 'categorie',
					index : 'tip_obj',
					stype : 'select',
					formatter: 'select',
					editoptions : {
						value : ":Toate;0:NA;1:plic;2:colet;3:palet;4:colet+palet"
					},
					searchoptions : {
						value : ":Toate;1:plic;2:colet;3:palet;4:colet+palet",
						sopt : [ 'eq' ]
					},
					align: 'center',
					width: 60,
					sortable:false
				}, {
					name : 'piese',
					sortable:false,
					search:false,
					width: 20,
				},{
					name : 'greutate',
					index : 'ep.greutate',
					width: 40,
				}, {
					name : 'tip',
					index : 'f.denumire',
					width: 80,
					sortable:false
				}, {
					name : 'operatiune',
					index : 'ep.operatiune',
					width: 80,
					sortable:true
				}, {
					name : 'ruta',
					index : 'd.denumire',
					width: 90,
					sortable:false
				}, {
					name : 'centru',
					index : 'g.nume',
					width: 100
				},{
					name : 'destinatar_centru',
					index : 'destinatar_centru',
					width: 100
				}, {
					name : 'curier',
					index : 'e.nume_ag',
					width: 90,
					sortable:false
				}, {
					name : 'expeditor',
					index : 'cle.nume',
					width: 100
				}, {
					name : 'expeditor_localitate',
					index : 'lce.nume_lc',
					width: 80
				}, {
					name : 'destinatar',
					index : 'cld.nume',
					width: 100
				}, {
					name : 'destinatar_localitate',
					index : 'lcd.nume_lc',
					width: 80
				}, {
					name : 'cash',
					index : 'ep.valoare_totala_expeditie',
					width: 60
				}, {
					name : 'ramburs',
					index : 'ep.valoare_asigurata',
					width: 60
				}, {
					name : 'data_expeditie',
					index : 'ep.data_expeditie',
					width: 60
				}, {
					name : 'data_scanare',
					index : 'a.data',
					width: 110
				}, {
                    name : 'user',
                    index : 'u.user',
                    width: 80
                }, {
                    name : 'expeditor_centru_cod',
                    index : 'expeditor_centru_cod',
                    width: 80
                }, {
                    name : 'destinatar_centru_cod',
                    index : 'destinatar_centru_cod',
                    width: 80
                } ],
				autowidth : true,
				width : 960,
				height : 400,
				rowNum : 300,
				// scroll : 1,
				mtype : "GET",
				shrinkToFit: false,
				forceFit: true,
				rownumbers : true,
				gridview : true,
				sortname : 'cld.nume',
				sortorder : "asc",
				viewrecords : true,
				pager : true,
				pager : '#paginatie',
				caption : false,
				multiselect : false,
				subGrid : false,
				grouping: false,
			    footerrow: true,
			    userDataOnFooter: true,
			    ondblClickRow : function(id) {
					var expeditie = jQuery("#liste_istoric_scanare").jqGrid('getCell', id, 'expeditie');
					if(expeditie){
						AfisareDetaliiExpeditieCuID(expeditie);
					}else{
						AfiseazaEroare('Nu exista expeditia','Eroare',125);
						return false;
					}
				}
			});
	jQuery("#liste_istoric_scanare").jqGrid('bindKeys');
	jQuery("#liste_istoric_scanare").jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
	jQuery("#liste_istoric_scanare").jqGrid('bindKeys');
}

function vExportIstoricScanareListeExpeditii(){
	var cond = '';
	if($('#agent_NUME').val() == '')$('#agent').val('');
	if($('#centru_nume').val() == '')$('#centru').val('');
	if($('#ruta_NUME').val() == '')$('#ruta').val('');

	var categorie = $('#categorie').val();
	var borderou = $('#borderou').val();
	var tip_scanare = $('#tip_scanare').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var agent = $('#agent').val();
	var centru = $('#centru').val();
	var ruta = $('#ruta').val();
	var puisori = ($('#puisori').is(':checked'))?1:0;

	cond = '?puisori='+puisori+'&tip_scanare='+tip_scanare+'&agent='+agent+'&ruta='+ruta+'&centru='+centru+'&borderou='+borderou+'&categorie='+categorie+'&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);

	var filters =  $('#liste_istoric_scanare').jqGrid('getGridParam','postData').filters;
	if(filters) cond+='&filters='+filters;
	sidx = $('#liste_istoric_scanare').jqGrid('getGridParam','sortname');
	if(sidx) cond+='&sidx='+sidx;
	sord = $('#liste_istoric_scanare').jqGrid('getGridParam','sortorder');
	if(sord) cond+='&sord='+sord;

	window.open(HTTP+"expeditii/v_export_expeditii_istoric_scanare"+cond, '_blank');
  	return true;
}