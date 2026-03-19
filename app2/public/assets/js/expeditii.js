function ReturnareExpeditie(sel, evt){
    var key = evt.key || evt.which || evt.keyCode;
    if (key !== 'Enter' && key !== 13) return false;

    sel = parseInt(sel.trim());
	if(isNaN(sel) || sel == 0) return false;

    var tip_exp = parseInt($('#tip_exp').val());
    if(isNaN(tip_exp) || tip_exp == 0) return false;

    $.post(HTTP + 'expeditii/valoare_return_expeditie/',{
        tip_exp:tip_exp,
        expeditie:sel
    }, function(response) {
        try {
			var vals = JSON.parse(response);
		}
		catch(err) {
			return false;
		}
        if (vals.error == 1){
            $('#CODURI').val('');

            $('#valoare_expeditie').html(vals.tExpeditie);
            $('#valoare_km').html(vals.tKm);
            $('#valoare_greutate').html(vals.tGreutate);
            $('#valoare_asigurare').html(vals.tAsigurare);
            $('#valoare_ramburs').html(vals.tRamburs);
            $('#PRET_IMPUS').val(vals.pret_impus);
            var moneda = vals.moneda;

            $('#span_ht').html(vals.ht + ' '+ moneda);
            $('#span_tva').html(vals.tva + ' '+ moneda);
            $('#span_ttc').html(vals.ttc +' '+ moneda);

            $('#EXPEDITOR').val(vals.expeditor_id);
            $('#EXPEDITOR_CC').val(vals.destinatar_cc);
            SetareCC();
            $('#EXPEDITOR_NUME').val(vals.expeditor_nume);
            $('#EXPEDITOR_LOCALITATE').val(vals.expeditor_localitate_id);
            $('#EXPEDITOR_LOCALITATE_NUME').val(vals.expeditor_localitate);
            $('#EXPEDITOR_TELEFON').val(vals.expeditor_telefon);
            $('#EXPEDITOR_ADRESA').val(vals.expeditor_adresa);
            $('#EXPEDITOR_CONTACT').val(vals.expeditor_contact);

            $('#DESTINATAR').val(vals.destinatar_id);
            $('#DESTINATAR_NUME').val(vals.destinatar_nume);
            $('#DESTINATAR_LOCALITATE').val(vals.destinatar_localitate_id);
            $('#DESTINATAR_LOCALITATE_NUME').val(vals.destinatar_localitate);
            $('#DESTINATAR_TELEFON').val(vals.destinatar_telefon);
            $('#DESTINATAR_ADRESA').val(vals.destinatar_adresa);
            $('#DESTINATAR_CONTACT').val(vals.destinatar_contact);

            if(tip_exp == 3){
                $('#ramburs').attr('readonly', true);
                $('#proc_asig').attr('readonly', true);
                $('#asigurare').attr('readonly', true);
                if(vals.tip_plata == 0 || vals.tip_plata == 3) {
                    $('#proc_asig').val(vals.procAsigurare);
                    $('#asigurare').val(vals.valoare_asigurata);
                }
                else
                {
                    $('#proc_asig').val(0.00);
                    $('#ramburs').val(vals.ramburs);
                }
                $('#tip_plata').val(vals.tip_plata);
            }
            else if(tip_exp==5){
                $('#GREUTATE').val(vals.greutate);
                $('#asigurare').val(vals.valoare_asigurata);
                if(vals.tip_obj == 2){
                    $("#TIP_OBJ_1").attr("checked", false);
                    $("#TIP_OBJ_2").val(vals.colete);
                    $("#TIP_OBJ_3").attr("checked", false);
                }else if(vals.tip_obj == 3){
                    $("#TIP_OBJ_1").attr("checked", false);
                    $("#TIP_OBJ_2").val("");
                    $("#TIP_OBJ_3").attr("checked", true);
                }
                else{
                    $("#TIP_OBJ_1").attr("checked", true);
                    $("#TIP_OBJ_2").val("0");
                    $("#TIP_OBJ_3").attr("checked", false);
                }
            }
            else if(tip_exp==6){
                $('#GREUTATE').val(vals.greutate);
                $("#TIP_OBJ_2").val(vals.colete);
                $('#asigurare').val('0.00');
            } 
            else if(tip_exp == 7) {
                $("#TIP_OBJ_1").attr("checked", false);
                $("#TIP_OBJ_2").val(vals.colete);
                $("#TIP_OBJ_3").attr("checked", false);
                $('#GREUTATE').val(vals.greutate);
                $('#GREUTATE_VOL').val('0.000');
                $('#VOLUM1').val('');
				$('#VOLUM2').val('');
				$('#VOLUM3').val('');
                $('#asigurare').val('0.00');
                $('#valoare_asigurare').val('0.00');
                $('#valoare_greutate').val('0.00');
            }

            $('#mod_plata').val(vals.mod_plata);
            $('#KM_EXT_LIV').val(vals.km_livrare);

            $('#PLATITOR').val(vals.platitor_id);
            $('#PLATITOR_NUME').val(vals.platitor_nume);

            $('#plateste_expeditor').attr("checked", false);
            $('#plateste_destinatar').attr("checked", false);
            $('#plateste_altul').attr("checked", false);
            $("#plateste").val(vals.platitor_id);

            var expeditor = parseInt($('#EXPEDITOR').val());
            var destinatar = parseInt($('#DESTINATAR').val());

            if (vals.plateste == 1) {//expeditor
                if(isNaN(expeditor) || expeditor == 0) { AfiseazaEroare('Introduceti expeditorul!'); return false; }
                $('#PLATITOR_NUME').val($('#EXPEDITOR_NUME').val());
                $('#PLATITOR').val(expeditor);
                $("#PLATITOR_NUME").prop('readonly', true);
                $('#plateste_expeditor').attr("checked", true);
            } else if (vals.plateste == 2) {//destinatar
                if(isNaN(destinatar) || destinatar == 0) { AfiseazaEroare('Introduceti destinatarul!');  return false; }
                $('#PLATITOR_NUME').val($('#DESTINATAR_NUME').val());
                $('#PLATITOR').val(destinatar);
                $("#PLATITOR_NUME").prop('readonly', true);
                $('#plateste_destinatar').attr("checked", true);
            } else {//tert
                if(isNaN(expeditor) || expeditor == 0 || isNaN(destinatar) || destinatar == 0) { AfiseazaEroare('Introduceti expeditorul/destinatarul!');  return false; }
                $("#PLATITOR_NUME").prop('readonly', false);
                $('#plateste_altul').attr("checked", true);
            }

            $('#OBSERVATII').val('');

            ValidareCampuriIntroducere('NR_EXP');

            ValidareCampuriIntroducere('DESTINATAR_NUME');
            ValidareCampuriIntroducere('DESTINATAR_LOCALITATE_NUME');
            ValidareCampuriIntroducere('DESTINATAR_ADRESA');
            ValidareCampuriIntroducere('DESTINATAR_TELEFON');
            ValidareCampuriIntroducere('DESTINATAR_CONTACT');

            ValidareCampuriIntroducere('EXPEDITOR_NUME');
            ValidareCampuriIntroducere('EXPEDITOR_LOCALITATE_NUME');
            ValidareCampuriIntroducere('EXPEDITOR_ADRESA');
            ValidareCampuriIntroducere('EXPEDITOR_TELEFON');
            ValidareCampuriIntroducere('EXPEDITOR_CONTACT');

            EditareExpeditieBlocare(true);

            if(tip_exp == 7) {
                $('#GREUTATE').prop('readonly', false);
                $("#TIP_OBJ_2").prop('readonly', false);
                $("#VOLUM1").prop('readonly', false);
                $("#VOLUM2").prop('readonly', false);
                $("#VOLUM3").prop('readonly', false);
            }

        }
        else if(vals.error == 0){
            EditareExpeditieBlocare(false);
            $('#valoare_expeditie').html('0.00');
            $('#valoare_km').html('0.00');
            $('#valoare_greutate').html('0.00');
            $('#valoare_asigurare').html('0.00');
            $('#proc_asig').val('');
            $('#asigurare').val('');


            $('#span_ht').html('0.00 LEI');
            $('#span_tva').html('0.00 LEI');
            $('#span_ttc').html('0.00 LEI');

            $('#DESTINATAR').val('');
            $('#DESTINATAR_NUME').val('');
            $('#DESTINATAR_LOCALITATE').val('');
            $('#DESTINATAR_LOCALITATE_NUME').val('');
            $('#DESTINATAR_CONTACT').val('');
            $('#DESTINATAR_ADRESA').val('');
            $('#DESTINATAR_TELEFON').val('');

            $('#EXPEDITOR').val('');
            $('#EXPEDITOR_NUME').val('');
            $('#EXPEDITOR_LOCALITATE').val('');
            $('#EXPEDITOR_LOCALITATE_NUME').val('');
            $('#EXPEDITOR_CONTACT').val('');
            $('#EXPEDITOR_ADRESA').val('');
            $('#EXPEDITOR_TELEFON').val('');

            $('#PLATITOR').val('');
            $('#PLATITOR_NUME').val('');

            $('#plateste_expeditor').attr("checked", false);
            $('#plateste_destinatar').attr("checked", false);
            $('#plateste_altul').attr("checked", false);

            $('#mod_plata').val(0);
        }
        else if(vals.error == 2){
            AfiseazaEroare(vals.msg);
            return false;
        }
    });
}


function EditareExpeditieBlocare(block) {

    if(block){
        $('#mod_plata').addClass('disabled-style');
    } else {
        $('#mod_plata').removeClass('disabled-style');
    }

    $("#expeditii_continut input:text,#expeditii_continut textarea,.expeditii_left input:text,.expeditii_left textarea,#expeditii_right input:text,#expeditii_right textarea").prop('readonly', block);
    $("#expeditii_continut input:radio, #expeditii_continut input:checkbox").prop('readonly', block);
    // $(".expeditii_right input:button, #tip_plata").prop('disabled', block);

    $('#detalii_doc,#OBSERVATII,#referinta_expeditie').prop('readonly', false);
    $('#adauga_expeditie').prop('readonly', false);
    $('#tip_plata').prop('readonly', false);
    $('#tip_exp').prop('readonly', false);
    $('#NR_EXP').prop('readonly', true);
}


function ReferintaExpeditieTrigger() {
    var e = jQuery.Event("keypress");
    e.which = 13;
    e.keyCode = 13;
    $("#referinta_expeditie").trigger(e);

}

$( document ).ready(function() {
    $('#tip_exp').change(function() {
        if($('#tip_exp').val() == 0){
            EditareExpeditieBlocare(false);
        }

    });
    $("#referinta_expeditie" ).focusout(function() {
        ReferintaExpeditieTrigger();
    });
});

function startImportRecantariri(){
    document.getElementById('progressor').style.width = "0%";
    var fxls = $('#fxls').val();
    var source = new EventSource('recantariri/xls/import?fxls='+fxls);
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
            source.close();
        }
    });

    source.addEventListener('error' , function(e)
    {
        add_log('Error occured', 'red');
        //kill the object ?
        source.close();
    });
}

function startVerificareRecantariri()
{
    var fxls = $('#fxls').val();
    document.getElementById('progressor').style.width = "0%";
    var source = new EventSource('recantariri/xls/check?fxls='+fxls);
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
            add_log('Fisierul initial are erori !!!','red');
            $('#merror').val(1);
            source.close();
        }
        else if(result.stop == 3)
        {
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

function Grid_Recantariri()
{
    var grid = jQuery('#cautare_recantariri');
    var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var cond = '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);

	grid.jqGrid(
	{
		url : HTTP + 'recantariri/expeditii/json?a'+cond,
		datatype: 'json',
		mtype: 'POST',
		colNames : [ 'Nr. NT', 'Ce', 'Exp', 'Dest','Plat', 'Data NT','Col','Pal','Gr. veche','Gr. noua','vL', 'vl', 'vI', 'RW data', 'RW validare', 'RW by', 'RW centru', 'RW status', 'Poza', 'RW motiv'],
		colModel : [ {
            name : 'expeditie',
            index : 'e.expeditie',
            sortable: true,
			width: 55
		}, {
			name : 'centru_exp',
            index : 'c_exp.label',
			align: 'center',
			width: 20
		}, {
            name : 'expeditor',
            index : 'cl_exp.nume',
			sortable: true,
			width: 40
		}, {
            name : 'destinatar',
            index : 'cl_dest.nume',
			sortable: true,
			width: 40
		},{
            name : 'platitor',
            index : 'cl_plat.nume',
			sortable: true,
			width: 45
		},{
            name : 'data_expeditie',
            index : 'e.data_expeditie',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			width: 60,
			align: 'center'
		}, {
			name : 'colete',
            index : 'e.colete',
            search: false,
			formatter: 'integer',
			align: 'right',
			width: 20
		}, {
			name : 'paleti',
            index : 'e.paleti',
            search: false,
			formatter: 'integer',
			align: 'right',
			width: 20
		}, {
			name : 'old_kg',
            index : 'err.oldKg',
            search: false,
			formatter: 'number',
			align: 'right',
			width: 40
		},{
			name : 'new_kg',
            index : 'err.kg',
            search: false,
			formatter: 'number',
			align: 'right',
			width: 50
		},{
			name : 'lungime',
            index : 'err.lungime',
            search: false,
			formatter: 'integer',
			align: 'right',
			width: 30
		},{
			name : 'latime',
            index : 'err.latime',
            search: false,
			formatter: 'integer',
			align: 'right',
			width: 30
		},{
			name : 'inaltime',
            index : 'err.inaltime',
            search: false,
			formatter: 'integer',
			align: 'right',
			width: 30
		},{
			name : 'data_recantarire',
			index : 'err.data',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d H:i',
				newformat : 'd.m.Y H:i'
			},
			width: 90,
			align: 'center'
		},{
			name : 'data_validare',
			index : 'DATE(err.vData)',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			width: 60,
			align: 'center'
		},{
			name : 'user',
            index : 'u.nume',
			align: 'center',
            width: 60
		},{
			name : 'centru',
            index : 'ce.label',
			align: 'center',
            width: 30
		},{
			name : 'status',
            index : 'err.vKg',
			align: 'center',
			stype : 'select',
            formatter:'select',
			searchoptions : {
				value : ":Toate;0:Recantarita;1:Validata;2:Anulata",
				sopt : [ 'eq' ]
			},
            edittype : "select",
			editoptions: {
				value: {0:'Recantarita', 1:'Validata', 2:'Anulata'}
			},
            width: 60
		},{
			name : 'poza',
            search: false,
			sortable: false,
            align: 'center',
            width: 30
		},{
			name : 'motiv',
            search: false,
			sortable: false,
            align: 'center',
            width: 80
		}],
		rownumbers : true,
		scroll : 1,
		rowNum : 100,
		width : 970,
		height : 295,
		sortname : 'err.data',
		sortorder : "desc",
		viewrecords : true,
		pager : '#paginatie',
		shrinkToFit: false,
		caption:'Lista Expeditii',
		forceFit: true,
		multiselect: false,
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

function CautareRecantariri(){
    var data_start = $('#data_start').val();
    var data_final = $('#data_final').val();
    var platitor_id  = 0;
    if($('#platitor_nume').val().length) {
        platitor_id=parseInt($('#platitor').val());
    }
	var cond = '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final) + '&platitor_id='+ platitor_id;
    jQuery("#cautare_recantariri").jqGrid('setGridParam',{url : HTTP + 'recantariri/expeditii/json?a' + cond}).trigger("reloadGrid");
}

function ExportRecantariri(){
    var data_start = $('#data_start').val();
    var data_final = $('#data_final').val();
    var platitor_id = 0;
    if($('#platitor_nume').val().length) {
        platitor_id=parseInt($('#platitor').val());
    }
    var cond = '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final) + '&platitor_id='+ platitor_id;

    var filters =  $('#cautare_recantariri').jqGrid('getGridParam','postData').filters;
	if(filters) cond+='&_search=true&filters='+filters;
	sidx = $('#cautare_recantariri').jqGrid('getGridParam','sortname');
	if(sidx) cond+='&sidx='+sidx;
	sord = $('#cautare_recantariri').jqGrid('getGridParam','sortorder');
	if(sord) cond+='&sord='+sord;

	$.download('recantariri/expeditii/export', cond, false);
}

function Grid_vRecantariri()
{
    var grid = jQuery('#cautare_vrecantariri');

	grid.jqGrid(
	{
		url : HTTP + 'recantariri/validare/json',
		datatype: 'json',
		mtype: 'POST',
		colNames : [ 'Nr. NT', 'NT ce.', 'Expeditor', 'Destinatar', 'Platitor', 'Data NT','Colete','Paleti','Gr. cf. NT','Gr. noua','RW data', 'RW ce.'],
		colModel : [ {
            name : 'expeditie',
            index : 'er.expeditie',
            width: 60,
            sortable: true
		}, {
			name : 'centru_exp',
            index : 'c_exp.label',
			align: 'center',
			width: 40
		}, {
            name : 'expeditor',
            index : 'cl_exp.nume',
			sortable: true,
			width: 140
		}, {
            name : 'destinatar',
            index : 'cl_dest.nume',
			sortable: true,
			width: 130
		}, {
            name : 'platitor',
            index : 'cl_plat.nume',
            width: 115,
			sortable: true
		},{
            name : 'data_expeditie',
            index : 'e.data_expeditie',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
            width: 80,
			align: 'center'
		}, {
			name : 'colete',
			sortable: false,
            search: false,
			formatter: 'integer',
            width: 30,
			align: 'right'
		}, {
			name : 'paleti',
			sortable: false,
            search: false,
			formatter: 'integer',
            width: 30,
			align: 'right'
		}, {
			name : 'old_kg',
            sortable: false,
            search: false,
			formatter: 'number',
            width: 50,
			align: 'right'
		},{
			name : 'new_kg',
            sortable: false,
            search: false,
			formatter: 'number',
            width: 50,
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
			name : 'data_recantarire',
			index : 'DATE(er.data)',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
            width: 80,
			align: 'center'
		},{
			name : 'centru',
            index : 'ce.label',
			align: 'center',
			width: 50
		}],
		rownumbers : true,
		scroll : 1,
		rowNum : 200,
		width : 970,
		height : 295,
		sortname : 'DATE(er.data)',
		sortorder : "desc",
		viewrecords : true,
		pager : '#paginatie',
		shrinkToFit: false,
		caption:'Lista Recantarite Nevalidae',
		multiselect: true,
        cellEdit: true,
		cellsubmit: 'remote',
		cellurl: HTTP + 'recantariri/validare/update_greutate',
        afterSubmitCell : function(serverresponse, rowid, cellname, value, iRow, iCol) {
			var response = jQuery.parseJSON(serverresponse.responseText);
			if (response.success == 1){
                grid.jqGrid('setCell', rowid, 'new_kg', '', {color:'red', weightfont:'bold'});
				//refresh total
				return [true,""];
			}
			else{
				return [false, response.error];
			}
		},
        ondblClickRow : function(rowId, iRow, iCol, e) {
            if(iCol == 10) return;
            var rowData = jQuery(this).getRowData(rowId);
            var nrNt = rowData['expeditie'];
            AfisareDetaliiExpeditieCuID(nrNt);
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