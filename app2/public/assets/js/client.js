/////////////Client\\\\\\\\\\\\\\
function ClientHydrateSelect(selectId, arrJson, optionSelected) {
	$('#'+selectId).find('option').remove(); 
	var items = '';
	$.each( arrJson, function( key, val ) {
		items += '<option value="' + key + '"' + (optionSelected == key ? ' selected' : '') +'>' + val.nume + '</option>';
	});
	$('#'+selectId).append(items);
}

function ClientComboClientiLocalitati(fieldOf){
	$('#' + fieldOf + 'NUME').autocomplete({
		source : function (request, response) {
			$.ajax({
				type: "POST",
				url: "client/json/clienti_all_info",
				data: {term: request.term},
				dataType: "json",
				success: function(data){
					response($.map(data.rezultat, function(item) {
						return {
								label : item.label,
								value : item.nume_cl,
								cod_cl : item.cod_cl,
								nume_lc : item.nume_lc,
								cod_lc : item.cod_lc,
								km_lc : item.km_lc,
								cod_jd : item.cod_jd,
								adresa : item.adresa,
								contact : item.contact,
								telefon : item.telefon
						}
					}));
					if(data.total == 0){
						$('#' + fieldOf + 'ID').val('');
						$('#' + fieldOf + 'ADRESA').val('');
						$('#' + fieldOf + 'CONTACT').val('');
						$('#' + fieldOf + 'TELEFON').val('');
					}
				}
			});
		},
		minLength: 2,
		select: function(event,ui){
			$('#' + fieldOf + 'ID').val(ui.item.cod_cl);
			$('#' + fieldOf + 'NUME').val(ui.item.value);
			$('#' + fieldOf + 'LOCALITATE_ID').val(ui.item.cod_lc);
			$('#' + fieldOf + 'LOCALITATE_KM').val(ui.item.km_lc);
			$('#' + fieldOf + 'LOCALITATE').val(ui.item.nume_lc);
			$('#' + fieldOf + 'ADRESA').val(ui.item.adresa);
			$('#' + fieldOf + 'CONTACT').val(ui.item.contact);
			$('#' + fieldOf + 'TELEFON').val(ui.item.telefon);
			ValoareExpeditieClient();
		},
		open: function() {
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
			$('#' + fieldOf + 'ID').val('');
			$('#' + fieldOf + 'ADRESA').val('');
			$('#' + fieldOf + 'CONTACT').val('');
			$('#' + fieldOf + 'TELEFON').val('');
		},
		close: function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
	$('#' + fieldOf + 'LOCALITATE').autocomplete({
		source : function (request, response) {
			$.ajax({
				type: "POST",
				url: "client/json/localitate_judete",
				data: {term: request.term},
				dataType: "json",
				success: function(data){
					response($.map(data.rezultat, function(item) {
						return {
							label : item.nume,
							value : item.nume_lc,
							cod_lc : item.cod_lc,
							km_lc : item.km_lc,
							cod_jd : item.cod_jd
						}
					}));
					if(data.total == 0){
						$('#' + fieldOf + 'LOCALITATE_ID').val("");
						$('#' + fieldOf + 'LOCALITATE_KM').val("");
						$('#' + fieldOf + 'ID').val("");
						$('#' + fieldOf + 'ADRESA').val("");
						$('#' + fieldOf + 'CONTACT').val("");
						$('#' + fieldOf + 'TELEFON').val("");
					}
				}
			});
		},
		minLength: 2,
		select: function(event,ui){
			$('#' + fieldOf + 'LOCALITATE_ID').val(ui.item.cod_lc);
			$('#' + fieldOf + 'LOCALITATE_KM').val(ui.item.km_lc);
			$('#' + fieldOf + 'LOCALITATE').val(ui.item.value);
			ValoareExpeditieClient();
		},
		open: function() {
			$(this).removeClass("ui-corner-all").addClass("ui-corner-top");
			$('#' + fieldOf + 'LOCALITATE_ID').val("");
			$('#' + fieldOf + 'LOCALITATE_KM').val("");
			$('#' + fieldOf + 'ID').val("");
			$('#' + fieldOf + 'ADRESA').val("");
			$('#' + fieldOf + 'CONTACT').val("");
			$('#' + fieldOf + 'TELEFON').val("");
		},
		close: function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}

function ClientExportExpeditii(field){
	var grid = jQuery('#'+field);
	var sel_ids = grid.jqGrid('getGridParam', 'selarrrow');
	if(sel_ids.length==0) { AfiseazaEroare("Selectioneaza minimum un awb pentru export !"); return false; }
	else if(sel_ids.length>251) { AfiseazaEroare("Nu se pot selectiona mai mult de 250 awb-uri pentru export !"); return false; }
	var postData = {};

	for(var $i=0, $l=sel_ids.length; $i<$l; $i++) postData[$i] = sel_ids[$i];
	var postData = JSON.stringify(postData);

	$("#detalii_expeditie").dialog("destroy");
	$.download('client/export_expeditii', postData, true);
}

function ClientExportRetururi(field){
	var grid = jQuery('#'+field);
	var sel_ids = grid.jqGrid('getGridParam', 'selarrrow');
	if(sel_ids.length==0) { AfiseazaEroare("Selectioneaza minimum un awb pentru export !"); return false; }
	else if(sel_ids.length>251) { AfiseazaEroare("Nu se pot selectiona mai mult de 250 awb-uri pentru export !"); return false; }
	var postData = {};

	for(var $i=0, $l=sel_ids.length; $i<$l; $i++) postData[$i] = sel_ids[$i];
	var postData = JSON.stringify(postData);

	$("#detalii_expeditie").dialog("destroy");
	$.download('client/export_retururi', postData, true);
}

function ClientImporturiExpeditii(sel){
	var nr_exp = $('#nr_expeditii').val();
	var expeditii = $('#expeditii').text();
	var not_import = $('#not_import').text();

	if(sel == 1)
	{
		$('#import-expeditii').html("");
	}
	var errors = $('#import-expeditii').html();
	if(sel<=nr_exp){
		$.post(HTTP + 'client/importuri/expeditii/step4', {
			sel:sel,
			expeditii:expeditii,
			not_import:not_import
		}, function(response) {
			var val = parseInt((100/nr_exp)*sel);
			var progressbar = $("#progressbar");
			progressbar.progressbar("value", val);
			sel++;
			$('#import-expeditii').html(errors + response);
			ClientImporturiExpeditii(sel);
		});
	}
	return true;
}

function ComboLocalitatiClient(client,field,b){
	$("#" + field).autocomplete({
		source : function(request, response) {
			$.ajax({
				url : HTTP + "client/json/localitati",
				dataType : "json",
				data : {
					maxRows : 20,
					name_startsWith : request.term
				},
				success : function(data) {
					response($.map(data.rezultat, function(item) {
						return {
							value : item.localitate,
							label : item.nume,
							id : item.cod
						}
					}));
					if (data.total === 0) {
						$("#" + field +'_id').val("");
						$("#" + field).val("");
					}
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
			if(!b)
			{
				$("#destinatar_id").val("");
				$("#destinatar_nume").val("");
			}
		},
		change : function(event, ui) {
			if($("#" + field + "_nume").val() == '')
			{
				$("#" + field +'_id').val("");
				if(!b)
				{
					$("#destinatar_id").val("");
					$("#destinatar_nume").val("");
				}
			}
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}

function ComboClientiClient(client,field,loca,b){
	$("#" + field + "_nume").autocomplete({
		source : function(request, response) {
			$.ajax({
				url : HTTP + "client/json/clienti",
				dataType : "json",
				data : {
					maxRows : 50,
					name_startsWith : request.term,
					localitate : $('#' + loca).val(),
					user : client
				},
				success : function(data) {
					response($.map(data.rezultat, function(item) {
						return {
							id : item.destinatar_id,
							value : item.value,
							label : item.label,
							adresa : item.adresa,
							contact : item.contact,
							telefon : item.telefon
						}
					}));
					if (data.total === 0) {
						$("#" + field).val("");
						if(b) {
							$("#" + field + "_adresa").val("");
							$("#" + field + "_contact_nume").val("");
							$("#" + field + "_contact_telefon").val("");
						}
					}
				}

			});
		},
		minLength : 1,
		select : function(event, ui) {
			$("#" + field +'_id').val(ui.item.id);
			$("#" + field + "_nume").val(ui.item.value);
			if(b) {
				$("#" + field + "_adresa").val(ui.item.adresa);
				$("#" + field + "_contact_nume").val(ui.item.contact);
				$("#" + field + "_contact_telefon").val(ui.item.telefon);
			}
		},
		open : function() {
			$("#" + field +'_id').val("");
			if(b) {
				$("#" + field + "_adresa").val("");
				$("#" + field + "_contact_nume").val("");
				$("#" + field + "_contact_telefon").val("");
			}
		},
		change : function() {
			if($("#" + field + "_nume").val() == '')
			{
				$("#" + field +'_id').val("");
				if(b) {
					$("#" + field + "_adresa").val("");
					$("#" + field + "_contact_nume").val("");
					$("#" + field + "_contact_telefon").val("");
				}
			}
		},
		search: function( event, ui ) {
			if($('#' + loca).val() == '')
			{
				alert('Selectati localitatea');
				return false;
			}
		},
		close : function() {
			$(this).removeClass("ui-corner-top").addClass("ui-corner-all");
		}
	});
}

function ValoareExpeditieClient(sel){
	var plicuri=paleti=colete=ret_nt=ret_doc=liv_sambata=liv_sediu=ret_amb=ret_colet=factura=tip_plata=sms=copen=ramburs=asigurare=0;

	var plicuri = 0;
	var paleti = 0;

	var colete = parseInt($('#TIP_OBJ_2').val().trim());
	if(isNaN(colete))  colete = 0;

	if($('#TIP_OBJ_1').is(':checked'))
		plicuri=1;

	if($('#TIP_OBJ_3').is(':checked'))
		paleti=1;

	asigurare = Number($('#asigurare').val().trim());
	ramburs = Number($('#ramburs').val().trim());
	tip_plata = $('#tip_plata').val();

	if(sel==1){
		if(plicuri == 1){
			$('#TIP_OBJ_3').attr('checked', false);
			$('#TIP_OBJ_2').val('');
			$('#GREUTATE').attr("readonly","true");
			$('#GREUTATE').val('0.500');
			$('#VOLUM1').attr('readonly', true);
			$('#VOLUM2').attr('readonly', true);
			$('#VOLUM3').attr('readonly', true);
			$('#VOLUM1').val('');
			$('#VOLUM2').val('');
			$('#VOLUM3').val('');

			ValidareCampuriRetur('TIP_OBJ_3');
			ValidareCampuriIntroducere('TIP_OBJ_2');
			ValidareCampuriRetur('TIP_OBJ_1');
		}
		else {
			$('#TIP_OBJ_2').val(1);
			$('#GREUTATE').val('');
			$('#GREUTATE').removeAttr('readonly');
			$('#VOLUM1').removeAttr('readonly');
			$('#VOLUM2').removeAttr('readonly');
			$('#VOLUM3').removeAttr('readonly');
		}
	}
	else if(sel==2){
		if(colete > 0) {
			$('#TIP_OBJ_3').attr('checked', false);
			$('#TIP_OBJ_1').attr('checked', false);
			$('#GREUTATE').removeAttr('readonly');
			$('#GREUTATE').val('');
			$('#VOLUM1').removeAttr('readonly');
			$('#VOLUM2').removeAttr('readonly');
			$('#VOLUM3').removeAttr('readonly');

			ValidareCampuriRetur('TIP_OBJ_3');
			ValidareCampuriIntroducere('TIP_OBJ_2');
			ValidareCampuriRetur('TIP_OBJ_1');
		}
		else {
			$('#TIP_OBJ_1').attr('checked', true);
			$('#TIP_OBJ_3').attr('checked', false);
			$('#TIP_OBJ_2').val('');
			$('#GREUTATE').attr("readonly","true");
			$('#GREUTATE').val('0.500');
			$('#VOLUM1').attr('readonly', true);
			$('#VOLUM2').attr('readonly', true);
			$('#VOLUM3').attr('readonly', true);
			$('#VOLUM1').val('');
			$('#VOLUM2').val('');
			$('#VOLUM3').val('');
		}
	}
	else if(sel==3){
		if(paleti == 1) {
			$('#TIP_OBJ_2').val('');
			$('#TIP_OBJ_1').attr('checked', false);
			$('#GREUTATE').removeAttr('readonly');
			$('#GREUTATE').val('');
			$('#VOLUM1').removeAttr('readonly');
			$('#VOLUM2').removeAttr('readonly');
			$('#VOLUM3').removeAttr('readonly');

			ValidareCampuriRetur('TIP_OBJ_3');
			ValidareCampuriIntroducere('TIP_OBJ_2');
			ValidareCampuriRetur('TIP_OBJ_1');
		}
		else {
			$('#TIP_OBJ_1').attr('checked', true);
			$('#TIP_OBJ_2').val('');
			$('#GREUTATE').attr("readonly","true");
			$('#GREUTATE').val('0.500');
			$('#VOLUM1').attr('readonly', true);
			$('#VOLUM2').attr('readonly', true);
			$('#VOLUM3').attr('readonly', true);
			$('#VOLUM1').val('');
			$('#VOLUM2').val('');
			$('#VOLUM3').val('');
		}
	}
	else if(sel==4){
		if(isNaN(parseInt($('#asigurare').val().trim())) || asigurare === 0) {
			$('#asigurare').val('');
		}
	}
	else if(sel==5){
		if(isNaN(parseInt($('#ramburs').val().trim())) || ramburs === 0) {
			$('#ramburs').val('');
			$('#tip_plata').val($("#tip_plata option:first").val());
		}
	}
	else if(sel==10){
		if(colete < 1 && paleti == 0) {
			$('#GREUTATE').attr("readonly","true");
			$('#GREUTATE').val('0.500');
			$('#TIP_OBJ_1').attr('checked', true);
			$('#VOLUM1').attr('readonly', true);
			$('#VOLUM2').attr('readonly', true);
			$('#VOLUM3').attr('readonly', true);
			$('#VOLUM1').val('');
			$('#VOLUM2').val('');
			$('#VOLUM3').val('');
			return;
		}
		ValidareCampuriIntroducere('TIP_OBJ_2');
	}

	if ($('#RET_NT').is(':checked'))
		ret_nt=1;
	if ($('#RET_DOC').is(':checked'))
		ret_doc=1;
	if ($('#LIV_S').is(':checked'))
		liv_sambata=1;
	if ($('#RET_AMB').is(':checked'))
		ret_amb=1;
	if($('#RET_COLET').is(':checked'))
		ret_colet = 1;
	if ($('#LIV_SEDIU').is(':checked'))
		liv_sediu=1;
	if ($('#SMS').is(':checked'))
		sms=1;
	if ($('#COPEN').is(':checked'))
		copen=1;

	var platitor = $('input:radio[name=platitor]:checked').val();
	var expeditor_id = $('#EXPEDITOR_ID').val();
	var expeditor_localitate_id = $('#EXPEDITOR_LOCALITATE_ID').val();
	var expeditor_localitate_km = $('#EXPEDITOR_LOCALITATE_KM').val();
	var destinatar_id = $('#DESTINATAR_ID').val();
	var destinatar_localitate_id = $('#DESTINATAR_LOCALITATE_ID').val();
	var destinatar_localitate_km = $('#DESTINATAR_LOCALITATE_KM').val();
	var greutate = Number($('#GREUTATE').val().trim());

	var lungime = 0;
	var latime = 0;
	var inaltime = 0;

	if ( $( "#VOLUM1" ).length ) {
		lungime = parseInt($('#VOLUM1').val().trim());
		if(isNaN(lungime))  lungime = 0;
	}
	if ( $( "#VOLUM2" ).length ) {
		latime = parseInt($('#VOLUM2').val().trim());
		if(isNaN(latime))  latime = 0;
	}
	if ( $( "#VOLUM3" ).length ) {
		inaltime = parseInt($('#VOLUM3').val().trim());
		if(isNaN(inaltime))  inaltime = 0;
	}

	if(isNaN(parseInt($('#DESTINATAR_LOCALITATE_ID').val().trim())) || parseInt($('#DESTINATAR_LOCALITATE_ID').val().trim()) == 0
		|| isNaN(parseInt($('#EXPEDITOR_LOCALITATE_ID').val().trim())) || parseInt($('#EXPEDITOR_LOCALITATE_ID').val().trim()) == 0) {
		return false;
	}

	if(plicuri == 0 && paleti == 0 && colete < 1) {
		return false;
	}

	if((colete > 0 || paleti == 1) && (isNaN(greutate) || greutate < 1) ){
		return false;
	}

	$.post(HTTP + 'client/valoare_expeditie/',{
		EXPEDITOR_ID:expeditor_id,
		EXPEDITOR_LOCALITATE_ID:expeditor_localitate_id,
		EXPEDITOR_LOCALITATE_KM:expeditor_localitate_km,
		platitor:platitor,
		DESTINATAR_ID:destinatar_id,
		DESTINATAR_LOCALITATE_ID:destinatar_localitate_id,
		DESTINATAR_LOCALITATE_KM:destinatar_localitate_km,
		TIP_OBJ_1:plicuri,
		TIP_OBJ_2:colete,
		TIP_OBJ_3:paleti,
		RET_NT:ret_nt,
		RET_DOC:ret_doc,
		LIV_S:liv_sambata,
		LIV_SEDIU:liv_sediu,
		RET_AMB:ret_amb,
		RET_COLET:ret_colet,
		SMS:sms,
		COPEN:copen,
		GREUTATE:greutate,
		VOLUM1:lungime,
		VOLUM2:latime,
		VOLUM3:inaltime,
		SWAPPED:swapped ? 1 : 0,
		asigurare:asigurare,
		ramburs:ramburs,
		tip_plata:tip_plata
		}, function(response) {
			var rasp = response.split('|||');
			PrepareResponse(rasp[0]);
			if (rasp[0] == 1){

				$('#valoare_expeditie').html(rasp[1]);
				$('#valoare_km').html(rasp[2]);
				$('#valoare_greutate').html(rasp[3]);
				$('#valoare_asigurare').html(rasp[4]);

				var exp = Number(rasp[1])+Number(rasp[2])+Number(rasp[3])+Number(rasp[4]);
				exp = roundNumber(exp,2);
				var total = roundNumber(exp * (1 +(parseInt(PROCENT_TVA)/100)), 2);
				var tva = roundNumber(total-exp,2);

				$('#valoare_totala').html(exp);
				$('#valoare_tva').html(tva);

				return true;
			}
			if (rasp[0] == 0){
				AfiseazaEroare(rasp[1]);
				return false;
			}
			return false;
		}
	);
}

function AdaugaExpeditieClient(sel,print){

	if(sel==2)
		AdaugaExpeditieClient_ff(print);
	else
		AdaugaExpeditieClient_gg();
}

function AdaugaExpeditieClient_gg(){
	var greutate = Number($('#GREUTATE').val().trim());
	var plicuri = 0;
	var colete = parseInt($('#TIP_OBJ_2').val().trim());
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

	if(!swapped) {
		if(!$('#DESTINATAR_NUME').val()) {
			AfiseazaEroare('Introduceti destinatarul!');
			$('#DESTINATAR_NUME').focus();
			return false;
		}
	}
	else {
		if(!$('#EXPEDITOR_NUME').val()) {
			AfiseazaEroare('Introduceti expeditorul!');
			$('#EXPEDITOR_NUME').focus();
			return false;
		}
	}

	if(!swapped) {
		if((!$('#DESTINATAR_LOCALITATE_ID').val() || !$('#DESTINATAR_LOCALITATE').val())) {
			AfiseazaEroare('Selectati localitatea din lista!');
			$('#DESTINATAR_LOCALITATE').focus();
			return false;
		}
	}
	else {
		if((!$('#EXPEDITOR_LOCALITATE_ID').val() || !$('#EXPEDITOR_LOCALITATE').val())) {
			AfiseazaEroare('Selectati localitatea din lista!');
			$('#EXPEDITOR_LOCALITATE').focus();
			return false;
		}
	}

	if(plicuri == 0 && paleti == 0) {
		if(colete == 0) {
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

	if((colete > 0 || paleti == 1) && (isNaN(greutate) || greutate < 1) ){
		AfiseazaEroare('Introduceti greutatea!');
		$('#GREUTATE').focus();
		return false;
	}

	if(sms == 1 && (!telefon || !telefonRegexp.test(telefon))) {
		AfiseazaEroare('Corectati telefon destinatar : 07XXXXXXXX');
		return false;
	}

	$('#confirmare_expeditie').html('<p>Doriti sa introduceti expeditia?</p>');
	$("#confirmare_expeditie").dialog("destroy");
	$("#confirmare_expeditie").dialog({
		width : 300,
		height : 160,
		draggable : false,
		resizable : false,
		modal: true,
		buttons: {
			"DA": function() {
				$(this).dialog("close");
				$.post(HTTP + 'client/adaugare_expeditie/',{
					data: $("#form_expeditie").serializeArray(),
				}, function(response) {
					update = response.split('|||');
					PrepareResponse(update[0]);
						if (update[0] == 1) {
							var url = HTTP+'client/expeditie_noua';
							window.location = url;
							jQuery("#liste_expeditii").jqGrid('setGridParam', {
								url : HTTP + 'client/json/expeditii'
							}).trigger("reloadGrid");
						} else {
							AfiseazaEroare(update[1]);
						}
					return true;
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

function AdaugaExpeditieClient_ff(){
	var greutate = Number($('#GREUTATE').val().trim());
	var plicuri = 0;
	var colete = parseInt($('#TIP_OBJ_2').val().trim());
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

	if(!swapped) {
		if(!$('#DESTINATAR_NUME').val()) {
			AfiseazaEroare('Introduceti destinatarul!');
			$('#DESTINATAR_NUME').focus();
			return false;
		}
	}
	else {
		if(!$('#EXPEDITOR_NUME').val()) {
			AfiseazaEroare('Introduceti expeditorul!');
			$('#EXPEDITOR_NUME').focus();
			return false;
		}
	}

	if(!swapped) {
		if((!$('#DESTINATAR_LOCALITATE_ID').val() || !$('#DESTINATAR_LOCALITATE').val())) {
			AfiseazaEroare('Selectati localitatea din lista!');
			$('#DESTINATAR_LOCALITATE').focus();
			return false;
		}
	}
	else {
		if((!$('#EXPEDITOR_LOCALITATE_ID').val() || !$('#EXPEDITOR_LOCALITATE').val())) {
			AfiseazaEroare('Selectati localitatea din lista!');
			$('#EXPEDITOR_LOCALITATE').focus();
			return false;
		}
	}

	if(plicuri == 0 && paleti == 0) {
		if(colete == 0) {
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

	if((colete > 0 || paleti == 1) && (isNaN(greutate) || greutate < 1) ){
		AfiseazaEroare('Introduceti greutatea!');
		$('#GREUTATE').focus();
		return false;
	}

	if(sms == 1 && (!telefon || !telefonRegexp.test(telefon))) {
		AfiseazaEroare('Corectati telefon destinatar : 07XXXXXXXX');
		return false;
	}

	$.post(HTTP + 'client/adaugare_expeditie/',{
		data: $("#form_expeditie").serializeArray(),
	}, function(response) {
		update = response.split('|||');
		PrepareResponse(update[0]);
			if (update[0] == 1) {
				ClientPrintExpeditie(update[1],1);
				var url = HTTP+'client/expeditie_noua';
				window.location = url;
				jQuery("#liste_expeditii").jqGrid('setGridParam', {
					url : HTTP + 'client/json/expeditii'
				}).trigger("reloadGrid");
			} else {
				AfiseazaEroare(update[1]);
			}
		return true;
	});
}

function AnuleazaExpeditieClient(exp){
	if (typeof exp === "undefined") {
			return false;
	}

	$('#confirmare_expeditie').html('<p>Doriti sa stergeti expeditia?</p>');
	$("#confirmare_expeditie").dialog("destroy");
	$("#confirmare_expeditie").dialog({
		width : 300,
		height : 160,
		draggable : false,
		resizable : false,
		modal: true,
		buttons: {
			"DA": function() {
				$(this).dialog("close");
				$.post(HTTP + 'client/stergere_expeditie/'+exp,{
					data: $("#form_expeditie").serializeArray(),
				}, function(response) {
					var m_delete = response.split('|||');
					PrepareResponse(m_delete[0]);
					if (m_delete[0] == 1) {
						var url = HTTP+'client/expeditie_noua';
						window.location = url;
						jQuery("#liste_expeditii").jqGrid('setGridParam', {
							url : HTTP + 'client/json/expeditii'
						}).trigger("reloadGrid");
					} else {
						AfiseazaEroare(m_delete[1]);
					}
					return true;
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

function AnuleazaExpeditieClientBorderou(exp) {
	$("#eroare").html('<p>Doriti sa stergeti expeditia?</p>');
	$("#eroare").attr('title', 'Confirmare');
	$("#eroare").dialog({
		modal : true,
		draggable : false,
		height : 150,
		width : 250,
		resizable : false,
		buttons : {
			'Da' : function() {
				$(this).dialog("destroy");
				$.post(HTTP + 'client/stergere_expeditie/'+exp, {},
				function(response) {
					var m_delete = response.split('|||');
					PrepareResponse(m_delete[0]);
					if (m_delete[0] == 1) {
						jQuery("#liste_expeditii").jqGrid('setGridParam', {url : HTTP + 'client/json/borderou_nou'}).trigger("reloadGrid");
					} else {
						AfiseazaEroare(m_delete[1]);
					}
					return true;
				});
			},
			'Nu' : function() {
				$(this).dialog("destroy");
			}
		}
	});
}

function EditareExpeditieClient(exp){
	var greutate = Number($('#GREUTATE').val().trim());
	var plicuri = 0;
	var colete = parseInt($('#TIP_OBJ_2').val().trim());
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

	if(!swapped) {
		if(!$('#DESTINATAR_NUME').val()) {
			AfiseazaEroare('Introduceti destinatarul!');
			$('#DESTINATAR_NUME').focus();
			return false;
		}
	}
	else {
		if(!$('#EXPEDITOR_NUME').val()) {
			AfiseazaEroare('Introduceti expeditorul!');
			$('#EXPEDITOR_NUME').focus();
			return false;
		}
	}

	if(!swapped) {
		if((!$('#DESTINATAR_LOCALITATE_ID').val() || !$('#DESTINATAR_LOCALITATE').val())) {
			AfiseazaEroare('Selectati localitatea din lista!');
			$('#DESTINATAR_LOCALITATE').focus();
			return false;
		}
	}
	else {
		if((!$('#EXPEDITOR_LOCALITATE_ID').val() || !$('#EXPEDITOR_LOCALITATE').val())) {
			AfiseazaEroare('Selectati localitatea din lista!');
			$('#EXPEDITOR_LOCALITATE').focus();
			return false;
		}
	}

	if(plicuri == 0 && paleti == 0) {
		if(colete == 0) {
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

	if((colete > 0 || paleti == 1) && (isNaN(greutate) || greutate < 1) ){
		AfiseazaEroare('Introduceti greutatea!');
		$('#GREUTATE').focus();
		return false;
	}

	if(sms == 1 && (!telefon || !telefonRegexp.test(telefon))) {
		AfiseazaEroare('Corectati telefon destinatar : 07XXXXXXXX');
		return false;
	}
	$('#confirmare_expeditie').html('<p>Doriti sa modificati expeditia?</p>');
	$("#confirmare_expeditie").dialog("destroy");
	$("#confirmare_expeditie").dialog({
		width : 300,
		height : 160,
		draggable : false,
		resizable : false,
		modal: true,
		buttons: {
			"DA": function() {
				$(this).dialog("close");
				$.post(HTTP + 'client/editare_expeditie/'+exp,{
					data: $("#form_expeditie").serializeArray(),
				}, function(response) {
					update = response.split('|||');
					PrepareResponse(update[0]);
					if (update[0] == 1) {
						jQuery("#liste_expeditii").jqGrid('setGridParam', {
							url : HTTP + 'client/json/expeditii'
						}).trigger("reloadGrid");
					} else {
						AfiseazaEroare(update[1]);
					}
					return true;
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

function Client_Grid_ListeExpeditiiIntroducere() {

	var grid = jQuery('#liste_expeditii');
	grid.jqGrid({
		url : HTTP + 'client/json/expeditii',
		datatype : "json",
		colNames : [ 'Expeditie','Expeditor', 'Localitate', 'Destinatar', 'Localitate','Tip','Piese','Greutate', 'Ramburs', 'Nr. Km','Valoare','TVA','Data','Optiuni', 'Modificata' ],
		colModel : [ {
			name : 'expeditie',
			index : 'e.expeditie',
			searchoptions:{sopt : [ 'eq' ]},
			width : 60
		},{
			name : 'expeditor',
			index : 'cle.nume',
			searchoptions:{sopt : [ 'bw' ]},
			width : 70
		}, {
			name : 'expeditor_localitate',
			index : 'lce.nume_lc',
			search:false,
			width : 70
		}, {
			name : 'destinatar',
			index : 'cld.nume',
			searchoptions:{sopt : [ 'bw' ]},
			width : 70
		}, {
			name : 'destinatar_localitate',
			index : 'lcd.nume_lc',
			search:false,
			width : 70
		}, {
			name : 'tip_obj',
			index : 'e.tip_obj',
			align : 'center',
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
			search:false,
			width : 40
		}, {
			name : 'piese',
			index : 'e.piese',
			formatter: 'number',
			formatoptions: {
				decimalPlaces: 0
			},
			sorttype: 'number',
			align : 'center',
			search:false,
			width : 30
		}, {
			name : 'greutate',
			index : 'e.greutate',
			formatter: 'number',
			formatoptions: {
				decimalPlaces: 3,
				defaultValue: '0.000'
			},
			sorttype: 'number',
			align : 'right',
			search:false,
			width : 40
		},{
			name : 'ramburs',
			index : 'e.ramburs',
			width : 40,
			formatter: 'number',
			formatoptions: {
				decimalPlaces: 2,
				defaultValue: '0.00'
			},
			sorttype: 'number',
			search:false,
			align : 'right'
		},  {
			name : 'km',
			index : 'km',
			formatter: 'number',
			formatoptions: {
				decimalPlaces: 0
			},
			sorttype: 'number',
			align : 'right',
			search:false,
			width : 40
		}, {
			name : 'valoare_totala',
			index : 'e.valoare_totala',
			formatter: 'number',
			formatoptions: {
				decimalPlaces: 2,
				defaultValue: '0.00'
			},
			sorttype: 'number',
			align : 'right',
			search:false,
			width : 50
		}, {
			name : 'valoare_tva',
			index : 'e.valoare_tva',
			formatter: 'number',
			formatoptions: {
				decimalPlaces: 2,
				defaultValue: '0.00'
			},
			sorttype: 'number',
			align : 'right',
			search:false,
			width : 50
		}, {
			name : 'data_expeditie',
			index : 'e.data_expeditie',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			align: 'center',
			search:false,
			width : 70
		}, {
			name : 'optiuni',
			align : 'center',
			sortable:false,
			search:false,
			width : 50
		}, {
			name : 'updated',
			sortable:false,
			search:false,
			hidden: true
		} ],
		rowNum : 500,
		autowidth : true,
		width : 970,
		height : 250,
		mtype : "GET",
		rownumbers : true,
		sortname : 'e.id',
		viewrecords : false,
		sortorder : "DESC",
		pager : false,
		caption : 'Expeditii',
		multiselect : false,
		subGrid : false,
		rowattr: function (rd) {
			if (rd.updated > 0) {
				return {"style": "background:LightGreen"};
		}},
		onSelectRow : function(rowId) {
			var myGrid = $(this),
				selAwb = myGrid.jqGrid('getCell', rowId, 1);
			ClientListeExpeditiiDetalii(rowId, selAwb);
		},
		footerrow: true,
		userDataOnFooter: true
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : true
	});
}

function ClientListeExpeditiiDetalii(sel, selAwb){
	$.post(HTTP + 'client/detalii_expeditii/', {
		exp : sel
	}, function(response) {
		$("#expeditii_continut").html(response);
		$(".title_page").html("Editare expeditie : <span style='color:red'>" + selAwb + "</span>" + 
			"<div class='change-roles-wrapper''><input type='button' class='dsc-btn' value='Expeditie noua' onclick='window.location.href = \"/client/expeditie_noua\"'></div>");
	});
	return true;
}


function ClientListeExpeditii(sel) {
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
	} else if (sel == 3) {
		if ($('#label_platitor').is(':checked')) {
			$('#platitor').removeAttr('disabled');
		} else {
			$('#platitor').attr('disabled', true);
		}
	} else if (sel == 2) {
		if ($('#destinatar').is(':checked')) {
			$('#destinatar_nume').removeAttr('disabled');
			$('#destinatar_localitate').removeAttr('disabled');
		} else {
			$('#destinatar_nume').attr('disabled', true);
			$('#destinatar_localitate').attr('disabled', true);
			$('#destinatar_nume').val("");
			$('#destinatar_localitate').val("");
			$('#destinatar_id').val("");
			$('#destinatar_localitate_id').val("");
		}
	}
}

function ClientAfisareListeExpeditii()
{
	setTimeout(function() {
		var cond = '';
		var search = $('#search').val();
		if(search != '')
		{
			cond += '&search='+ search;
		}

		if ($('#perioada').is(':checked')) {
			var data_start = $('#data_start').val();
			var data_final = $('#data_final').val();
			cond += '&data_start=' + data_start + '&data_final='+ data_final;
		}
		if ($('#label_platitor').is(':checked')) {
			var platitor = $('#platitor').val();
			cond += '&platitor=' + platitor;
		}
		if ($('#destinatar').is(':checked')) {
			if ($('#destinatar_localitate_id').val() != '') {
				var destinatar_localitate_id = $('#destinatar_localitate_id').val();
				cond += '&destinatar_localitate_id=' + destinatar_localitate_id;
			}
			if ($('#destinatar_id').val() != '') {
				var destinatar_id = $('#destinatar_id').val();
				cond += '&destinatar_id=' + destinatar_id;
			}
		}
		jQuery("#liste_expeditii").jqGrid('setGridParam',{url : HTTP + 'client/json/liste_expeditii?a' + cond}).trigger("reloadGrid");
	}, 250 );
}

function Client_Grid_ListeExpeditii()
{
	SetareDate();
	var grid = jQuery('#liste_expeditii');

	grid.jqGrid(
	{
		url : HTTP + 'client/json/liste_expeditii',
		datatype: 'json',
		mtype: 'POST',
		colNames : [ 'Expeditie','Expeditor', 'Localitate', 'Destinatar','Localitate','Tip','Piese','Greut','Ramburs','Bord.','Data col.','Status','Data status', 'Primitor', 'Checkpoint', 'Centru'],
		colModel : [ {
			name : 'expeditie',
			index : 'cep.expeditie',
			sorttype: 'int',
			width : 50
		}, {
			name : 'expeditor',
			index : 'cle.nume',
			searchoptions:{sopt : [ 'bw' ]},
			width : 70
		}, {
			name : 'expeditor_localitate',
			index : 'lce.nume_lc',
			width : 70
		}, {
			name : 'destinatar',
			index : 'cld.nume',
			width : 70
		}, {
			name : 'destinatar_localitate',
			index : 'lcd.nume_lc',
			width : 70
		}, {
			name : 'tip_obj',
			index : 'cep.tip_obj',
			align : 'center',
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
			width : 40
		}, {
			name : 'piese',
			index : 'cep.piese',
			formatter: 'number',
			formatoptions: {
				decimalPlaces: 0
			},
			sorttype: 'number',
			align : 'center',
			width : 30
		}, {
			name : 'greutate',
			index : 'cep.greutate',
			formatter: 'number',
			formatoptions: {
				decimalPlaces: 0
			},
			sorttype: 'number',
			align : 'center',
			width : 40
		},
		{
			name : 'ramburs',
			index : 'cep.ramburs',
			formatter: 'number',
			formatoptions: {
				decimalPlaces: 2
			},
			sorttype: 'number',
			align : 'center',
			width : 40
		},{
			name : 'borderou_id',
			index : 'b.borderou_id',
			sorttype: 'int',
			align: 'center',
			width: 40
		}, {
			name : 'data_expeditie',
			index : 'cep.data_expeditie',
			align: 'center',
			width : 60
		},{
			name : 'operatiune',
			index : 'ep.operatiune',
			stype : 'select',
			searchoptions : {
				value : ":Toate;COLECTATA:COLECTATA;PRELUATA:PRELUATA;LIVRAT:LIVRAT",
				sopt : [ 'eq' ]
			},
			width : 60
		},{
			name : 'data_op',
			index : 'ep.data_op',
			align: 'center',
			width : 60
		}, {
			name : 'primitor',
			index : 'ep.primitor',
			width : 60
		},{
			name : 'sc_ckp',
			search: false,
			sortable:false,
			align: 'left',
			width : 50
		},{
			name : 'sc_centru',
			search: false,
			sortable:false,
			align: 'left',
			width : 50
		} ],
		mtype : "GET",
		gridview : true,
		rowNum : 20,
		rowList : [20,50,100,250],
		rownumbers : true,
		width : 970,
		height : 'auto',
		maxHeight : 700,
		sortname : 'cep.data_expeditie',
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
			ClientAfisareDetaliiExpeditieCuID(id)
		}
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
}

function ClientAfisareRetururi()
{
	setTimeout(function() {
		var cond = '';
		var awb = $('#awb').val();
		if(awb != '')
		{
			cond += '&awb='+ awb;
		}

		if ($('#perioada').is(':checked')) {
			var data_start = $('#data_start').val();
			var data_final = $('#data_final').val();
			cond += '&data_start=' + data_start + '&data_final='+ data_final;
		}
		
		jQuery("#liste_retururi").jqGrid('setGridParam',{url : '/client/json/liste_retururi?a' + cond}).trigger("reloadGrid");
	}, 250 );
}

function Client_Grid_ListeRetururi()
{
	SetareDate();
	var grid = jQuery('#liste_retururi');

	grid.jqGrid(
	{
		url : '/client/json/liste_retururi',
		datatype: 'json',
		mtype: 'POST',
		colNames : [ 'Tip ret.', 'Awb ret.', 'Initiala', 'Expeditor', 'Localitate', 'Destinatar','Localitate','Tip','Piese','Greut','Data col.','Status','Data status', 'Primitor', 'Checkpoint', 'Centru'],
		colModel : [ 
		{
			name : 'tip_exp',
			index : 'epr.tip_exp',
			formatter: 'select',
			edittype : 'select',
			editoptions : {
				value : "1:Retur NT;2:Retur Doc.;6:Retur Ambalaj;7:Retur Colet;5:Returnare",
			},
			stype : 'select',
			searchoptions : {
				value : ":Toate;1:Retur NT;2:Retur Doc.;6:Retur Ambalaj;7:Retur Colet;5:Returnare",
				sopt : [ 'eq' ]
			},
			width : 80
		}, {
			name : 'retur',
			index : 'epr.expeditie',
			sorttype: 'int',
			width : 60
		}, {
			name : 'initiala',
			index : 'epr.referire',
			sorttype: 'int',
			width : 60
		}, {
			name : 'expeditor',
			index : 'cle.nume',
			searchoptions:{sopt : [ 'bw' ]},
			width : 70
		}, {
			name : 'expeditor_localitate',
			index : 'lce.nume_lc',
			width : 70
		}, {
			name : 'destinatar',
			index : 'cld.nume',
			width : 70
		}, {
			name : 'destinatar_localitate',
			index : 'lcd.nume_lc',
			width : 70
		}, {
			name : 'tip_obj',
			index : 'epr.tip_obj',
			align : 'center',
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
			width : 50
		}, {
			name : 'piese',
			search: false,
			sortable:false,
			formatter: 'number',
			formatoptions: {
				decimalPlaces: 0
			},
			align : 'center',
			width : 30
		}, {
			name : 'greutate',
			index : 'epr.greutate',
			formatter: 'number',
			formatoptions: {
				decimalPlaces: 3
			},
			sorttype: 'number',
			align : 'center',
			width : 40
		}, {
			name : 'data_expeditie',
			index : 'epr.data_expeditie',
			align: 'center',
			width : 60
		},{
			name : 'operatiune',
			index : 'epr.operatiune',
			stype : 'select',
			searchoptions : {
				value : ":Toate;COLECTATA:COLECTATA;PRELUATA:PRELUATA;LIVRAT:LIVRAT",
				sopt : [ 'eq' ]
			},
			width : 60
		},{
			name : 'data_op',
			index : 'epr.data_op',
			align: 'center',
			width : 60
		}, {
			name : 'primitor',
			index : 'epr.primitor',
			width : 60
		},{
			name : 'sc_ckp',
			search: false,
			sortable:false,
			align: 'left',
			width : 60
		},{
			name : 'sc_centru',
			search: false,
			sortable:false,
			align: 'left',
			width : 60
		} ],
		mtype : "GET",
		gridview : true,
		rowNum : 20,
		rowList : [20,50,100,250],
		rownumbers : true,
		width : 970,
		height : 'auto',
		maxHeight : 700,
		sortname : 'epr.data_expeditie',
		sortorder : "desc",
		viewrecords : true,
		pager : '#paginatie',
		shrinkToFit: false,
		caption:'Lista Retururi',
		forceFit: true,
		multiselect: true,
		beforeSelectRow: function (rowid, e) {
			var $myGrid = $(this),
					i = $.jgrid.getCellIndex($(e.target).closest('td')[0]),
					cm = $myGrid.jqGrid('getGridParam', 'colModel');
			return (cm[i].name === 'cb');
		},
		ondblClickRow : function(id) {
			//ClientAfisareDetaliiExpeditieCuID(id)
		}
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
}

function Client_Grid_BorderouNou(){
	var grid = jQuery('#liste_expeditii');

	grid.jqGrid(
	{
		url : HTTP + 'client/json/borderou_nou',
		datatype: 'json',
		mtype: 'POST',
		colNames : [ 'Expeditie', 'Expeditor', 'Localitate', 'Destinatar','Localitate','Data','Tip','Piese','Greut','Km Ext','Valoare','TVA','Optiuni' ],
		colModel : [ {
			name : 'expeditie',
			index : 'e.expeditie',
			formatter: 'number',
			sorttype: 'number',
			formatoptions: {
				thousandsSeparator: "",
				decimalPlaces: 0
			},
			width: 60,
			align: 'right'
		}, {
			name : 'expeditor_nume',
			index : 'cle.nume',
			width: 100
		}, {
			name : 'expeditor_localitate',
			index : 'lce.nume_lc',
			width: 100
		},{
			name : 'destinatar',
			index : 'cld.nume',
			width: 130
		}, {
			name : 'destinatar_localitate',
			index : 'lcd.nume_lc',
			width: 100
		}, {
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
			name : 'tip_obj',
			index : 'e.tip_obj',
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
			width: 40
		}, {
			name : 'piese',
			index : 'e.piese',
			formatter: 'number',
			sorttype: 'number',
			formatoptions: {
				decimalPlaces: 0
			},
			align: 'center',
			width: 30
		}, {
			name : 'greutate',
			index : 'e.greutate',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 50
		}, {
			name : 'km',
			sortable:false,
			search:false,
			formatter: 'number',
			align: 'right',
			width: 40
		}, {
			name : 'valoare_totala',
			index : 'e.valoare_totala',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 50
		}, {
			name : 'valoare_tva',
			index : 'e.valoare_tva',
			formatter: 'number',
			sorttype: 'number',
			align: 'right',
			width: 40
		},
		{
			name : 'optiuni',
			align: 'center',
			sortable:false,
			search:false,
			width: 60
		} ],
		rowNum:10000,
		scroll: true,
		rownumbers : true,
		width : 970,
		height : 295,
		sortname : 'e.data_expeditie',
		sortorder : "desc",
		viewrecords : true,
		pager : '#paginatie',
		shrinkToFit: false,
		caption:'Lista Expeditii',
		forceFit: true,
		multiselect: true,
		ondblClickRow : function(id) {
			ClientAfisareDetaliiExpeditieCuID(id)
		},
		beforeSelectRow: function (rowid, e) {
							var myGrid = $(this),
							i = $.jgrid.getCellIndex($(e.target).closest('td')[0]),
							cm = myGrid.jqGrid('getGridParam', 'colModel');
							return (cm[i].name === 'cb');
				},
		gridComplete: function() {
			jqGridSelectFirst99Rows('liste_expeditii');
			}
	});
	grid.jqGrid('bindKeys');
	grid.jqGrid('filterToolbar',{
		stringResult : true,
		searchOnEnter : false
	});
}

function jqGridSelectFirst99Rows(gridName, act = false){
	var theGrid = jQuery('#'+gridName), i = 0;
		rows = theGrid.jqGrid('getDataIDs');
	if(act)
		theGrid.jqGrid('resetSelection');
	for (i = 0; i < 249 && i < rows.length; i++)
		{
		theGrid.jqGrid('setSelection',rows[i],true);
	}
}

function GenerareBorderou(){
	var grid = jQuery('#liste_expeditii');
	var sel_ids = grid.jqGrid('getGridParam', 'selarrrow');
	if(sel_ids.length==0) { AfiseazaEroare("Selectioneaza minimum o expeditie !"); return false; }
	if(sel_ids.length > 250) { AfiseazaEroare("Borderoul este limitat la maximum 250 de expeditii !"); return false; }
	var postData = {};

	for(var $i=0, $l=sel_ids.length; $i<$l; $i++) postData[$i] = sel_ids[$i];
	var postData = JSON.stringify(postData);
		//alert("JSON serialized jqGrid data:\n" + postData);
		//return false;

	$('#detalii_expeditie').html('<p>Doriti sa generati borderoul?</p>');
	$("#detalii_expeditie").dialog("destroy");
	$("#detalii_expeditie").dialog({
		width : 225,
		height : 125,
		draggable : false,
		resizable : false,
		modal: true,
		buttons: {
			"DA": function() {
				$(this).dialog("close");
				$.post(HTTP + 'client/generare_borderou',
					{
						exps : postData
					},
					function(response) {
						if(response == 0)
							AfiseazaEroare("Nu aveti dreptul sa generati borderou !");
						else if(response == 1)
							AfiseazaEroare("Nici o expeditie !");
						else
							jQuery('#liste_expeditii').jqGrid('setGridParam',{url : HTTP + 'client/json/borderou_nou'}).trigger("reloadGrid");
				});
				return true;
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

function ClientBorderouPrintareMultipla(borderou_id, tip){
	var cond = 'tip='+ tip;
	cond += borderou_id > 0 ? '&borderou_id='+ borderou_id : '';
	var postData = {};

	if(borderou_id == 0){
		var grid = jQuery('#liste_expeditii');
		var sel_ids = grid.jqGrid('getGridParam', 'selarrrow');
		if(sel_ids.length==0) { AfiseazaEroare("Selectioneaza minimum o expeditie !"); return false; }
		if(sel_ids.length > 250) { AfiseazaEroare("Printarea este limitata la maximum 250 de expeditii !"); return false; }

		for(var $i=0, $l=sel_ids.length; $i<$l; $i++) postData[$i] = sel_ids[$i];
		cond += '&pJson=' + JSON.stringify(postData);
	}

	if(tip == 1 || tip == 2 || tip == 7){
		$("#detalii_expeditie").dialog("destroy");
		$.download('printawb/multipla', cond, false);
	} 
	else if(tip == 5 || tip == 6){
		$("#detalii_expeditie").dialog("destroy");
		$.download('printawb/multipla_master_puisori', cond, false);
	}
	else if(tip == 3 || tip == 4){ //print puisori only
		$('#detalii_expeditie').html('Printare');
		$("#detalii_expeditie").dialog("destroy");
		$("#detalii_expeditie").dialog({
			width : 320,
			height : 130,
			draggable : false,
			resizable : false,
			modal: true,
			title : 'Printare puisori pe etichete autocolante',
			buttons: {
				"Print Master": function() {
					$.download('printawb/multipla_master', cond, false);
				},
				'Print Puisori': function() {
					$.download('printawb/multipla_puisori', cond, false);
				},
				'Inchide': function() {
					$(this).dialog("destroy");
				}
			},
			close : function() {
				$(this).dialog("destroy");
			}
		});
	}
	else {
		//$('#detalii_expeditie').html('Expeditie: '+sel);
		$("#detalii_expeditie").dialog("destroy");
		$("#detalii_expeditie").dialog({
			width : 320,
			height : 130,
			draggable : false,
			resizable : false,
			modal: true,
			title : 'Alegere printare',
			buttons: {
				"Master": function() {
					$.download('printawb/multipla_master', cond, false);
				},
				'Master+Puisori': function() {
					$.download('printawb/multipla', cond, false);
				},
				'Inchide': function() {
					$(this).dialog("destroy");
				}
			},
			close : function() {
				$(this).dialog("destroy");
			}
		});
	}

	return true;
}

function Client_Grid_ListeBorderouri() {
	jQuery("#liste_borderouri").jqGrid(
		{
			url : HTTP + 'client/json/liste_borderouri',
			datatype : "json",
			colNames : ['ID','Expeditii','Data','Optiuni'],
			colModel : [ {
				name : 'borderou_id',
				index : 'b.borderou_id',
				width: 100,
				sorttype : 'int',
				align:'center'
			}, {
				name : 'expeditii',
				width: 100,
				align:'center'
			}, {
				name : 'data',
				index : 'b.data',
				sorttype : 'date',
				formatter : 'date',
				formatoptions : {
					srcformat : 'Y-m-d',
					newformat : 'Y-m-d'
				},
				align: 'center',
				width: 100
			}, {
				name : 'optiuni',
				align: 'center',
				sortable:false,
				search:false
			} ],
			rowNum : 200,
			width : 970,
			height: 400,
			scroll : 1,
			mtype : "GET",
			rownumbers : false,
			gridview : true,
			sortname: 'b.id',
			sortorder: "desc",
			viewrecords : true,
			caption : 'Lista Borderouri',
			multiselect : false,
			subGrid: true,
			subGridRowExpanded: function(subgrid_id, row_id) {
				var subgrid_table_id, pager_id;
				subgrid_table_id = subgrid_id+"_t";
				pager_id = "p_"+subgrid_table_id;
				$("#"+subgrid_id).html("<table id='"+subgrid_table_id+"' class='scroll'></table><div id='"+pager_id+"' class='scroll'></div>");

				jQuery("#"+subgrid_table_id).jqGrid({
					url : HTTP + 'client/json/liste_expeditii_borderou/'+row_id,
					datatype : "json",
					colNames : [ 'Expeditie', 'Expeditor','Localitate','Destinatar','Localitate','Data','Tip','Piese','Greut','Km Ext','Valoare','TVA','Optiuni' ],
					colModel : [ {
						name : 'expeditie',
						index : 'e.expeditie',
						sorttype : 'int',
						width: 60,
						align: 'right'
					},{
						name : 'expeditor_nume',
						search : false,
						sortable : false,
						width: 100
					}, {
						name : 'expeditor_localitate',
						search : false,
						sortable : false,
						width: 90
					},{
						name : 'destinatar_nume',
						index : 'cld.nume',
						width: 150
					}, {
						name : 'destinatar_localitate',
						index : 'lcd.nume_lc',
						width: 90
					}, {
						name : 'data_expeditie',
						index : 'e.data_expeditie',
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
						index : 'e.tip_obj',
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
						width: 40,
						align: 'center'
					}, {
						name : 'piese',
						index : 'e.piese',
						formatter: 'number',
						sorttype: 'number',
						formatoptions: {
							decimalPlaces: 0
						},
						align: 'right',
						width: 30
					}, {
						name : 'greutate',
						index : 'e.greutate',
						formatter: 'number',
						sorttype: 'number',
						align: 'right',
						width: 40
					}, {
						name : 'km',
						index : 'km',
						formatter: 'number',
						sortable:false,
						search:false,
						align: 'right',
						width: 50
					}, {
						name : 'valoare_totala',
						index : 'e.valoare_totala',
						formatter: 'number',
						sorttype: 'number',
						align: 'right',
						width: 60
					}, {
						name : 'valoare_tva',
						index : 'e.valoare_tva',
						formatter: 'number',
						sorttype: 'number',
						align: 'right',
						width: 60
					}, {
						name : 'optiune',
						align: 'center',
						sortable:false,
						search:false,
						width: 40
					} ],
					rowNum : 2000,
						 pager: pager_id,
						 sortname: 'e.expeditie',
						sortorder: "desc",
						height: 200,
						gridview : false,
					pager : false,
					viewrecords : false,
						ondblClickRow : function(id) {
						ClientAfisareDetaliiExpeditieCuID(id)
					},
					footerrow: true,
						userDataOnFooter: true
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



function ClientAfisareDetaliiExpeditieCuID(sel)
{
	$.post(HTTP + 'client/detalii_expeditie/', {expeditie : sel}, function(response) {
		$("#detalii_expeditie").html(response);
		$("#detalii_expeditie:ui-dialog").dialog("destroy");
		$("#detalii_expeditie").dialog({
			width : 624,
			height : 420,
			modal : true,
			draggable : false,
			resizable : false,
			title : 'Nr. AWB: '+sel
		});
	});
//return true;
}

function Client_AdaugaComanda()
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
							url: HTTP + 'client/adaugare_comanda',
							data: { data:$("#comanda_form").serializeArray() },
							success: function(response) {
							if (response == 1) {
								$("#comanda_form")[0].reset();
								$("#collect_at").datepicker('setDate', new Date());
								jQuery("#istoric_comenzi").jqGrid('setGridParam', {
									url : HTTP + 'client/json/istoric_comenzi'
								}).trigger("reloadGrid");
							}
							else {
								$("#messageBox").html(response);
								$("#messageBox").show();
							}
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

function Client_Grid_ListeComenzi_Short()
{
	var grid = jQuery('#istoric_comenzi');

	grid.jqGrid(
	{
		url : HTTP + 'client/json/istoric_comenzi',
		datatype: 'json',
		mtype: 'GET',
		colNames : [ 'Nr. comanda', 'Data colectarii','Data status', 'Status','Motiv','Curier'],
		colModel : [ {
			name : 'comanda_id',
			index : 'c.id',
			width : 65
		}, {
			name : 'collect_at',
			index : 'c.collect_at',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			align: 'center',
			width : 90
		},
		{
			name : 'created_at',
			index : 'ch.created_at',
			sorttype : 'date',
			formatter : 'date',
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
			stype: 'select',
			editoptions : {
				value : "1:initiala;2:transmisa;3:distribuita;4:acceptata;5:refuzata;6:colectata;7:anulata"
			},
			width : 100
		}, {
			name : 'motiv',
			sortable:false,
			width : 110
		}, {
			name : 'agent',
			sortable:false,
			width : 110
		} ],
		rowNum : 10,
		rowList : [ 10, 50, 100 ],
		width : 595,
		height : 260,
		sortname : 'ch.created_at',
		sortorder : "desc",
		viewrecords : true,
		pager : '#paginatie_istoric',
		shrinkToFit: false,
		caption:'Lista Comenzi',
		forceFit: true
	});
	grid.jqGrid('bindKeys');
}

function Client_Grid_ListeComenzi()
{
	var grid = jQuery('#liste_comenzi');

	grid.jqGrid(
	{
		url : HTTP + 'client/json/liste_comenzi',
		datatype: 'json',
		mtype: 'GET',
		colNames : [ 'Nr. comanda', 'Data colectarii','Data status', 'Status','Motiv', 'Localitate', 'Adresa', 'Colete', 'Paleti', 'Greutate', 'Observatii', 'Curier'],
		colModel : [ {
			name : 'comanda_id',
			index : 'c.id',
			width : 70
		}, {
			name : 'collect_at',
			index : 'c.collect_at',
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			align: 'center',
			width : 90
		},
		{
			name : 'created_at',
			index : 'ch.created_at',
			sorttype : 'date',
			formatter : 'date',
			stype: 'select',
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
			editoptions : {
				value : "1:initiala;2:transmisa;3:distribuita;4:acceptata;5:refuzata;6:colectata;7:anulata"
			},
			width : 60
		}, {
			name : 'motiv',
			sortable:false,
			width : 85
		}, {
			name : 'localitate',
			sortable:false,
			width : 80,
			align:'right'
		}, {
			name : 'adresa',
			sortable:false,
			width : 100,
			align:'right'
		},
		{
			name : 'colete',
			sortable:false,
			width : 33,
			align:'right'
		},
		{
			name : 'paleti',
			sortable:false,
			width : 33,
			align:'right'
		},{
			name : 'greutate',
			sortable:false,
			width : 47,
			align:'right'
		},{
			name : 'observatii',
			sortable:false,
			width : 100,
			align:'right'
		}, {
			name : 'agent',
			sortable:false,
			width : 93,
			align:'right'
		} ],
		rowNum : 12,
		rowList : [ 12, 50, 100 ],
		rownumbers : false,
		width : 970,
		height : 295,
		sortname : 'ch.created_at',
		sortorder : "desc",
		viewrecords : true,
		pager : '#paginatie',
		shrinkToFit: false,
		caption:'Lista Comenzi',
		forceFit: true
	});
	grid.jqGrid('bindKeys');
}

function ClientPrintExpeditie(sel, tip){
	if(sel == 0) return false;
	var cond = 'expeditie='+ sel + '&tip='+ tip;
	
	if(tip < 3 || tip == 7){ // 1,2,7
		$("#detalii_expeditie").dialog("destroy");
		$.download('client/print_expeditie', cond, false);
	}
	else if(tip == 5 || tip == 6){ // 5,6
		$("#detalii_expeditie").dialog("destroy");
		$.download('printawb/print_expeditie', cond, false);
	}
	else if(tip == 10){
		$('#detalii_expeditie').html('Expeditie: '+sel);
		$("#detalii_expeditie").dialog("destroy");
		$("#detalii_expeditie").dialog({
			width : 320,
			height : 130,
			draggable : false,
			resizable : false,
			modal: true,
			title : 'Alegere printare',
			buttons: {
				"Master": function() {
					$.download('client/print_master_expeditie', cond, false);
				},
				'Master+Puisori': function() {
					$.download('client/print_expeditie', cond, false);
				},
				'Inchide': function() {
						$(this).dialog("destroy");
				}
			},
			close : function() {
					$(this).dialog("destroy");
			}
		});
	}
	else{
		$('#detalii_expeditie').html('Expeditie: '+sel);
		$("#detalii_expeditie").dialog("destroy");
		$("#detalii_expeditie").dialog({
			width : 320,
			height : 130,
			draggable : false,
			resizable : false,
			modal: true,
			title : 'Printare puisori pe etichete autocolante',
			buttons: {
				"Print Master": function() {
					$.download('client/print_master_expeditie', cond, false);
				},
				'Print Puisori': function() {
					$.download('client/print_puisori_expeditie', cond, false);
				},
				'Inchide': function() {
					$(this).dialog("destroy");
				}
			},
			close : function() {
				$(this).dialog("destroy");
			}
		});
	}
	return true;
}

function ClientPrintBorderou(borderou_id){
	borderou_id = parseInt(borderou_id);
	if(borderou_id == 0) return false;

	var cond = 'borderou_id='+ borderou_id;
	var theSubGrid = jQuery('#liste_borderouri_'+borderou_id+'_t');
	var sidx = theSubGrid.jqGrid('getGridParam','sortname');
	var sord = theSubGrid.jqGrid('getGridParam','sortorder');
	if (typeof sidx != "undefined") {
		cond += '&sidx='+sidx;
	}
	if (typeof sord != "undefined") {
		cond += '&sord='+sord;
	}
	$.download('client/print_borderou', cond, false);
	return true;
}

function Client_GridListareClienti() {
	$("#listare_clienti").jqGrid({
		url : HTTP + 'client/json/listare_clienti',
		datatype : "json",
		colNames : [ 'Nume', 'Localitate', 'Adresa', 'Contact', 'Telefon', 'Optiuni'],
		colModel : [ {
			name : 'NUME',
			index : 'cld.NUME',
			width : 150
		},{
			name : 'LOCALITATE',
			index : 'lcd.NUME_LC',
			width : 150
		}, {
			name : 'ADRESA',
			index : 'cld.ADRESA',
			width : 300
		},{
			name : 'CONTACT',
			index : 'cld.contact',
			width : 100
		},{
			name : 'TELEFON',
			index : 'cld.telefon',
			width : 100
		},{
			name : 'optioni',
			sortable:false,
			search:false,
			width : 100,
			align:'center'
		} ],
		height : 400,
		width : 970,
		scroll : 1,
		rowNum : 50,
		mtype : "GET",
		rownumbers : true,
		rownumWidth : 40,
		gridview : true,
		pager : '#listare_clienti_pag',
		sortname : 'cld.NUME',
		viewrecords : true,
		sortorder : "asc",
		caption : 'Lista clienti'
	});
	$("#listare_clienti").jqGrid('bindKeys');
	$("#listare_clienti").jqGrid('filterToolbar', {
		stringResult : true,
		searchOnEnter : false
	});
	$("#listare_clienti").jqGrid('navGrid', '#listare_clienti_pag', {
		del : false,
		add : false,
		edit : false,
		search : false
	});
}

function ClientEditareClient(sel) {

	$.get(HTTP + 'client/afisare_client/' + sel, {},
		function(response) {
			var rasp = response.split('|||');
			$('#destinatar_id').val(rasp[0]);
			$('#destinatar_nume').val(rasp[1]);
			$('#destinatar_localitate_id').val(rasp[2]);
			$('#destinatar_localitate').val(rasp[3]);
			$('#destinatar_adresa').val(rasp[4]);
			$('#destinatar_contact').val(rasp[5]);
			$('#destinatar_telefon').val(rasp[6]);
		}
	);


	$("#editare_client").attr('title', 'Editare client');
	$("#editare_client").dialog({
		modal : true,
		draggable : false,
		height : 380,
		width : 250,
		resizable : false,
		buttons : {
			'Da' : function() {
				$.post(HTTP + 'client/editare_client/',{
					data: $("#editare_client_form").serializeArray(),
				}, function(response) {
					var rasp = response.split('|||');
					PrepareResponse(rasp[0]);
					if (rasp[0] == 1){
						$("#editare_client").dialog("destroy");
						jQuery("#listare_clienti").jqGrid('setGridParam',{url : HTTP + 'client/json/listare_clienti'}).trigger("reloadGrid");
					}
					else {
						$("#eroare").html(rasp[1]);
						return false;
					}
				});
			},
			'Nu' : function() {
				$(this).dialog("destroy");
			}
		}
	});
}

function ClientAdaugareClient(){

	$('#destinatar_id').val("");
	$('#destinatar_nume').val("");
	$('#destinatar_localitate_id').val("");
	$('#destinatar_localitate').val("");
	$('#destinatar_adresa').val("");
	$('#destinatar_contact').val("");
	$('#destinatar_telefon').val("");

	$("#editare_client").attr('title', 'Adaugare client');
	$("#editare_client").dialog({
		modal : true,
		draggable : false,
		height : 380,
		width : 250,
		resizable : false,
		buttons : {
			'Da' : function() {
				$.post(HTTP + 'client/adaugare_client/',{
					data: $("#editare_client_form").serializeArray(),
				}, function(response) {
					var rasp = response.split('|||');
					PrepareResponse(rasp[0]);
					if (rasp[0] == 1){
						$("#editare_client").dialog("destroy");
						jQuery("#listare_clienti").jqGrid('setGridParam',{url : HTTP + 'client/json/listare_clienti'}).trigger("reloadGrid");
					}
					else {
						$("#eroare").html(rasp[1]);
						return false;
					}
				});
			},
			'Nu' : function() {
				$(this).dialog("destroy");
			}
		}
	});
}

function ClientDeleteClient(sel) {
	$("#eroare").html('Sunteti sigur ca doriti sa stergeti acest client?');
	$("#eroare").attr('title', 'Confirmare');
	$("#eroare").dialog({
		modal : true,
		draggable : false,
		height : 150,
		width : 250,
		resizable : false,
		buttons : {
			'Da' : function() {
				$.ajax(HTTP + 'client/stergere_client/' + sel);
				$(this).dialog("destroy");
				jQuery("#listare_clienti").jqGrid('setGridParam',{url : HTTP + 'client/json/listare_clienti'}).trigger("reloadGrid");
			},
			'Nu' : function() {
				$(this).dialog("destroy");
			}
		}
	});
}

function ValidareCampuriIntroducere(field){
	if(field=='plateste1' || field=='plateste2' || field=='plateste3'){
		if( $("#PLATITOR_NUME").attr('rel') != $("#PLATITOR_NUME").val() ){
			$("#label_platitor").css('color', 'red');
		}else
			$("#" + field).css('color', '#232222');
	}else if(field=='TIP_OBJ_1' || field=='RET_NT' || field=='RET_DOC' || field=='LIV_S' || field=='RET_AMB' || field =='RET_COLET' || field=='SMS' || field=='COPEN' || field=='LIV_SEDIU' || field=='RET_NC' || field=='TIP_OBJ_3'){
		ValidareCampuriRetur(field);
	}else{
		if( $("#" + field).attr('rel') != $("#" + field).val() ){
			$("#" + field).css('color', 'red');
		}else
			$("#" + field).css('color', '#232222');
	}
	return true;
}

function startImportXlsClient(){
	document.getElementById('progressor').style.width = "0%";
	var fxls = $('#fxls').val();
	var source = new EventSource('client/import/expeditii/import?fxls='+fxls);
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

function startVerificareXlsClient()
{
	var fxls = $('#fxls').val();
	document.getElementById('progressor').style.width = "0%";
	var source = new EventSource('client/import/expeditii/check?fxls='+fxls);
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

	function clDownloadConfirmare(exp){
	var expeditie =parseInt(exp);
	if(!isNaN(expeditie) && expeditie > 0)
		window.location = HTTP + 'client/download_confirmare?expeditie='+expeditie;
}


/////////////Client\\\\\\\\\\\\\\
function Grid_Recantariri()
{
	var grid = jQuery('#cautare_recantariri');
	var data_start = $('#data_start').val();
	var data_final = $('#data_final').val();
	var cond = '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);

	grid.jqGrid(
	{
		url : HTTP + 'client/json/recantarite?a'+cond,
		datatype: 'json',
		mtype: 'POST',
		colNames : [ 'Expeditie', 'Expeditor', 'Destinatar','Data exp.','Tip','Gr. veche','Gr. noua','Data recantarire'],
		colModel : [ {
						name : 'expeditie',
						index : 'e.expeditie',
			width: 100
		}, {
						name : 'expeditor',
						index : 'cle.nume',
			width: 200
		}, {
						name : 'destinatar',
						index : 'cld.nume',
			width: 200
		},{
						name : 'data_expeditie',
			index : 'e.data_expeditie',
			search: false,
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			width: 90,
			align: 'center'
		}, {
			name : 'tip_obj',
			index : 'e.tip_obj',
			align : 'center',
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
			width : 60
		}, {
			name : 'old_kg',
			sortable:false,
			search:false,
			formatter: 'number',
			align: 'right',
			width: 50
		},{
			name : 'new_kg',
			sortable:false,
			search:false,
			formatter: 'number',
			align: 'right',
			width: 50
		},{
			name : 'data_recantarire',
			sortable:false,
			search:false,
			sorttype : 'date',
			formatter : 'date',
			formatoptions : {
				srcformat : 'Y-m-d',
				newformat : 'd.m.Y'
			},
			width: 90,
			align: 'center'
		}],
		rownumbers : true,
		scroll : 1,
		rowNum : 100,
		width : 970,
		height : 295,
		sortname : 'e.data_expeditie',
		sortorder : "desc",
		viewrecords : true,
		pager : '#paginatie',
		shrinkToFit: false,
		caption:'Lista Recantarite',
		forceFit: true,
		multiselect: false,
				ondblClickRow : function(id) {
			ClientAfisareDetaliiExpeditieCuID(id)
		},
		footerrow: false,
			userDataOnFooter: false
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
	var cond = '&data_start=' + escape(data_start) + '&data_final='+ escape(data_final);
	jQuery("#cautare_recantariri").jqGrid('setGridParam',{url : HTTP + 'client/json/recantarite?a' + cond}).trigger("reloadGrid");
}