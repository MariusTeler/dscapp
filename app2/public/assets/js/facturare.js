/* //////////////////////////////////////////////////////
				START FACTURI
//////////////////////////////////////////////////////  */

function CuiPlatitorCheck() {

	$('#platitor_afm').removeClass('cod-valid');
	$('#platitor_afm').removeClass('cod-invalid');
	$('#detalii_societate').html('');
	$('#plateste_buton').prop('disabled', true);
	var cui = $('#platitor_afm').val();

	if($('#platitor_pf:checkbox:checked').length > 0 && isValidCui(cui)){
		$('#platitor_afm').addClass('cod-invalid');
		$('#detalii_societate').html('<h4 style="color: red">Ai bifat Pers. fizica si CUI este valid</h4>');
		return true;
	}
	if(!isValidCui(cui)) return;

	$('#detalii_societate').html('');
	$.ajax( {type: "POST", url : HTTP + 'facturi/verificare_cui', dataType: 'json', data : {cui:$('#platitor_afm').val()}} )
		.done(function(res) {
			if(res.eroare == 1 ){
				$('#detalii_societate').html('<h4 style="color: red">' + res.denumire + '</h4><br><h4 style="color: red">' + res.mesaj + '</h4>');
				$('#platitor_afm').addClass('cod-invalid');
			}
			else if(res.eroare == 0){
				$('#detalii_societate').html('<label>Nume:</label><strong>'+res.denumire + '</strong><br><label>Adresa:</label>'+res.adresa + '<br><label>Mesaj:</label>'+res.mesaj);
				$('#platitor_afm').val($('#platitor_afm').val().toUpperCase());

				if($("#platitor_nume").val().length == 0){
					$("#platitor_nume").val(res.denumire);
				}
				$('#platitor_afm').addClass('cod-valid');
				$('#plateste_buton').prop('disabled', false);

				if(res.tva){
					$('#platitor_afm').val('RO'+res.cui);
				} else {
					$('#platitor_afm').val(res.cui);
				}
			}
		})
		.fail(function(res) {
			$('#plateste_buton').prop('disabled', false);
		})
		.always(function() {
			$('#plateste_buton').prop('disabled', false);
		});

}

function VerificareExpeditie(){
	$('#detalii_societate').html("");
	$('#platitor_pf').prop( "checked", false );
	
	var exps = $('#expeditie').val().trim().replace(/[\r\n\s\t\v\u0085\u2028\u2029,]+/g, ',');
	if(exps.length < 7) { AfiseazaEroare('Introduceti o expeditie!'); return false; }
	
	$.post(HTTP + 'facturi/verificare_expeditie/', {
			exps : exps
	}, function(response) {
		if(response == 0) { AfiseazaEroare('Factura eronata(stearsa) sau expeditii eronate(sterse)!'); return false; }
		else {
			var rasp = response.split('|||');
			if(rasp[0]==1) { AfiseazaEroare('Expeditia : '+rasp[1]+' inexistenta !'); return false; }
			if(rasp[0]==3) { AfiseazaEroare('Expeditia : '+rasp[1]+' are plata periodica !'); return false; }
			if(rasp[0]==2) { AfiseazaEroare('Expeditia : '+rasp[1]); return false; }
			if(rasp[0]==4) {
				$('#platitor_id').val(rasp[1]);
				$('#platitor_nume').val(rasp[2]);
				$('#platitor_afm').val(rasp[3]);
				$('#localitate_id').val(rasp[4]);
				$('#localitate_nume').html(rasp[5]);
				$('#sumamnt').val(rasp[7]);
				if(!isValidCui($('#platitor_afm').val()))
					$('#platitor_pf').prop( "checked", true );
				else
					$('#platitor_pf').prop( "checked", false );
			}
			jQuery("#factura_expeditii").jqGrid('setGridParam', {
				postData: { exps: exps }
			}).trigger("reloadGrid");
			return true;
		}
	});
}

function Grid_ExpeditiiFactura()
{
	var grid = jQuery('#factura_expeditii');

	grid.jqGrid(
	{
		url : HTTP + 'facturi/json/expeditii',
		datatype: 'json',
		mtype: 'POST',
		postData: { exps: 0 },
		colNames : [ 'Nr. Exp', 'Referire', 'Expeditor', 'Destinatar','Data','Plicuri','Colete','Paleti','Greutate','Km Prel','Km Livr','Val Exp','Val Exp + TVA'],
		colModel : [ {
			name : 'expeditie',
			sortable: false,
			width: 60
		},{
			name : 'referire',
			sortable: false,
			width: 60
		}, {
			name : 'expeditor',
			sortable: false,
			width: 130
		}, {
			name : 'destinatar',
			sortable: false,
			width: 130
		},{
			name : 'data_expeditie',
			sortable: false,
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			width: 60,
			align: 'center'
		}, {
			name : 'plicuri',
			sortable: false,
			formatter: 'number',
			align: 'right',
			width: 40
		}, {
			name : 'colete',
			sortable: false,
			formatter: 'number',
			align: 'right',
			width: 40
		}, {
			name : 'paleti',
			sortable: false,
			formatter: 'number',
			align: 'right',
			width: 40
		}, {
			name : 'greutate',
			sortable: false,
			formatter: 'number',
			align: 'right',
			width: 50
		}, {
			name : 'km_preluare',
			sortable: false,
			formatter: 'number',
			align: 'right',
			width: 40
		}, {
			name : 'km_livrare',
			sortable: false,
			formatter: 'number',
			align: 'right',
			width: 40
		}, {
			name : 'valoare_totala_expeditie',
			sortable: false,
			formatter: 'number',
			align: 'right',
			width: 75
		}, {
			name : 'valoare_totala_expeditie_cu_tva',
			sortable: false,
			formatter: 'number',
			align: 'right',
			width: 75
		}],
		rownumbers : true,
		scroll : 1,
		rowNum : 200,
		width : 970,
		height : 'auto',
		shrinkToFit: false,
		caption:'Expeditii',
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
		gridComplete: function(data) {
			$('#cb_factura_expeditii').click();
			$('#factura_expeditii .cbox').click();
			$('#cb_factura_expeditii').attr('checked','checked');
			$('#factura_expeditii .cbox').attr('checked','checked');
			$('#cb_factura_expeditii').attr('checked','checked');
			$('#factura_expeditii .cbox').attr('checked','checked');
		},
		footerrow: true,
	    userDataOnFooter: true
	});
	grid.jqGrid('bindKeys');
}

function ComboClientiFacturare(field) {
	$("#" + field + "_nume").autocomplete({
		source : function(request, response) {
			$.ajax({
				url : HTTP + "facturi/json/platitori",
				dataType : "json",
				data : { name_startsWith : request.term },
				success : function(data) {
					response($.map(data.rezultat, function(item) {
						return {
							cod_cl : item.cod_cl,
							localitate_id : item.localitate_id,
							cod_fiscal : item.cod_fiscal,
							value : item.value,
							label : item.label,
						}
					}));
				}

			});
		},
		minLength : 3,
		select: function(event,ui){
			$('#'+field+'_id').val(ui.item.cod_cl);
			$('#localitate_id').val(ui.item.localitate_id);
			$('#'+field+"_nume").val(ui.item.value);
			$('#'+field+"_afm").val(ui.item.cod_fiscal);
		},
		open: function() {
			$('#'+field+"_id").val("");
			$('#'+field+"_afm").val("");
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
		},
		close: function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}

function IncasareFacturaCtr(){
	var rec = $.trim($('#receipt').val());
	var inv = $.trim($('#invoice').val());
	if(!(rec.length)) { AfiseazaEroare('Introduceti chitanta'); return false; }
	if(!(inv.length)) { AfiseazaEroare('Introduceti factura'); return false; }
	if(!(inv.substring(0, 3) == "DSC")) { AfiseazaEroare('Factura nu incepe cu DSC'); return false; }

	$.post(HTTP + 'facturi/ctr_plateste/', {
			invoice : $.trim($('#invoice').val()),
			receipt : $.trim($('#receipt').val())
	}, function(response) {
		if(response==0) AfiseazaEroare('Lipsa valori!');
		else{
			var rasp = response.split('|||');
			if(rasp[0]==2) { AfiseazaEroare(rasp[1]); return false; }
			else {
				$('#receipt').val('');
				$('#invoice').val('');
				$('#receipt').focus();
			}
		}
	});
}

function PlatesteFactura(){
    var d1 = new Date();
    var trndate = $('#trndate').val().split('.');
    var d2 = new Date(trndate[2] + '-'+trndate[1] + '-'+trndate[0]);
	var d3 = new Date().setDate(d1.getDate()-ALERTA_NR_ZILE);

    if(d2 < d3 && ALERTA_NR_ZILE > 0) {
        $("#eroare").attr('title', 'Atentie MINNIE !!!');
        $("#eroare").html('Data introdusa este mai veche de <strong>' + ALERTA_NR_ZILE + '</strong> zile, sigur ai introdus data corect ?');
        $("#eroare").dialog({
            modal: true,
            draggable: false,
            height: 200,
            width: 300,
            resizable: false,
            buttons: {
                DA: function () {
					$(this).dialog("destroy");
                    PlatesteFacturaAcc();
                },
                NU: function () {
					$(this).dialog("destroy");
					$('#trndate').focus();
                }
            }
        });
    } else {
        PlatesteFacturaAcc();
	}
}

function PlatesteFacturaAcc(){

    var d1 = new Date();
    var trndate = $('#trndate').val().split('.');
	var d2 = new Date(trndate[2] + '-'+trndate[1] + '-'+trndate[0]);
	var days = Math.floor((d1-d2)/(86400000));

	if(days < 0){
		AfiseazaEroare('Data introdusa este mai mare decat data curenta');
		return false;
	}
	if(days > 300){
		AfiseazaEroare('Data introdusa este mai veche de 300 zile');
		return false;
	}
	var persoana_fizica = $('#platitor_pf:checkbox:checked').length > 0;
	if(isValidCui($( "#platitor_afm" ).val()) && persoana_fizica == true){
		AfiseazaEroare('Ai bifat Pers. fizica si CUI este valid');
		return false;
	}

	if(!isValidCui($( "#platitor_afm" ).val()) && persoana_fizica == false){
		AfiseazaEroare('Nu ai bifat Pers. fizica si CUI invalid');
		return false;
	}

	if(!($.trim($('#invoice').val()).length)) { AfiseazaEroare('Introduceti factura si chitanta'); return false; }
	if(!$.trim($('#platitor_nume').val()).length) { AfiseazaEroare('Introduceti platitorul'); return false; }
	if(!(Number($('#sumamnt').val()) > 0)) { AfiseazaEroare('Introduceti suma'); return false; }
	var platitor_pf = 0;
	if($('#platitor_pf').is(":checked")) platitor_pf = 1;



	var sel_ids = jQuery('#factura_expeditii').jqGrid('getGridParam', 'selarrrow');
	if(sel_ids.length==0){
		AfiseazaEroare('Selectati minim o expeditie!');
		return false;
	}
	var exps = sel_ids.toString();
	$.post(HTTP + 'facturi/plateste/', {
			exps : exps,
			trndate : $('#trndate').val(),
			invoice : $('#invoice').val(),
			receipt : $('#receipt').val(),
			sumamnt : Number($('#sumamnt').val()),
			platitor_id : parseInt($('#platitor_id').val()),
			localitate_id : parseInt($('#localitate_id').val()),
			platitor_nume : $('#platitor_nume').val(),
			platitor_afm : $('#platitor_afm').val(),
			platitor_pf : platitor_pf
	}, function(response) {
		if(response==0) AfiseazaEroare('Lipsa valori!');
		else{
			var rasp = response.split('|||');
			if(rasp[0]==2) { AfiseazaEroare(rasp[1]); return false; }
			else {
				$('#expeditie').val('');
				jQuery("#factura_expeditii").jqGrid('setGridParam', {
					postData: { exps: 0 }
				}).trigger("reloadGrid");
				$('#invoice').val('');
				$('#sumamnt').val('');
				$('#receipt').val('');
				$('#platitor_id').val('');
				$('#localitate_id').val('');
				$('#platitor_nume').val('');
				$('#platitor_afm').val('');
				$('#detalii_societate').html('');
				$('#platitor_pf').prop( "checked", false );
				$('#platitor_afm').removeClass('cod-invalid');
				$('#platitor_afm').removeClass('cod-valid');
				$('#invoice').focus();
			}
		}
	});
}

function StergeFactura(id){
	var fid=parseInt(id);
	if(isNaN(fid) || fid == 0) { AfiseazaEroare('Eroare reset!'); return false; }

	$("#detalii_factura").dialog("destroy");
	$("#detalii_factura").html('Confirmare resetare');
	$("#detalii_factura").dialog({
		width : 200,
		height : 120,
		draggable : false,
		resizable : false,
		modal: true,
		title : 'Confirmare',
		buttons: {
			"Da": function() {
				$.post(HTTP + 'facturi/sterge/', {
					id : fid,
				}, function(response) {
					if(response==0) AfiseazaEroare('Eroare reset!');
					else{
						jQuery("#g_facturi").jqGrid().trigger("reloadGrid");
						$("#detalii_factura").dialog("destroy");
						return true;
					}
				});
			},
			"Nu": function() {
				$("#detalii_factura").dialog("destroy");
			}
		},
		open: function(event, ui) {
          $(":button:contains('Nu')").focus(); // Set focus to the [Ok] button
     	},
		close : function() {
			$("#detalii_factura").dialog("destroy");
		}
	});
}

function ModificaFactura(){
	$.post(HTTP + 'facturi/modifica/', {
			invoice : $('#invoice').val(),
	}, function(response) {
		if(response==0) AfiseazaEroare('Selectati factura!');
		else{
			;
		}
	});
}

function AfisareFacturi(){
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var filtru_sel = $('#filtru_sel').val();
	var filtru_val = $('#filtru_val').val();
	var selectie_data = $('#selectie_data').val();
	jQuery("#g_facturi").jqGrid('setGridParam', {
		 postData: { selectie_data:selectie_data, data_start:data_start, data_final:data_final, filtru_sel:filtru_sel, filtru_val:filtru_val}
	}).trigger("reloadGrid");
}

function Grid_Facturi()
{
	var grid = jQuery('#g_facturi');

	grid.jqGrid(
	{
		url : HTTP + 'facturi/json/facturi',
		datatype: 'json',
		mtype: 'POST',
		colNames : [ 'Data facturare', 'Factura', 'Chitanta', 'Suma', 'Tva', 'Incasat', 'Platitor', 'Centru', 'Expeditii', 'WME', 'WME Mess', 'Resetare' ],
		colModel : [
		{
			name : 'trndate',
			search:false,
			index : 'a.trndate',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			width: 80,
			align: 'center'
		}, {
			name : 'factura',
			index : 'a.invoice',
			width: 100
		}, {
			name : 'chitanta',
			index : 'a.receipt',
			width: 100
		},{
			name : 'suma',
			index : 'a.sumamnt',
			search:false,
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 50
		},{
			name : 'procTva',
			index : 'a.procTva',
			formatter: 'integer',
			sorttype: 'integer',
			align: 'right',
			width: 20
		},{
			name : 'mod_generare',
			index : 'a.mod_generare',
			stype : 'select',
			formatter: 'select',
			searchoptions : {
				value : ":Toate;0:CASH;3:CARD",
				sopt : [ 'eq' ]
			},
			editoptions : {
				value : "0:CASH;3:CARD"
			},
			align: 'center',
			width: 30
		},{
			name : 'platitor',
            index : 'c.NUME',
			sortable : false,
			width: 100
		},{
			name : 'centru',
			index : 'cn.nume',
			width: 80
		},{
			name : 'expeditii',
			search:false,
			sortable : false,
			width: 320
		},{
			name : 'a.wme',
			index : 'a.wme',
			width: 80,
			align: 'left',
			stype : 'select',
			formatter: 'select',
			searchoptions : {
				value : ":Toate;0:Factura netrimise;1:Factura trimisa;2:Factura si chitanta trimisa;3:Eroare",
				sopt : [ 'eq' ]
			},
			editoptions : {
				value : "0:Factura netrimisa;1:Factura trimisa;2:Factura si chitanta trimisa;3:Eroare"
			}
		}, {
			name : 'a.wme_message',
			index : 'a.wme_message',
			width: 300,
			align: 'left'
		},{
			name : 'resetare',
			search: false,
			sortable : false,
			align: 'center',
			width: 50
		}],
		rowNum : 25,
		rowList : [ 25, 50, 100 ],
		rownumbers : true,
		width : 970,
		height : 'auto',
		sortname : 'a.trndate',
		sortorder : "desc",
		viewrecords : true,
		pager : '#paginatie',
		shrinkToFit: false,
		caption:'',
		forceFit: true,
        multiselect: true,
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
}

function AfisareRestante(){
	var cond = '';
	var centru = $('#centru').val();
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	cond += '&data_start=' + encodeURIComponent(data_start) + '&data_final='+ encodeURIComponent(data_final);
	cond += '&centru='+ centru;

	jQuery("#restante_livrari").jqGrid('setGridParam', {
		 url : HTTP + 'facturi/json/restante_livrari?a' + cond
	}).trigger("reloadGrid");
	jQuery("#restante_colectari").jqGrid('setGridParam', {
		 url : HTTP + 'facturi/json/restante_colectari?a' + cond
	}).trigger("reloadGrid");
	jQuery("#restante_facturi").jqGrid('setGridParam', {
		url : HTTP + 'facturi/json/restante_facturi?a' + cond
   	}).trigger("reloadGrid");
}

function Grid_RestanteLivrari() {
	jQuery("#restante_livrari").jqGrid(
			{
				url : HTTP + 'facturi/json/restante_livrari',
				datatype : "json",
				colNames : ['Colectat','Expeditie','Destinatar','Loca. Dest.','Expeditor','Loca. Exp.','Valoare','Tip Exp.','Nr scanari', 'Decontata'],
				colModel : [ {
					name : 'data_expeditie',
					index : 'ep.data_expeditie',
					width: 80,
					sorttype:'date',
					formatter:'date',
					datefmt:'d/m/Y',
					align: 'center'
				}, {
					name : 'expeditie',
					index : 'ep.expeditie',
					align: 'center',
					width: 80
				}, {
					name : 'destinatar',
					index : 'cld.nume',
					width: 200
				}, {
					name : 'destinatar_localitate',
					index : 'lcd.nume_lc',
					width: 110
				}, {
					name : 'expeditor',
					index : 'cle.nume',
					width: 200
				}, {
					name : 'expeditor_localitate',
					index : 'lce.nume_lc',
					width: 110
				}, {
					name : 'valoare_totala_expeditie',
					index : 'ep.valoare_totala_expeditie',
					align : 'right',
					search:false,
					width : 70
				}, {
					name : 'tip_exp',
					index : 'ep.tip_exp',
					stype : 'select',
					formatter: 'select',
					editoptions : {
						value : ":;0:Initiala;5:Returnare"
					},
					searchoptions : {
						value : ":Toate;0:Initiala;5:Returnare",
						sopt : [ 'eq' ]
					},
					align: 'center',
					edittype : "select",
					width: 65
				}, {
					name : 'nr_scanari',
					sortable:false,
					search:false,
					align : 'right',
					width : 70
				},{
					name : 'decontata',
					index : 'decontata',
					stype : 'select',
					formatter: 'select',
					searchoptions : {
						value : ":Toate;0:Nu;1:Da",
						sopt : [ 'eq' ]
					},
					editoptions : {
						value : "0:Nu;1:Da"
					},
					align: 'center',
					width: 70
				} ],
				rowNum : 1000,
				width : 970,
				height : 250,
				mtype : "GET",
				rownumbers : false,
				gridview : true,
				sortname : 'ep.data_expeditie',
				viewrecords : true,
				sortorder : "ASC",
				pager : false,
				caption : false,
				multiselect : false,
				subGrid : false,
				ondblClickRow : function(id) {
					AfisareDetaliiExpeditieCuID(id);
				},
				footerrow: true,
			    userDataOnFooter: true
			});
	jQuery("#restante_livrari").jqGrid('bindKeys');
	jQuery("#restante_livrari").jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
}

function Grid_RestanteFacturi() {
	jQuery("#restante_facturi").jqGrid(
			{
				url : HTTP + 'facturi/json/restante_facturi',
				datatype : "json",
				colNames : ['Societate','Data','Nr. factura', 'Chitanta', 'Suma','Tva','Nr. Exp.','Generare'],
				colModel : [ {
					name : 'client',
					index : 'c.NUME_SOCIETATE',
					width: 150
				}, {
					name : 'trndate',
					index : 'f.trndate',
					sorttype : 'date',
					formatter : 'date',
					formatoptions : {
						srcformat : 'Y-m-d',
						newformat : 'd/m/Y'
					},
					width: 60,
					align: 'center'
				}, {
					name : 'factura',
					index : 'f.invoice',
					width: 60,
					align: 'left'
				},{
					name : 'chitanta',
					index : 'f.receipt',
					width: 60,
					align: 'left'
				}, {
					name : 'sumamnt',
					index : 'f.sumamnt',
					search:false,
					formatter: 'integer',
					sorttype: 'integer',
					width: 60,
					align: 'right'
				}, {
					name : 'procTva',
					index : 'f.procTva',
					search:false,
					formatter: 'integer',
					sorttype: 'integer',
					width: 40,
					align: 'right'
				}, {
					name : 'nr_exp',
					index : 'f.nr_exp',
					formatter: 'integer',
					sorttype: 'integer',
					width: 50,
					align: 'right'
				}, {
					name : 'mod_generare',
					index : 'f.mod_generare',
					stype : 'select',
					formatter: 'select',
					searchoptions : {
						value : ":Toate;1:Automat;2:Manual",
						sopt : [ 'eq' ]
					},
					editoptions : {
						value : "1:Automat;2:Manual"
					},
					width: 50,
					align: 'center'
				}],
				rowNum : 1000,
				width : 970,
				height : 250,
				mtype : "GET",
				rownumbers : false,
				gridview : true,
				sortname : 'f.trndate',
				viewrecords : true,
				sortorder : "ASC",
				pager : false,
				caption : false,
				multiselect : false,
				subGrid : false,
				footerrow: true,
			    userDataOnFooter: true
			});
	jQuery("#restante_facturi").jqGrid('bindKeys');
	jQuery("#restante_facturi").jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
}

function Grid_RestanteColectari() {
	jQuery("#restante_colectari").jqGrid(
			{
				url : HTTP + 'facturi/json/restante_colectari',
				datatype : "json",
				colNames : ['Colectat','Expeditie','Expeditor','Loca. Exp.','Destinatar','Loca. Dest.','Valoare','Tip Exp.','Nr scanari', 'Decontata'],
				colModel : [ {
					name : 'data_expeditie',
					index : 'ep.data_expeditie',
					width: 80,
					sorttype:'date',
					formatter:'date',
					datefmt:'d/m/Y',
					align: 'center'
				}, {
					name : 'expeditie',
					index : 'ep.expeditie',
					align: 'center',
					width: 80
				}, {
					name : 'expeditor',
					index : 'cle.nume',
					width: 200
				}, {
					name : 'expeditor_localitate',
					index : 'lce.nume_lc',
					width: 110
				}, {
					name : 'destinatar',
					index : 'cld.nume',
					width: 200
				}, {
					name : 'destinatar_localitate',
					index : 'lcd.nume_lc',
					width: 110
				}, {
					name : 'valoare_totala_expeditie',
					index : 'ep.valoare_totala_expeditie',
					search:false,
					align : 'right',
					width : 70
				},{
					name : 'tip_exp',
					index : 'ep.tip_exp',
					stype : 'select',
					formatter: 'select',
					editoptions : {
						value : ":;0:Initiala;5:Returnare"
					},
					searchoptions : {
						value : ":Toate;0:Initiala;5:Returnare",
						sopt : [ 'eq' ]
					},
					align: 'center',
					edittype : "select",
					width: 65
				}, {
					name : 'nr_scanari',
					sortable:false,
					search:false,
					align : 'right',
					width : 70
				}, {
					name : 'decontata',
					index : 'decontata',
					stype : 'select',
					formatter: 'select',
					searchoptions : {
						value : ":Toate;0:Nu;1:Da",
						sopt : [ 'eq' ]
					},
					editoptions : {
						value : "0:Nu;1:Da"
					},
					align: 'center',
					width: 70
				}],
				rowNum : 10000,
				width : 970,
				height : 250,
				mtype : "GET",
				rownumbers : false,
				gridview : true,
				sortname : 'ep.data_expeditie',
				viewrecords : true,
				sortorder : "ASC",
				pager : false,
				caption : false,
				multiselect : false,
				subGrid : false,
				ondblClickRow : function(id) {
					AfisareDetaliiExpeditieCuID(id);
				},
				footerrow: true,
			    userDataOnFooter: true
			});
	jQuery("#restante_colectari").jqGrid('bindKeys');
	jQuery("#restante_colectari").jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
}

function ExportRestanteFacturi()
{
	var centru = $('#centru').val();
	var cond = '&centru=' + centru;
	var filters =  $('#restante_facturi').jqGrid('getGridParam','postData').filters;
	if(filters) cond+='&_search=true&filters='+filters;
	sidx = $('#restante_facturi').jqGrid('getGridParam','sortname');
	if(sidx) cond+='&sidx='+sidx;
	sord = $('#restante_facturi').jqGrid('getGridParam','sortorder');
	if(sord) cond+='&sord='+sord;

	$.download('facturi/export_restante_facturi/', cond, false);
}

function PrintListeFacturi(url){
	var data = $('#lista_facturare').serialize();


	var doar_nefacturate =  $('#doar_nefacturate:checked').length > 0;
	var mod_plata_normala =  $('#mod_plata_normala:checked').length > 0;
	var mod_plata_periodica =  $('#mod_plata_periodica:checked').length > 0;

	$.post(HTTP + 'facturi/'+url, {data:data, doar_nefacturate:doar_nefacturate,mod_plata_normala:mod_plata_normala,mod_plata_periodica:mod_plata_periodica}, function(response) {
		$("#detalii_expeditie").html(response).show();
		$('#detalii_expeditie').printElement(
		{
            leaveOpen:false,
            printMode:'iframe',
            title : 'Centralizator Expeditii',
            overrideElementCSS:[
				HTTP+'assets/css/style_screen.css'
				 ,{href:HTTP+'assets/css/style_print.css',media:'print'}
				]

        });
        $("#detalii_expeditie").hide();
	});
}

function AfisareClientiListeFacturi(){
	var data_start = encodeURIComponent($('#data_start').val());
	var data_final = encodeURIComponent($('#data_final').val());
	var cond = 'data_start=' + data_start + '&data_final='+ data_final;
    var doar_nefacturate =  $('#doar_nefacturate:checked').length > 0;
    cond += '&doar_nefacturate='+doar_nefacturate;

    var mod_plata_normala =  $('#mod_plata_normala:checked').length > 0;
    cond += '&mod_plata_normala='+mod_plata_normala;
    var mod_plata_periodica =  $('#mod_plata_periodica:checked').length > 0;
    cond += '&mod_plata_periodica='+mod_plata_periodica;

    jQuery("#liste_clienti").jqGrid('setGridParam', {
		 url : HTTP + 'facturi/json/liste_clienti?'+'&'+cond
	}).trigger("reloadGrid");
	cond = '1=2';
	jQuery("#liste_facturi").jqGrid('setGridParam', {
		 url : HTTP + 'facturi/json/liste_facturi?'+'&'+cond
	}).trigger("reloadGrid");
}


function AfisareListeFacturi(sel){//alert(sel);
	var data_start = encodeURIComponent($('#data_start').val());
	var data_final = encodeURIComponent($('#data_final').val());
	cond = 'data_start=' + data_start + '&data_final='+ data_final;
    var doar_nefacturate =  $('#doar_nefacturate:checked').length > 0;
    cond += '&doar_nefacturate='+doar_nefacturate;
    var mod_plata_normala =  $('#mod_plata_normala:checked').length > 0;
    cond += '&mod_plata_normala='+mod_plata_normala;
    var mod_plata_periodica =  $('#mod_plata_periodica:checked').length > 0;
    cond += '&mod_plata_periodica='+mod_plata_periodica;
	$('#client').val(sel);

	jQuery("#liste_facturi").jqGrid('setGridParam', {
		 url : HTTP + 'facturi/json/liste_facturi?client=' + sel+'&'+cond
	}).trigger("reloadGrid");
}

function Grid_ListeClienti()
{
	SetareDate();
	var grid = jQuery('#liste_clienti');

	grid.jqGrid(
	{
		url : HTTP + 'facturi/json/liste_clienti',
		datatype: 'json',
		mtype: 'GET',
		colNames : [ 'Client', 'Exp', 'Fct' ],
		colModel : [ {
			name : 'platitor',
			index : 'clp.nume',
			width: 145
		}, {
			name : 'nr_exp',
			index : 'nr_exp',
			sorttype : 'int',
			width: 45,
			align: 'right'
		}, {
			name : 'tip_facturare',
			index : 'clp.TIP_FACTURARE',
			stype : 'select',
			formatter: 'select',
			editoptions : {
				value : "0:FF;1:L;2:B;3:S;4:M;5:D"
			},
			searchoptions : {
				value : ":All;0:FF;1:L;2:B;3:S;4:M;5:D",
				sopt : [ 'eq' ]
			},
			width: 45,
			align: 'center'
		} ],
		rowNum : 10000,
		rownumbers : true,
		width : 300,
		height : 350,
		sortname : 'clp.nume',
		sortorder : "asc",
		viewrecords : true,
		pager : '#paginatie_clienti',
		shrinkToFit: false,
		caption:'Lista Clienti',
		forceFit: true,
		multiselect: true,
		beforeSelectRow: function (rowid, e) {
                    var $myGrid = $(this),
                        i = $.jgrid.getCellIndex($(e.target).closest('td')[0]),
                        cm = $myGrid.jqGrid('getGridParam', 'colModel');
                    return (cm[i].name === 'cb');
        },
		ondblClickRow : function(id) {
			AfisareListeFacturi(id)
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

function Grid_ListeFacturi()
{
	SetareDate();
	var grid = jQuery('#liste_facturi');
	var val_greutate = '';
	var valoare_totala_expeditie = '';
	var tva = '';

	grid.jqGrid(
	{
		url : HTTP + 'facturi/json/liste_facturi',
		datatype: 'json',
		mtype: 'GET',
		colNames : [ 'Data', 'Mod plata', 'Expeditor', 'Localitate','Destinatar', 'Nr. NT', 'Greutate', 'Colete','Val Exp', 'Val Km', 'Val G', 'Val Asig', 'Total','TVA' ],
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
			name : 'mod_plata',
			index : 'ep.mod_plata',
			stype : 'select',
			formatter: 'select',
			editoptions : {
				value : "0:Per NT;1:Factura periodica"
			},
			searchoptions : {
				value : ":Toate;0:Per NT;1:Factura periodica",
				sopt : [ 'eq' ]
			},
			align: 'center',
			edittype : "select",
			width: 150
		}, {
			name : 'expeditor',
			index : 'cle.nume',
			width: 150,
			summaryType:'count',
			summaryTpl:'<b>Total: </b>'
		}, {
			name : 'destinatar_localitate',
			index : 'lcd.nume_lc',
			width: 100
		}, {
			name : 'destinatar',
			index : 'cld.nume',
			width: 130,
			summaryType:'count',
			summaryTpl:'<b>{0} Expeditii</b>'
		}, {
			name : 'expeditie',
			index : 'ep.expeditie',
			sorttype : 'int',
			width: 60,
			align: 'right'
		}, {
			name : 'greutate',
			index : 'ep.greutate',
			sorttype : 'int',
			width: 70,
			align: 'right',
			editable : true,
			editoptions: {
				dataInit: function (domElem) {
				setTimeout(function() {
					$(domElem).focus();
					$(domElem).setCursorPosition(0);
					}, 100);
				}
			}
		},{
			name : 'piese',
			index : 'ep.colete',
			sorttype : 'int',
			width: 70,
			align: 'right'
		},{
			name : 'valoare_expeditie',
			index : 'ep.valoare_expeditie',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 70,
			formatter:"number",
			summaryType:'sum'
		}, {
			name : 'val_km',
			index : 'ep.val_km',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 70,
			formatter:"number",
			summaryType:'sum'
		}, {
			name : 'val_greutate',
			index : 'ep.val_greutate',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 70,
			formatter:"number",
			summaryType:'sum'
		}, {
			name : 'val_asig',
			index : 'ep.val_asig',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 70,
			formatter:"number",
			summaryType:'sum'
		}, {
			name : 'valoare_totala_expeditie',
			index : 'ep.valoare_totala_expeditie',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 70,
			formatter:"number",
			summaryType:'sum'
		}, {
			name : 'tva',
			index : 'ep.tva',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 70,
			formatter:"number",
			summaryType:'sum'
		} ],
		rowNum : 5000,
		rownumbers : true,
		width : 650,
		height : 350,
		sortname : 'ep.data_expeditie',
		sortorder : "asc",
		viewrecords : true,
		pager : '#paginatie_facturi',
		shrinkToFit: false,
		caption:'Lista Expeditii',
		forceFit: true,
		cellEdit: true,
		cellsubmit: 'remote',
		cellurl: HTTP + 'facturi/update_greutate',
		afterSubmitCell : function(serverresponse, rowid, cellname, value, iRow, iCol) {
			var response = jQuery.parseJSON(serverresponse.responseText);
			if (response.success == 1){
				//set row data
				val_greutate = response.m_data.val_greutate;
				valoare_totala_expeditie = response.m_data.valoare_totala_expeditie;
				valoare_totala_expeditie_cu_tva = response.m_data.valoare_totala_expeditie_cu_tva;
				tva = response.m_data.tva;
				//refresh total
				return [true,""];
			}
			else{
				return [false,response.error];
			}
		},
		afterSaveCell : function(rowid, cellname, value, iRow, iCol) {
			if (val_greutate != '')
				grid.jqGrid('setCell', rowid, 'val_greutate', val_greutate, {color:'red', weightfont:'bold'});
			if (valoare_totala_expeditie != '')
				grid.jqGrid('setCell', rowid, 'valoare_totala_expeditie', valoare_totala_expeditie, {color:'red', weightfont:'bold'});
			if (tva != '')
				grid.jqGrid('setCell', rowid, 'tva', tva, {color:'red', weightfont:'bold'});
			val_greutate = '';
			valoare_totala_expeditie = '';
			tva = '';
		},
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

function ExportFacturi(){
	var data_start = encodeURIComponent($('#data_start').val());
	var data_final = encodeURIComponent($('#data_final').val());

	var clienti = jQuery('#liste_clienti');
	var sel_ids = clienti.jqGrid('getGridParam', 'selarrrow');

	var doar_nefacturate =  $('#doar_nefacturate:checked').length > 0;
	var mod_plata_normala =  $('#mod_plata_normala:checked').length > 0;
	var mod_plata_periodica =  $('#mod_plata_periodica:checked').length > 0;

	if(sel_ids.length==0){
		AfiseazaEroare('Selectati minim un client!');
		return false;
	}

	var platitori = sel_ids.join(',');

	var cond = '&data_start=' + data_start + '&data_final='+ data_final + '&platitori='+ platitori +
	'&doar_nefacturate='+ doar_nefacturate + '&mod_plata_normala='+ mod_plata_normala + '&mod_plata_periodica='+ mod_plata_periodica;

	$.download('facturi/export/facturi', cond, false);
}

function ExportCentralizatorFacturi(){
	var data_start = encodeURIComponent($('#data_start').val());
	var data_final = encodeURIComponent($('#data_final').val());

	var clienti = jQuery('#liste_clienti');
	var sel_ids = clienti.jqGrid('getGridParam', 'selarrrow');

	if(sel_ids.length==0){
		AfiseazaEroare('Selectati minim un client!');
		return false;
	}
	var url = HTTP + 'facturi/export_centralizator_facturi';
	var platitori = sel_ids.join(',');
	var cond = '&data_start=' + data_start + '&data_final='+ data_final + '&platitori='+ platitori;

	$.download(url, cond, false);
}

/* //////////////////////////////////////////////////////
				END FACTURI RESTANTE
//////////////////////////////////////////////////////  */

function ActualizareTarifeExpeditii(){
	var nrr_expeditii=0;
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var clienti = jQuery('#liste_clienti');
	var sel_ids = clienti.jqGrid('getGridParam', 'selarrrow');
	if(sel_ids.length==0) { AfiseazaEroare("Selectioneaza minimum un client pentru actualizare !"); return false; }

	var url = HTTP + 'facturi/actualizare_expeditii';
	for(var i=0, l=sel_ids.length ; i<l; i++)
	{
		$.ajax({
              url: url,
              data: { data_start: data_start, data_final: data_final, client: sel_ids[i] },
              type: "POST",
              async: false,
              success: function(response) {
                if(response == 'error'){ AfiseazaEroare('Error !!!'); }
				else { nrr_expeditii+=parseInt(response); }
              }
        });
	}
	AfiseazaEroare('Gata : '+ nrr_expeditii.toString() +' expeditii');
}

function getProgress() {
    $('.progress_area_mess').css("background",'#FFF');
    $('.progress_area_mess').html('').hide();
    $('.progress_area_buton').addClass('in-progress');

    if(ajax_progress){
        return;
    }
    ajax_progress = true;
    $.ajax({
        type: "POST",
        url : HTTP + 'facturare/acc',
        dataType : "json",
        data : { get_progress: 1 },
        cache: false,
        success : function(data) {

            if(data.running == 1){
                $('.progress_area_mess').css("background",'#c9ffa8');
            }
            if(data.progress.length>0){
                $('.progress_area_mess').text('');
                $('.progress_area_mess').show();
                $('#job_uri_in_asteptare').text(data.progress.length);
                jQuery.each(data.progress, function() {
                    $('.progress_area_mess').append('<li>' + this.name + '</li>');
                });
            } else {
                $('#job_uri_in_asteptare').text(0);
                $('.progress_area_mess').hide();
            }
        }
    }).always(function() {
        $('.progress_area_buton').removeClass('in-progress');
        ajax_progress = false;
    });;
}

/* //////////////////////////////////////////////////////
					START CAUTARE FACTURI DECONT
//////////////////////////////////////////////////////  */
function CautareFacturiDecont(sel) {
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
		if ($('#curier').is(':checked')) {
			$('#curier_livrare_nume').removeAttr('disabled');
		} else {
			$('#curier_livrare').val('');
			$('#curier_livrare_nume').val('');
			$('#curier_livrare_nume').attr('disabled', true);
		}
	} else if (sel == 3) {
		if ($('#client').is(':checked')) {
			$('#expeditor_nume').removeAttr('disabled');
		} else {
			$('#expeditor').val('');
			$('#expeditor_nume').val('');
			$('#expeditor_nume').attr('disabled', true);
		}
	}
}

function AfisareFacturiDecont()
{
	var cond = '';
	if($.trim($('#search').val()).length) {
		cond += '&operatiune=' + $('#operatiune').val() + '&search='+ $.trim($('#search').val());
	}

	if ($('#perioada').is(':checked') && $.trim($('#data_start').val()).length && $.trim($('#data_final').val()).length) {
		var data_start = $('#data_start').val();
		var data_final = $('#data_final').val();
		cond += '&data_start=' + encodeURIComponent(data_start) + '&data_final='+ encodeURIComponent(data_final);
	}
	if ($('#curier').is(':checked') && $.trim($('#curier_livrare').val()).length) {
		var curier_id = parseInt($('#curier_livrare').val());
		if(!isNaN(curier_id) && curier_id > 0)
			cond += '&cod_ag=' + curier_id;
	}
    if ($('#client').is(':checked') && $.trim($('#expeditor').val()).length) {
		var expeditor_id = parseInt($('#expeditor').val());
		if(!isNaN(expeditor_id) && expeditor_id > 0)
			cond += '&cod_cl=' + expeditor_id;
	}

	var exps = $('#expeditii_area').val().split(new RegExp(/[\r\n\x0B\x0C\u0085\u2028\u2029,]+/g)).filter(function (el) {
		return el != null && el != "";
	});

	$("#text_lines").text(exps.length);
	postExpeditii = $('#expeditii_area').val().replace(/[\r\n\s\t\v\u0085\u2028\u2029,]+/g, ',');

	if($.trim(postExpeditii).length)
		jQuery("#jqGrid_decont_cautare_facturi").jqGrid('setGridParam', { url: HTTP + 'facturi/decont/json_cautare', postData : { expeditii: postExpeditii }}).trigger("reloadGrid");
	else if($.trim(cond).length)
		jQuery("#jqGrid_decont_cautare_facturi").jqGrid('setGridParam',{ url: HTTP + 'facturi/decont/json_cautare?a' + cond, postData : { expeditii: "" }}).trigger("reloadGrid");
}

function Grid_FacturiCautareDecont()
{
	var grid = jQuery('#jqGrid_decont_cautare_facturi');

	grid.jqGrid(
	{
		url : HTTP + 'facturi/decont/json_cautare',
		datatype: 'json',
		mtype: 'POST',
		colNames : [ 'Factura', 'Valoare', 'Tva', 'Data', 'Expeditii', 'Expeditor', 'Destinatar','C. Exp.','C. Dest.', 'Agent', 'Primitor', 'CUI', 'Incasat', 'Decontata'],
		colModel : [ {
			name : 'serie',
			index : 'df.serie',
			width: 70
		},{
			name : 'suma',
			index : 'df.suma',
			align: 'right',
			width: 50
		},{
			name : 'procTva',
			index : 'df.proc_tva',
			formatter: 'integer',
			sorttype: 'integer',
			align: 'right',
			width: 20
		},{
			name : 'data',
			index : 'df.data',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			width: 60,
			align: 'center'
		},{
			name : 'expeditii',
			search : false,
			sortable : false,
			width: 100
		}, {
			name : 'expeditor',
			index : 'cee.nume',
			width: 100
		}, {
			name : 'destinatar',
			index : 'ced.nume',
			width: 100
		}, {
			name : 'expeditor_centru',
			index : 'cee.label',
			width: 30,
			align : 'center'
		}, {
			name : 'destinatar_centru',
			index : 'ced.label',
			width: 30,
			align : 'center'
		}, {
			name : 'agent',
			index : 'ag.nume_ag',
			width: 80
		}, {
			name : 'primitor',
			index : 'e.primitor',
			width: 70
		}, {
			name : 'cui',
			index : 'df.cui',
			width: 70
		}, {
			name : 'tipPlata',
			index : 'tipPlata',
			stype : 'select',
			formatter: 'select',
			searchoptions : {
				value : ":Toate;0:CASH;1:CARD",
				sopt : [ 'eq' ]
			},
			editoptions : {
				value : "0:CASH;1:CARD"
			},
			align: 'center',
			width: 40
		}, {
			name : 'decontata',
			index : 'decontata',
			stype : 'select',
			formatter: 'select',
			searchoptions : {
				value : ":Toate;0:Nu;1:Da",
				sopt : [ 'eq' ]
			},
			editoptions : {
				value : "0:Nu;1:Da"
			},
			align: 'center',
			width: 35
		}],
		rownumbers : true,
		scroll : 1,
		rowNum : 100,
		width : 970,
		height : 295,
		sortname : "df.data",
		sortorder : "desc",
		viewrecords : true,
		gridview : true,
		pager : '#jqGrid_paginatie',
		shrinkToFit: false,
		caption: 'Lista facturi android',
		forceFit: true,
		multiselect: false,
		footerrow: true,
	    userDataOnFooter: true,
		ondblClickRow : function(id) {
			AfisareDetaliiFacturaDecont(id);
		},
		loadError : function(xhr,st,err) {
			AfiseazaEroare(xhr.responseText);
		}
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});

}

function ExportFacturiDecont(){
	let cond = '';
	let data_start = encodeURIComponent($('#data_start').val());
	let data_final = encodeURIComponent($('#data_final').val());
	let agent = $('#curier_livrare').val();
	let expeditor = $('#expeditor').val();
	cond += '&data_start=' + data_start + '&data_final='+ data_final + '&agent=' + agent + '&expeditor=' + expeditor;
	var filters = $('#jqGrid_decont_cautare_facturi').jqGrid('getGridParam','postData').filters;
	if(filters) cond+='&_search=true&filters='+filters;
	sidx = $('#jqGrid_decont_cautare_facturi').jqGrid('getGridParam','sortname');
	if(sidx) cond+='&sidx='+sidx;
	sord = $('#jqGrid_decont_cautare_facturi').jqGrid('getGridParam','sortorder');
	if(sord) cond+='&sord='+sord;

	postExpeditii = $('#expeditii_area').val().replace(/[\r\n\s\t\v\u0085\u2028\u2029,]+/g, ',');
	if($.trim(postExpeditii).length) {
		cond = '&expeditii=' + encodeURIComponent(postExpeditii);
	}
	if($.trim(cond).length)
		$.download('facturi/decont/export', cond, false);
	return true;
}

function AfisareDetaliiFacturaDecont(id){
	$("#modal-error-message").text("Factura invalida");
	$("#modal-error-message").hide();
	$('#dialog-form #agent_id').val("");
	$('#dialog-form #agent').text("");
	$('#dialog-form #factura').text("");
	$('#dialog-form #expeditii').text("");
	$('#dialog-form #rambursuri').text("");
	$('#dialog-form #data').text("");
	$('#dialog-form #transport').text("");
	$('#dialog-form #d-client').val("");
	$('#dialog-form #d-cui').val("");
	$('#dialog-form #d-adresa').val("");
	$('#dialog-form #tipPlata').val("");
	$('#dialog-form').dialog( "option", "title", "");
	$.get(HTTP + 'facturi/decont/detalii/'+id,'', function(response) {
		var rasp = response.split('|||');
		PrepareResponse(rasp[0]);
		$('#dialog-form #id').val(rasp[1]);
		if (rasp[0] == 1){
			var idDel = rasp[1];
			var txtDel = rasp[6];
			$('#dialog-form #agent_id').val(rasp[2]);
			$('#dialog-form #agent').text(rasp[5]);
			$('#dialog-form #factura').text(rasp[6]);
			$('#dialog-form #expeditii').text(rasp[4]);
			$('#dialog-form #rambursuri').text(rasp[12]);
			$('#dialog-form #data').text(rasp[3]);
			$('#dialog-form #transport').text(rasp[7]);

			$('#dialog-form #d-client').val(rasp[8]);
			$('#dialog-form #d-cui').val(rasp[11]);
			$('#dialog-form #d-adresa').val(rasp[10]);
			$('#dialog-form #tipPlata').text(rasp[13]);
			  
			$('#dialog-form').dialog( "option", "title", "Detalii factura nr. : "+rasp[6] );
			var btn_close = {"text": "Inchide [esc]",click: function() {$( this ).dialog( "close" );}}
			var btn_print = {"text": 'Print', "tabIndex": -1, click: function() { ModificaPrintFacturaDecont(id); $( this ).dialog( "close" );}};
			var btn_modify = {"text": 'Modifica', "tabIndex": -1, click: function() { ModificareFacturaDecont(id); $( this ).dialog( "close" );}};
			var btn_xclose = {
				"text": 'DEL', 
				"tabIndex": -1, 
				"style" : "margin-right:300px;background:red;color:black",
				click: function() {
						$( this ).dialog( "close" );
						AnulareFacturaFiscalaDecont(idDel, txtDel);
				}
			};
			$('#dialog-form #agent').prop("readonly", true);
			$('#dialog-form').dialog( "option", "buttons",
						[
							btn_xclose,
							btn_print,
							btn_modify,
							btn_close
						]
			);
		}
		else {
			  //error
			$("#modal-error-message").text("Factura invalida");
			$("#modal-error-message").show();
		}
		$('#dialog-form').dialog("open");
	});
}

function AnulareFacturaFiscalaDecont(idDel, txtDel){
	$('#dialog-confirm').dialog( "option", "title", "Confirmare");
	$('#dialog-confirm #ch').text(txtDel);
	var btn_no = {"text": "NU [esc]",click: function() {$( this ).dialog( "close" );}}
	var btn_yes = {
		"text": "DA",
		"tabIndex": -1, 
		click: function() { 
			$.post(HTTP + 'facturi/decont/xclose', {
				id : idDel
			}, function(response2) {
				if(response2 == 0) {
					$("#confirm-error-message").text("FAILED");
					$("#confirm-error-message").show();
				}
				else { 
					$('#dialog-confirm').dialog( "close" );
					jQuery("#jqGrid_decont_cautare_facturi").trigger("reloadGrid");
				}
				
			})
	}};
	$('#dialog-confirm').dialog( "option", "buttons",
		[
			btn_yes,
			btn_no
		]
	);
	$('#dialog-confirm').dialog("open");
}

function ModificaPrintFacturaDecont(id){
	var client = $('#dialog-form #d-client').val();
	var cui = $('#dialog-form #d-cui').val();
	var adresa = $('#dialog-form #d-adresa').val();

	var postData = {};

	postData['id'] = id;
	postData['client'] = client;
	postData['cui'] = cui;
	postData['adresa'] = adresa;
	
	$.download('facturi/decont/printone', JSON.stringify(postData), true);
	return true;
}

function ModificareFacturaDecont(id){
	var client = $('#dialog-form #d-client').val();
	var cui = $('#dialog-form #d-cui').val();
	var adresa = $('#dialog-form #d-adresa').val();

	$.post(HTTP + 'facturi/decont/edit', {
		id : id,
		client : client,
		cui : cui,
		adresa : adresa
	}, function(response) {
		//if(response == 0) 
		//AfiseazaEroare('Eroare modificare ');
	});
	return true;
}
/* //////////////////////////////////////////////////////
					START CAUTARE CHELTUIELI DECONT
//////////////////////////////////////////////////////  */
function CautareCheltuieliDecont(sel) {
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
		if ($('#curier').is(':checked')) {
			$('#curier_livrare_nume').removeAttr('disabled');
		} else {
			$('#curier_livrare').val('');
			$('#curier_livrare_nume').val('');
			$('#curier_livrare_nume').attr('disabled', true);
		}
	}
}

function AfisareCheltuieliDecont()
{
	var cond = '';

	if ($('#perioada').is(':checked') && $.trim($('#data_start').val()).length && $.trim($('#data_final').val()).length) {
		var data_start = $('#data_start').val();
		var data_final = $('#data_final').val();
		cond += '&data_start=' + encodeURIComponent(data_start) + '&data_final='+ encodeURIComponent(data_final);
	}
	if ($('#curier').is(':checked') && $.trim($('#curier_livrare').val()).length) {
		var curier_id = parseInt($('#curier_livrare').val());
		if(!isNaN(curier_id) && curier_id > 0)
			cond += '&cod_ag=' + curier_id;
	}
	jQuery("#jqGrid_decont_cautare_cheltuieli").jqGrid('setGridParam',{ url: HTTP + 'cheltuieli/decont/json_cautare?a' + cond, postData : { curieri: "" }}).trigger("reloadGrid");
}

function Grid_CheltuieliCautareDecont()
{
	var grid = jQuery('#jqGrid_decont_cautare_cheltuieli');

	grid.jqGrid(
	{
		url : HTTP + 'cheltuieli/decont/json_cautare',
		datatype: 'json',
		mtype: 'POST',
		colNames : [ 'Tip', 'Valoare', 'Data', 'Descriere', 'Agent', 'Centru', 'Chitanta', 'Factura'],
		colModel : [ {
			name : 'tip',
			index : 'dicd.tip',
			stype : 'select',
			searchoptions : {
				value : ":Toate;1:CASA FA/CH - Cheltuiala;2:CASA FA/CH - Lipsa bani;3:CASA FA/CH - Retur lipsa bani;6:CASA FA/CH - Compensare;10:CASA FA/CH - Achizitie paleti;11:CASA FA/CH - Avans salariu;12:CASA FA/CH - Chirie Motostivuitor;13:CASA FA/CH - Chirie SBK;14:CASA FA/CH - Consumabile auto;15:CASA FA/CH - ITP;16:CASA FA/CH - Mententanta depozit;17:CASA FA/CH - Ore suplimentare;18:CASA FA/CH - Piese auto;19:CASA FA/CH - Service auto;20:CASA FA/CH - Servicii spalatorie;21:CASA FA/CH - Taxa parcare;22:CASA FA/CH - Taxa port;23:CASA FA/CH - Tractari auto;24:CASA FA/CH - Vulcanizare;34:CASA FA/CH - Incarcare casa;30:CASA RBS - Salarii;31:CASA RBS - Auto;32:CASA RBS - Plata SBK;33:CASA RBS - Diverse;35:CASA RBS - Incarcare casa;4:OLD - Incarcare CASA;5:OLD - Descarcare CASA",
				sopt : [ 'eq' ]
			},
			edittype : "select",
			editoptions: {
				value : "1:CASA FA/CH - Cheltuiala;2:CASA FA/CH - Lipsa bani;3:CASA FA/CH - Retur lipsa bani;6:CASA FA/CH - Compensare;10:CASA FA/CH - Achizitie paleti;11:CASA FA/CH - Avans salariu;12:CASA FA/CH - Chirie Motostivuitor;13:CASA FA/CH - Chirie SBK;14:CASA FA/CH - Consumabile auto;15:CASA FA/CH - ITP;16:CASA FA/CH - Mententanta depozit;17:CASA FA/CH - Ore suplimentare;18:CASA FA/CH - Piese auto;19:CASA FA/CH - Service auto;20:CASA FA/CH - Servicii spalatorie;21:CASA FA/CH - Taxa parcare;22:CASA FA/CH - Taxa port;23:CASA FA/CH - Tractari auto;24:CASA FA/CH - Vulcanizare;34:CASA FA/CH - Incarcare casa;30:CASA RBS - Salarii;31:CASA RBS - Auto;32:CASA RBS - Plata SBK;33:CASA RBS - Diverse;35:CASA RBS - Incarcare casa;4:OLD - Incarcare CASA;5:OLD - Descarcare CASA",
			},
			formatter: 'select',
			width: 200
		},{
			name : 'suma',
			index : 'dicd.suma',
			align: 'right',
			width: 50
		},{
			name : 'data',
			index : 'dicd.data',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d H:i:s',
				newformat : 'd.m.Y H:i'
			},
			width: 100,
			align: 'center'
		},{
			name : 'descriere',
			search : false,
			sortable : false,
			width: 180
		}, {
			name : 'agent',
			index : 'ag.nume_ag',
			width: 140
		}, {
			name : 'centru',
			index : 'ce.label',
			width: 70,
			align : 'center'
		}, {
			name : 'chitanta',
			search : false,
			sortable : false,
			align: 'center',
			width: 100
		}, {
			name : 'factura',
			search : false,
			sortable : false,
			align: 'center',
			width: 70
		}],
		rownumbers : true,
		scroll : 1,
		rowNum : 100,
		width : 970,
		height : 295,
		sortname : "dicd.data",
		sortorder : "desc",
		viewrecords : true,
		gridview : true,
		pager : '#jqGrid_paginatie',
		shrinkToFit: false,
		caption: 'Lista cheltuieli',
		forceFit: true,
		footerrow: true,
	    userDataOnFooter: true,
		multiselect: false,
		ondblClickRow : function(id) {
			AfisareDetaliiCheltuieliDecont(id);
		},
		loadError : function(xhr,st,err) {
			AfiseazaEroare(xhr.responseText);
		}
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});

}

function ExportCheltuieliDecont()
{
	var data_start = encodeURIComponent($('#data_start').val());
	var data_final = encodeURIComponent($('#data_final').val());
	var agent = $('#curier_livrare').val();
	var cond = '&data_start=' + data_start + '&data_final='+ data_final + '&agent=' + agent;
	var filters =  $('#jqGrid_decont_cautare_cheltuieli').jqGrid('getGridParam','postData').filters;
	if(filters) cond+='&_search=true&filters='+filters;
	sidx = $('#jqGrid_decont_cautare_cheltuieli').jqGrid('getGridParam','sortname');
	if(sidx) cond+='&sidx='+sidx;
	sord = $('#jqGrid_decont_cautare_cheltuieli').jqGrid('getGridParam','sortorder');
	if(sord) cond+='&sord='+sord;

	$.download('cheltuieli/decont/export', cond, false);
}

function AfisareDetaliiCheltuieliDecont(id){
	$("#modal-error-message").text("Cheltuiala invalida");
	$("#modal-error-message").hide();
	$('#dialog-form #agent_id').val("");
	$('#dialog-form #agent').text("");
	$('#dialog-form #tip').text("");
-	$('#dialog-form #data').text("");
	$('#dialog-form #descriere').text("");
	$('#dialog-form #suma').val("");
	$('#dialog-form #chitanta').val("");
	$('#dialog-form #factura').val("");
	$('#dialog-form').dialog( "option", "title", "");
	$.get(HTTP + 'cheltuieli/decont/detalii/'+id,'', function(response) {
		var rasp = response.split('|||');
		PrepareResponse(rasp[0]);
		$('#dialog-form #id').val(rasp[1]);
		if (rasp[0] == 1){
			var idDel = rasp[1];
			var txtDel = rasp[5];
			$('#dialog-form #agent').prop("readonly", true);
			$('#dialog-form #agent_id').val(rasp[2]);
			$('#dialog-form #agent').text(rasp[3]);
			$('#dialog-form #data').text(rasp[4]);
			$('#dialog-form #tip').text(rasp[5]);
			$('#dialog-form #suma').text(rasp[6]);
			$('#dialog-form #descriere').text(rasp[7]);
			
			$('#dialog-form #chitanta').text(rasp[8]);
			$('#dialog-form #factura').text(rasp[9]);
			  
			$('#dialog-form').dialog( "option", "title", "Detalii cheltuiala : "+rasp[5]);

			var btn_close = {"text": "Inchide [esc]",click: function() {$( this ).dialog( "close" );}}
			$('#dialog-form').dialog( "option", "buttons",[ btn_close ]);
			
		}
		else {
			  //error
			$("#modal-error-message").text("Cheltuiala invalida");
			$("#modal-error-message").show();
		}
		$('#dialog-form').dialog("open");
	});
}

/* //////////////////////////////////////////////////////
					START CAUTARE CHITANTE DECONT
//////////////////////////////////////////////////////  */

function CautareDecontCf(sel) {
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
		if ($('#curier').is(':checked')) {
			$('#curier_livrare_nume').removeAttr('disabled');
		} else {
			$('#curier_livrare').val('');
			$('#curier_livrare_nume').val('');
			$('#curier_livrare_nume').attr('disabled', true);
		}
	}
}

function AfisareDecontCf()
{
	var cond = '';
	if($.trim($('#chitanta').val()).length) {
		cond += '&chitanta='+ $.trim($('#chitanta').val());
	}

	if ($('#perioada').is(':checked') && $.trim($('#data_start').val()).length && $.trim($('#data_final').val()).length) {
		var data_start = $('#data_start').val();
		var data_final = $('#data_final').val();
		cond += '&data_start=' + encodeURIComponent(data_start) + '&data_final='+ encodeURIComponent(data_final);
	}
	if ($('#curier').is(':checked') && $.trim($('#curier_livrare').val()).length) {
		var curier_id = parseInt($('#curier_livrare').val());
		if(!isNaN(curier_id) && curier_id > 0)
			cond += '&cod_ag=' + curier_id;
	}
	jQuery("#jqGrid_decont_cautare_cf").jqGrid('setGridParam',{ url: HTTP + 'facturi/decont/json_cautare_cf?a' + cond, postData : { chitante: "" }}).trigger("reloadGrid");
}

function Grid_DecontCautareCf()
{
	var grid = jQuery('#jqGrid_decont_cautare_cf');

	grid.jqGrid(
	{
		url : HTTP + 'facturi/decont/json_cautare_cf',
		datatype: 'json',
		mtype: 'POST',
		colNames : ['Chitanta', 'Valoare', 'Data', 'Descriere', 'Client','CUI', 'Agent', 'Centru', 'Incasat', 'Decontata'],
		colModel : [ {
			name : 'chitanta',
			index : 'dfc.ch_bon',
			align: 'right',
			width: 70
		}, {
			name : 'suma',
			index : 'dfc.suma',
			align: 'right',
			width: 50
		},{
			name : 'data',
			index : 'dfc.dataInc',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d H:i:s',
				newformat : 'd.m.Y H:i'
			},
			width: 100,
			align: 'center'
		},{
			name : 'descriere',
			search : false,
			sortable : false,
			width: 150
		}, {
			name : 'client',
			search : false,
			sortable : false,
			width: 120
		}, {
			name : 'cui',
			search : false,
			sortable : false,
			width: 80
		}, {
			name : 'agent',
			index : 'ag.nume_ag',
			width: 140
		}, {
			name : 'centru',
			index : 'ce.label',
			width: 70,
			align: 'center'
		}, {
			name : 'tipPlata',
			index : 'tipPlata',
			stype : 'select',
			formatter: 'select',
			searchoptions : {
				value : ":Toate;0:CASH;1:CARD",
				sopt : [ 'eq' ]
			},
			editoptions : {
				value : "0:CASH;1:CARD"
			},
			align: 'center',
			width: 40
		}, {
			name : 'decontata',
			index : 'decontata',
			stype : 'select',
			formatter: 'select',
			searchoptions : {
				value : ":Toate;0:Nu;1:Da",
				sopt : [ 'eq' ]
			},
			editoptions : {
				value : "0:Nu;1:Da"
			},
			align: 'center',
			width: 40
		}],
		rownumbers : true,
		scroll : 1,
		rowNum : 100,
		width : 970,
		height : 295,
		sortname : "dfc.dataInc",
		sortorder : "desc",
		viewrecords : true,
		gridview : true,
		pager : '#jqGrid_paginatie',
		shrinkToFit: false,
		caption: 'Lista chitante client CTR',
		forceFit: true,
		footerrow: true,
	    userDataOnFooter: true,
		multiselect: false,
		ondblClickRow : function(id) {
			AfisareDetaliiDecontCf(id);
		},
		loadError : function(xhr,st,err) {
			AfiseazaEroare(xhr.responseText);
		}
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});

}

function ExportDecontCf()
{
	var data_start = encodeURIComponent($('#data_start').val());
	var data_final = encodeURIComponent($('#data_final').val());
	var agent = $('#curier_livrare').val();
	var chitanta = $('#chitanta').val();
	var cond = '&data_start=' + data_start + '&data_final='+ data_final + '&agent=' + agent + '&chitanta=' + chitanta;
	var filters =  $('#jqGrid_decont_cautare_cf').jqGrid('getGridParam','postData').filters;
	if(filters) cond+='&_search=true&filters='+filters;
	sidx = $('#jqGrid_decont_cautare_cf').jqGrid('getGridParam','sortname');
	if(sidx) cond+='&sidx='+sidx;
	sord = $('#jqGrid_decont_cautare_cf').jqGrid('getGridParam','sortorder');
	if(sord) cond+='&sord='+sord;

	$.download('facturi/decont/export_cf', cond, false);
}

function AfisareDetaliiDecontCf(id){
	$("#modal-error-message").text("Chitanta fiscala invalida");
	$("#modal-error-message").hide();
	$('#dialog-form #agent_id').val("");
	$('#dialog-form #agent').text("");
-	$('#dialog-form #data').text("");
	$('#dialog-form #descriere').text("");
	$('#dialog-form #suma').val("");
	$('#dialog-form #chitanta').val("");
	$('#dialog-form #client').val("");
	$('#dialog-form #tipPlata').val("");
	$('#dialog-form').dialog( "option", "title", "");
	$.get(HTTP + 'facturi/decont/detalii_cf/'+id,'', function(response) {
		var rasp = response.split('|||');
		PrepareResponse(rasp[0]);
		$('#dialog-form #id').val(rasp[1]);
		if (rasp[0] == 1){
			var idDel = rasp[1];
			var txtDel = rasp[5];
			$('#dialog-form #agent').prop("readonly", true);
			$('#dialog-form #agent_id').val(rasp[2]);
			$('#dialog-form #agent').text(rasp[3]);
			$('#dialog-form #data').text(rasp[4]);
			$('#dialog-form #suma').text(rasp[5]);
			$('#dialog-form #descriere').text(rasp[6]);
			$('#dialog-form #chitanta').text(rasp[7]);
			$('#dialog-form #client').text(rasp[8]);
			$('#dialog-form #tipPlata').text(rasp[11]);
			  
			$('#dialog-form').dialog( "option", "title", "Detalii chitanta fiscala : "+rasp[7] );

			var btn_close = {"text": "Inchide [esc]",click: function() {$( this ).dialog( "close" );}}
			var btn_print = {"text": 'Print', "tabIndex": -1, click: function() { ModificaPrintDecontCf(id); $( this ).dialog( "close" );}};
			var btn_xclose = {
				"text": 'DEL', 
				"tabIndex": -1, 
				"style" : "margin-right:390px;background:red;color:black",
				click: function() {
						$( this ).dialog( "close" );
						AnulareDecontCf(idDel, txtDel);
				}
			};
			$('#dialog-form').dialog( "option", "buttons",
						[
							btn_xclose,
							btn_print,
							btn_close
						]
			);
		}
		else {
			  //error
			$("#modal-error-message").text("Chitanta fiscala invalida");
			$("#modal-error-message").show();
		}
		$('#dialog-form').dialog("open");
	});
}

function AnulareDecontCf(idDel, txtDel){
	$('#dialog-confirm').dialog( "option", "title", "Confirmare");
	$('#dialog-confirm #ch').text(txtDel);
	var btn_no = {"text": "NU [esc]",click: function() {$( this ).dialog( "close" );}}
	var btn_yes = {
		"text": "DA",
		"tabIndex": -1, 
		click: function() { 
			$.post(HTTP + 'facturi/decont/xclose_cf', {
				id : idDel
			}, function(response2) {
				if(response2 == 0) {
					$("#confirm-error-message").text("FAILED");
					$("#confirm-error-message").show();
				}
				else { 
					$('#dialog-confirm').dialog( "close" );
					jQuery("#jqGrid_decont_cautare_cf").trigger("reloadGrid");
				}
				
			})
	}};
	$('#dialog-confirm').dialog( "option", "buttons",
		[
			btn_yes,
			btn_no
		]
	);
	$('#dialog-confirm').dialog("open");
}

function ModificaPrintDecontCf(id){
	var postData = {};

	postData['id'] = id;
	
	$.download('facturi/decont/printone_cf', JSON.stringify(postData), true);
	return true;
}